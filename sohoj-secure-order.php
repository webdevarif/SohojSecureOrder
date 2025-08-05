<?php
/**
 * Plugin Name: Sohoj Secure Order
 * Plugin URI: https://github.com/webdevarif/SohojSecureOrder.git
 * Description: A secure customer order management plugin with fraud detection and order prevention features.
 * Version: 1.0.0
 * Author: WebDevArif
 * Author URI: https://github.com/webdevarif
 * License: GPL v2 or later
 * Text Domain: sohoj-secure-order
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SOHOJ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SOHOJ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SOHOJ_PLUGIN_VERSION', '1.0.0');
define('SOHOJ_PLUGIN_FILE', __FILE__);
define('SOHOJ_GITHUB_REPO', 'webdevarif/SohojSecureOrder');

/**
 * Initialize the plugin
 */
function sohoj_secure_order_init() {
    // Load dependencies
    require_once SOHOJ_PLUGIN_PATH . 'includes/Admin/Form_Components.php';
    require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Phone_Validator.php';

    // Load the main plugin class
    require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Plugin.php';
    
    // Initialize the plugin
    \SohojSecureOrder\Core\Plugin::get_instance()->init();

    // Initialize IP Blocker
    require_once SOHOJ_PLUGIN_PATH . 'includes/Core/IP_Blocker.php';
    new \SohojSecureOrder\Core\IP_Blocker();

    // Initialize Fraud Checker
    require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Fraud_Checker.php';
    new \SohojSecureOrder\Core\Fraud_Checker();
}
add_action('plugins_loaded', 'sohoj_secure_order_init');

/**
 * Plugin activation hook
 */
function sohoj_secure_order_activate() {
    require_once SOHOJ_PLUGIN_PATH . 'includes/Core/IP_Blocker.php';
    $ip_blocker = new \SohojSecureOrder\Core\IP_Blocker();
    $ip_blocker->create_table();
}
register_activation_hook(__FILE__, 'sohoj_secure_order_activate');