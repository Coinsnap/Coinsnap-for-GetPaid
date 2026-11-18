<?php
declare(strict_types=1);

namespace CoinsnapGP\Webhook;

defined('ABSPATH') || exit;

// Only the invoice id is taken from the request; every decision comes from the payment server's API.
class WebhookHandler
{
    private const FINAL = ['publish', 'wpi-renewal', 'wpi-refunded'];
    private const OPEN = ['wpi-pending', 'wpi-processing', 'wpi-onhold', 'draft'];

    private $provider;
    private $secret;
    private $statuses;

    // Plain properties, not constructor promotion: the plugin still supports PHP 7.4.
    public function __construct(string $provider, string $secret, array $statuses)
    {
        $this->provider = $provider;
        $this->secret = $secret;
        $this->statuses = $statuses;
    }

    public function handle(string $rawBody, string $signatureHeader, int $nowSeconds): int
    {
        $problem = ($this->provider === 'btcpay')
            ? Signature::btcpayProblem($signatureHeader, $rawBody, $this->secret)
            : Signature::coinsnapProblem($signatureHeader, $rawBody, $this->secret, $nowSeconds);
        if ($problem !== null) {
            $this->logSecurity($problem);
            return 401;
        }

        $body = json_decode($rawBody, true);
        $invoiceId = (is_array($body) && is_string($body['invoiceId'] ?? null)) ? $body['invoiceId'] : '';
        if ($invoiceId === '') {
            return 200;
        }
        $claimsPaid = in_array($body['type'] ?? '', ['Settled', 'InvoiceSettled'], true);

        try {
            $invoice = $this->fetchInvoice($invoiceId);
        } catch (\Coinsnap\Exception\RequestException $e) {
            if (in_array($e->getCode(), [400, 404], true)) {
                // The dashboard's "Send test webhook" button lands here.
                $this->logInfo('unknown invoice', $invoiceId);
                return 200;
            }
            $this->logInfo('retry: API unavailable', $invoiceId);
            return 503;
        } catch (\Throwable $e) {
            $this->logInfo('retry: API unavailable', $invoiceId);
            return 503;
        }

        $metadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $orderId = ($this->provider === 'btcpay') ? ($metadata['orderId'] ?? '') : ($invoice['orderId'] ?? '');
        // GetPaid invoices are referenced by their number, not the post id.
        $paymentId = is_scalar($orderId) ? $this->findPaymentId((string) $orderId) : 0;
        $payment = $paymentId > 0 ? $this->payment($paymentId) : null;
        if ($payment === null || $payment['gateway'] !== 'coinsnap') {
            $this->logInfo('not ours', $invoiceId);
            return 200;
        }

        $recorded = $payment['invoices'];
        // Without a record, only invoices open at the upgrade are accepted, for one day after it.
        if ((!is_array($recorded) || $recorded === []) && !$this->legacyAllowed()) {
            $this->logSecurity('no invoice record for this payment', $invoiceId);
            return 200;
        }
        if (is_array($recorded) && $recorded !== [] && !isset($recorded[$invoiceId])) {
            $this->logSecurity('invoice not created for this payment', $invoiceId);
            return 200;
        }
        // Invoices created before 1.2.0 have no record; check against the invoice total.
        $expected = (is_array($recorded) && isset($recorded[$invoiceId])) ? $recorded[$invoiceId] : [
            'amount' => number_format(round((float) $payment['total'], 2), 2, '.', ''),
            'currency' => (string) $payment['currency'],
        ];

        $decision = InvoiceDecision::decide($invoice, $this->provider === 'btcpay', (string) $expected['amount'], (string) $expected['currency']);
        if ($decision !== InvoiceDecision::PAID && ($claimsPaid || $decision === InvoiceDecision::MISMATCH)) {
            $this->logSecurity($claimsPaid ? 'forged status' : 'amount mismatch', $invoiceId);
            return 200;
        }
        // A pending GetPaid invoice can still be edited; an invoice for the old total must not pay the new one.
        if ($decision === InvoiceDecision::PAID && isset($expected['invoice_total'])
            && (InvoiceDecision::toMinorUnits((string) $payment['total']) !== InvoiceDecision::toMinorUnits((string) $expected['invoice_total'])
                || (string) $payment['currency'] !== (string) ($expected['invoice_currency'] ?? ''))) {
            $this->logSecurity('invoice total changed', $invoiceId);
            return 200;
        }
        if ($decision === InvoiceDecision::NONE) {
            $this->logInfo('no change', $invoiceId);
            return 200;
        }

        if (!$this->lock($paymentId)) {
            return 503;
        }
        try {
            // Read again under the lock so a parallel webhook's change is seen. The invoice was already
            // loaded above, so WordPress' object cache would return that copy: drop it first.
            $this->forgetCached($paymentId);
            $current = (string) ($this->payment($paymentId)['status'] ?? '');
            $this->apply($decision, $paymentId, $current, $invoiceId);
        } finally {
            $this->unlock($paymentId);
        }
        return 200;
    }

    // Statuses only move forward; a completed, refunded or revoked payment is never changed.
    private function apply(string $decision, int $paymentId, string $current, string $invoiceId): void
    {
        if (in_array($current, self::FINAL, true)) {
            $this->logInfo('already processed', $invoiceId);
            return;
        }
        if ($decision === InvoiceDecision::PAID) {
            // A late payment may still settle a cancelled or failed invoice.
            $this->markPaid($paymentId, $invoiceId);
            $this->logInfo('paid', $invoiceId);
            return;
        }
        $target = '';
        if ($decision === InvoiceDecision::PROCESSING && in_array($current, ['wpi-pending', 'draft'], true)) {
            $target = $this->statuses['processing'];
        }
        elseif ($decision === InvoiceDecision::EXPIRED && in_array($current, self::OPEN, true)) {
            $target = $this->statuses['expired'];
        }
        // Only a settled, verified invoice may mark an invoice paid, whatever the settings say.
        if (in_array($target, self::FINAL, true)) {
            $target = '';
        }
        if ($target === '' || $target === $current) {
            $this->logInfo('no change', $invoiceId);
            return;
        }
        $this->setStatus($paymentId, $target, sprintf('Coinsnap invoice %s: %s', $invoiceId, $decision));
        $this->logInfo('status ' . $decision, $invoiceId);
    }

    protected function fetchInvoice(string $invoiceId): array
    {
        $apiUrl = ($this->provider === 'btcpay') ? (string) wpinv_get_option('btcpay_server_url') : COINSNAP_SERVER_URL;
        $apiKey = ($this->provider === 'btcpay') ? (string) wpinv_get_option('btcpay_api_key') : (string) wpinv_get_option('coinsnap_api_key', '');
        $storeId = ($this->provider === 'btcpay') ? (string) wpinv_get_option('btcpay_store_id') : (string) wpinv_get_option('coinsnap_store_id', '');
        return (new \Coinsnap\Client\Invoice($apiUrl, $apiKey))->getInvoice($storeId, $invoiceId)->getData();
    }

    protected function findPaymentId(string $number): int
    {
        return $number === '' ? 0 : (int) \WPInv_Invoice::get_invoice_id_by_field($number, 'number');
    }

    protected function payment(int $paymentId): ?array
    {
        $invoice = wpinv_get_invoice($paymentId);
        if (!$invoice || !$invoice->get_id()) {
            return null;
        }
        return [
            'status' => (string) $invoice->get_status(),
            'gateway' => (string) $invoice->get_gateway(),
            'total' => (string) $invoice->get_total(),
            'currency' => (string) $invoice->get_currency(),
            'invoices' => get_post_meta($paymentId, '_coinsnap_invoices', true),
        ];
    }

    protected function forgetCached(int $paymentId): void
    {
        clean_post_cache($paymentId);
        wp_cache_delete($paymentId, 'getpaid_invoice_special_fields');
        wp_cache_delete($paymentId, 'getpaid_invoice_cart_details');
    }

    protected function markPaid(int $paymentId, string $invoiceId): void
    {
        wpinv_get_invoice($paymentId)->mark_paid($invoiceId, 'Coinsnap invoice ' . $invoiceId);
    }

    protected function setStatus(int $paymentId, string $status, string $note): void
    {
        $invoice = wpinv_get_invoice($paymentId);
        $invoice->set_status($status);
        $invoice->add_note($note, false, false, true);
        $invoice->save();
    }

    protected function lock(int $paymentId): bool
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', 'coinsnap_gp_' . $paymentId)) === 1;
    }

    protected function unlock(int $paymentId): void
    {
        global $wpdb;
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', 'coinsnap_gp_' . $paymentId));
    }

    protected function logInfo(string $reason, string $invoiceId = ''): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(self::line('INFO', $reason, $invoiceId));
        }
    }

    // At most 10 security lines a minute, so a flood of fake requests can't fill the log.
    protected function logSecurity(string $reason, string $invoiceId = ''): void
    {
        $key = 'coinsnapgp_seclog_' . gmdate('YmdHi');
        $count = (int) get_transient($key) + 1;
        set_transient($key, $count, 120);
        if ($count === 11) {
            error_log('Coinsnap SECURITY webhook: more rejections this minute, not logged');
        }
        if ($count <= 10) {
            error_log(self::line('SECURITY', $reason, $invoiceId));
        }
    }

    public static function line(string $level, string $reason, string $invoiceId): string
    {
        // The invoice id comes from the request.
        $id = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', $invoiceId), 0, 64);
        return sprintf('Coinsnap %s webhook reason=%s invoice=%s', $level, $reason, $id !== '' ? $id : '-');
    }

    protected function legacyAllowed(): bool
    {
        $since = (int) get_option('coinsnapgp_records_since', 0);
        return $since > 0 && time() - $since < DAY_IN_SECONDS;
    }
}
