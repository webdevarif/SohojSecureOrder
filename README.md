# Sohoj Secure Order - WordPress Plugin

A comprehensive WordPress plugin for secure customer information validation with advanced fraud detection, VPN blocking, phone validation, and repeat validation prevention. Built with OOP architecture, license key validation, and automatic update checking from GitHub.

## Features

### Core Features
- **OOP Architecture**: Well-organized object-oriented PHP code structure
- **License Management**: Secure license key validation and activation
- **Auto Updates**: Automatic update checking from GitHub repository
- **Security Features**: Advanced fraud detection and prevention
- **Database Integration**: Custom database tables for leads and settings
- **Security**: Nonce verification, data sanitization, and proper validation

### Security Features
- **Phone Validation**: Require 11-digit phone numbers for validation
- **VPN Blocking**: Block validation requests from VPN connections
- **Fraud Detection**: Auto-block suspicious browsers/IPs
- **Incomplete Lead Tracking**: Save leads from incomplete validations
- **Repeat Validation Blocking**: Block repeat validations by time/limit per phone
- **Phone History**: View previous validation history by phone number
- **IP Blocking**: Block specific IP addresses and phone numbers

### Admin Features
- **Dashboard**: Overview of security features and quick actions
- **Security Settings**: Configure all security features
- **Phone History**: Search and view validation history by phone number
- **Settings Panel**: Configure notifications and general settings
- **License Management**: Activate and manage plugin license
- **Update Notifications**: Automatic update notifications
- **AJAX Integration**: Smooth admin interface with AJAX functionality

### Public Features
- **Security Validation Forms**: Frontend customer information validation
- **Phone Validator**: Standalone phone number validation
- **Responsive Design**: Mobile-friendly forms and interfaces
- **Email Notifications**: Automatic email notifications for security events
- **Security Validation**: Real-time security checks on validation submission

## Installation

1. Upload the plugin files to `/wp-content/plugins/sohoj-secure-order/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Sohoj Secure Order' > 'License' to enter your license key
4. Configure security settings in 'Sohoj Secure Order' > 'Security Settings'

## Usage

### Shortcodes

#### Security Validation Form
```
[sohoj_security_form show_phone="yes" show_email="yes" show_name="yes"]
```

#### Phone Validator
```
[sohoj_phone_validator]
```

### Admin Interface

1. **Dashboard**: Overview of security features and quick actions
2. **Security Settings**: Configure all security features
3. **Phone History**: Search and view validation history by phone number
4. **Settings**: Configure general settings and notifications
5. **License**: Manage plugin license

## Configuration

### License Setup
1. Navigate to 'Sohoj Secure Order' > 'License'
2. Enter your license key
3. Click 'Activate License'

### Security Configuration
1. Go to 'Sohoj Secure Order' > 'Security Settings'
2. Configure:
   - Phone validation (11-digit requirement)
   - VPN blocking
   - Fraud detection
   - Incomplete lead tracking
   - Repeat validation blocking (time/limit)
   - IP and phone number blocking

### Update Configuration
The plugin automatically checks for updates from the configured GitHub repository. Update the repository URL in the `Update_Checker` class if needed.

## File Structure

```
sohoj-secure-order/
├── sohoj-secure-order.php          # Main plugin file
├── includes/
│   ├── Core/
│   │   ├── Plugin.php              # Main plugin class
│   │   ├── License_Manager.php     # License management
│   │   ├── Update_Checker.php      # Update checking
│   │   ├── Activator.php           # Plugin activation
│   │   ├── Deactivator.php         # Plugin deactivation
│   │   └── Uninstaller.php         # Plugin uninstallation
│   ├── Admin/
│   │   ├── Admin.php               # Admin functionality
│   │   └── Settings.php            # Settings management
│   └── Public/
│       └── Public_Frontend.php     # Public functionality
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin styles
│   │   └── public.css              # Public styles
│   └── js/
│       ├── admin.js                # Admin JavaScript
│       └── public.js               # Public JavaScript
└── README.md                       # This file
```

## Database Tables

The plugin creates the following database tables:

### `wp_sohoj_incomplete_leads`
- `id`: Primary key
- `customer_name`: Customer name
- `customer_email`: Customer email
- `customer_phone`: Customer phone
- `ip_address`: Client IP address
- `user_agent`: Browser user agent
- `created_at`: Creation timestamp

### `wp_sohoj_settings`
- `id`: Primary key
- `setting_key`: Setting key
- `setting_value`: Setting value
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

## API Endpoints

### AJAX Endpoints

#### Public Endpoints
- `sohoj_validate_customer`: Validate customer information
- `sohoj_check_security_status`: Check security status for phone number

#### Admin Endpoints
- `sohoj_save_settings`: Save plugin settings
- `sohoj_activate_license`: Activate license key
- `sohoj_deactivate_license`: Deactivate license key

### Security Features

#### Phone Validation
- Requires exactly 11 digits for phone numbers
- Validates format: `[0-9]{11}`
- Example: `01712345678`

#### VPN Blocking
- Detects common VPN IP ranges
- Blocks suspicious user agents
- Prevents validation from VPN connections

#### Fraud Detection
- Rate limiting (max 10 requests per hour per IP)
- Bot/crawler detection
- Suspicious user agent blocking
- IP range checking

#### IP Blocking
- Block specific IP addresses
- Block IP ranges (CIDR notation)
- Block phone numbers
- Configurable blocking lists

#### Repeat Validation Blocking
- Configurable blocking time (1-720 hours)
- Blocks repeat validations from same phone
- Time-based blocking after last validation

#### Incomplete Lead Tracking
- Saves customer information even if validation incomplete
- Stores IP address and user agent
- Tracks validation attempts for analysis

## Development

### Adding New Security Features

1. Add the feature to the `perform_security_checks()` method in `Public_Frontend.php`
2. Add corresponding settings in `Settings.php`
3. Update the admin interface to configure the feature
4. Add any necessary database fields

### Customizing Security Rules

The plugin provides a flexible framework for adding custom security rules:

```php
// Example: Add custom security check
private function custom_security_check($form_data) {
    // Your custom logic here
    return ['success' => true];
}
```

## Support

For support and updates, please visit the GitHub repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Security validation functionality
- Phone validation (11-digit requirement)
- VPN blocking
- Fraud detection
- IP and phone blocking
- Repeat validation blocking
- Incomplete lead tracking
- License management
- Automatic updates from GitHub 