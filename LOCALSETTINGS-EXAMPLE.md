# LocalSettings.php Quick Start Example

## Example 1: Simple Production Configuration

```php
<?php
/**
 * LocalSettings.php - Production Deployment
 */

// Application Settings
$GLOBALS['wgCommonsUploader']['app_name'] = 'Wikimedia Conference Uploader';
$GLOBALS['wgCommonsUploader']['app_url'] = 'https://uploader.wikimedia.org';
$GLOBALS['wgCommonsUploader']['debug'] = false;

// Database (Production)
$GLOBALS['wgCommonsUploader']['db_host'] = 'prod-db.internal';
$GLOBALS['wgCommonsUploader']['db_database'] = 'commons_uploader_prod';
$GLOBALS['wgCommonsUploader']['db_username'] = 'uploader_user';
$GLOBALS['wgCommonsUploader']['db_password'] = getenv('DB_PASSWORD'); // From environment

// OAuth Credentials (Production)
$GLOBALS['wgCommonsUploader']['oauth_client_id'] = getenv('OAUTH_CLIENT_ID');
$GLOBALS['wgCommonsUploader']['oauth_client_secret'] = getenv('OAUTH_CLIENT_SECRET');
$GLOBALS['wgCommonsUploader']['oauth_redirect_uri'] = 'https://uploader.wikimedia.org/auth/callback';
```

## Example 2: Development Configuration

```php
<?php
/**
 * LocalSettings.php - Development Environment
 */

// Application Settings
$GLOBALS['wgCommonsUploader']['app_name'] = 'CommonsUploader (DEV)';
$GLOBALS['wgCommonsUploader']['app_url'] = 'http://localhost:8000';
$GLOBALS['wgCommonsUploader']['debug'] = true; // Enable debug mode

// Database (Local)
$GLOBALS['wgCommonsUploader']['db_host'] = 'localhost';
$GLOBALS['wgCommonsUploader']['db_database'] = 'commons_uploader_dev';
$GLOBALS['wgCommonsUploader']['db_username'] = 'root';
$GLOBALS['wgCommonsUploader']['db_password'] = '';

// Higher upload limit for testing
$GLOBALS['wgCommonsUploader']['max_upload_size'] = 500 * 1024 * 1024; // 500MB

// Allow more file types for testing
$GLOBALS['wgCommonsUploader']['allowed_file_types'] = [
    'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'tiff', 'pdf', 'djvu', 'ogg', 'ogv', 'webm'
];
```

## Example 3: Test Wiki Configuration

```php
<?php
/**
 * LocalSettings.php - Testing with Test Commons
 */

// Use test wiki
$GLOBALS['wgCommonsUploader']['commons_api_url'] = 'https://test-commons.wikimedia.org/w/api.php';

// Test OAuth endpoints
$GLOBALS['wgCommonsUploader']['oauth_authorization_endpoint'] = 'https://test.wikimedia.org/w/rest.php/oauth2/authorize';
$GLOBALS['wgCommonsUploader']['oauth_token_endpoint'] = 'https://test.wikimedia.org/w/rest.php/oauth2/access_token';
$GLOBALS['wgCommonsUploader']['oauth_userinfo_endpoint'] = 'https://test.wikimedia.org/w/rest.php/oauth2/resource/profile';
```

## Example 4: Custom Features and Hooks

```php
<?php
/**
 * LocalSettings.php - With Custom Features
 */

// Enable/disable features
$GLOBALS['wgCommonsUploader']['features'] = [
    'enable_category_autocomplete' => true,
    'enable_statistics_dashboard' => true,
    'enable_event_analytics' => true,
    'enable_bulk_upload' => true,
    'enable_api_documentation' => true,
];

// Custom hook: Log all uploads
$GLOBALS['wgCommonsUploader']['hooks']['afterUpload'][] = function($upload, $result) {
    if ($result['success']) {
        error_log(sprintf(
            "Upload successful: %s by user %d to event %d",
            $upload->commons_filename,
            $upload->user_id,
            $upload->event_id
        ));
    }
};

// Custom hook: Validate uploads before processing
$GLOBALS['wgCommonsUploader']['hooks']['beforeUpload'][] = function($upload) {
    // Custom validation logic
    if (strlen($upload->title) < 10) {
        throw new Exception("Title too short");
    }
};
```

## Example 5: Multi-Site Configuration

```php
<?php
/**
 * LocalSettings.php - Multi-site Setup
 */

// Detect environment from server name
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

if ($serverName === 'prod.uploader.example.com') {
    // Production settings
    $GLOBALS['wgCommonsUploader']['app_name'] = 'Production Uploader';
    $GLOBALS['wgCommonsUploader']['db_host'] = 'prod-db.internal';
    $GLOBALS['wgCommonsUploader']['debug'] = false;
    
} elseif ($serverName === 'staging.uploader.example.com') {
    // Staging settings
    $GLOBALS['wgCommonsUploader']['app_name'] = 'Staging Uploader';
    $GLOBALS['wgCommonsUploader']['db_host'] = 'staging-db.internal';
    $GLOBALS['wgCommonsUploader']['debug'] = true;
    
} else {
    // Development settings
    $GLOBALS['wgCommonsUploader']['app_name'] = 'Dev Uploader';
    $GLOBALS['wgCommonsUploader']['db_host'] = 'localhost';
    $GLOBALS['wgCommonsUploader']['debug'] = true;
}
```

## Comparison with .env

**Using .env:**
```bash
APP_NAME="My Uploader"
DB_HOST=localhost
DB_DATABASE=commons_uploader
OAUTH_CLIENT_ID=abc123
```

**Using LocalSettings.php:**
```php
<?php
$GLOBALS['wgCommonsUploader']['app_name'] = 'My Uploader';
$GLOBALS['wgCommonsUploader']['db_host'] = 'localhost';
$GLOBALS['wgCommonsUploader']['db_database'] = 'commons_uploader';
$GLOBALS['wgCommonsUploader']['oauth_client_id'] = 'abc123';
```

**Both can be used together!** LocalSettings.php will override .env values.

## Quick Setup

1. **Copy the example:**
   ```bash
   cp LocalSettings.php.example LocalSettings.php
   ```

2. **Edit LocalSettings.php** with your settings

3. **Done!** The application will automatically load LocalSettings.php

## Helper Functions

Use these anywhere in your code:

```php
// Get a setting
$maxSize = getLocalSetting('max_upload_size', 100 * 1024 * 1024);

// Check if LocalSettings exists
if (hasLocalSettings()) {
    echo "Using LocalSettings.php";
}

// Get all settings
$all = getAllLocalSettings();

// Run custom hook
runLocalSettingsHook('myCustomHook', $data);
```

## For MediaWiki Administrators

If you're familiar with MediaWiki, here's the mapping:

| What you know (MediaWiki) | What to use (CommonsUploader) |
|---------------------------|------------------------------|
| `$wgSitename` | `$GLOBALS['wgCommonsUploader']['app_name']` |
| `$wgDBserver` | `$GLOBALS['wgCommonsUploader']['db_host']` |
| `$wgDBname` | `$GLOBALS['wgCommonsUploader']['db_database']` |
| `$wgUploadDirectory` | (Managed by Laravel) |
| `$wgMaxUploadSize` | `$GLOBALS['wgCommonsUploader']['max_upload_size']` |
| `$wgFileExtensions` | `$GLOBALS['wgCommonsUploader']['allowed_file_types']` |

## Documentation

For complete documentation, see:
- [LocalSettings.md](LocalSettings.md) - Full configuration guide
- [LocalSettings.php.example](LocalSettings.php.example) - All available settings
- [README.md](README.md) - Installation guide
