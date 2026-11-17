<?php
// Removes only this plugin's own data: its webhook record and its keys inside the GetPaid settings.
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('wpinv_settings_coinsnap_webhook');
delete_option('coinsnapgp_records_since');

$settings = get_option('wpinv_settings');
if (is_array($settings)) {
    foreach (['coinsnap_api_key', 'coinsnap_store_id', 'btcpay_api_key', 'btcpay_store_id', 'btcpay_server_url'] as $key) {
        unset($settings[$key]);
    }
    update_option('wpinv_settings', $settings);
}
