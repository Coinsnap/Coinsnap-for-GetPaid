<?php

/**
 *Plugin Name: 		Coinsnap for GetPaid
 *Plugin URI: 		https://coinsnap.io
 *Description: 		Provides a <a href="https://coinsnap.io">Coinsnap</a>  - Bitcoin + Lightning Payment Gateway for <a href="https://wpgetpaid.com/">GetPaid</a>.
 *Version: 			1.0.0
 *Author: 			Coinsnap
 *Author URI: 		https://coinsnap.io
 */


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
function wpinv_coinsnap_init()
{
    if (!defined('_VERSION')) {
        define('COINSNAP_GETPAID_VERSION', '1.0.0');
    }
    require_once(plugin_dir_path(__FILE__) . 'includes/class-coinsnap-getpaid.php');
    new GetPaidGateway_coinsnap();
}
add_action('getpaid_init', 'wpinv_coinsnap_init');
