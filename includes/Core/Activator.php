<?php
/**
 * Plugin Activator
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Activation Handler
 */
class Activator {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Sohoj Secure Order: Plugin activated successfully');
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        add_option('sohoj_plugin_version', SOHOJ_PLUGIN_VERSION);
    }
} 