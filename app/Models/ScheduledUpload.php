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

namespace App\Models;

use App\Database;
use PDO;

class ScheduledUpload
{
    public ?int $id = null;
    public int $user_id;
    public int $event_id;
    public string $title;
    public ?string $description = null;
    public string $file_path;
    public string $file_type = 'photo';
    public int $file_size;
    public ?string $categories_json = null;
    public ?int $main_category_id = null;
    public string $publish_type = 'immediate';
    public ?string $scheduled_at = null;
    public string $status = 'pending';
    public ?string $commons_filename = null;
    public ?string $error_message = null;
    public int $attempts = 0;
    public int $max_attempts = 3;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $processed_at = null;

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM scheduled_uploads WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function getPending(int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT * FROM scheduled_uploads 
            WHERE status = "pending" 
            AND (
                (publish_type = "immediate")
                OR (publish_type = "scheduled" AND scheduled_at <= NOW())
            )
            AND attempts < max_attempts
            ORDER BY scheduled_at ASC, created_at ASC
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getByUser(int $userId, ?string $status = null, int $limit = 100): array
    {
        $pdo = Database::getConnection();
        
        if ($status) {
            $stmt = $pdo->prepare('
                SELECT * FROM scheduled_uploads 
                WHERE user_id = ? AND status = ?
                ORDER BY created_at DESC 
                LIMIT ?
            ');
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $status, PDO::PARAM_STR);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare('
                SELECT * FROM scheduled_uploads 
                WHERE user_id = ?
                ORDER BY created_at DESC 
                LIMIT ?
            ');
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getByEvent(int $eventId, int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT * FROM scheduled_uploads 
            WHERE event_id = ?
            ORDER BY created_at DESC 
            LIMIT ?
        ');
        $stmt->bindValue(1, $eventId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getByCancelledStatus(int $limit = 1000): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT * FROM scheduled_uploads 
            WHERE status = "cancelled"
            ORDER BY updated_at DESC 
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO scheduled_uploads (user_id, event_id, title, description, file_path, file_type, file_size, categories_json, main_category_id, publish_type, scheduled_at, status, attempts, max_attempts) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $this->user_id,
                $this->event_id,
                $this->title,
                $this->description,
                $this->file_path,
                $this->file_type,
                $this->file_size,
                $this->categories_json,
                $this->main_category_id,
                $this->publish_type,
                $this->scheduled_at,
                $this->status,
                $this->attempts,
                $this->max_attempts
            ]);

            if ($result) {
                $this->id = (int) $pdo->lastInsertId();
            }

            return $result;
        } else {
            $stmt = $pdo->prepare(
                'UPDATE scheduled_uploads SET status = ?, commons_filename = ?, error_message = ?, attempts = ?, processed_at = ? WHERE id = ?'
            );
            return $stmt->execute([
                $this->status,
                $this->commons_filename,
                $this->error_message,
                $this->attempts,
                $this->processed_at,
                $this->id
            ]);
        }
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM scheduled_uploads WHERE id = ?');
        return $stmt->execute([$this->id]);
    }

    public function logAction(string $action, string $message, ?array $metadata = null): void
    {
        if ($this->id === null) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO publish_logs (scheduled_upload_id, action, message, metadata) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $this->id,
            $action,
            $message,
            $metadata ? json_encode($metadata) : null
        ]);
    }

    public function getLogs(): array
    {
        if ($this->id === null) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT * FROM publish_logs 
            WHERE scheduled_upload_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$this->id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function hydrate(array $data): self
    {
        $upload = new self();
        $upload->id = (int) $data['id'];
        $upload->user_id = (int) $data['user_id'];
        $upload->event_id = (int) $data['event_id'];
        $upload->title = $data['title'];
        $upload->description = $data['description'];
        $upload->file_path = $data['file_path'];
        $upload->file_type = $data['file_type'];
        $upload->file_size = (int) $data['file_size'];
        $upload->categories_json = $data['categories_json'];
        $upload->main_category_id = $data['main_category_id'] ? (int) $data['main_category_id'] : null;
        $upload->publish_type = $data['publish_type'];
        $upload->scheduled_at = $data['scheduled_at'];
        $upload->status = $data['status'];
        $upload->commons_filename = $data['commons_filename'];
        $upload->error_message = $data['error_message'];
        $upload->attempts = (int) $data['attempts'];
        $upload->max_attempts = (int) $data['max_attempts'];
        $upload->created_at = $data['created_at'];
        $upload->updated_at = $data['updated_at'];
        $upload->processed_at = $data['processed_at'];
        return $upload;
    }
}
