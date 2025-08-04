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
    
    // Toggle incomplete orders configuration options
    function toggleIncompleteOrdersOptions() {
        var isEnabled = $('#incomplete_orders_enabled').is(':checked');
        var $configuration = $('#incomplete-orders-configuration');
        
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
            console.log('Checkbox checked state:', $('#edit-show-on-checkout').is(':checked'));
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
            console.log('Checkbox checked state:', $('#edit-show-order-limit-on-checkout').is(':checked'));
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
            console.log('Hidden field value after save:', $('#show_phone_info').val());
        } else if (currentEditType === 'order_limit_info') {
            var showOnCheckout = $('#edit-show-order-limit-on-checkout').is(':checked') ? '1' : '0';
            $('#show_order_limit_info').val(showOnCheckout);
            console.log('Saving order limit info setting:', showOnCheckout);
            console.log('Hidden field value after save:', $('#show_order_limit_info').val());
        }
        
        updateCardPreview(currentEditType);
        closeEditModal();
    }
    
    // Initialize on page load
    setTimeout(function() {
        toggleNotificationOptions();
        toggleOrderLimitOptions();
        toggleIncompleteOrdersOptions();
        
        // Initialize hidden fields if they don't have values
        if ($('#show_phone_info').length && $('#show_phone_info').val() === '') {
            $('#show_phone_info').val('1'); // Default to enabled
            console.log('Initialized show_phone_info to 1 (default)');
        }
        
        if ($('#show_order_limit_info').length && $('#show_order_limit_info').val() === '') {
            $('#show_order_limit_info').val('0'); // Default to disabled
            console.log('Initialized show_order_limit_info to 0 (default)');
        }
        
        console.log('Page initialized. Hidden field values:');
        console.log('show_phone_info:', $('#show_phone_info').val());
        console.log('show_order_limit_info:', $('#show_order_limit_info').val());
    }, 100);
    
    // Event handlers
    $(document).on('change', '#phone_validation', function() {
        toggleNotificationOptions();
    });
    
    $(document).on('change', '#order_limit', function() {
        toggleOrderLimitOptions();
    });
    
    $(document).on('change', '#incomplete_orders_enabled', function() {
        toggleIncompleteOrdersOptions();
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
    
    // Real-time checkbox change handlers
    $(document).on('change', '#edit-show-on-checkout', function() {
        var isChecked = $(this).is(':checked');
        $('#show_phone_info').val(isChecked ? '1' : '0');
        console.log('Phone info checkbox changed. New value:', isChecked ? '1' : '0');
    });
    
    $(document).on('change', '#edit-show-order-limit-on-checkout', function() {
        var isChecked = $(this).is(':checked');
        $('#show_order_limit_info').val(isChecked ? '1' : '0');
        console.log('Order limit info checkbox changed. New value:', isChecked ? '1' : '0');
    });
    
    // Ensure hidden fields are updated when modal is closed
    $(document).on('click', '.sohoj-btn-save', function() {
        // Force update hidden fields before saving
        if (currentEditType === 'info') {
            var showOnCheckout = $('#edit-show-on-checkout').is(':checked') ? '1' : '0';
            $('#show_phone_info').val(showOnCheckout);
            console.log('Forced update - show_phone_info:', showOnCheckout);
        } else if (currentEditType === 'order_limit_info') {
            var showOnCheckout = $('#edit-show-order-limit-on-checkout').is(':checked') ? '1' : '0';
            $('#show_order_limit_info').val(showOnCheckout);
            console.log('Forced update - show_order_limit_info:', showOnCheckout);
        }
    });
    
    // Form submission handler to ensure hidden fields are included
    $(document).on('submit', 'form[method="post"]', function(e) {
        console.log('Form submission detected');
        console.log('show_phone_info value before submit:', $('#show_phone_info').val());
        console.log('show_order_limit_info value before submit:', $('#show_order_limit_info').val());
        
        // Ensure hidden fields are properly set
        if ($('#show_phone_info').length && $('#show_phone_info').val() === '') {
            $('#show_phone_info').val('0');
            console.log('Set show_phone_info to 0 (default)');
        }
        
        if ($('#show_order_limit_info').length && $('#show_order_limit_info').val() === '') {
            $('#show_order_limit_info').val('0');
            console.log('Set show_order_limit_info to 0 (default)');
        }
    });
    
    // Add any basic admin interactions here
    $('.sohoj-card').on('click', function() {
        console.log('Card clicked:', $(this).find('h2').text());
    });
    
    // Incomplete Orders functionality
    var currentIncompleteOrderId = null;
    
    // Call button
    $(document).on('click', '.sohoj-call-btn', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        
        if (confirm('Mark this order as called?')) {
            $btn.prop('disabled', true).text('Calling...');
            
            $.ajax({
                url: sohoj_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sohoj_mark_called',
                    id: id,
                    nonce: sohoj_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false).text('📞 Call');
                    }
                },
                error: function() {
                    alert('Request failed');
                    $btn.prop('disabled', false).text('📞 Call');
                }
            });
        }
    });
    
    // Convert button
    $(document).on('click', '.sohoj-convert-btn', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        
        if (confirm('Convert this incomplete order to a WooCommerce order?')) {
            $btn.prop('disabled', true).text('Converting...');
            
            $.ajax({
                url: sohoj_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sohoj_convert_incomplete_order',
                    id: id,
                    nonce: sohoj_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Order converted successfully! Order ID: #' + response.data.order_id);
                        if (response.data.order_url && confirm('Do you want to view the created order now?')) {
                            window.open(response.data.order_url, '_blank');
                        }
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false).text('✅ Convert');
                    }
                },
                error: function() {
                    alert('Request failed');
                    $btn.prop('disabled', false).text('✅ Convert');
                }
            });
        }
    });
    
    // Reject button
    $(document).on('click', '.sohoj-reject-btn', function() {
        currentIncompleteOrderId = $(this).data('id');
        $('#sohoj-reject-modal').show();
        $('#rejection-reason').focus();
    });
    
    // Confirm reject
    $(document).on('click', '#sohoj-confirm-reject', function() {
        if (!currentIncompleteOrderId) return;
        
        var reason = $('#rejection-reason').val();
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Rejecting...');
        
        $.ajax({
            url: sohoj_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sohoj_reject_incomplete_order',
                id: currentIncompleteOrderId,
                reason: reason,
                nonce: sohoj_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sohoj-reject-modal').hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text('Reject Order');
                }
            },
            error: function() {
                alert('Request failed');
                $btn.prop('disabled', false).text('Reject Order');
            }
        });
    });
    
    // View details button
    $(document).on('click', '.sohoj-view-details-btn', function() {
        var id = $(this).data('id');
        
        $('#sohoj-details-content').html('Loading...');
        $('#sohoj-details-modal').show();
        
        // Fetch order details via AJAX (we'll add this endpoint)
        $.ajax({
            url: sohoj_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sohoj_get_incomplete_order_details',
                id: id,
                nonce: sohoj_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#sohoj-details-content').html(response.data);
                } else {
                    $('#sohoj-details-content').html('Error loading details: ' + response.data);
                }
            },
            error: function() {
                $('#sohoj-details-content').html('Failed to load order details');
            }
        });
    });
    
    // Close modals
    $(document).on('click', '#sohoj-close-details, #sohoj-close-reject', function() {
        $(this).closest('[id$="-modal"]').hide();
        currentIncompleteOrderId = null;
        $('#rejection-reason').val('');
    });
    
    // Close modals on outside click
    $(document).on('click', '[id$="-modal"]', function(e) {
        if (e.target === this) {
            $(this).hide();
            currentIncompleteOrderId = null;
            $('#rejection-reason').val('');
        }
    });
    
    // Phone History button
    $(document).on('click', '.sohoj-history-btn', function() {
        var phone = $(this).data('phone');
        
        $('#sohoj-phone-history-content').html('Loading...');
        $('#sohoj-phone-history-modal').show();
        
        // Fetch phone order history via AJAX
        $.ajax({
            url: sohoj_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sohoj_get_phone_history',
                phone: phone,
                nonce: sohoj_ajax.nonce
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
    
    // Filter period functionality
    $('#filter-period').on('change', function() {
        var period = $(this).val();
        if (period === 'custom') {
            $('#filter-custom-dates').show();
        } else {
            $('#filter-custom-dates').hide();
        }
    });
    
    // Initialize Chart if canvas exists
    if ($('#incomplete-orders-chart').length > 0) {
        initializeChart();
    }
    
    function initializeChart() {
        var canvas = document.getElementById('incomplete-orders-chart');
        var ctx = canvas.getContext('2d');
        
        // Get data from canvas data attributes
        var incomplete = parseInt(canvas.getAttribute('data-incomplete')) || 0;
        var converted = parseInt(canvas.getAttribute('data-converted')) || 0;
        var called = parseInt(canvas.getAttribute('data-called')) || 0;
        var rejected = parseInt(canvas.getAttribute('data-rejected')) || 0;
        
        // Create simple chart without colors
        createSimpleChart(ctx, {
            incomplete: incomplete,
            converted: converted,
            called: called,
            rejected: rejected
        });
    }
    
    function createSimpleChart(ctx, data) {
        var canvas = ctx.canvas;
        var width = canvas.width;
        var height = canvas.height;
        
        var values = [data.incomplete, data.converted, data.called, data.rejected];
        var labels = ['Incomplete', 'Converted', 'Called', 'Rejected'];
        var colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']; // Blue, Green, Orange, Red
        
        var maxValue = Math.max(...values);
        if (maxValue === 0) {
            ctx.fillStyle = '#9ca3af';
            ctx.font = '14px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('No data available', width / 2, height / 2);
            return;
        }
        
        // Chart area
        var padding = 40;
        var chartWidth = width - (padding * 2);
        var chartHeight = height - (padding * 2) - 60; // Space for legend
        var chartX = padding;
        var chartY = padding;
        
        // Draw background grid
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        
        // Horizontal grid lines
        for (var i = 0; i <= 5; i++) {
            var y = chartY + (chartHeight / 5) * i;
            ctx.beginPath();
            ctx.moveTo(chartX, y);
            ctx.lineTo(chartX + chartWidth, y);
            ctx.stroke();
            
            // Y-axis labels
            var value = Math.round(maxValue - (maxValue / 5) * i);
            ctx.fillStyle = '#6b7280';
            ctx.font = '11px Arial';
            ctx.textAlign = 'right';
            ctx.fillText(value.toString(), chartX - 5, y + 4);
        }
        
        // Vertical grid lines and x-axis labels
        var barWidth = chartWidth / values.length;
        for (var i = 0; i < values.length; i++) {
            var x = chartX + (barWidth * i) + (barWidth / 2);
            
            // Vertical grid line
            ctx.strokeStyle = '#e5e7eb';
            ctx.beginPath();
            ctx.moveTo(x, chartY);
            ctx.lineTo(x, chartY + chartHeight);
            ctx.stroke();
            
            // X-axis label
            ctx.fillStyle = '#6b7280';
            ctx.font = '11px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(labels[i], x, chartY + chartHeight + 15);
        }
        
        // Draw line chart
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        
        // Create gradient for line
        var gradient = ctx.createLinearGradient(0, chartY, 0, chartY + chartHeight);
        gradient.addColorStop(0, '#3b82f6');
        gradient.addColorStop(0.33, '#10b981');
        gradient.addColorStop(0.66, '#f59e0b');
        gradient.addColorStop(1, '#ef4444');
        
        ctx.strokeStyle = gradient;
        ctx.beginPath();
        
        // Draw the line
        for (var i = 0; i < values.length; i++) {
            var x = chartX + (barWidth * i) + (barWidth / 2);
            var barHeight = (values[i] / maxValue) * chartHeight;
            var y = chartY + chartHeight - barHeight;
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.stroke();
        
        // Draw data points with different colors
        for (var i = 0; i < values.length; i++) {
            var x = chartX + (barWidth * i) + (barWidth / 2);
            var barHeight = (values[i] / maxValue) * chartHeight;
            var y = chartY + chartHeight - barHeight;
            
            // Draw point
            ctx.fillStyle = colors[i];
            ctx.beginPath();
            ctx.arc(x, y, 5, 0, 2 * Math.PI);
            ctx.fill();
            
            // Draw value label above point
            ctx.fillStyle = '#374151';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(values[i].toString(), x, y - 10);
        }
        
        // Draw legend
        var legendY = chartY + chartHeight + 35;
        var legendSpacing = chartWidth / values.length;
        
        for (var i = 0; i < values.length; i++) {
            var x = chartX + (legendSpacing * i) + (legendSpacing / 2);
            
            // Color indicator
            ctx.fillStyle = colors[i];
            ctx.fillRect(x - 30, legendY, 12, 12);
            
            // Label
            ctx.fillStyle = '#374151';
            ctx.font = '11px Arial';
            ctx.textAlign = 'left';
            ctx.fillText(labels[i], x - 15, legendY + 9);
        }
    }
    
}); 