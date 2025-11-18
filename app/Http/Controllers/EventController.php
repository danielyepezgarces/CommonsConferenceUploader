<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Models\Event;

class EventController
{
    public function index(): void
    {
        $user = AuthMiddleware::handle();
        
        $limit = (int) ($_GET['limit'] ?? 100);
        $offset = (int) ($_GET['offset'] ?? 0);

        $events = $user->hasRole('super_admin') 
            ? Event::getAll($limit, $offset)
            : Event::getByUser($user->id, $limit);

        header('Content-Type: application/json');
        echo json_encode($events);
    }

    public function show(int $id): void
    {
        $user = AuthMiddleware::handle();
        
        $event = Event::findById($id);

        if ($event === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        // Check permissions
        if (!$user->isSuperAdmin() && $event->created_by !== $user->id) {
            // Allow regular users to view events
            if (!$user->hasRole('user')) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($event);
    }

    public function create(): void
    {
        $user = AuthMiddleware::handle();
        AuthMiddleware::requireRole($user, 'conference_admin', 'super_admin');
        CsrfMiddleware::handle();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['title'], $data['start_date'], $data['end_date'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $event = new Event();
        $event->title = $data['title'];
        $event->slug = Event::generateSlug($data['title']);
        $event->description = $data['description'] ?? null;
        $event->start_date = $data['start_date'];
        $event->end_date = $data['end_date'];
        $event->wikidata_event_id = $data['wikidata_event_id'] ?? null;
        $event->created_by = $user->id;

        if ($event->save()) {
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode($event);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create event']);
        }
    }

    public function update(int $id): void
    {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::handle();

        $event = Event::findById($id);

        if ($event === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        // Check permissions
        if (!$user->isSuperAdmin() && $event->created_by !== $user->id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['title'])) {
            $event->title = $data['title'];
            if ($data['title'] !== $event->title) {
                $event->slug = Event::generateSlug($data['title']);
            }
        }
        if (isset($data['description'])) $event->description = $data['description'];
        if (isset($data['start_date'])) $event->start_date = $data['start_date'];
        if (isset($data['end_date'])) $event->end_date = $data['end_date'];
        if (isset($data['wikidata_event_id'])) $event->wikidata_event_id = $data['wikidata_event_id'];

        if ($event->save()) {
            header('Content-Type: application/json');
            echo json_encode($event);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update event']);
        }
    }

    public function delete(int $id): void
    {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::handle();

        $event = Event::findById($id);

        if ($event === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        // Check permissions
        if (!$user->isSuperAdmin() && $event->created_by !== $user->id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        if ($event->delete()) {
            http_response_code(204);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete event']);
        }
    }

    public function statistics(int $id): void
    {
        $user = AuthMiddleware::handle();
        
        $event = Event::findById($id);

        if ($event === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        // Check permissions
        if (!$user->isSuperAdmin() && !$user->hasRole('conference_admin') && $event->created_by !== $user->id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $stats = Event::getStatistics($id);

        header('Content-Type: application/json');
        echo json_encode($stats);
    }
}
