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

class Conference
{
    public ?int $id = null;
    public string $name;
    public string $slug;
    public ?string $description = null;
    public string $start_date;
    public string $end_date;
    public ?string $location = null;
    public ?string $website_url = null;
    public ?string $logo_url = null;
    public ?string $wikidata_id = null;
    public ?string $organizer_name = null;
    public ?string $organizer_email = null;
    public string $status = 'draft';
    public int $created_by;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM conferences WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function findBySlug(string $slug): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM conferences WHERE slug = ?');
        $stmt->execute([$slug]);
        $data = $stmt->fetch();

        return $data ? self::hydrate($data) : null;
    }

    public static function getAll(int $limit = 100, int $offset = 0, ?string $status = null): array
    {
        $pdo = Database::getConnection();
        
        if ($status !== null) {
            $stmt = $pdo->prepare('SELECT * FROM conferences WHERE status = ? ORDER BY start_date DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $status, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM conferences ORDER BY start_date DESC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function getPublished(int $limit = 100, int $offset = 0): array
    {
        return self::getAll($limit, $offset, 'published');
    }

    public static function getByUser(int $userId, int $limit = 100): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM conferences WHERE created_by = ? ORDER BY start_date DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public function getEvents(): array
    {
        if ($this->id === null) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM events WHERE conference_id = ? ORDER BY start_date ASC');
        $stmt->execute([$this->id]);

        return array_map([Event::class, 'hydrate'], $stmt->fetchAll());
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO conferences (name, slug, description, start_date, end_date, location, website_url, logo_url, wikidata_id, organizer_name, organizer_email, status, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $this->name,
                $this->slug,
                $this->description,
                $this->start_date,
                $this->end_date,
                $this->location,
                $this->website_url,
                $this->logo_url,
                $this->wikidata_id,
                $this->organizer_name,
                $this->organizer_email,
                $this->status,
                $this->created_by
            ]);

            if ($result) {
                $this->id = (int) $pdo->lastInsertId();
            }

            return $result;
        } else {
            $stmt = $pdo->prepare(
                'UPDATE conferences SET name = ?, slug = ?, description = ?, start_date = ?, end_date = ?, location = ?, website_url = ?, logo_url = ?, wikidata_id = ?, organizer_name = ?, organizer_email = ?, status = ? WHERE id = ?'
            );
            return $stmt->execute([
                $this->name,
                $this->slug,
                $this->description,
                $this->start_date,
                $this->end_date,
                $this->location,
                $this->website_url,
                $this->logo_url,
                $this->wikidata_id,
                $this->organizer_name,
                $this->organizer_email,
                $this->status,
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
        $stmt = $pdo->prepare('DELETE FROM conferences WHERE id = ?');
        return $stmt->execute([$this->id]);
    }

    public static function getStatistics(int $conferenceId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT 
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT u.id) as total_uploads,
                SUM(CASE WHEN u.status = "uploaded" THEN 1 ELSE 0 END) as successful_uploads,
                COUNT(DISTINCT u.user_id) as unique_contributors
            FROM conferences c
            LEFT JOIN events e ON e.conference_id = c.id
            LEFT JOIN uploads u ON u.event_id = e.id
            WHERE c.id = ?
        ');
        $stmt->execute([$conferenceId]);
        return $stmt->fetch() ?: [];
    }

    public static function hydrate(array $data): self
    {
        $conference = new self();
        $conference->id = (int) $data['id'];
        $conference->name = $data['name'];
        $conference->slug = $data['slug'];
        $conference->description = $data['description'];
        $conference->start_date = $data['start_date'];
        $conference->end_date = $data['end_date'];
        $conference->location = $data['location'];
        $conference->website_url = $data['website_url'];
        $conference->logo_url = $data['logo_url'];
        $conference->wikidata_id = $data['wikidata_id'];
        $conference->organizer_name = $data['organizer_name'];
        $conference->organizer_email = $data['organizer_email'];
        $conference->status = $data['status'];
        $conference->created_by = (int) $data['created_by'];
        $conference->created_at = $data['created_at'];
        $conference->updated_at = $data['updated_at'];
        return $conference;
    }

    public static function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $pdo = Database::getConnection();
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM conferences WHERE slug = ?');
            $stmt->execute([$slug]);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
