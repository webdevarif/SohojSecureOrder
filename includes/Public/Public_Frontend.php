<?php
/**
 * Public Frontend Handler
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Public;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public Frontend Handler Class
 */
class Public_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize public functionality
     */
    private function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add shortcodes
        add_shortcode('sohoj_example_form', array($this, 'example_form_shortcode'));
        
        // Add AJAX handlers
        add_action('wp_ajax_sohoj_example_action', array($this, 'example_action'));
        add_action('wp_ajax_nopriv_sohoj_example_action', array($this, 'example_action'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('sohoj-public', SOHOJ_PLUGIN_URL . 'assets/js/public.js', array('jquery'), SOHOJ_PLUGIN_VERSION, true);
        wp_enqueue_style('sohoj-public', SOHOJ_PLUGIN_URL . 'assets/css/public.css', array(), SOHOJ_PLUGIN_VERSION);
        
        wp_localize_script('sohoj-public', 'sohoj_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sohoj_example_nonce')
        ));
    }
    
    /**
     * Example form shortcode
     */
    public function example_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Example Form'
        ), $atts);
        
        ob_start();
        ?>
        <div class="sohoj-form">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form id="sohoj-example-form">
                <?php wp_nonce_field('sohoj_example_nonce', 'sohoj_nonce'); ?>
                <p>
                    <label for="example_field">Example Field:</label>
                    <input type="text" id="example_field" name="example_field" required />
                </p>
                <button type="submit" class="button button-primary">Submit</button>
            </form>
            <div id="sohoj-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Example AJAX action
     */
    public function example_action() {
        check_ajax_referer('sohoj_example_nonce', 'nonce');
        
        $example_field = sanitize_text_field($_POST['example_field']);
        
        // Basic validation
        if (empty($example_field)) {
            wp_send_json_error('Field is required');
        }
        
        // Process the data
        $result = $this->process_example_data($example_field);
        
        wp_send_json_success('Data processed successfully!');
    }
    
    /**
     * Process example data
     */
    private function process_example_data($data) {
        // Add your custom processing logic here
        return true;
    }
} 