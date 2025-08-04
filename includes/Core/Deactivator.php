<?php
/**
 * Plugin Deactivator
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Deactivation Handler
 */
class Deactivator {
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('sohoj_check_updates');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Sohoj Secure Order: Plugin deactivated');
    }
} 