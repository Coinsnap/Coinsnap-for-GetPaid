<?php
// Runs inside WordPress with GetPaid and this plugin active: wp eval-file tests/invoice-metadata-test.php
// Every HTTP call is answered by a fake (no API key needed). Checks the create-invoice request for Coinsnap
// and BTCPay: orderId = GetPaid invoice number (the webhook finds the invoice by it) plus the invoice metadata.
if (!defined('ABSPATH')) { exit; }

$failures = 0;
$check = function (bool $ok, string $label) use (&$failures) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$ok) { $failures++; }
};

$gateway = new CoinsnapGP_Gateway();
$bodies = [];
$fake = function ($pre, $args, $url) use (&$bodies, $gateway) {
    $reply = function ($body) {
        return ['headers' => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary(['content-type' => 'application/json']),
            'body' => wp_json_encode($body), 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => [], 'filename' => null];
    };
    $method = $args['method'] ?? 'GET';
    if (strpos($url, 'coingecko') !== false) {
        return $reply(['rates' => ['btc' => ['value' => 1], 'eur' => ['value' => 90000]]]);
    }
    if (strpos($url, '/payment-methods') !== false) {
        return $reply([['paymentMethodId' => 'BTC-LN', 'enabled' => true]]);
    }
    if (strpos($url, '/rates') !== false) {
        return $reply([['currencyPair' => 'BTC_EUR', 'rate' => '90000', 'errors' => []]]);
    }
    if (preg_match('~/webhooks/[^/]+$~', $url) && $method === 'GET') {
        return $reply(['id' => 'wh1', 'url' => $gateway->get_webhook_url()]);
    }
    if ($method === 'POST' && preg_match('~/invoices$~', $url)) {
        $bodies[] = json_decode((string) $args['body'], true);
        return $reply(['id' => 'inv' . count($bodies), 'checkoutLink' => 'https://pay.example/i/x']);
    }
    return $reply([]);
};
$stop = function ($location) { throw new \RuntimeException('redirect ' . $location); };
add_filter('pre_http_request', $fake, 10, 3);
add_filter('wp_redirect', $stop, 1);

$settingsBefore = get_option('wpinv_settings');
$webhookBefore = get_option('wpinv_settings_coinsnap_webhook');

$item = new WPInv_Item();
$item->set_name('Consulting hour');
$item->set_price(10);
$item->set_status('publish');
$item->save();

$invoice = new WPInv_Invoice();
$invoice->set_email('ana@example.com');
$invoice->set_first_name('Ana');
$invoice->set_last_name('Buyer');
$invoice->set_phone('+381111');
$invoice->set_address('Street 1');
$invoice->set_city('Belgrade');
$invoice->set_zip('11000');
$invoice->set_country('RS');
$invoice->set_currency('EUR');
$formItem = new GetPaid_Form_Item($item->get_id());
$formItem->set_price(10);
$formItem->set_quantity(2);
$invoice->add_item($formItem);
$invoice->recalculate_total();
$invoice->save();

try {
    update_option('wpinv_settings_coinsnap_webhook', ['id' => 'wh1', 'secret' => 'sec'], false);
    foreach (['coinsnap', 'btcpay'] as $provider) {
        $settings = (array) get_option('wpinv_settings', []);
        $settings = array_merge($settings, ['coinsnap_provider' => $provider, 'coinsnap_api_key' => 'fake', 'coinsnap_store_id' => 'fake-store',
            'btcpay_server_url' => 'https://btcpay.example', 'btcpay_api_key' => 'fake', 'btcpay_store_id' => 'fake-store']);
        update_option('wpinv_settings', $settings);
        $GLOBALS['wpinv_options'] = $settings;

        $before = count($bodies);
        try {
            $gateway->process_payment(wpinv_get_invoice($invoice->get_id()), [], null);
        } catch (\RuntimeException $e) {
        }
        $body = count($bodies) > $before ? end($bodies) : [];
        $m = $body["metadata"] ?? []; if (getenv("DUMP")) { echo wp_json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n"; }
        $number = (string) $invoice->get_number();
        $check($body !== [], "$provider: invoice request sent");
        $check((string) ($body['orderId'] ?? '') === $number, "$provider: top-level orderId = GetPaid invoice number ($number)");
        $check((string) ($m['orderId'] ?? '') === $number, "$provider: metadata.orderId = invoice number (webhook match)");
        $check((string) ($m['orderNumber'] ?? '') === $number, "$provider: orderNumber");
        $check(strpos((string) ($m['orderUrl'] ?? ''), 'post=' . $invoice->get_id()) !== false && ($m['getpaidInvoiceId'] ?? 0) === $invoice->get_id(), "$provider: orderUrl, getpaidInvoiceId");
        $check(($m['itemDesc'] ?? '') === 'Consulting hour x 2' && ($m['itemCode'] ?? '') === (string) $item->get_id(), "$provider: itemDesc, itemCode");
        $li = $m['lineItems'][0] ?? [];
        $check(($li['name'] ?? '') === 'Consulting hour' && ($li['quantity'] ?? 0) === 2 && (float) ($li['price'] ?? 0) === 10.0 && (float) ($li['total'] ?? 0) === 20.0, "$provider: lineItems");
        $check(($m['platform'] ?? '') === 'getpaid' && ($m['pluginVersion'] ?? '') === COINSNAPGP_VERSION, "$provider: platform, pluginVersion");
        // GetPaid takes the email from the invoice's customer (here the site admin).
        $email = (string) wpinv_get_invoice($invoice->get_id())->get_email();
        $check($email !== '' && ($body['buyerEmail'] ?? '') === $email && ($m['buyerEmail'] ?? '') === $email, "$provider: buyerEmail");
        $check(($m['buyerName'] ?? '') === 'Ana Buyer' && ($m['customerName'] ?? '') === 'Ana Buyer' && ($m['buyerPhone'] ?? '') === '+381111', "$provider: name, phone");
        $check(($m['buyerAddress1'] ?? '') === 'Street 1' && ($m['buyerCity'] ?? '') === 'Belgrade' && ($m['buyerZip'] ?? '') === '11000' && ($m['buyerCountry'] ?? '') === 'RS', "$provider: address");
        $check(!in_array('', $m, true) && !in_array(null, $m, true), "$provider: no empty values");
    }
} finally {
    remove_filter('pre_http_request', $fake, 10);
    remove_filter('wp_redirect', $stop, 1);
    update_option('wpinv_settings', $settingsBefore);
    if ($webhookBefore === false) { delete_option('wpinv_settings_coinsnap_webhook'); } else { update_option('wpinv_settings_coinsnap_webhook', $webhookBefore, false); }
    wp_delete_post($invoice->get_id(), true);
    wp_delete_post($item->get_id(), true);
}

echo $failures === 0 ? "OK\n" : "$failures FAILED\n";
