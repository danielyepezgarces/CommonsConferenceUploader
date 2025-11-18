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

use App\Models\Event;
use App\Models\Conference;
use App\Models\Upload;
use App\Models\Category;
use App\Database;

class PublicController
{
    public function index(): void
    {
        // Get public statistics (no authentication required)
        $db = Database::getInstance();
        
        // Get recent conferences (last 5, published only)
        $conferencesStmt = $db->prepare("
            SELECT c.*, u.username as creator_name,
                   COUNT(DISTINCT e.id) as event_count,
                   COUNT(DISTINCT up.id) as upload_count,
                   COUNT(DISTINCT up.user_id) as participant_count
            FROM conferences c
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN events e ON c.id = e.conference_id
            LEFT JOIN uploads up ON e.id = up.event_id
            WHERE c.status = 'published'
            GROUP BY c.id
            ORDER BY c.start_date DESC
            LIMIT 5
        ");
        $conferencesStmt->execute();
        $conferences = $conferencesStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get recent events (last 10)
        $eventsStmt = $db->prepare("
            SELECT e.*, u.username as creator_name,
                   COUNT(DISTINCT up.id) as upload_count,
                   COUNT(DISTINCT up.user_id) as participant_count
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN uploads up ON e.id = up.event_id
            GROUP BY e.id
            ORDER BY e.created_at DESC
            LIMIT 10
        ");
        $eventsStmt->execute();
        $events = $eventsStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get global statistics
        $statsStmt = $db->query("
            SELECT 
                COUNT(DISTINCT id) as total_uploads,
                COUNT(DISTINCT user_id) as total_contributors,
                COUNT(DISTINCT event_id) as total_events,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_uploads
            FROM uploads
        ");
        $globalStats = $statsStmt->fetch(\PDO::FETCH_ASSOC);
        
        // Get top categories
        $categoriesStmt = $db->query("
            SELECT c.name, c.total_uploads
            FROM categories c
            ORDER BY c.total_uploads DESC
            LIMIT 5
        ");
        $topCategories = $categoriesStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get recent uploads
        $recentUploadsStmt = $db->query("
            SELECT u.commons_filename, u.created_at, us.username, e.title as event_title
            FROM uploads u
            LEFT JOIN users us ON u.user_id = us.id
            LEFT JOIN events e ON u.event_id = e.id
            WHERE u.status = 'completed'
            ORDER BY u.created_at DESC
            LIMIT 5
        ");
        $recentUploads = $recentUploadsStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Pass data to view
        $data = [
            'conferences' => $conferences,
            'events' => $events,
            'stats' => $globalStats,
            'topCategories' => $topCategories,
            'recentUploads' => $recentUploads
        ];
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
