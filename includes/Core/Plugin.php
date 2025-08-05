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
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/GitHub_Updater.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Phone_Validator.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Order_Limiter.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Incomplete_Orders.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/IP_Blocker.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Phone_History.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Core/Fraud_Checker.php';
        
        // Admin classes
        require_once SOHOJ_PLUGIN_PATH . 'includes/Admin/Form_Components.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Admin/Admin.php';
        require_once SOHOJ_PLUGIN_PATH . 'includes/Admin/Settings.php';
        
        // Public classes
        require_once SOHOJ_PLUGIN_PATH . 'includes/Public/Public_Frontend.php';
        
        // WooCommerce classes
        require_once SOHOJ_PLUGIN_PATH . 'includes/WooCommerce/Checkout_Validator.php';
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
        
        // Initialize GitHub updater
        $this->init_github_updater();
        
        // Initialize WooCommerce checkout validator
        new \SohojSecureOrder\WooCommerce\Checkout_Validator();
        
        // Initialize order limiter  
        new \SohojSecureOrder\Core\Order_Limiter();
        
        // Initialize incomplete orders tracker
        new \SohojSecureOrder\Core\Incomplete_Orders();
        
        // Initialize IP blocker
        $ip_blocker = new \SohojSecureOrder\Core\IP_Blocker();
        $ip_blocker->create_table();
        
        // Initialize Phone History
        new \SohojSecureOrder\Core\Phone_History();
        
        // Initialize Fraud Checker
        new \SohojSecureOrder\Core\Fraud_Checker();
    }
    
    /**
     * Initialize GitHub updater
     */
    private function init_github_updater() {
        if (is_admin()) {
            $config = array(
                'slug' => plugin_basename(SOHOJ_PLUGIN_FILE),
                'proper_folder_name' => dirname(plugin_basename(SOHOJ_PLUGIN_FILE)),
                'api_url' => 'https://api.github.com/repos/webdevarif/SohojSecureOrder',
                'raw_url' => 'https://raw.githubusercontent.com/webdevarif/SohojSecureOrder/main',
                'github_url' => 'https://github.com/webdevarif/SohojSecureOrder',
                'zip_url' => 'https://github.com/webdevarif/SohojSecureOrder/zipball/main',
                'sslverify' => true,
                'requires' => '5.0',
                'tested' => '6.4',
                'readme' => 'README.md',
                'access_token' => '', // Add if private repo
            );
            new \SohojSecureOrder\Core\GitHub_Updater($config);
        }
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