<?php

/**
 * Plugin Name:     Coinsnap for GetPaid
 * Description:     Provides a <a href="https://coinsnap.io">Coinsnap</a>  - Bitcoin + Lightning Payment Gateway for GetPaid.
 * Version:         1.0.0
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io
 * Text Domain:     coinsnap-for-getpaid
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Tested up to:    6.6
 * Requires at least: 5.2
 * Getpaid tested up to: 2.8.12
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 */

if (!defined( 'ABSPATH' )){ exit;}
define( 'SERVER_PHP_VERSION', '7.4' );
define( 'COINSNAP_GETPAID_VERSION', '1.0.0' );
define('COINSNAP_GETPAID_REFERRAL_CODE', 'D15432');
define( 'COINSNAP_PLUGIN_ID', 'coinsnap-for-getpaid' );
define( 'COINSNAP_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'COINSNAP_PLUGIN_URL', plugin_dir_url(__FILE__ ) );
define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );
define( 'COINSNAP_API_PATH', '/api/v1/');
define( 'COINSNAP_SERVER_PATH', 'stores' );

require_once(dirname(__FILE__) . "/library/loader.php");

function wpinv_coinsnap_init()
{
    require_once(plugin_dir_path(__FILE__) . 'includes/class-coinsnap-getpaid.php');
    new GetPaidGateway_coinsnap();
}
add_action('getpaid_init', 'wpinv_coinsnap_init');
