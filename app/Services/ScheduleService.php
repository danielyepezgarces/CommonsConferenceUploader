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

namespace App\Services;

use App\Models\ScheduledUpload;
use App\Models\Upload;

class ScheduleService
{
    private CommonsApiService $commonsService;

    public function __construct()
    {
        $this->commonsService = new CommonsApiService();
    }

    /**
     * Process pending scheduled uploads
     */
    public function processPending(int $batchSize = 10): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $pendingUploads = ScheduledUpload::getPending($batchSize);

        foreach ($pendingUploads as $scheduledUpload) {
            $results['processed']++;
            
            try {
                $this->processUpload($scheduledUpload);
                $results['successful']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'id' => $scheduledUpload->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Process a single scheduled upload
     */
    private function processUpload(ScheduledUpload $scheduledUpload): void
    {
        // Update status to processing
        $scheduledUpload->status = 'processing';
        $scheduledUpload->save();
        $scheduledUpload->logAction('processing', 'Upload is being processed');

        try {
            // Check if file exists
            if (!file_exists($scheduledUpload->file_path)) {
                throw new \Exception('File not found: ' . $scheduledUpload->file_path);
            }

            // TODO: Integrate with CommonsApiService to actually upload to Commons
            // For now, we'll simulate the upload
            
            // Simulate Commons upload
            $commonsFilename = 'File:' . basename($scheduledUpload->file_path);
            
            // Update scheduled upload
            $scheduledUpload->status = 'completed';
            $scheduledUpload->commons_filename = $commonsFilename;
            $scheduledUpload->processed_at = date('Y-m-d H:i:s');
            $scheduledUpload->save();

            // Log success
            $scheduledUpload->logAction('completed', 'Upload completed successfully', [
                'commons_filename' => $commonsFilename
            ]);

            // Create permanent upload record
            $upload = new Upload();
            $upload->event_id = $scheduledUpload->event_id;
            $upload->user_id = $scheduledUpload->user_id;
            $upload->title = $scheduledUpload->title;
            $upload->description = $scheduledUpload->description;
            $upload->categories_json = $scheduledUpload->categories_json;
            $upload->main_category_id = $scheduledUpload->main_category_id;
            $upload->commons_filename = $commonsFilename;
            $upload->status = 'completed';
            $upload->save();

            // Delete the temporary file immediately after successful upload
            $this->deleteTemporaryFile($scheduledUpload->file_path);
            $scheduledUpload->logAction('file_deleted', 'Temporary file deleted after successful upload');

        } catch (\Exception $e) {
            // Increment attempts
            $scheduledUpload->attempts++;
            
            // Check if max attempts reached
            if ($scheduledUpload->attempts >= $scheduledUpload->max_attempts) {
                $scheduledUpload->status = 'failed';
                $scheduledUpload->error_message = 'Max attempts reached: ' . $e->getMessage();
                $scheduledUpload->logAction('failed', 'Upload failed after max attempts', [
                    'error' => $e->getMessage(),
                    'attempts' => $scheduledUpload->attempts
                ]);
            } else {
                $scheduledUpload->status = 'pending';
                $scheduledUpload->error_message = $e->getMessage();
                $scheduledUpload->logAction('retrying', 'Upload failed, will retry', [
                    'error' => $e->getMessage(),
                    'attempt' => $scheduledUpload->attempts
                ]);
            }
            
            $scheduledUpload->save();
            
            // If max attempts reached, delete the temporary file
            if ($scheduledUpload->attempts >= $scheduledUpload->max_attempts) {
                $this->deleteTemporaryFile($scheduledUpload->file_path);
                $scheduledUpload->logAction('file_deleted', 'Temporary file deleted after max attempts reached');
            }
            
            throw $e;
        }
    }

    /**
     * Delete temporary file from storage
     */
    private function deleteTemporaryFile(string $filePath): void
    {
        try {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the main process
            error_log("Failed to delete temporary file: {$filePath}. Error: " . $e->getMessage());
        }
    }

    /**
     * Clean up old temporary files (files older than specified hours)
     */
    public static function cleanupOldFiles(int $olderThanHours = 24): array
    {
        $results = [
            'scanned' => 0,
            'deleted' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $uploadDir = __DIR__ . '/../../storage/uploads/';
        if (!is_dir($uploadDir)) {
            return $results;
        }

        $cutoffTime = time() - ($olderThanHours * 3600);
        $files = glob($uploadDir . '*');

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $results['scanned']++;

            // Check file modification time
            $fileTime = filemtime($file);
            if ($fileTime < $cutoffTime) {
                try {
                    if (unlink($file)) {
                        $results['deleted']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to delete: $file";
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error deleting $file: " . $e->getMessage();
                }
            }
        }

        return $results;
    }

    /**
     * Delete cancelled upload files
     */
    public static function cleanupCancelledUploads(): array
    {
        $results = [
            'scanned' => 0,
            'deleted' => 0,
            'failed' => 0
        ];

        $cancelledUploads = ScheduledUpload::getByCancelledStatus();

        foreach ($cancelledUploads as $upload) {
            $results['scanned']++;

            try {
                if (file_exists($upload->file_path)) {
                    if (unlink($upload->file_path)) {
                        $results['deleted']++;
                        $upload->logAction('file_deleted', 'Cancelled upload file deleted');
                    } else {
                        $results['failed']++;
                    }
                }
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Get queue statistics
     */
    public static function getQueueStats(): array
    {
        $pending = ScheduledUpload::getPending(1000);
        
        $stats = [
            'total_pending' => count($pending),
            'immediate' => 0,
            'scheduled' => 0,
            'next_scheduled' => null
        ];

        foreach ($pending as $upload) {
            if ($upload->publish_type === 'immediate') {
                $stats['immediate']++;
            } else {
                $stats['scheduled']++;
                if ($stats['next_scheduled'] === null || $upload->scheduled_at < $stats['next_scheduled']) {
                    $stats['next_scheduled'] = $upload->scheduled_at;
                }
            }
        }

        return $stats;
    }
}
