#!/usr/bin/env php
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
 * MediaWiki-style System Update Script
 * 
 * Usage: php update.php [--dry-run]
 * 
 * This script updates the database schema automatically by:
 * - Creating required tables during installation
 * - Applying schema changes during version upgrades
 * - Tracking applied migrations
 * - Supporting rollback via transaction
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Updater\SystemUpdater;

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// ANSI color codes for terminal output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'gray' => "\033[90m",
];

function colorize($text, $color, $colors) {
    return $colors[$color] . $text . $colors['reset'];
}

// Parse command line options
$dryRun = in_array('--dry-run', $argv);

try {
    echo colorize("\nCommonsEventUploader System Updater\n", 'blue', $colors);
    echo str_repeat('=', 50) . "\n\n";
    
    if ($dryRun) {
        echo colorize("⚠ DRY RUN MODE - No changes will be made\n\n", 'yellow', $colors);
    }
    
    // Run the updater
    $updater = new SystemUpdater();
    $output = $updater->run($dryRun);
    
    // Display output
    foreach ($output as $log) {
        $message = $log['message'];
        
        switch ($log['level']) {
            case 'success':
                echo colorize($message, 'green', $colors) . "\n";
                break;
            case 'error':
                echo colorize($message, 'red', $colors) . "\n";
                break;
            case 'warn':
                echo colorize($message, 'yellow', $colors) . "\n";
                break;
            case 'info':
            default:
                echo $message . "\n";
                break;
        }
    }
    
    echo "\n";
    exit(0);
    
} catch (Exception $e) {
    echo colorize("\n✗ ERROR: " . $e->getMessage() . "\n", 'red', $colors);
    echo colorize("Stack trace:\n" . $e->getTraceAsString() . "\n", 'gray', $colors);
    exit(1);
}
