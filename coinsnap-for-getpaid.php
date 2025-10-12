<?php

/**
 * Plugin Name:     Bitcoin payments for Getpaid
 * Description:     Accept Bitcoin payments with Getpaid. All Bitcoin payments are transferred directly from your customer’s wallet into your Lightning wallet.
 * Version:         1.1.0
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io
 * Text Domain:     coinsnap-for-getpaid
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Tested up to:    6.8
 * Requires at least: 6.0
 * Requires Plugins: invoicing
 * Getpaid tested up to: 2.8.35
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 */

if (!defined( 'ABSPATH' )){ exit;}
if(!defined('COINSNAPGP_PHP_VERSION')){define( 'COINSNAPGP_PHP_VERSION', '7.4' );}
if(!defined('COINSNAPGP_VERSION')){define( 'COINSNAPGP_VERSION', '1.1.0' );}
if(!defined('COINSNAPGP_REFERRAL_CODE')){define( 'COINSNAPGP_REFERRAL_CODE', 'D15432');}
if(!defined('COINSNAPGP_PLUGIN_ID')){define( 'COINSNAPGP_PLUGIN_ID', 'coinsnap-for-getpaid' );}
if(!defined('COINSNAPGP_PATH')){ define( 'COINSNAPGP_PATH', plugin_dir_path( __FILE__ ) ); }
if(!defined('COINSNAPGP_URL')){ define( 'COINSNAPGP_URL', plugin_dir_url( __FILE__ ) ); }
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}
if(!defined('COINSNAP_SERVER_PATH')){define( 'COINSNAP_SERVER_PATH', 'stores' );}
if(!defined('COINSNAP_CURRENCIES')){define( 'COINSNAP_CURRENCIES', array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB") );}

require_once(dirname(__FILE__) . "/library/loader.php");

function coinsnapgp_init(){
    require_once(plugin_dir_path(__FILE__) . 'includes/class-coinsnap-getpaid.php');
    new CoinsnapGP_Gateway();
}
add_action('getpaid_init', 'coinsnapgp_init');
add_action('admin_init', 'coinsnapgp_dependency_check');
add_action('init', function() {
    // Setting up and handling custom endpoint for api key redirect from BTCPay Server.
    add_rewrite_endpoint('coinsnap-for-getpaid-btcpay-settings-callback', EP_ROOT);
});

// To be able to use the endpoint without appended url segments we need to do this.
add_filter('request', function($vars) {
    if (isset($vars['coinsnap-for-getpaid-btcpay-settings-callback'])) {
        $vars['coinsnap-for-getpaid-btcpay-settings-callback'] = true;
        $vars['coinsnap-for-getpaid-btcpay-nonce'] = wp_create_nonce( 'coinsnapgp-btcpay-nonce' );
    }
    return $vars;
});

function coinsnapgp_dependency_check(){
    if (!is_plugin_active('invoicing/invoicing.php')) {
        add_action('admin_notices', 'coinsnapgp_dependency_notice');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

function coinsnapgp_dependency_notice(){?>
    <div class="notice notice-error">
        <p><?php echo esc_html_e('Bitcoin Donation for Getpaid plugin requires GetPaid to be installed and activated.','coinsnap-for-getpaid'); ?></p>
    </div>
    <?php
}
