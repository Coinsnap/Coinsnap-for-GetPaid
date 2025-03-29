<?php

/**
 * Plugin Name:     Bitcoin Donation for Getpaid
 * Description:     Accept Bitcoin payments with Getpaid. All Bitcoin payments are transferred directly from your customer’s wallet into your Lightning wallet.
 * Version:         1.0.0
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io
 * Text Domain:     coinsnap-for-getpaid
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Tested up to:    6.7
 * Requires at least: 6.0
 * Requires Plugins: invoicing
 * Getpaid tested up to: 2.8.24
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 */

if (!defined( 'ABSPATH' )){ exit;}
define( 'COINSNAP_GETPAID_PHP_VERSION', '7.4' );
define( 'COINSNAP_GETPAID_VERSION', '1.0.0' );
define( 'COINSNAP_GETPAID_REFERRAL_CODE', 'D15432');
define( 'COINSNAP_GETPAID_PLUGIN_ID', 'coinsnap-for-getpaid' );
define( 'COINSNAP_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'COINSNAP_PLUGIN_URL', plugin_dir_url(__FILE__ ) );
define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );
define( 'COINSNAP_API_PATH', '/api/v1/');
define( 'COINSNAP_SERVER_PATH', 'stores' );

require_once(dirname(__FILE__) . "/library/loader.php");

function wpinv_coinsnap_init()
{
    require_once(plugin_dir_path(__FILE__) . 'includes/class-coinsnap-getpaid.php');
    new CoinsnapGP_Gateway();
}
add_action('getpaid_init', 'wpinv_coinsnap_init');
add_action('admin_init', 'check_getpaid_dependency');

function check_getpaid_dependency(){
    if (!is_plugin_active('invoicing/invoicing.php')) {
        add_action('admin_notices', 'getpaid_dependency_notice');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

function getpaid_dependency_notice(){?>
    <div class="notice notice-error">
        <p><?php echo esc_html_e('Bitcoin Donation for Getpaid plugin requires GetPaid to be installed and activated.','coinsnap-for-getpaid'); ?></p>
    </div>
    <?php
}
