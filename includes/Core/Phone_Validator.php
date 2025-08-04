<?php
/**
 * Phone Validator
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bangladeshi Phone Number Validator
 */
class Phone_Validator {
    
    /**
     * Validate Bangladeshi phone number
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_bd_phone($phone) {
        $validation = self::validate_bangladeshi_phone($phone);
        return $validation['valid'];
    }
    
    /**
     * Validate Bangladeshi phone number with detailed response
     * 
     * @param string $phone Phone number to validate
     * @return array Validation result with message and normalized number
     */
    public static function validate_bangladeshi_phone($phone) {
        if (empty($phone)) {
            return ['valid' => false, 'message' => 'Phone number is required'];
        }
        
        // Remove all non-digit characters
        $clean_phone = preg_replace('/\D+/', '', $phone);
        
        // Check if it's a valid Bangladeshi mobile number
        // Pattern: ^(880|0)?1[3-9]\d{8}$
        // - (880|0)? : Optional country code 880 or leading 0
        // - 1 : Must start with 1
        // - [3-9] : Second digit must be 3-9 (covers all BD operators)
        // - \d{8} : Followed by exactly 8 more digits
        if (preg_match('/^(880|0)?1[3-9]\d{8}$/', $clean_phone)) {
            // Normalize to 11 digits (01XXXXXXXXX format)
            $normalized = self::normalize_bangladeshi_phone($clean_phone);
            return [
                'valid' => true, 
                'message' => 'Valid phone number',
                'normalized' => $normalized,
                'international' => '+880' . substr($normalized, 1) // +8801XXXXXXXXX format
            ];
        }
        
        return ['valid' => false, 'message' => 'Please enter a valid Bangladeshi mobile number'];
    }
    
    /**
     * Normalize Bangladeshi phone number to 01XXXXXXXXX format
     * 
     * @param string $phone Cleaned phone number
     * @return string Normalized phone number
     */
    public static function normalize_bangladeshi_phone($phone) {
        // Remove all non-digit characters
        $clean_phone = preg_replace('/\D+/', '', $phone);
        
        // Handle different formats
        if (strlen($clean_phone) === 13 && strpos($clean_phone, '880') === 0) {
            // Format: 8801XXXXXXXXX -> 01XXXXXXXXX
            return '0' . substr($clean_phone, 3);
        } elseif (strlen($clean_phone) === 10 && strpos($clean_phone, '1') === 0) {
            // Format: 1XXXXXXXXX -> 01XXXXXXXXX
            return '0' . $clean_phone;
        } elseif (strlen($clean_phone) === 11 && strpos($clean_phone, '0') === 0) {
            // Format: 01XXXXXXXXX -> keep as is
            return $clean_phone;
        }
        
        // If none of the above, return as is
        return $clean_phone;
    }
    
    /**
     * Get validation error message
     * 
     * @return string Error message
     */
    public static function get_error_message() {
        $custom_message = get_option('sohoj_error_message', '');
        if (!empty($custom_message)) {
            return $custom_message;
        }
        return __('Please enter a valid Bangladeshi mobile number (e.g., 01712345678, +8801712345678)', 'sohoj-secure-order');
    }
    
    /**
     * Get custom error details for notifications
     * 
     * @return array Error details with icon and heading
     */
    public static function get_error_details() {
        return array(
            'icon' => get_option('sohoj_error_icon', '⚠️'),
            'heading' => get_option('sohoj_error_heading', 'Invalid Phone Number'),
            'message' => self::get_error_message()
        );
    }
    
    /**
     * Format phone number for display
     * 
     * @param string $phone Phone number to format
     * @return string Formatted phone number
     */
    public static function format_phone($phone) {
        $validation = self::validate_bangladeshi_phone($phone);
        
        if ($validation['valid']) {
            return $validation['international'];
        }
        
        return $phone;
    }
    
    /**
     * Validate phone for checkout (main validation function)
     * 
     * @param string $phone Phone number to validate
     * @return array Validation result
     */
    public static function validate_phone_for_checkout($phone) {
        return self::validate_bangladeshi_phone($phone);
    }
}