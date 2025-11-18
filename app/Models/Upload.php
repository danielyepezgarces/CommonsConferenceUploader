<?php

namespace App\Models;

use App\Database;
use PDO;

class Upload
{
    public ?int $id = null;
    public int $event_id;
    public int $user_id;
    public string $title;
    public ?string $description = null;
    public ?string $categories_json = null;
    public ?int $main_category_id = null;
    public string $commons_filename;
    public string $status = 'pending';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM uploads WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function getByEvent(int $eventId, int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM uploads WHERE event_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $eventId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getByUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM uploads WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO uploads (event_id, user_id, title, description, categories_json, main_category_id, commons_filename, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $this->event_id,
                $this->user_id,
                $this->title,
                $this->description,
                $this->categories_json,
                $this->main_category_id,
                $this->commons_filename,
                $this->status
            ]);

            if ($result) {
                $this->id = (int) $pdo->lastInsertId();
            }

            return $result;
        } else {
            $stmt = $pdo->prepare(
                'UPDATE uploads SET title = ?, description = ?, categories_json = ?, main_category_id = ?, status = ? WHERE id = ?'
            );
            return $stmt->execute([
                $this->title,
                $this->description,
                $this->categories_json,
                $this->main_category_id,
                $this->status,
                $this->id
            ]);
        }
    }

    public function addCategories(array $categoryIds): bool
    {
        if ($this->id === null || empty($categoryIds)) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO upload_categories (upload_id, category_id) VALUES (?, ?)');

        foreach ($categoryIds as $categoryId) {
            $stmt->execute([$this->id, $categoryId]);
        }

        return true;
    }

    public function getCategories(): array
    {
        if ($this->id === null) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT c.* FROM categories c
            INNER JOIN upload_categories uc ON c.id = uc.category_id
            WHERE uc.upload_id = ?
        ');
        $stmt->execute([$this->id]);

        return array_map([Category::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getGlobalStatistics(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT 
                COUNT(*) as total_uploads,
                SUM(CASE WHEN status = "uploaded" THEN 1 ELSE 0 END) as successful_uploads,
                COUNT(DISTINCT user_id) as total_users,
                COUNT(DISTINCT event_id) as total_events
            FROM uploads
        ');
        return $stmt->fetch() ?: [];
    }

    public static function hydrate(array $data): self
    {
        $upload = new self();
        $upload->id = (int) $data['id'];
        $upload->event_id = (int) $data['event_id'];
        $upload->user_id = (int) $data['user_id'];
        $upload->title = $data['title'];
        $upload->description = $data['description'];
        $upload->categories_json = $data['categories_json'];
        $upload->main_category_id = $data['main_category_id'] ? (int) $data['main_category_id'] : null;
        $upload->commons_filename = $data['commons_filename'];
        $upload->status = $data['status'];
        $upload->created_at = $data['created_at'];
        $upload->updated_at = $data['updated_at'];
        return $upload;
    }
}
