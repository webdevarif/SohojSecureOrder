/**
 * Sohoj Secure Order - Admin JavaScript
 */

jQuery(document).ready(function($) {
    // Basic admin functionality
    console.log('Sohoj Secure Order Admin JS loaded');
    
    // Add any basic admin interactions here
    $('.sohoj-card').on('click', function() {
        console.log('Card clicked:', $(this).find('h2').text());
    });
}); 