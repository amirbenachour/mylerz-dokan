<?php
/**
 * Plugin Name: Dokan Mylerz
 * Plugin URI: https://yourwebsite.com
 * Description: Integrates WooCommerce with Mylerz API for order shipping and AWB PDF generation.
 * Version: 1.0
 * Author: amir
 * Author URI: https://yourwebsite.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Define Constants
define('DOKAN_MYLERZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOKAN_MYLERZ_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include Dependencies
require_once DOKAN_MYLERZ_PLUGIN_DIR . 'includes/api-functions.php';
require_once DOKAN_MYLERZ_PLUGIN_DIR . 'includes/order-handler.php';
require_once DOKAN_MYLERZ_PLUGIN_DIR . 'includes/pdf-generator.php';

// Activate the plugin
function dokan_mylerz_activate() {
    // Code to run on activation (like database setup)
}
register_activation_hook(__FILE__, 'dokan_mylerz_activate');

// Deactivate the plugin
function dokan_mylerz_deactivate() {
    // Code to clean up on deactivation
}
register_deactivation_hook(__FILE__, 'dokan_mylerz_deactivate');
