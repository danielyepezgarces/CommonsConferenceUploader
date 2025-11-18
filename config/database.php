<?php

return [
    'host' => $_ENV['DB_HOST'] ?? getLocalSetting('db_host', 'localhost'),
    'port' => $_ENV['DB_PORT'] ?? getLocalSetting('db_port', '3306'),
    'database' => $_ENV['DB_DATABASE'] ?? getLocalSetting('db_database', 'commons_uploader'),
    'username' => $_ENV['DB_USERNAME'] ?? getLocalSetting('db_username', 'root'),
    'password' => $_ENV['DB_PASSWORD'] ?? getLocalSetting('db_password', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
