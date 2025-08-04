<?php
/**
 * Plugin Uninstaller
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Uninstallation Handler
 */
class Uninstaller {
    
    /**
     * Plugin uninstallation
     */
    public static function uninstall() {
        // Check if user has permission
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Delete plugin options
        self::delete_options();
        
        // Log uninstallation
        error_log('Sohoj Secure Order: Plugin uninstalled');
    }
    
    /**
     * Delete plugin options
     */
    private static function delete_options() {
        $options = array(
            'sohoj_plugin_version'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
} 