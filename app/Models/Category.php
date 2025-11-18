<?php

namespace App\Models;

use App\Database;
use PDO;

class Category
{
    public ?int $id = null;
    public string $name;
    public ?string $description = null;
    public int $total_uploads = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function findByName(string $name): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE name = ?');
        $stmt->execute([$name]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function search(string $query, int $limit = 20): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE name LIKE ? ORDER BY total_uploads DESC LIMIT ?');
        $stmt->bindValue(1, '%' . $query . '%');
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getAll(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories ORDER BY total_uploads DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getTopCategories(int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories ORDER BY total_uploads DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare('INSERT INTO categories (name, description, total_uploads) VALUES (?, ?, ?)');
            $result = $stmt->execute([$this->name, $this->description, $this->total_uploads]);

            if ($result) {
                $this->id = (int) $pdo->lastInsertId();
            }

            return $result;
        } else {
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ?, total_uploads = ? WHERE id = ?');
            return $stmt->execute([$this->name, $this->description, $this->total_uploads, $this->id]);
        }
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        return $stmt->execute([$this->id]);
    }

    public function incrementUploadCount(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE categories SET total_uploads = total_uploads + 1 WHERE id = ?');
        return $stmt->execute([$this->id]);
    }

    private static function hydrate(array $data): self
    {
        $category = new self();
        $category->id = (int) $data['id'];
        $category->name = $data['name'];
        $category->description = $data['description'];
        $category->total_uploads = (int) $data['total_uploads'];
        $category->created_at = $data['created_at'];
        $category->updated_at = $data['updated_at'];
        return $category;
    }
}
