<?php
/**
 * CommonsEventUploader
 * 
 * Copyright (C) 2025 Daniel Yepez Garces
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */
/**
 * LocalSettings Loader
 * 
 * This file loads LocalSettings.php if it exists and applies configuration overrides.
 * Similar to MediaWiki's LocalSettings.php pattern.
 */

// Initialize global settings array
if (!isset($GLOBALS['wgCommonsUploader'])) {
    $GLOBALS['wgCommonsUploader'] = [];
}

// Load LocalSettings.php if it exists
$localSettingsPath = dirname(__DIR__) . '/LocalSettings.php';
if (file_exists($localSettingsPath)) {
    require_once $localSettingsPath;
}

/**
 * Apply LocalSettings to environment variables
 * This allows LocalSettings.php values to override .env values
 */
function applyLocalSettings(): void
{
    $settings = $GLOBALS['wgCommonsUploader'] ?? [];
    
    // Map LocalSettings keys to environment variables
    $mapping = [
        // Application
        'app_name' => 'APP_NAME',
        'app_url' => 'APP_URL',
        'debug' => 'APP_DEBUG',
        'timezone' => 'APP_TIMEZONE',
        
        // Database
        'db_host' => 'DB_HOST',
        'db_port' => 'DB_PORT',
        'db_database' => 'DB_DATABASE',
        'db_username' => 'DB_USERNAME',
        'db_password' => 'DB_PASSWORD',
        
        // OAuth
        'oauth_client_id' => 'OAUTH_CLIENT_ID',
        'oauth_client_secret' => 'OAUTH_CLIENT_SECRET',
        'oauth_redirect_uri' => 'OAUTH_REDIRECT_URI',
        
        // Session
        'session_driver' => 'SESSION_DRIVER',
        'session_lifetime' => 'SESSION_LIFETIME',
        
        // Cache
        'cache_driver' => 'CACHE_STORE',
        
        // Logging
        'log_channel' => 'LOG_CHANNEL',
        'log_level' => 'LOG_LEVEL',
    ];
    
    foreach ($mapping as $localKey => $envKey) {
        if (isset($settings[$localKey])) {
            $value = $settings[$localKey];
            
            // Convert boolean to string for environment variables
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            // Set in both $_ENV and $_SERVER for compatibility
            $_ENV[$envKey] = $value;
            $_SERVER[$envKey] = $value;
            putenv("{$envKey}={$value}");
        }
    }
}

// Apply settings immediately
applyLocalSettings();

/**
 * Get a LocalSettings value
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function getLocalSetting(string $key, $default = null)
{
    return $GLOBALS['wgCommonsUploader'][$key] ?? $default;
}

/**
 * Set a LocalSettings value programmatically
 * 
 * @param string $key The setting key
 * @param mixed $value The value to set
 */
function setLocalSetting(string $key, $value): void
{
    $GLOBALS['wgCommonsUploader'][$key] = $value;
}

/**
 * Check if LocalSettings.php is loaded
 * 
 * @return bool
 */
function hasLocalSettings(): bool
{
    return file_exists(dirname(__DIR__) . '/LocalSettings.php');
}

/**
 * Get all LocalSettings
 * 
 * @return array
 */
function getAllLocalSettings(): array
{
    return $GLOBALS['wgCommonsUploader'] ?? [];
}

/**
 * Run custom hooks if defined
 * 
 * @param string $hookName The name of the hook
 * @param mixed ...$args Arguments to pass to the hook
 * @return void
 */
function runLocalSettingsHook(string $hookName, ...$args): void
{
    $hooks = $GLOBALS['wgCommonsUploader']['hooks'][$hookName] ?? [];
    
    foreach ($hooks as $callback) {
        if (is_callable($callback)) {
            call_user_func_array($callback, $args);
        }
    }
}
