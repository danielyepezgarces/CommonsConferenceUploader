<?php

namespace App\Models;

use App\Database;
use PDO;

class User
{
    public ?int $id = null;
    public string $wikimedia_id;
    public string $username;
    public string $role = 'user';
    public ?string $avatar = null;
    public ?string $registered_at = null;
    public ?string $last_login = null;

    public static function findByWikimediaId(string $wikimediaId): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE wikimedia_id = ?');
        $stmt->execute([$wikimediaId]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return self::hydrate($data);
    }

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return self::hydrate($data);
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            // Insert new user
            $stmt = $pdo->prepare(
                'INSERT INTO users (wikimedia_id, username, role, avatar, registered_at, last_login) 
                 VALUES (?, ?, ?, ?, NOW(), NOW())'
            );
            $result = $stmt->execute([
                $this->wikimedia_id,
                $this->username,
                $this->role,
                $this->avatar
            ]);
            
            if ($result) {
                $this->id = (int) $pdo->lastInsertId();
            }
            
            return $result;
        } else {
            // Update existing user
            $stmt = $pdo->prepare(
                'UPDATE users SET username = ?, role = ?, avatar = ?, last_login = ? WHERE id = ?'
            );
            return $stmt->execute([
                $this->username,
                $this->role,
                $this->avatar,
                $this->last_login,
                $this->id
            ]);
        }
    }

    public function updateLastLogin(): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        return $stmt->execute([$this->id]);
    }

    public static function getStatistics(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT 
                COUNT(*) as total_uploads,
                SUM(CASE WHEN status = "uploaded" THEN 1 ELSE 0 END) as successful_uploads,
                COUNT(DISTINCT event_id) as events_participated
            FROM uploads 
            WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }

    private static function hydrate(array $data): self
    {
        $user = new self();
        $user->id = (int) $data['id'];
        $user->wikimedia_id = $data['wikimedia_id'];
        $user->username = $data['username'];
        $user->role = $data['role'];
        $user->avatar = $data['avatar'];
        $user->registered_at = $data['registered_at'];
        $user->last_login = $data['last_login'];
        return $user;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('conference_admin', 'super_admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
