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
 * Queue Processor Script
 * 
 * Processes pending scheduled uploads and publishes them to Wikimedia Commons.
 * This script should be run via cron job for continuous processing.
 * 
 * Usage: php process-queue.php [--batch-size=10] [--daemon]
 * 
 * Options:
 *   --batch-size=N   Process N uploads per run (default: 10)
 *   --daemon         Run continuously with 60 second intervals
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ScheduleService;

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Parse command line options
$batchSize = 10;
$daemon = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--batch-size=') === 0) {
        $batchSize = (int) substr($arg, 13);
    }
    if ($arg === '--daemon') {
        $daemon = true;
    }
}

// ANSI color codes
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'gray' => "\033[90m",
];

function log_message($message, $color = 'reset', $colors) {
    echo $colors[$color] . '[' . date('Y-m-d H:i:s') . '] ' . $message . $colors['reset'] . "\n";
}

// Main processing loop
log_message("CommonsEventUploader Queue Processor", 'blue', $colors);
log_message("Batch size: $batchSize" . ($daemon ? " (daemon mode)" : ""), 'gray', $colors);
log_message(str_repeat('-', 50), 'gray', $colors);

$scheduleService = new ScheduleService();

do {
    try {
        // Get queue stats
        $stats = ScheduleService::getQueueStats();
        
        if ($stats['total_pending'] > 0) {
            log_message("Processing {$stats['total_pending']} pending upload(s)...", 'blue', $colors);
            
            // Process batch
            $results = $scheduleService->processPending($batchSize);
            
            // Display results
            log_message("Processed: {$results['processed']}, Successful: {$results['successful']}, Failed: {$results['failed']}", 'green', $colors);
            
            // Display errors if any
            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    log_message("  Error (ID {$error['id']}): {$error['error']}", 'red', $colors);
                }
            }
        } else {
            log_message("Queue is empty", 'gray', $colors);
        }
        
        // Display next scheduled
        if ($stats['next_scheduled']) {
            log_message("Next scheduled upload: {$stats['next_scheduled']}", 'yellow', $colors);
        }
        
        // Sleep if in daemon mode
        if ($daemon) {
            log_message("Sleeping for 60 seconds...\n", 'gray', $colors);
            sleep(60);
        }
        
    } catch (Exception $e) {
        log_message("Fatal error: " . $e->getMessage(), 'red', $colors);
        log_message($e->getTraceAsString(), 'gray', $colors);
        
        if ($daemon) {
            log_message("Sleeping for 60 seconds before retry...\n", 'yellow', $colors);
            sleep(60);
        } else {
            exit(1);
        }
    }
    
} while ($daemon);

log_message("Done!", 'green', $colors);
exit(0);
