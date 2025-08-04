<?php
/**
 * Admin Handler
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Handler Class
 */
class Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize admin functionality
     */
    private function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Initialize settings
        new Settings();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Sohoj Secure Order',
            'Sohoj Secure',
            'manage_options',
            'sohoj-secure-order',
            array($this, 'dashboard_page'),
            'dashicons-shield',
            30
        );
        
        // Sub menus
        add_submenu_page(
            'sohoj-secure-order',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sohoj-secure-order',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'sohoj-secure-order',
            'Settings',
            'Settings',
            'manage_options',
            'sohoj-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'sohoj-secure-order',
            'License',
            'License',
            'manage_options',
            'sohoj-license',
            array($this, 'license_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>Sohoj Secure Order - Dashboard</h1>
            <div class="sohoj-dashboard">
                <div class="sohoj-card">
                    <h2>Welcome to Your Plugin</h2>
                    <p>This is a clean starter plugin template.</p>
                    <p>Features:</p>
                    <ul>
                        <li>Professional OOP structure</li>
                        <li>Admin menu system</li>
                        <li>Settings management</li>
                        <li>License system</li>
                        <li>GitHub updates</li>
                        <li>Frontend shortcodes</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <div class="sohoj-settings">
                <div class="sohoj-card">
                    <h2>Plugin Settings</h2>
                    <p>Configure your plugin settings here.</p>
                    <p><em>Add your custom settings here...</em></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * License page
     */
    public function license_page() {
        ?>
        <div class="wrap">
            <h1>License Management</h1>
            <div class="sohoj-license">
                <div class="sohoj-card">
                    <h2>Plugin License</h2>
                    <p>Manage your plugin license.</p>
                    <p><em>License functionality coming soon...</em></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'sohoj-secure-order') === false) {
            return;
        }
        
        wp_enqueue_style('sohoj-admin-css', SOHOJ_PLUGIN_URL . 'assets/css/admin.css', array(), SOHOJ_PLUGIN_VERSION);
        wp_enqueue_script('sohoj-admin-js', SOHOJ_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SOHOJ_PLUGIN_VERSION, true);
    }
} 