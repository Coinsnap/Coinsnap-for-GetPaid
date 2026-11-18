<?php
// Run: php tests/webhook-lock-test.php  (no WordPress needed; WP functions are stubbed)
// Two webhooks for the same invoice arrive at once. The second one reads the GetPaid invoice before the
// first one marks it paid, then waits for the lock. Under the lock it must see "paid", not its own cached copy.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit; }
define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', false);
define('DAY_IN_SECONDS', 86400);

$GLOBALS['db_status'] = 'wpi-pending';   // what the database holds
$GLOBALS['cache'] = [];                  // WordPress' per-request object cache
function get_transient($k) { return false; }
function set_transient($k, $v, $e) { return true; }
function get_option($k, $d = false) { return $d; }
function clean_post_cache($id) { unset($GLOBALS['cache'][$id]); }
function wp_cache_delete($key, $group = '') { unset($GLOBALS['cache'][$key]); return true; }

require dirname(__DIR__) . '/includes/Webhook/Signature.php';
require dirname(__DIR__) . '/includes/Webhook/InvoiceDecision.php';
require dirname(__DIR__) . '/includes/Webhook/WebhookHandler.php';

class StaleCacheHandler extends \CoinsnapGP\Webhook\WebhookHandler
{
    public $paidCalls = 0;
    public $whileWaiting;
    protected function fetchInvoice(string $invoiceId): array
    {
        return ['id' => $invoiceId, 'status' => 'Settled', 'invoiceAmount' => '10.00', 'currency' => 'EUR', 'orderId' => 'INV-1'];
    }
    protected function findPaymentId(string $number): int { return 7; }
    protected function payment(int $paymentId): ?array
    {
        // Like get_post(): served from the request's object cache once loaded.
        if (!isset($GLOBALS['cache'][$paymentId])) {
            $GLOBALS['cache'][$paymentId] = $GLOBALS['db_status'];
        }
        return ['status' => $GLOBALS['cache'][$paymentId], 'gateway' => 'coinsnap', 'total' => '10', 'currency' => 'EUR',
            'invoices' => ['inv1' => ['amount' => '10.00', 'currency' => 'EUR', 'invoice_total' => '10', 'invoice_currency' => 'EUR']]];
    }
    protected function lock(int $paymentId): bool
    {
        // The other request finishes while this one waits for the lock.
        if ($this->whileWaiting) { ($this->whileWaiting)(); }
        return true;
    }
    protected function unlock(int $paymentId): void {}
    protected function markPaid(int $paymentId, string $invoiceId): void { $this->paidCalls++; $GLOBALS['db_status'] = 'publish'; }
    protected function logInfo(string $reason, string $invoiceId = ''): void {}
    protected function logSecurity(string $reason, string $invoiceId = ''): void {}
}

$fail = 0;
function ok($c, $m) { global $fail; echo ($c ? 'PASS ' : 'FAIL ') . $m . "\n"; if (!$c) { $fail++; } }

$body = '{"type":"Settled","invoiceId":"inv1"}';
$t = time();
$sig = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $body, 'sec');

$h = new StaleCacheHandler('coinsnap', 'sec', ['processing' => 'wpi-processing', 'expired' => 'wpi-cancelled']);
$h->whileWaiting = function () { $GLOBALS['db_status'] = 'publish'; }; // the parallel webhook marked it paid
ok($h->handle($body, $sig, $t) === 200, 'second parallel webhook replies 200');
ok($h->paidCalls === 0, 'second parallel webhook does not mark the invoice paid again (stale cache ignored)');

$GLOBALS['db_status'] = 'wpi-pending'; $GLOBALS['cache'] = [];
$h = new StaleCacheHandler('coinsnap', 'sec', ['processing' => 'wpi-processing', 'expired' => 'wpi-cancelled']);
ok($h->handle($body, $sig, $t) === 200 && $h->paidCalls === 1, 'single webhook marks the invoice paid once');

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit($fail ? 1 : 0);
