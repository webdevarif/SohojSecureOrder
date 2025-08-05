<?php
/**
 * Fraud Checker
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Fraud_Checker {

    public function __construct() {
        add_action('wp_ajax_sohoj_fraud_check', array($this, 'handle_fraud_check'));
        add_action('wp_ajax_nopriv_sohoj_fraud_check', array($this, 'handle_fraud_check'));
    }

    public function handle_fraud_check() {
        check_ajax_referer('sohoj_fraud_check_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions.');
        }

        $phone = sanitize_text_field($_POST['phone'] ?? '');

        if (empty($phone)) {
            wp_send_json_error('Phone number is required.');
        }

        // Check if license is active
        if (!\SohojSecureOrder\Core\License_Manager::is_license_active()) {
            wp_send_json_error('License is not active. Please activate your license to use fraud check.');
        }

        // Get API key from license manager
        $api_key = \SohojSecureOrder\Core\License_Manager::get_api_key();

        if (empty($api_key)) {
            wp_send_json_error('API key not found. Please check your license configuration.');
        }

        // Make actual API call to CurtCommerz
        $api_response = $this->call_curtcommerz_fraud_api($phone, $api_key);

        if (is_wp_error($api_response)) {
            error_log('Fraud Check AJAX Error: ' . $api_response->get_error_message());
            wp_send_json_error('API Error: ' . $api_response->get_error_message());
        }

        // Log the API response structure for debugging
        error_log('Fraud Check API Response Structure: ' . wp_json_encode($api_response));

        // Process the API response and generate HTML
        $html_output = $this->generate_fraud_report_html($api_response);

        // Check if HTML generation failed
        if (strpos($html_output, 'Fraud check failed') !== false) {
            error_log('Fraud Check HTML Generation Failed - Response Data: ' . wp_json_encode($api_response));
            wp_send_json_error('Failed to process fraud check data. Please check the logs for details.');
        }

        wp_send_json_success($html_output);
    }

    private function call_curtcommerz_fraud_api($phone, $api_key) {
        $api_url = \SohojSecureOrder\Core\License_Manager::API_BASE_URL . 'couriers/fraud_check/';
        
        // Get AI setting from WordPress options
        $use_ai = get_option('sohoj_fraud_check_use_ai', 0) == 1;
        
        $body = [
            'phone' => $phone,
            'courier_type' => 'all', // Check all courier types
            'use_ai' => $use_ai
        ];

        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $api_key
            ],
            'body' => wp_json_encode($body),
            'timeout' => 300, // Increase timeout
            'sslverify' => false // For development, enable in production
        ];

        // Log the request for debugging
        error_log('CurtCommerz Fraud Check Request:');
        error_log('URL: ' . $api_url);
        error_log('Body: ' . wp_json_encode($body));
        error_log('API Key: ' . substr($api_key, 0, 10) . '...');

        $response = wp_remote_request($api_url, $args);

        if (is_wp_error($response)) {
            error_log('CurtCommerz Fraud Check WP Error: ' . $response->get_error_message());
            return new \WP_Error('api_error', 'Failed to connect to CurtCommerz API: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Log the response for debugging
        error_log('CurtCommerz Fraud Check Response:');
        error_log('Status Code: ' . $response_code);
        error_log('Response Body: ' . $response_body);

        if ($response_code !== 200) {
            error_log('CurtCommerz Fraud Check Failed - Status Code: ' . $response_code);
            return new \WP_Error('api_error', 'API returned error code: ' . $response_code . ' - Response: ' . $response_body);
        }

        $decoded_response = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('CurtCommerz Fraud Check JSON Error: ' . json_last_error_msg());
            error_log('Raw Response Body: ' . $response_body);
            return new \WP_Error('json_error', 'Invalid JSON response from API: ' . json_last_error_msg());
        }

        error_log('CurtCommerz Fraud Check Success - Decoded Response: ' . wp_json_encode($decoded_response));
        return $decoded_response;
    }


    private function generate_fraud_report_html($response_data) {
        // Handle API response structure - check different possible response formats
        error_log('Fraud Check - Analyzing response structure: ' . wp_json_encode($response_data));
        
        // Check if it's an error response
        if (isset($response_data['success']) && !$response_data['success']) {
            $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Unknown API error';
            return '<div class="notice notice-error"><p>Fraud check failed: ' . esc_html($error_msg) . '</p></div>';
        }
        
        // Check for different status formats
        $status_ok = false;
        if (isset($response_data['status'])) {
            $status_ok = ($response_data['status'] === true || $response_data['status'] === 'true' || $response_data['status'] === 1);
        } elseif (isset($response_data['success'])) {
            $status_ok = ($response_data['success'] === true || $response_data['success'] === 'true' || $response_data['success'] === 1);
        }
        
        // If no clear status, check if we have data that looks like fraud check results
        if (!$status_ok) {
            // Check if response has the expected fraud check data structure
            if (!isset($response_data['total']) && !isset($response_data['deliveredPercentage']) && 
                !isset($response_data['pathao']) && !isset($response_data['steadfast'])) {
                error_log('Fraud Check - Status check failed. Response: ' . wp_json_encode($response_data));
                
                // Create a debugging display for unknown response format
                return $this->generate_debug_response_html($response_data);
            }
            // If we have fraud data but unclear status, proceed anyway
            $status_ok = true;
        }

        // Determine risk level based on delivery percentage and fraud percentage
        $delivered_percentage = isset($response_data['deliveredPercentage']) ? floatval($response_data['deliveredPercentage']) : 0;
        $fraud_percentage = isset($response_data['fraudPercentage']) ? floatval($response_data['fraudPercentage']) : 0;
        
        $risk_level = 'low';
        $risk_score = 100 - $fraud_percentage;
        
        if ($fraud_percentage > 20 || $delivered_percentage < 50) {
            $risk_level = 'high';
        } elseif ($fraud_percentage > 10 || $delivered_percentage < 70) {
            $risk_level = 'medium';
        }

        $risk_class = 'fraud-risk-' . $risk_level;

        ob_start();
        ?>
        <div class="fraud-result-card <?php echo esc_attr($risk_class); ?>">
            <div class="fraud-header">
                <h3 class="fraud-title">📊 Fraud Analysis Report</h3>
                <div class="risk-badge risk-<?php echo esc_attr($risk_level); ?>">
                    Risk: <?php echo esc_html(ucfirst($risk_level)); ?> (<?php echo esc_html(number_format($risk_score, 1)); ?>%)
                </div>
            </div>

            <!-- Overall Summary -->
            <div class="fraud-summary">
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-number"><?php echo esc_html($response_data['total'] ?? 0); ?></span>
                        <span class="summary-label">Total Orders</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number"><?php echo esc_html($response_data['success'] ?? 0); ?></span>
                        <span class="summary-label">Successful</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number"><?php echo esc_html($response_data['cancel'] ?? 0); ?></span>
                        <span class="summary-label">Cancelled</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-number"><?php echo esc_html(number_format($delivered_percentage, 1)); ?>%</span>
                        <span class="summary-label">Success Rate</span>
                    </div>
                </div>
            </div>

            <div class="fraud-sections-wrapper">
                <!-- Courier Breakdown -->
                <div class="fraud-section courier-breakdown">
                    <h4>🚚 Courier Performance Analysis</h4>
                    <?php
                    $couriers = ['pathao', 'steadfast', 'redx', 'paperfly'];
                    $active_couriers = 0;
                    
                    foreach ($couriers as $courier) {
                        if (isset($response_data[$courier]) && $response_data[$courier]['status']) {
                            $active_couriers++;
                            $courier_data = $response_data[$courier]['data'];
                            $courier_success_rate = $courier_data['deliveredPercentage'] ?? 0;
                            $courier_logo = $response_data[$courier]['courier_logo'] ?? '';
                            
                            // Determine courier status
                            $status_class = 'good';
                            $status_icon = '✅';
                            if ($courier_success_rate < 50) {
                                $status_class = 'poor';
                                $status_icon = '❌';
                            } elseif ($courier_success_rate < 80) {
                                $status_class = 'medium';
                                $status_icon = '⚠️';
                            }
                            ?>
                            <div class="courier-item">
                                <div class="courier-header">
                                    <div class="courier-info">
                                        <?php if ($courier_logo): ?>
                                            <img src="<?php echo esc_url($courier_logo); ?>" alt="<?php echo esc_attr(ucfirst($courier)); ?>" class="courier-logo">
                                        <?php endif; ?>
                                        <strong><?php echo esc_html(ucfirst($courier)); ?></strong>
                                        <span class="status-icon"><?php echo $status_icon; ?></span>
                                    </div>
                                    <span class="courier-rate <?php echo $status_class; ?>">
                                        <?php echo esc_html(number_format($courier_success_rate, 1)); ?>%
                                    </span>
                                </div>
                                <div class="courier-stats">
                                    <div class="stat-row">
                                        <span class="stat-label">Total Orders:</span>
                                        <span class="stat-value"><?php echo esc_html($courier_data['total'] ?? 0); ?></span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Successful:</span>
                                        <span class="stat-value success"><?php echo esc_html($courier_data['success'] ?? 0); ?></span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Cancelled:</span>
                                        <span class="stat-value cancelled"><?php echo esc_html($courier_data['cancel'] ?? 0); ?></span>
                                    </div>
                                    <?php if (isset($courier_data['returnPercentage'])): ?>
                                    <div class="stat-row">
                                        <span class="stat-label">Return Rate:</span>
                                        <span class="stat-value return"><?php echo esc_html(number_format($courier_data['returnPercentage'], 1)); ?>%</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    
                    if ($active_couriers === 0) {
                        echo '<p class="no-courier-data">No courier data available for this phone number.</p>';
                    }
                    ?>
                </div>

                <!-- Fraud Reports -->
                <div class="fraud-section fraud-reports">
                    <h4>⚠️ Fraud Reports (<?php echo count($response_data['fraudReport'] ?? []); ?>)</h4>
                    <?php if (!empty($response_data['fraudReport'])): ?>
                        <div class="fraud-reports-list">
                            <?php foreach ($response_data['fraudReport'] as $index => $report): ?>
                                <div class="fraud-report-item" <?php echo $index >= 2 ? 'style="display:none;"' : ''; ?>>
                                    <div class="report-header">
                                        <strong><?php echo esc_html($report['name'] ?? 'N/A'); ?></strong>
                                        <small><?php echo esc_html($report['courier'] ?? ''); ?> - <?php echo esc_html(date('M j, Y', strtotime($report['created_at'] ?? ''))); ?></small>
                                    </div>
                                    <div class="report-details">
                                        <?php echo esc_html(wp_trim_words($report['details'] ?? '', 20)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($response_data['fraudReport']) > 2): ?>
                                <button type="button" class="button button-small show-more-reports">Show More Reports</button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reports">
                            <div class="no-reports-icon">✅</div>
                            <div class="no-reports-text">
                                <strong>No fraud reports found</strong><br>
                                <small>This phone number has no reported fraud incidents.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- AI Recommendation -->
            <?php if (!empty($response_data['recommendation'])): ?>
            <div class="ai-suggestion-box">
                <div class="ai-header">
                    <h4>🤖 AI Risk Assessment</h4>
                    <div class="processing-time">
                        <small>Processed in <?php echo esc_html(number_format($response_data['processing_time'] ?? 0, 2)); ?>s</small>
                    </div>
                </div>
                <div class="recommendation-text">
                    <?php echo wp_kses_post(wpautop($response_data['recommendation'])); ?>
                </div>
                <?php if ($response_data['required_otp'] ?? false): ?>
                    <div class="otp-required">
                        <strong>📱 OTP Required:</strong> This customer requires OTP verification for orders.
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.show-more-reports').click(function() {
                $(this).siblings('.fraud-report-item:hidden').show();
                $(this).text('Show Less').off('click').click(function() {
                    $(this).siblings('.fraud-report-item:gt(1)').hide();
                    $(this).text('Show More Reports');
                });
            });
        });
        </script>
        
        <style>
            .fraud-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid rgba(0,0,0,0.1);
            }
            .fraud-title {
                margin: 0;
                font-size: 24px;
                color: #1f2937;
            }
            .risk-badge {
                padding: 10px 20px;
                border-radius: 25px;
                font-weight: bold;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .risk-low {
                background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                color: #065f46;
                border: 2px solid #10b981;
            }
            .risk-medium {
                background: linear-gradient(135deg, #fef3c7, #fde68a);
                color: #92400e;
                border: 2px solid #f59e0b;
            }
            .risk-high {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                color: #991b1b;
                border: 2px solid #ef4444;
            }
            .fraud-summary {
                margin-bottom: 30px;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            .summary-item {
                background: rgba(255,255,255,0.8);
                padding: 15px;
                border-radius: 10px;
                text-align: center;
                border: 1px solid rgba(0,0,0,0.1);
            }
            .summary-number {
                display: block;
                font-size: 28px;
                font-weight: bold;
                color: #1f2937;
                margin-bottom: 5px;
            }
            .summary-label {
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #6b7280;
            }
            .fraud-sections-wrapper {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 25px;
                margin-bottom: 25px;
            }
            .fraud-section {
                background: rgba(255,255,255,0.9);
                padding: 20px;
                border-radius: 12px;
                border: 1px solid rgba(0,0,0,0.1);
            }
            .fraud-section h4 {
                margin-top: 0;
                color: #1f2937;
                font-size: 16px;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }
            .courier-item {
                margin-bottom: 15px;
                padding: 15px;
                background: #f9fafb;
                border-radius: 8px;
                border-left: 4px solid #e5e7eb;
            }
            .courier-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .courier-info {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .courier-logo {
                width: 20px;
                height: 20px;
                object-fit: contain;
            }
            .status-icon {
                font-size: 14px;
            }
            .courier-rate {
                padding: 6px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
            }
            .courier-rate.good { background: #d1fae5; color: #065f46; }
            .courier-rate.medium { background: #fef3c7; color: #92400e; }
            .courier-rate.poor { background: #fee2e2; color: #991b1b; }
            .courier-stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                font-size: 12px;
            }
            .stat-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .stat-label {
                color: #6b7280;
            }
            .stat-value {
                font-weight: 600;
            }
            .stat-value.success { color: #065f46; }
            .stat-value.cancelled { color: #991b1b; }
            .stat-value.return { color: #92400e; }
            .fraud-report-item {
                margin-bottom: 12px;
                padding: 12px;
                background: #fef2f2;
                border-radius: 8px;
                border-left: 4px solid #ef4444;
            }
            .report-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 5px;
            }
            .report-header small {
                color: #6b7280;
                font-size: 11px;
            }
            .report-details {
                font-size: 13px;
                line-height: 1.4;
                color: #374151;
            }
            .no-reports {
                display: flex;
                align-items: center;
                gap: 15px;
                color: #065f46;
                background: #d1fae5;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                margin: 0;
            }
            .no-reports-icon {
                font-size: 24px;
            }
            .no-reports-text {
                text-align: left;
            }
            .no-reports-text strong {
                display: block;
                margin-bottom: 4px;
            }
            .no-reports-text small {
                color: #047857;
            }
            .no-courier-data {
                color: #6b7280;
                text-align: center;
                font-style: italic;
                margin: 0;
            }
            .ai-suggestion-box {
                background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                border: 2px solid #3b82f6;
                border-radius: 12px;
                padding: 20px;
                color: #1e40af;
                line-height: 1.6;
            }
            .ai-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            .ai-header h4 {
                color: #1e40af;
                margin: 0;
                font-size: 18px;
                border-bottom: none;
                padding-bottom: 0;
            }
            .processing-time {
                font-size: 11px;
                color: #6b7280;
                background: rgba(255,255,255,0.5);
                padding: 4px 8px;
                border-radius: 12px;
            }
            .recommendation-text {
                margin-bottom: 15px;
            }
            .otp-required {
                background: rgba(255,255,255,0.8);
                padding: 12px;
                border-radius: 8px;
                border: 1px solid #3b82f6;
                font-weight: bold;
            }
            .show-more-reports {
                margin-top: 10px;
                font-size: 12px;
            }
            @media (max-width: 768px) {
                .fraud-sections-wrapper {
                    grid-template-columns: 1fr;
                }
                .summary-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
                .courier-stats {
                    grid-template-columns: 1fr;
                }
                .fraud-header {
                    flex-direction: column;
                    gap: 10px;
                    text-align: center;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate debug HTML for unknown API response format
     */
    private function generate_debug_response_html($response_data) {
        ob_start();
        ?>
        <div class="fraud-result-card" style="border: 2px solid #f59e0b; background: #fef3c7; padding: 20px; border-radius: 10px;">
            <h3 style="color: #92400e; margin-top: 0;">🔍 API Response Debug Information</h3>
            <p style="color: #92400e; margin-bottom: 15px;">
                The API returned data, but in an unexpected format. Here's what we received:
            </p>
            
            <div style="background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #d1d5db;">
                <h4 style="margin-top: 0; color: #374151;">Raw API Response:</h4>
                <pre style="background: #f3f4f6; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; color: #1f2937;">
<?php echo esc_html(wp_json_encode($response_data, JSON_PRETTY_PRINT)); ?>
                </pre>
            </div>
            
            <div style="margin-top: 15px; padding: 10px; background: #dbeafe; border-radius: 5px;">
                <strong style="color: #1e40af;">Next Steps:</strong>
                <ul style="color: #1e40af; margin: 5px 0 0 20px;">
                    <li>Check the WordPress error logs for detailed API request/response info</li>
                    <li>Verify the API endpoint is correct and responding as expected</li>
                    <li>Compare this response with the expected format in the documentation</li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
