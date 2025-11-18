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

namespace App\Http\Controllers;

use App\Models\ScheduledUpload;
use App\Models\User;
use App\Services\ScheduleService;

class ScheduleController
{
    /**
     * Schedule a new upload (immediate or future)
     */
    public function schedule(): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        
        // Handle file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File upload error']);
            return;
        }

        $file = $_FILES['file'];
        $eventId = (int) $_POST['event_id'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? null;
        $publishType = $_POST['publish_type'] ?? 'immediate';
        $scheduledAt = $_POST['scheduled_at'] ?? null;
        $fileType = $_POST['file_type'] ?? 'photo';

        if (empty($title) || empty($eventId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Validate file type
        if (!in_array($fileType, ['photo', 'video'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type']);
            return;
        }

        // Validate publish type and scheduled_at
        if ($publishType === 'scheduled') {
            if (empty($scheduledAt)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Scheduled uploads require scheduled_at timestamp']);
                return;
            }
            
            $scheduledTime = strtotime($scheduledAt);
            if ($scheduledTime === false || $scheduledTime < time()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid scheduled_at time (must be in the future)']);
                return;
            }
        }

        // Save file to uploads directory
        $uploadDir = __DIR__ . '/../../../storage/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            return;
        }

        // Create scheduled upload record
        $scheduledUpload = new ScheduledUpload();
        $scheduledUpload->user_id = $user->id;
        $scheduledUpload->event_id = $eventId;
        $scheduledUpload->title = $title;
        $scheduledUpload->description = $description;
        $scheduledUpload->file_path = $filePath;
        $scheduledUpload->file_type = $fileType;
        $scheduledUpload->file_size = $file['size'];
        $scheduledUpload->categories_json = $_POST['categories'] ?? null;
        $scheduledUpload->main_category_id = isset($_POST['main_category_id']) ? (int) $_POST['main_category_id'] : null;
        $scheduledUpload->publish_type = $publishType;
        $scheduledUpload->scheduled_at = $publishType === 'scheduled' ? $scheduledAt : null;
        $scheduledUpload->status = 'pending';

        if ($scheduledUpload->save()) {
            // Log the scheduling action
            $scheduledUpload->logAction('scheduled', "Upload scheduled by user {$user->username}", [
                'publish_type' => $publishType,
                'scheduled_at' => $scheduledAt
            ]);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => $publishType === 'immediate' ? 'Upload queued for immediate processing' : 'Upload scheduled successfully',
                'data' => $scheduledUpload
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to schedule upload']);
        }
    }

    /**
     * List scheduled uploads for current user
     */
    public function myScheduledUploads(): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $status = $_GET['status'] ?? null;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;

        $uploads = ScheduledUpload::getByUser($_SESSION['user_id'], $status, $limit);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $uploads
        ]);
    }

    /**
     * Get details of a scheduled upload
     */
    public function show(int $id): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        $scheduledUpload = ScheduledUpload::findById($id);

        if (!$scheduledUpload) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Scheduled upload not found']);
            return;
        }

        // Users can only view their own scheduled uploads, unless super_admin
        if ($scheduledUpload->user_id !== $user->id && $user->role !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return;
        }

        // Get logs
        $logs = $scheduledUpload->getLogs();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $scheduledUpload,
            'logs' => $logs
        ]);
    }

    /**
     * Cancel a scheduled upload
     */
    public function cancel(int $id): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        $scheduledUpload = ScheduledUpload::findById($id);

        if (!$scheduledUpload) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Scheduled upload not found']);
            return;
        }

        // Users can only cancel their own uploads, unless super_admin
        if ($scheduledUpload->user_id !== $user->id && $user->role !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return;
        }

        // Can only cancel pending uploads
        if (!in_array($scheduledUpload->status, ['pending', 'failed'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Can only cancel pending or failed uploads']);
            return;
        }

        $scheduledUpload->status = 'cancelled';
        
        if ($scheduledUpload->save()) {
            $scheduledUpload->logAction('cancelled', "Upload cancelled by user {$user->username}");

            echo json_encode([
                'success' => true,
                'message' => 'Upload cancelled successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to cancel upload']);
        }
    }

    /**
     * Get queue status
     */
    public function queueStatus(): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);

        // Get counts by status for current user
        $pending = count(ScheduledUpload::getByUser($user->id, 'pending'));
        $processing = count(ScheduledUpload::getByUser($user->id, 'processing'));
        $completed = count(ScheduledUpload::getByUser($user->id, 'completed'));
        $failed = count(ScheduledUpload::getByUser($user->id, 'failed'));

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'pending' => $pending,
                'processing' => $processing,
                'completed' => $completed,
                'failed' => $failed,
                'total' => $pending + $processing + $completed + $failed
            ]
        ]);
    }
}
