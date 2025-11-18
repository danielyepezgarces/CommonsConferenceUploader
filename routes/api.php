<?php

/**
 * API Routes for CommonsEventUploader
 * 
 * All routes return JSON responses
 */

$routes = [
    // Authentication routes
    'GET /auth/login' => ['AuthController', 'login'],
    'GET /auth/callback' => ['AuthController', 'callback'],
    'POST /auth/logout' => ['AuthController', 'logout'],
    'GET /auth/me' => ['AuthController', 'me'],

    // Event routes
    'GET /events' => ['EventController', 'index'],
    'GET /events/{id}' => ['EventController', 'show'],
    'POST /events' => ['EventController', 'create'],
    'PUT /events/{id}' => ['EventController', 'update'],
    'DELETE /events/{id}' => ['EventController', 'delete'],
    'GET /events/{id}/stats' => ['EventController', 'statistics'],

    // Upload routes
    'POST /events/{id}/upload' => ['UploadController', 'upload'],
    'GET /events/{id}/uploads' => ['UploadController', 'list'],
    'GET /uploads/my' => ['UploadController', 'myUploads'],

    // Category routes
    'GET /categories' => ['CategoryController', 'index'],
    'GET /categories/search' => ['CategoryController', 'search'],
    'GET /categories/top' => ['CategoryController', 'top'],
    'GET /categories/{id}' => ['CategoryController', 'show'],
    'POST /categories' => ['CategoryController', 'create'],
    'PUT /categories/{id}' => ['CategoryController', 'update'],
    'DELETE /categories/{id}' => ['CategoryController', 'delete'],

    // Statistics routes
    'GET /stats/user' => ['StatsController', 'user'],
    'GET /stats/global' => ['StatsController', 'global'],
];

return $routes;
