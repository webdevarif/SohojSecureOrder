<?php
/**
 * Debug Checkout Validation
 * This simulates WooCommerce checkout validation
 * Access via: /wp-content/plugins/SohojSecureOrder/debug-checkout.php
 */

// Load WordPress
require_once '../../../../../wp-load.php';

// Load our classes
require_once 'includes/Core/Phone_Validator.php';
require_once 'includes/WooCommerce/Checkout_Validator.php';

use SohojSecureOrder\Core\Phone_Validator;
use SohojSecureOrder\WooCommerce\Checkout_Validator;

echo '<h1>Debug Checkout Validation</h1>';

// Test if validation is enabled
$validation_enabled = get_option('sohoj_phone_validation_enabled', 0);
$wc_active = class_exists('WooCommerce');

echo '<h2>Plugin Status</h2>';
echo '<ul>';
echo '<li>Phone validation enabled: <strong>' . ($validation_enabled ? 'Yes' : 'No') . '</strong></li>';
echo '<li>WooCommerce active: <strong>' . ($wc_active ? 'Yes' : 'No') . '</strong></li>';
echo '</ul>';

// Simulate checkout form
if (isset($_POST['test_phone'])) {
    echo '<h2>Test Results</h2>';
    
    $phone = sanitize_text_field($_POST['test_phone']);
    echo '<p>Testing phone: <strong>' . esc_html($phone) . '</strong></p>';
    
    // Simulate the checkout validation
    $_POST['billing_phone'] = $phone;
    
    $validator = new Checkout_Validator();
    
    // Capture any notices
    ob_start();
    $validator->validate_checkout_phone();
    $output = ob_get_clean();
    
    // Check if WooCommerce notices were added
    if (function_exists('wc_get_notices')) {
        $notices = wc_get_notices('error');
        if (!empty($notices)) {
            echo '<div style="background: #ffebee; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; color: #721c24;">';
            echo '<strong>❌ Validation Failed:</strong><br>';
            foreach ($notices as $notice) {
                echo '• ' . esc_html($notice['notice']) . '<br>';
            }
            echo '</div>';
            wc_clear_notices(); // Clear for next test
        } else {
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; color: #155724;">';
            echo '<strong>✅ Validation Passed</strong>';
            echo '</div>';
        }
    }
    
    // Also test direct validation
    echo '<h3>Direct Validation Test</h3>';
    $direct_validation = Phone_Validator::validate_phone_for_checkout($phone);
    echo '<pre>' . print_r($direct_validation, true) . '</pre>';
}

// Test form
echo '<h2>Test Checkout Validation</h2>';
echo '<form method="post">';
echo '<input type="text" name="test_phone" placeholder="Enter phone number (e.g., 165666)" value="' . (isset($_POST['test_phone']) ? esc_attr($_POST['test_phone']) : '') . '" />';
echo '<button type="submit">Test Validation</button>';
echo '</form>';

echo '<h3>Test Examples:</h3>';
echo '<ul>';
echo '<li><code>165666</code> - Should fail (invalid)</li>';
echo '<li><code>01712345678</code> - Should pass (valid)</li>';
echo '<li><code>+8801812345678</code> - Should pass (valid)</li>';
echo '</ul>';

echo '<p><strong>Check your WordPress error log for detailed debugging information.</strong></p>';