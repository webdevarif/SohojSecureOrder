<?php
/**
 * Test Phone Validation
 * This is a temporary test file to verify phone validation is working
 * Access via: /wp-content/plugins/SohojSecureOrder/test-phone-validation.php
 */

// Load WordPress
require_once '../../../../../wp-load.php';

// Load our phone validator
require_once 'includes/Core/Phone_Validator.php';

use SohojSecureOrder\Core\Phone_Validator;

// Test numbers with your correct validation logic
$test_numbers = array(
    '165666' => false,           // Invalid - too short, wrong pattern
    '01712345678' => true,       // Valid Grameenphone (13-19)
    '+880 1712345678' => true,   // Valid with country code
    '8801712345678' => true,     // Valid with 880
    '01812345678' => true,       // Valid Robi (18)
    '01912345678' => true,       // Valid Banglalink (19)
    '01512345678' => true,       // Valid Teletalk (15)
    '01612345678' => true,       // Valid Aircell (16)
    '01412345678' => true,       // Valid Banglalink (14)
    '01312345678' => true,       // Valid Grameenphone (13)
    '01712345' => false,         // Invalid - too short
    '02112345678' => false,      // Invalid - starts with 02 (landline)
    '01012345678' => false,      // Invalid - 10 is not valid operator
    '01112345678' => false,      // Invalid - 11 is not valid operator  
    '01212345678' => false,      // Invalid - 12 is not valid operator
    'abcd1234567' => false,      // Invalid characters
    '' => false,                 // Empty
);

echo '<h1>Phone Validation Test Results</h1>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>Phone Number</th><th>Expected</th><th>Result</th><th>Status</th></tr>';

foreach ($test_numbers as $phone => $expected) {
    $result = Phone_Validator::is_valid_bd_phone($phone);
    $validation_details = Phone_Validator::validate_bangladeshi_phone($phone);
    $status = ($result === $expected) ? '✅ PASS' : '❌ FAIL';
    
    echo '<tr>';
    echo '<td>' . esc_html($phone) . '</td>';
    echo '<td>' . ($expected ? 'Valid' : 'Invalid') . '</td>';
    echo '<td>' . ($result ? 'Valid' : 'Invalid') . '</td>';
    echo '<td>' . $status . '</td>';
    echo '</tr>';
    
    // Show detailed validation for failed tests
    if (!$result && $expected) {
        echo '<tr style="background: #ffebee;"><td colspan="4"><small>❌ Expected valid but got: ' . esc_html($validation_details['message']) . '</small></td></tr>';
    } elseif ($result && !$expected) {
        echo '<tr style="background: #ffebee;"><td colspan="4"><small>❌ Expected invalid but validation passed</small></td></tr>';
    }
}

echo '</table>';

// Test current plugin settings
echo '<h2>Plugin Settings</h2>';
echo '<p>Phone validation enabled: ' . (get_option('sohoj_phone_validation_enabled', 0) ? 'Yes' : 'No') . '</p>';
echo '<p>WooCommerce active: ' . (class_exists('WooCommerce') ? 'Yes' : 'No') . '</p>';

echo '<h2>Error Log</h2>';
echo '<p>Check your WordPress error log for detailed validation debug messages.</p>';
echo '<p>You can now test checkout with invalid numbers like "165666" - it should be blocked.</p>';