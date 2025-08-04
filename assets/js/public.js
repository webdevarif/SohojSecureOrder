/**
 * Sohoj Secure Order - Public JavaScript
 */

jQuery(document).ready(function($) {
    // Basic public functionality
    console.log('Sohoj Secure Order Public JS loaded');
    
    // Handle example form submission
    $('#sohoj-example-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'sohoj_example_action',
            nonce: sohoj_ajax.nonce,
            example_field: $('#example_field').val()
        };
        
        $.ajax({
            url: sohoj_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#sohoj-result').html('<div class="sohoj-success">' + response.data + '</div>');
                } else {
                    $('#sohoj-result').html('<div class="sohoj-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $('#sohoj-result').html('<div class="sohoj-error">An error occurred. Please try again.</div>');
            }
        });
    });
}); 