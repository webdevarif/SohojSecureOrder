/**
 * Phone History JavaScript for WooCommerce Orders
 */

jQuery(document).ready(function($) {
    
    // Phone History button click handler
    $(document).on('click', '.sohoj-history-btn', function(e) {
        e.preventDefault();
        
        var phone = $(this).data('phone');
        
        // Create modal if it doesn't exist
        if ($('#sohoj-phone-history-modal').length === 0) {
            $('body').append(`
                <div id="sohoj-phone-history-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; max-width: 800px; width: 95%; max-height: 80vh; overflow-y: auto;">
                        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                            <h2 style="margin: 0;">Phone Order History</h2>
                            <button id="sohoj-close-phone-history" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
                        </div>
                        <div id="sohoj-phone-history-content" style="padding: 20px;">
                            Loading...
                        </div>
                    </div>
                </div>
            `);
        }
        
        $('#sohoj-phone-history-content').html('Loading...');
        $('#sohoj-phone-history-modal').show();
        
        // Fetch phone order history via AJAX
        $.ajax({
            url: sohoj_phone_history_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sohoj_get_phone_history',
                phone: phone,
                nonce: sohoj_phone_history_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sohoj-phone-history-content').html(response.data);
                } else {
                    $('#sohoj-phone-history-content').html('Error loading history: ' + response.data);
                }
            },
            error: function() {
                $('#sohoj-phone-history-content').html('Failed to load phone history');
            }
        });
    });
    
    // Close phone history modal
    $(document).on('click', '#sohoj-close-phone-history', function() {
        $('#sohoj-phone-history-modal').hide();
    });
    
    // Close modal on outside click
    $(document).on('click', '#sohoj-phone-history-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#sohoj-phone-history-modal').is(':visible')) {
            $('#sohoj-phone-history-modal').hide();
        }
    });
    
    // Fraud Check button click handler
    $(document).on('click', '.sohoj-fraud-btn', function(e) {
        e.preventDefault();
        
        var phone = $(this).data('phone');
        var $button = $(this);
        var originalText = $button.text();
        
        // Show loading state
        $button.prop('disabled', true).text('Loading...');
        
        // Create fraud modal if it doesn't exist
        if ($('#sohoj-fraud-check-modal').length === 0) {
            $('body').append(`
                <div id="sohoj-fraud-check-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 12px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                        <div style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <h2 style="margin: 0; color: #111827; font-size: 18px;" id="sohoj-fraud-modal-title">Fraud Check Report</h2>
                            <button id="sohoj-close-fraud-check" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">&times;</button>
                        </div>
                        <div id="sohoj-fraud-check-content" style="padding: 24px;">
                            Loading fraud check data...
                        </div>
                    </div>
                </div>
            `);
        }
        
        $('#sohoj-fraud-modal-title').text('Fraud Check Report for ' + phone);
        $('#sohoj-fraud-check-content').html('Loading fraud check data...');
        $('#sohoj-fraud-check-modal').fadeIn(300);
        
        // Prevent body scroll
        $('body').css('overflow', 'hidden');
        
        // Fetch fraud check details via AJAX
        $.ajax({
            url: sohoj_phone_history_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sohoj_get_fraud_check_details',
                phone: phone,
                nonce: sohoj_phone_history_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Handle different response formats
                    if (typeof response.data === 'object' && response.data.html) {
                        // New format with stats
                        $('#sohoj-fraud-check-content').html(response.data.html);
                        
                        // Update button with new stats
                        if (response.data.stats) {
                            updateFraudButton($button, phone, response.data.stats);
                        }
                    } else {
                        // Old format (just HTML)
                        $('#sohoj-fraud-check-content').html(response.data);
                    }
                } else {
                    $('#sohoj-fraud-check-content').html('<div style="text-align: center; padding: 30px; color: #ef4444;"><p><strong>Error:</strong> ' + response.data + '</p></div>');
                }
                // Restore button (with original text if no update)
                if (!response.data.stats) {
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                $('#sohoj-fraud-check-content').html('<div style="text-align: center; padding: 30px; color: #ef4444;"><p><strong>Connection Error:</strong> Unable to connect to fraud check service. Please try again.</p></div>');
                // Restore button
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Close fraud check modal
    $(document).on('click', '#sohoj-close-fraud-check', function() {
        $('#sohoj-fraud-check-modal').fadeOut(300);
        // Restore body scroll
        $('body').css('overflow', '');
    });
    
    // Close fraud modal on outside click
    $(document).on('click', '#sohoj-fraud-check-modal', function(e) {
        if (e.target === this) {
            $(this).fadeOut(300);
            // Restore body scroll
            $('body').css('overflow', '');
        }
    });
    
    // Close fraud modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#sohoj-fraud-check-modal').is(':visible')) {
            $('#sohoj-fraud-check-modal').fadeOut(300);
            // Restore body scroll
            $('body').css('overflow', '');
        }
    });
    
    // Handle clicks inside modal content (prevent closing)
    $(document).on('click', '#sohoj-fraud-check-content', function(e) {
        e.stopPropagation();
    });
    
    // Function to update fraud button and risk circle after API fetch
    function updateFraudButton($button, phone, stats) {
        // Determine new display text
        var displayText = '';
        if (stats.total_orders == 0) {
            displayText = 'New Customer';
        } else {
            displayText = stats.total_success + 'S / ' + stats.total_cancel + 'C';
        }
        
        // Update button text
        $button.prop('disabled', false).text(displayText);
        
        // Update risk circle color
        var $riskCircle = $button.siblings('.risk-circle');
        if ($riskCircle.length > 0) {
            // Remove existing risk classes
            $riskCircle.removeClass('risk-low risk-medium risk-high risk-unknown');
            // Add new risk class
            $riskCircle.addClass('risk-' + stats.risk_level);
        }
        
        console.log('Updated fraud button for phone:', phone, 'Stats:', stats, 'Display:', displayText);
    }
    
});