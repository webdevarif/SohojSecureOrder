<?php
/**
 * Uninstall Sohoj Secure Order Plugin
 * 
 * This file is executed when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the uninstaller class
require_once plugin_dir_path(__FILE__) . 'includes/Core/Uninstaller.php';

// Run the uninstaller
\SohojSecureOrder\Core\Uninstaller::uninstall(); 