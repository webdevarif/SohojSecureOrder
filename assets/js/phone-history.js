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
    
});