<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Models\Event;
use App\Models\Upload;
use App\Models\Category;
use App\Services\CommonsApiService;

class UploadController
{
    private CommonsApiService $commonsApi;

    public function __construct()
    {
        $this->commonsApi = new CommonsApiService();
    }

    public function upload(int $eventId): void
    {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::handle();

        $event = Event::findById($eventId);

        if ($event === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }

        // Validate file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'File upload failed']);
            return;
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $originalFilename = $_FILES['file']['name'];

        // Validate file
        if (!$this->commonsApi->validateFile($tmpPath)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type or size']);
            return;
        }

        // Get form data
        $title = $_POST['title'] ?? pathinfo($originalFilename, PATHINFO_FILENAME);
        $description = $_POST['description'] ?? '';
        $categoryIds = isset($_POST['categories']) ? json_decode($_POST['categories'], true) : [];
        $mainCategoryId = isset($_POST['main_category_id']) ? (int) $_POST['main_category_id'] : null;

        // Generate unique filename
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $commonsFilename = $this->generateFilename($title, $event->slug, $extension);

        // Create upload record
        $upload = new Upload();
        $upload->event_id = $eventId;
        $upload->user_id = $user->id;
        $upload->title = $title;
        $upload->description = $description;
        $upload->categories_json = json_encode($categoryIds);
        $upload->main_category_id = $mainCategoryId;
        $upload->commons_filename = $commonsFilename;
        $upload->status = 'pending';

        if (!$upload->save()) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload record']);
            return;
        }

        // Add categories to junction table
        if (!empty($categoryIds)) {
            $upload->addCategories($categoryIds);
        }

        try {
            // Get access token from session/database
            $accessToken = $this->getAccessToken($user->id);

            if (!$accessToken) {
                throw new \RuntimeException('Access token not found');
            }

            // Prepare category names for Commons
            $categoryNames = [];
            foreach ($categoryIds as $catId) {
                $cat = Category::findById($catId);
                if ($cat) {
                    $categoryNames[] = $cat->name;
                }
            }

            // Upload to Commons
            $fileContent = file_get_contents($tmpPath);
            $uploadText = $this->buildUploadText($title, $description, $event);
            
            $result = $this->commonsApi->uploadFile(
                $accessToken,
                $commonsFilename,
                $fileContent,
                $uploadText,
                $categoryNames
            );

            if (isset($result['upload']) && $result['upload']['result'] === 'Success') {
                $upload->status = 'uploaded';
                $upload->save();

                // Increment category upload counts
                foreach ($categoryIds as $catId) {
                    $cat = Category::findById($catId);
                    if ($cat) {
                        $cat->incrementUploadCount();
                    }
                }

                http_response_code(201);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'upload' => $upload,
                    'commons_url' => "https://commons.wikimedia.org/wiki/File:{$commonsFilename}"
                ]);
            } else {
                $upload->status = 'failed';
                $upload->save();

                http_response_code(500);
                echo json_encode([
                    'error' => 'Upload to Commons failed',
                    'details' => $result
                ]);
            }
        } catch (\Exception $e) {
            $upload->status = 'failed';
            $upload->save();

            http_response_code(500);
            echo json_encode([
                'error' => 'Upload failed: ' . $e->getMessage()
            ]);
        }
    }

    public function list(int $eventId): void
    {
        $user = AuthMiddleware::handle();
        
        $limit = (int) ($_GET['limit'] ?? 100);
        $offset = (int) ($_GET['offset'] ?? 0);

        $uploads = Upload::getByEvent($eventId, $limit, $offset);

        header('Content-Type: application/json');
        echo json_encode($uploads);
    }

    public function myUploads(): void
    {
        $user = AuthMiddleware::handle();
        
        $limit = (int) ($_GET['limit'] ?? 100);
        $offset = (int) ($_GET['offset'] ?? 0);

        $uploads = Upload::getByUser($user->id, $limit, $offset);

        header('Content-Type: application/json');
        echo json_encode($uploads);
    }

    private function generateFilename(string $title, string $eventSlug, string $extension): string
    {
        $clean = preg_replace('/[^A-Za-z0-9-]/', '_', $title);
        $timestamp = time();
        return "{$eventSlug}_{$clean}_{$timestamp}.{$extension}";
    }

    private function buildUploadText(string $title, string $description, Event $event): string
    {
        $text = "== {{int:filedesc}} ==\n";
        $text .= "{{Information\n";
        $text .= "|description={$description}\n";
        $text .= "|date=" . date('Y-m-d') . "\n";
        $text .= "|source={{own}}\n";
        $text .= "|author={{subst:REVISIONUSER}}\n";
        $text .= "}}\n\n";
        $text .= "== {{int:license-header}} ==\n";
        $text .= "{{self|cc-by-sa-4.0}}\n\n";
        $text .= "[[Category:Uploaded via CommonsEventUploader]]\n";
        
        if ($event->title) {
            $text .= "<!-- Event: {$event->title} -->\n";
        }

        return $text;
    }

    private function getAccessToken(int $userId): ?string
    {
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT access_token_hash FROM oauth_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        // Note: In production, you'd need to store and retrieve the actual token securely
        // This is a simplified version
        return $result ? $result['access_token_hash'] : null;
    }
}
