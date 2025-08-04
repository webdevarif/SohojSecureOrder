<?php
/**
 * Main Plugin Class
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
class Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Activator.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Deactivator.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Uninstaller.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/License_Manager.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Update_Checker.php';
        
        // Admin classes
        require_once SOHOJ_PLUGIN_PATH . 'includes/Admin/Admin.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Admin/Settings.php';
        
        // Public classes
        require_once SOHOJ_PLUGIN_PATH . 'includes/Public/Public_Frontend.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize admin
        if (is_admin()) {
            new \SohojSecureOrder\Admin\Admin();
        }
        
        // Initialize public frontend
        new \SohojSecureOrder\Public\Public_Frontend();
        
        // Initialize license manager
        new \SohojSecureOrder\Core\License_Manager();
        
        // Initialize update checker
        new \SohojSecureOrder\Core\Update_Checker();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Activation hook
        register_activation_hook(SOHOJ_PLUGIN_FILE, array('\SohojSecureOrder\Core\Activator', 'activate'));
        
        // Deactivation hook
        register_deactivation_hook(SOHOJ_PLUGIN_FILE, array('\SohojSecureOrder\Core\Deactivator', 'deactivate'));
    }
} 