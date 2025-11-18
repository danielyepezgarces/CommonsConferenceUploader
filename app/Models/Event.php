<?php

namespace App\Models;

use App\Database;
use PDO;

class Event
{
    public ?int $id = null;
    public string $title;
    public string $slug;
    public ?string $description = null;
    public string $start_date;
    public string $end_date;
    public ?string $wikidata_event_id = null;
    public int $created_by;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function findBySlug(string $slug): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM events WHERE slug = ?');
        $stmt->execute([$slug]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function getAll(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM events ORDER BY start_date DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getByUser(int $userId, int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM events WHERE created_by = ? ORDER BY start_date DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO events (title, slug, description, start_date, end_date, wikidata_event_id, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $this->title,
                $this->slug,
                $this->description,
                $this->start_date,
                $this->end_date,
                $this->wikidata_event_id,
                $this->created_by
            ]);

            if ($result) {
                $this->id = (int) $pdo->lastInsertId();
            }

            return $result;
        } else {
            $stmt = $pdo->prepare(
                'UPDATE events SET title = ?, slug = ?, description = ?, start_date = ?, end_date = ?, wikidata_event_id = ? WHERE id = ?'
            );
            return $stmt->execute([
                $this->title,
                $this->slug,
                $this->description,
                $this->start_date,
                $this->end_date,
                $this->wikidata_event_id,
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
        $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
        return $stmt->execute([$this->id]);
    }

    public static function getStatistics(int $eventId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT 
                COUNT(*) as total_uploads,
                SUM(CASE WHEN status = "uploaded" THEN 1 ELSE 0 END) as successful_uploads,
                COUNT(DISTINCT user_id) as unique_contributors
            FROM uploads 
            WHERE event_id = ?
        ');
        $stmt->execute([$eventId]);
        return $stmt->fetch() ?: [];
    }

    private static function hydrate(array $data): self
    {
        $event = new self();
        $event->id = (int) $data['id'];
        $event->title = $data['title'];
        $event->slug = $data['slug'];
        $event->description = $data['description'];
        $event->start_date = $data['start_date'];
        $event->end_date = $data['end_date'];
        $event->wikidata_event_id = $data['wikidata_event_id'];
        $event->created_by = (int) $data['created_by'];
        $event->created_at = $data['created_at'];
        $event->updated_at = $data['updated_at'];
        return $event;
    }

    public static function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $pdo = Database::getConnection();
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE slug = ?');
            $stmt->execute([$slug]);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
