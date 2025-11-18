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

use App\Models\Conference;
use App\Models\User;

class ConferenceController
{
    public function index(): void
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $status = $_GET['status'] ?? null;

        $conferences = Conference::getAll($limit, $offset, $status);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $conferences,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }

    public function show(int $id): void
    {
        $conference = Conference::findById($id);

        if (!$conference) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conference not found']);
            return;
        }

        // Get associated events
        $events = $conference->getEvents();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $conference,
            'events' => $events
        ]);
    }

    public function store(): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        
        // Only conference_admin and super_admin can create conferences
        if (!in_array($user->role, ['conference_admin', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient permissions']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $conference = new Conference();
        $conference->name = $data['name'];
        $conference->slug = isset($data['slug']) ? $data['slug'] : Conference::generateSlug($data['name']);
        $conference->description = $data['description'] ?? null;
        $conference->start_date = $data['start_date'];
        $conference->end_date = $data['end_date'];
        $conference->location = $data['location'] ?? null;
        $conference->website_url = $data['website_url'] ?? null;
        $conference->logo_url = $data['logo_url'] ?? null;
        $conference->wikidata_id = $data['wikidata_id'] ?? null;
        $conference->organizer_name = $data['organizer_name'] ?? null;
        $conference->organizer_email = $data['organizer_email'] ?? null;
        $conference->status = $data['status'] ?? 'draft';
        $conference->created_by = $user->id;

        if ($conference->save()) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Conference created successfully',
                'data' => $conference
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create conference']);
        }
    }

    public function update(int $id): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        $conference = Conference::findById($id);

        if (!$conference) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conference not found']);
            return;
        }

        // Super admin can edit all, conference_admin can only edit their own
        if ($user->role !== 'super_admin' && $conference->created_by !== $user->id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: You can only edit your own conferences']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $conference->name = $data['name'] ?? $conference->name;
        $conference->slug = $data['slug'] ?? $conference->slug;
        $conference->description = $data['description'] ?? $conference->description;
        $conference->start_date = $data['start_date'] ?? $conference->start_date;
        $conference->end_date = $data['end_date'] ?? $conference->end_date;
        $conference->location = $data['location'] ?? $conference->location;
        $conference->website_url = $data['website_url'] ?? $conference->website_url;
        $conference->logo_url = $data['logo_url'] ?? $conference->logo_url;
        $conference->wikidata_id = $data['wikidata_id'] ?? $conference->wikidata_id;
        $conference->organizer_name = $data['organizer_name'] ?? $conference->organizer_name;
        $conference->organizer_email = $data['organizer_email'] ?? $conference->organizer_email;
        $conference->status = $data['status'] ?? $conference->status;

        if ($conference->save()) {
            echo json_encode([
                'success' => true,
                'message' => 'Conference updated successfully',
                'data' => $conference
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update conference']);
        }
    }

    public function delete(int $id): void
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        $conference = Conference::findById($id);

        if (!$conference) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conference not found']);
            return;
        }

        // Only super_admin can delete conferences
        if ($user->role !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: Only super_admin can delete conferences']);
            return;
        }

        if ($conference->delete()) {
            echo json_encode([
                'success' => true,
                'message' => 'Conference deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete conference']);
        }
    }

    public function stats(int $id): void
    {
        $conference = Conference::findById($id);

        if (!$conference) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conference not found']);
            return;
        }

        $stats = Conference::getStatistics($id);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }
}
