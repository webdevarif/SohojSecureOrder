/**
 * Sohoj Secure Order - Admin JavaScript
 */

jQuery(document).ready(function($) {
    // Basic admin functionality
    console.log('Sohoj Secure Order Admin JS loaded');
    
    // Cool card-based notification system
    var currentEditType = '';
    
    // Toggle notification customization options
    function toggleNotificationOptions() {
        var isEnabled = $('#phone_validation').is(':checked');
        var $customization = $('#notification-customization');
        
        if (isEnabled && $customization.length) {
            $customization.slideDown(300);
        } else if ($customization.length) {
            $customization.slideUp(300);
        }
    }
    
    // Toggle order limit configuration options
    function toggleOrderLimitOptions() {
        var isEnabled = $('#order_limit').is(':checked');
        var $configuration = $('#order-limit-configuration');
        
        if (isEnabled && $configuration.length) {
            $configuration.slideDown(300);
        } else if ($configuration.length) {
            $configuration.slideUp(300);
        }
    }
    
    // Open edit modal
    function openEditModal(type) {
        currentEditType = type;
        var modal = $('#edit-modal');
        
        if (type === 'error') {
            $('#modal-title').text('🚨 Edit Phone Validation Error');
            $('#edit-icon').val($('#error_icon').val());
            $('#edit-heading').val($('#error_heading').val());
            $('#edit-message').val($('#error_message').val());
            $('#info-message-options').hide();
            $('#order-limit-info-options').hide();
        } else if (type === 'info') {
            $('#modal-title').text('ℹ️ Edit Phone Validation Info');
            $('#edit-icon').val($('#info_icon').val());
            $('#edit-heading').val($('#info_heading').val());
            $('#edit-message').val($('#info_message').val());
            
            // Show the checkout display toggle for info messages
            $('#info-message-options').show();
            $('#order-limit-info-options').hide();
            var currentPhoneInfoValue = $('#show_phone_info').val();
            $('#edit-show-on-checkout').prop('checked', currentPhoneInfoValue == '1');
            console.log('Phone info modal opened. Current value:', currentPhoneInfoValue);
        } else if (type === 'order_limit_error') {
            $('#modal-title').text('🛑 Edit Order Limit Error');
            $('#edit-icon').val($('#order_limit_error_icon').val());
            $('#edit-heading').val($('#order_limit_error_heading').val());
            $('#edit-message').val($('#order_limit_error_message').val());
            $('#info-message-options').hide();
            $('#order-limit-info-options').hide();
        } else if (type === 'order_limit_info') {
            $('#modal-title').text('⏰ Edit Order Limit Info');
            $('#edit-icon').val($('#order_limit_info_icon').val());
            $('#edit-heading').val($('#order_limit_info_heading').val());
            $('#edit-message').val($('#order_limit_info_message').val());
            $('#info-message-options').hide();
            
            // Show the checkout display toggle for order limit info messages
            $('#order-limit-info-options').show();
            var currentOrderLimitInfoValue = $('#show_order_limit_info').val();
            $('#edit-show-order-limit-on-checkout').prop('checked', currentOrderLimitInfoValue == '1');
            console.log('Order limit info modal opened. Current value:', currentOrderLimitInfoValue);
        }
        
        modal.fadeIn(200);
    }
    
    // Close edit modal
    function closeEditModal() {
        $('#edit-modal').fadeOut(200);
        currentEditType = '';
    }
    
    // Update card preview
    function updateCardPreview(type) {
        var icon = $('#' + type + '_icon').val();
        var heading = $('#' + type + '_heading').val();
        var message = $('#' + type + '_message').val();
        
        var card = $('[data-type="' + type + '"]');
        card.find('.card-icon').html(icon);
        card.find('.card-preview strong').text(heading);
        card.find('.card-preview p').text(message.length > 50 ? message.substring(0, 50) + '...' : message);
    }
    
    // Save modal changes
    function saveModalChanges() {
        var icon = $('#edit-icon').val();
        var heading = $('#edit-heading').val();
        var message = $('#edit-message').val();
        
        $('#' + currentEditType + '_icon').val(icon);
        $('#' + currentEditType + '_heading').val(heading);
        $('#' + currentEditType + '_message').val(message);
        
        // Save show on checkout setting for info messages
        if (currentEditType === 'info') {
            var showOnCheckout = $('#edit-show-on-checkout').is(':checked') ? '1' : '0';
            $('#show_phone_info').val(showOnCheckout);
            console.log('Saving phone info setting:', showOnCheckout);
        } else if (currentEditType === 'order_limit_info') {
            var showOnCheckout = $('#edit-show-order-limit-on-checkout').is(':checked') ? '1' : '0';
            $('#show_order_limit_info').val(showOnCheckout);
            console.log('Saving order limit info setting:', showOnCheckout);
        }
        
        updateCardPreview(currentEditType);
        closeEditModal();
    }
    
    // Initialize on page load
    setTimeout(function() {
        toggleNotificationOptions();
        toggleOrderLimitOptions();
    }, 100);
    
    // Event handlers
    $(document).on('change', '#phone_validation', function() {
        toggleNotificationOptions();
    });
    
    $(document).on('change', '#order_limit', function() {
        toggleOrderLimitOptions();
    });
    
    // Card click to edit
    $(document).on('click', '.sohoj-notification-card', function() {
        var type = $(this).data('type');
        openEditModal(type);
    });
    
    // Modal controls
    $(document).on('click', '.close-btn, .sohoj-btn-cancel', function() {
        closeEditModal();
    });
    
    $(document).on('click', '.sohoj-btn-save', function() {
        saveModalChanges();
    });
    
    // Close modal on outside click
    $(document).on('click', '.sohoj-edit-modal', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    
    // Escape key to close
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#edit-modal').is(':visible')) {
            closeEditModal();
        }
    });
    
    // Add any basic admin interactions here
    $('.sohoj-card').on('click', function() {
        console.log('Card clicked:', $(this).find('h2').text());
    });
    
    // Update functionality
    $('.update-link').on('click', function(e) {
        if (!confirm('Are you sure you want to update the plugin? This will download and install the latest version.')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        $(this).text('Updating...').prop('disabled', true);
    });
    
    // Auto-refresh update status
    if ($('.update-available').length > 0) {
        // Check for updates every 5 minutes
        setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sohoj_check_updates_ajax',
                    nonce: sohoj_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.update_available) {
                        location.reload();
                    }
                }
            });
        }, 300000); // 5 minutes
    }
}); 