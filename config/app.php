<?php

return [
    'name' => 'CommonsEventUploader',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'key' => $_ENV['APP_KEY'] ?? '',
    'timezone' => 'UTC',
    'locale' => 'en',
    'max_upload_size' => 100 * 1024 * 1024, // 100MB
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'tiff', 'pdf'],
    'commons_api_url' => 'https://commons.wikimedia.org/w/api.php',
];
