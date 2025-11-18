<?php

namespace App\Services;

use GuzzleHttp\Client;

class CommonsApiService
{
    private Client $client;
    private string $apiUrl;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->apiUrl = $config['commons_api_url'];
        $this->client = new Client(['timeout' => 60]);
    }

    public function uploadFile(string $accessToken, string $filename, string $fileContent, string $text, array $categories = []): array
    {
        // Get CSRF token
        $csrfToken = $this->getCsrfToken($accessToken);

        // Prepare categories
        $categoryText = '';
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $categoryText .= "\n[[Category:{$category}]]";
            }
        }

        $fullText = $text . $categoryText;

        // Upload file
        $response = $this->client->post($this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'multipart' => [
                [
                    'name' => 'action',
                    'contents' => 'upload',
                ],
                [
                    'name' => 'filename',
                    'contents' => $filename,
                ],
                [
                    'name' => 'text',
                    'contents' => $fullText,
                ],
                [
                    'name' => 'token',
                    'contents' => $csrfToken,
                ],
                [
                    'name' => 'format',
                    'contents' => 'json',
                ],
                [
                    'name' => 'file',
                    'contents' => $fileContent,
                    'filename' => $filename,
                ],
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getCsrfToken(string $accessToken): string
    {
        $response = $this->client->get($this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => [
                'action' => 'query',
                'meta' => 'tokens',
                'type' => 'csrf',
                'format' => 'json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['query']['tokens']['csrftoken'])) {
            throw new \RuntimeException('Failed to obtain CSRF token');
        }

        return $data['query']['tokens']['csrftoken'];
    }

    public function searchCategories(string $query, int $limit = 20): array
    {
        $response = $this->client->get($this->apiUrl, [
            'query' => [
                'action' => 'opensearch',
                'search' => $query,
                'namespace' => 14, // Category namespace
                'limit' => $limit,
                'format' => 'json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        // Format: [query, [titles], [descriptions], [urls]]
        if (isset($data[1]) && is_array($data[1])) {
            return array_map(function ($title) {
                // Remove "Category:" prefix
                return str_replace('Category:', '', $title);
            }, $data[1]);
        }

        return [];
    }

    public function validateFile(string $filePath): bool
    {
        $config = require __DIR__ . '/../../config/app.php';
        $allowedTypes = $config['allowed_file_types'];
        $maxSize = $config['max_upload_size'];

        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize > $maxSize) {
            return false;
        }

        // Validate extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes, true)) {
            return false;
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'image/webp',
            'image/tiff',
            'application/pdf'
        ];

        return in_array($mimeType, $allowedMimeTypes, true);
    }
}
