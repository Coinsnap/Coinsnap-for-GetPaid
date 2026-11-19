<?php
// Runs inside WordPress with GetPaid and this plugin active: wp eval-file tests/checkout-webhook-test.php
// Every HTTP call is answered by a fake API and recorded. A customer's checkout must make no webhook API call
// (list, get, create, delete) and must not replace the stored secret; only the admin side manages the webhook.
if (!defined('ABSPATH')) { exit; }

$failures = 0;
$check = function (bool $ok, string $label) use (&$failures) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$ok) { $failures++; }
};

$gateway = new CoinsnapGP_Gateway();
$calls = [];
$fake = function ($pre, $args, $url) use (&$calls, $gateway) {
    $method = $args['method'] ?? 'GET';
    $calls[] = $method . ' ' . $url;
    $reply = function ($body, $code = 200) {
        return ['headers' => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary(['content-type' => 'application/json']),
            'body' => wp_json_encode($body), 'response' => ['code' => $code, 'message' => ''], 'cookies' => [], 'filename' => null];
    };
    if (strpos($url, 'coingecko') !== false) {
        return $reply(['rates' => ['btc' => ['value' => 1], 'eur' => ['value' => 90000]]]);
    }
    if (strpos($url, '/payment-methods') !== false) {
        return $reply([['paymentMethodId' => 'BTC-LN', 'enabled' => true]]);
    }
    if (strpos($url, '/rates') !== false) {
        return $reply([['currencyPair' => 'BTC_EUR', 'rate' => '90000', 'errors' => []]]);
    }
    if (preg_match('~/webhooks$~', $url) && $method === 'GET') {
        return $reply([['id' => 'whOld', 'url' => $gateway->get_webhook_url()]]);
    }
    if (preg_match('~/webhooks/([^/]+)$~', $url, $m) && $method === 'GET') {
        return $m[1] === 'wh1' ? $reply(['id' => 'wh1', 'url' => $gateway->get_webhook_url()]) : $reply(['code' => 'not-found'], 404);
    }
    if (preg_match('~/webhooks(/[^/]+)?$~', $url)) {
        return $reply(['id' => 'whNew', 'secret' => 'NEWSECRET', 'url' => $gateway->get_webhook_url()]);
    }
    if ($method === 'POST' && preg_match('~/invoices$~', $url)) {
        return $reply(['id' => 'inv1', 'checkoutLink' => 'https://pay.example/i/inv1']);
    }
    return $reply(['id' => 'fake-store']);
};
// wp_redirect() (invoice created) and wpinv_send_back_to_checkout() (refused) both end the request; stop here instead.
$redirect = function ($location) { throw new \RuntimeException('redirect ' . $location); };
$refused = function () { throw new \RuntimeException('back'); };
add_filter('pre_http_request', $fake, 10, 3);
add_filter('wp_redirect', $redirect, 1);
add_action('getpaid_checkout_invoice_exception', $refused, 1);

$settingsBefore = get_option('wpinv_settings');
$webhookBefore = get_option('wpinv_settings_coinsnap_webhook');

$item = new WPInv_Item();
$item->set_name('Consulting hour');
$item->set_price(10);
$item->set_status('publish');
$item->save();

$invoice = new WPInv_Invoice();
$invoice->set_email('ana@example.com');
$invoice->set_currency('EUR');
$formItem = new GetPaid_Form_Item($item->get_id());
$formItem->set_price(10);
$formItem->set_quantity(1);
$invoice->add_item($formItem);
$invoice->recalculate_total();
$invoice->save();

$checkout = function () use (&$calls, $gateway, $invoice) {
    $calls = [];
    try {
        $gateway->process_payment(wpinv_get_invoice($invoice->get_id()), [], null);
        return 'none';
    } catch (\RuntimeException $e) {
        return $e->getMessage();
    }
};
$webhookCalls = function () use (&$calls) { return array_values(preg_grep('~/webhooks~', $calls)); };
$invoiceCalls = function () use (&$calls) { return array_values(preg_grep('~^POST .*/invoices$~', $calls)); };
$apiWrites = function () use (&$calls) { return array_values(preg_grep('~^(POST|PUT|DELETE) ~', $calls)); };

try {
    foreach (['coinsnap', 'btcpay'] as $provider) {
        $settings = (array) get_option('wpinv_settings', []);
        $settings = array_merge($settings, ['coinsnap_provider' => $provider, 'coinsnap_api_key' => 'fake', 'coinsnap_store_id' => 'STORE1',
            'btcpay_server_url' => 'https://btcpay.example', 'btcpay_api_key' => 'fake', 'btcpay_store_id' => 'STORE1']);
        update_option('wpinv_settings', $settings);
        $GLOBALS['wpinv_options'] = $settings;

        // 1. Webhook connected (record as written by 1.2.0, without a store entry): only the invoice is created.
        update_option('wpinv_settings_coinsnap_webhook', ['id' => 'wh1', 'secret' => 'S1', 'url' => $gateway->get_webhook_url()], false);
        $r = $checkout();
        $check($r === 'redirect https://pay.example/i/inv1', "$provider: connected webhook: checkout redirects to the invoice");
        $check($webhookCalls() === [], "$provider: connected webhook: no webhook API call (" . implode(', ', $webhookCalls()) . ')');
        $check(count($invoiceCalls()) === 1 && count($apiWrites()) === 1, "$provider: connected webhook: create invoice is the only API write");
        $check(get_option('wpinv_settings_coinsnap_webhook')['secret'] === 'S1', "$provider: connected webhook: stored secret unchanged");

        // 2. Record for this provider and store.
        update_option('wpinv_settings_coinsnap_webhook', ['id' => 'wh1', 'secret' => 'S1', 'url' => $gateway->get_webhook_url(), 'store' => "$provider:STORE1"], false);
        $r = $checkout();
        $check($r === 'redirect https://pay.example/i/inv1' && $webhookCalls() === [], "$provider: record for this store: invoice, no webhook call");

        // 3. No webhook stored: refused, nothing listed, deleted or registered, no secret stored.
        delete_option('wpinv_settings_coinsnap_webhook');
        $r = $checkout();
        $check($r === 'back', "$provider: no webhook: checkout sent back with an error");
        $check($webhookCalls() === [], "$provider: no webhook: no webhook API call (" . implode(', ', $webhookCalls()) . ')');
        $check($invoiceCalls() === [], "$provider: no webhook: no invoice created");
        $check(get_option('wpinv_settings_coinsnap_webhook') === false, "$provider: no webhook: checkout stores no secret");

        // 4. Record for another store (provider or store switched without connecting): refused, secret kept.
        update_option('wpinv_settings_coinsnap_webhook', ['id' => 'wh9', 'secret' => 'S9', 'url' => 'x', 'store' => 'other:OTHER'], false);
        $r = $checkout();
        $check($r === 'back' && $webhookCalls() === [] && $invoiceCalls() === [], "$provider: record for another store: refused, no API call");
        $check(get_option('wpinv_settings_coinsnap_webhook')['secret'] === 'S9', "$provider: record for another store: stored secret unchanged");

        // 5. The admin side still registers the webhook and records the store.
        $calls = [];
        $ok = $gateway->registerWebhook($gateway->getApiUrl(), $gateway->getApiKey(), $gateway->getStoreId());
        $stored = get_option('wpinv_settings_coinsnap_webhook');
        $check($ok && $stored['secret'] === 'NEWSECRET' && ($stored['store'] ?? '') === "$provider:STORE1", "$provider: admin registerWebhook stores the secret with provider:store");
        $check(count($webhookCalls()) === 1, "$provider: admin registerWebhook makes one create-webhook call");
        $check(method_exists($gateway, "webhookConnected") && $gateway->webhookConnected(), "$provider: after admin registration the webhook counts as connected");
    }
} finally {
    remove_filter('pre_http_request', $fake, 10);
    remove_filter('wp_redirect', $redirect, 1);
    remove_action('getpaid_checkout_invoice_exception', $refused, 1);
    update_option('wpinv_settings', $settingsBefore);
    if ($webhookBefore === false) { delete_option('wpinv_settings_coinsnap_webhook'); } else { update_option('wpinv_settings_coinsnap_webhook', $webhookBefore, false); }
    wp_delete_post($invoice->get_id(), true);
    wp_delete_post($item->get_id(), true);
}

echo $failures === 0 ? "OK\n" : "$failures FAILED\n";
