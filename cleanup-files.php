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
 * Cleanup old temporary upload files
 * 
 * This script removes temporary upload files from storage/uploads/ directory
 * to prevent disk space issues in production environments like Heroku.
 * 
 * Features:
 * - Removes files older than specified hours (default: 24 hours)
 * - Removes files from cancelled uploads
 * - Never stores images permanently
 * 
 * Usage:
 *   php cleanup-files.php [--hours=24] [--cancelled] [--dry-run]
 * 
 * Options:
 *   --hours=N     Delete files older than N hours (default: 24)
 *   --cancelled   Delete files from cancelled uploads
 *   --dry-run     Show what would be deleted without actually deleting
 * 
 * Cron example (run every hour):
 *   0 * * * * cd /path/to/app && php cleanup-files.php
 */

require __DIR__ . '/bootstrap/localsettings.php';
require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/Models/ScheduledUpload.php';
require __DIR__ . '/app/Services/ScheduleService.php';

use App\Services\ScheduleService;

// ANSI color codes
const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";

// Parse command line arguments
$options = getopt('', ['hours:', 'cancelled', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Cleanup old temporary upload files\n\n";
    echo "Usage: php cleanup-files.php [options]\n\n";
    echo "Options:\n";
    echo "  --hours=N     Delete files older than N hours (default: 24)\n";
    echo "  --cancelled   Delete files from cancelled uploads\n";
    echo "  --dry-run     Show what would be deleted without actually deleting\n";
    echo "  --help        Show this help message\n\n";
    echo "Examples:\n";
    echo "  php cleanup-files.php\n";
    echo "  php cleanup-files.php --hours=12\n";
    echo "  php cleanup-files.php --cancelled\n";
    echo "  php cleanup-files.php --hours=24 --cancelled\n";
    echo "  php cleanup-files.php --dry-run\n\n";
    exit(0);
}

$hours = isset($options['hours']) ? (int) $options['hours'] : 24;
$cleanCancelled = isset($options['cancelled']);
$dryRun = isset($options['dry-run']);

echo COLOR_BLUE . "===================================\n" . COLOR_RESET;
echo COLOR_BLUE . "  CommonsEventUploader File Cleanup\n" . COLOR_RESET;
echo COLOR_BLUE . "===================================\n\n" . COLOR_RESET;

if ($dryRun) {
    echo COLOR_YELLOW . "[DRY RUN MODE - No files will be deleted]\n\n" . COLOR_RESET;
}

echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Cleanup old files
echo COLOR_BLUE . "Cleaning up files older than {$hours} hours...\n" . COLOR_RESET;

if ($dryRun) {
    // In dry-run mode, just list files that would be deleted
    $uploadDir = __DIR__ . '/storage/uploads/';
    if (is_dir($uploadDir)) {
        $cutoffTime = time() - ($hours * 3600);
        $files = glob($uploadDir . '*');
        $count = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $fileTime = filemtime($file);
            if ($fileTime < $cutoffTime) {
                $age = round((time() - $fileTime) / 3600, 1);
                echo COLOR_YELLOW . "  Would delete: " . basename($file) . " (age: {$age} hours)\n" . COLOR_RESET;
                $count++;
            }
        }
        
        if ($count === 0) {
            echo COLOR_GREEN . "  No files older than {$hours} hours found\n" . COLOR_RESET;
        } else {
            echo COLOR_YELLOW . "  Would delete {$count} file(s)\n" . COLOR_RESET;
        }
    }
} else {
    $result = ScheduleService::cleanupOldFiles($hours);
    
    echo "  Scanned: " . $result['scanned'] . " files\n";
    echo COLOR_GREEN . "  Deleted: " . $result['deleted'] . " files\n" . COLOR_RESET;
    
    if ($result['failed'] > 0) {
        echo COLOR_RED . "  Failed: " . $result['failed'] . " files\n" . COLOR_RESET;
        foreach ($result['errors'] as $error) {
            echo COLOR_RED . "    - $error\n" . COLOR_RESET;
        }
    }
}

echo "\n";

// Cleanup cancelled uploads if requested
if ($cleanCancelled) {
    echo COLOR_BLUE . "Cleaning up cancelled upload files...\n" . COLOR_RESET;
    
    if ($dryRun) {
        $cancelledUploads = \App\Models\ScheduledUpload::getByCancelledStatus();
        $count = 0;
        
        foreach ($cancelledUploads as $upload) {
            if (file_exists($upload->file_path)) {
                echo COLOR_YELLOW . "  Would delete: " . basename($upload->file_path) . " (ID: {$upload->id})\n" . COLOR_RESET;
                $count++;
            }
        }
        
        if ($count === 0) {
            echo COLOR_GREEN . "  No cancelled upload files found\n" . COLOR_RESET;
        } else {
            echo COLOR_YELLOW . "  Would delete {$count} file(s)\n" . COLOR_RESET;
        }
    } else {
        $result = ScheduleService::cleanupCancelledUploads();
        
        echo "  Scanned: " . $result['scanned'] . " uploads\n";
        echo COLOR_GREEN . "  Deleted: " . $result['deleted'] . " files\n" . COLOR_RESET;
        
        if ($result['failed'] > 0) {
            echo COLOR_RED . "  Failed: " . $result['failed'] . " files\n" . COLOR_RESET;
        }
    }
    
    echo "\n";
}

// Summary
echo COLOR_BLUE . "===================================\n" . COLOR_RESET;
echo "Finished at: " . date('Y-m-d H:i:s') . "\n";

if ($dryRun) {
    echo COLOR_YELLOW . "\n[DRY RUN COMPLETED - No changes were made]\n" . COLOR_RESET;
    echo "Run without --dry-run to actually delete files\n";
}

echo COLOR_GREEN . "\n✓ Cleanup completed successfully\n" . COLOR_RESET;
