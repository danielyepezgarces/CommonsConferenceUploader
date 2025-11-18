# LocalSettings.php Configuration Guide

CommonsEventUploader supports MediaWiki-style configuration through `LocalSettings.php`. This provides a familiar configuration pattern for MediaWiki users and allows for easy deployment-specific customizations without modifying `.env` or config files.

## Overview

`LocalSettings.php` is an optional configuration file that:
- Loads after `.env` but before the application bootstraps
- Can override any configuration value
- Uses the global `$wgCommonsUploader` array (similar to MediaWiki's `$wg` prefix)
- Is git-ignored for security
- Provides a simple PHP-based configuration syntax

## Getting Started

1. **Copy the example file:**
   ```bash
   cp LocalSettings.php.example LocalSettings.php
   ```

2. **Edit LocalSettings.php** with your custom settings

3. **LocalSettings.php is automatically loaded** on every request

## Basic Usage

### Application Settings

```php
<?php
// Override application name
$GLOBALS['wgCommonsUploader']['app_name'] = 'My Custom Uploader';

// Override application URL
$GLOBALS['wgCommonsUploader']['app_url'] = 'https://myuploader.example.com';

// Enable debug mode
$GLOBALS['wgCommonsUploader']['debug'] = true;

// Set timezone
$GLOBALS['wgCommonsUploader']['timezone'] = 'America/New_York';
```

### File Upload Settings

```php
<?php
// Set maximum upload size to 200MB
$GLOBALS['wgCommonsUploader']['max_upload_size'] = 200 * 1024 * 1024;

// Allow additional file types
$GLOBALS['wgCommonsUploader']['allowed_file_types'] = [
    'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'tiff', 'pdf', 'djvu', 'ogg'
];
```

### Database Settings

```php
<?php
$GLOBALS['wgCommonsUploader']['db_host'] = 'db.example.com';
$GLOBALS['wgCommonsUploader']['db_port'] = 3306;
$GLOBALS['wgCommonsUploader']['db_database'] = 'my_uploader_db';
$GLOBALS['wgCommonsUploader']['db_username'] = 'dbuser';
$GLOBALS['wgCommonsUploader']['db_password'] = 'secure_password';
```

### OAuth Settings

```php
<?php
$GLOBALS['wgCommonsUploader']['oauth_client_id'] = 'your_client_id';
$GLOBALS['wgCommonsUploader']['oauth_client_secret'] = 'your_client_secret';
$GLOBALS['wgCommonsUploader']['oauth_redirect_uri'] = 'https://myuploader.example.com/auth/callback';

// Override OAuth scopes
$GLOBALS['wgCommonsUploader']['oauth_scopes'] = 'basic profile highvolume';
```

### Wikimedia Commons API

```php
<?php
// Use a different Commons API endpoint (e.g., test wiki)
$GLOBALS['wgCommonsUploader']['commons_api_url'] = 'https://test-commons.wikimedia.org/w/api.php';
```

## Advanced Usage

### Custom Hooks

You can define hooks that run at specific points in the application:

```php
<?php
// Hook before file upload
$GLOBALS['wgCommonsUploader']['hooks']['beforeUpload'][] = function($upload) {
    // Custom validation or logging
    error_log("Uploading file: " . $upload->title);
};

// Hook after file upload
$GLOBALS['wgCommonsUploader']['hooks']['afterUpload'][] = function($upload, $result) {
    // Custom post-processing
    if ($result['success']) {
        error_log("Successfully uploaded: " . $upload->commons_filename);
    }
};
```

### Feature Flags

Control which features are enabled:

```php
<?php
$GLOBALS['wgCommonsUploader']['features']['enable_category_autocomplete'] = true;
$GLOBALS['wgCommonsUploader']['features']['enable_statistics_dashboard'] = true;
$GLOBALS['wgCommonsUploader']['features']['enable_event_analytics'] = true;
$GLOBALS['wgCommonsUploader']['features']['enable_bulk_upload'] = false;
```

### Custom Settings

Add your own custom settings:

```php
<?php
// Define custom settings
$GLOBALS['wgCommonsUploader']['my_custom_setting'] = 'custom_value';
$GLOBALS['wgCommonsUploader']['site_notice'] = 'This is a test deployment';
$GLOBALS['wgCommonsUploader']['maintenance_mode'] = false;

// Access in your code
$customValue = getLocalSetting('my_custom_setting');
```

### Direct Environment Variable Override

For maximum compatibility with Laravel, you can also set environment variables directly:

```php
<?php
putenv('APP_NAME=My Custom Uploader');
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['DB_HOST'] = 'custom-db-host';
```

## Helper Functions

LocalSettings provides several helper functions:

### getLocalSetting()

Get a setting value with an optional default:

```php
$maxSize = getLocalSetting('max_upload_size', 100 * 1024 * 1024);
```

### setLocalSetting()

Set a setting programmatically:

```php
setLocalSetting('my_setting', 'my_value');
```

### hasLocalSettings()

Check if LocalSettings.php exists:

```php
if (hasLocalSettings()) {
    // LocalSettings.php is loaded
}
```

### getAllLocalSettings()

Get all LocalSettings as an array:

```php
$allSettings = getAllLocalSettings();
```

### runLocalSettingsHook()

Run a custom hook:

```php
runLocalSettingsHook('beforeUpload', $uploadObject);
```

## Using in Your Code

Access LocalSettings values in controllers, services, or anywhere in your application:

```php
<?php

namespace App\Http\Controllers;

class MyController extends Controller
{
    public function index()
    {
        // Get a LocalSettings value
        $maxSize = getLocalSetting('max_upload_size', config('app.max_upload_size'));
        
        // Check a feature flag
        $analyticsEnabled = getLocalSetting('features')['enable_event_analytics'] ?? true;
        
        // Run a hook
        runLocalSettingsHook('myCustomHook', $someData);
    }
}
```

## Priority Order

Configuration values are loaded in this order (later overrides earlier):

1. **Config file defaults** (e.g., `config/app.php`)
2. **Environment variables** (`.env` file)
3. **LocalSettings.php** (highest priority)

## Best Practices

1. **Don't commit LocalSettings.php** - It's git-ignored for security
2. **Use LocalSettings.php.example** - Document your custom settings
3. **Keep sensitive data secure** - LocalSettings.php should have restricted permissions
4. **Document custom settings** - Add comments explaining custom configurations
5. **Test thoroughly** - Verify that LocalSettings overrides work as expected

## Migration from .env

If you're migrating from `.env` configuration:

```bash
# Old way (.env):
APP_NAME="My Uploader"
DB_HOST=localhost

# New way (LocalSettings.php):
<?php
$GLOBALS['wgCommonsUploader']['app_name'] = 'My Uploader';
$GLOBALS['wgCommonsUploader']['db_host'] = 'localhost';
```

Both methods work, but LocalSettings.php provides more flexibility for complex configurations.

## Example: Production Deployment

```php
<?php
/**
 * Production LocalSettings.php
 */

// Application
$GLOBALS['wgCommonsUploader']['app_name'] = 'Wikimedia Conference Uploader';
$GLOBALS['wgCommonsUploader']['app_url'] = 'https://uploader.wikimedia.org';
$GLOBALS['wgCommonsUploader']['debug'] = false;

// Database
$GLOBALS['wgCommonsUploader']['db_host'] = 'prod-db.internal';
$GLOBALS['wgCommonsUploader']['db_database'] = 'commons_uploader_prod';
$GLOBALS['wgCommonsUploader']['db_username'] = 'uploader_prod';
$GLOBALS['wgCommonsUploader']['db_password'] = getenv('DB_PASSWORD'); // From environment

// OAuth (production credentials)
$GLOBALS['wgCommonsUploader']['oauth_client_id'] = getenv('OAUTH_CLIENT_ID');
$GLOBALS['wgCommonsUploader']['oauth_client_secret'] = getenv('OAUTH_CLIENT_SECRET');
$GLOBALS['wgCommonsUploader']['oauth_redirect_uri'] = 'https://uploader.wikimedia.org/auth/callback';

// File upload (higher limits for production)
$GLOBALS['wgCommonsUploader']['max_upload_size'] = 500 * 1024 * 1024; // 500MB

// Features
$GLOBALS['wgCommonsUploader']['features']['enable_category_autocomplete'] = true;
$GLOBALS['wgCommonsUploader']['features']['enable_statistics_dashboard'] = true;

// Custom hooks for monitoring
$GLOBALS['wgCommonsUploader']['hooks']['afterUpload'][] = function($upload, $result) {
    // Send metrics to monitoring system
    if ($result['success']) {
        // Log success metric
    }
};
```

## Troubleshooting

### LocalSettings not loading?

Check that:
1. File is named exactly `LocalSettings.php` (case-sensitive)
2. File is in the root directory (same level as `artisan`)
3. File has proper PHP syntax (no parse errors)

### Settings not taking effect?

Remember the priority order:
1. Verify LocalSettings.php is being loaded: `hasLocalSettings()`
2. Check if `.env` variables are overriding your settings
3. Clear any cached configuration

### Debugging

Add this to your LocalSettings.php to verify it's loading:

```php
<?php
error_log("LocalSettings.php loaded at " . date('Y-m-d H:i:s'));
```

## Security Notes

- **Never commit LocalSettings.php** to version control
- **Set restrictive file permissions**: `chmod 600 LocalSettings.php`
- **Don't expose sensitive credentials** in LocalSettings.php.example
- **Use environment variables** for truly sensitive data when possible

## Related Documentation

- [README.md](README.md) - General installation and usage
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment guide
- [SECURITY.md](SECURITY.md) - Security best practices
- [.env.example](.env.example) - Environment variable configuration

## Comparison with MediaWiki

For MediaWiki administrators, here's how CommonsEventUploader's LocalSettings compares:

| MediaWiki | CommonsEventUploader | Notes |
|-----------|---------------------|-------|
| `$wgSitename` | `$GLOBALS['wgCommonsUploader']['app_name']` | Site name |
| `$wgDBserver` | `$GLOBALS['wgCommonsUploader']['db_host']` | Database host |
| `$wgDBname` | `$GLOBALS['wgCommonsUploader']['db_database']` | Database name |
| `$wgUploadSizeLimit` | `$GLOBALS['wgCommonsUploader']['max_upload_size']` | Max upload size |
| `$wgFileExtensions` | `$GLOBALS['wgCommonsUploader']['allowed_file_types']` | Allowed file types |

The `wgCommonsUploader` prefix maintains the MediaWiki convention of prefixing global configuration with `wg`.
