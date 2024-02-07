<?php

/**
 * Plugin Name: 		Coinsnap for GetPaid
 * Plugin URI: 		    https://getpaid.coinsnap.org
 * Description: 		Provides a <a href="https://coinsnap.io">Coinsnap</a>  - Bitcoin + Lightning Payment Gateway for <a href="https://wpgetpaid.com/">GetPaid</a>.
 * Version: 			1.0.0
 * Author: 			    Coinsnap
 * Author URI: 		    https://coinsnap.io
 * License:             GPL-2.0+
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
function wpinv_coinsnap_init()
{
    if (!defined('COINSNAP_GETPAID_VERSION')) {
        define('COINSNAP_GETPAID_VERSION', '1.0.0');
    }
    require_once(plugin_dir_path(__FILE__) . 'includes/class-coinsnap-getpaid.php');
    new GetPaidGateway_coinsnap();
}
add_action('getpaid_init', 'wpinv_coinsnap_init');
