<?php

/**
 * CommonsEventUploader - Main Entry Point
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Error handling
$config = require __DIR__ . '/../config/app.php';
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set default timezone
date_default_timezone_set($config['timezone']);

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Load routes
$routes = require __DIR__ . '/../routes/api.php';

// Simple router
function matchRoute($routes, $method, $path) {
    foreach ($routes as $routePattern => $handler) {
        list($routeMethod, $routePath) = explode(' ', $routePattern, 2);
        
        if ($routeMethod !== $method) {
            continue;
        }

        // Convert route pattern to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return [$handler, $params];
        }
    }

    return null;
}

$match = matchRoute($routes, $method, $path);

if ($match === null) {
    // Serve static files or show 404
    if ($path === '/' || $path === '') {
        header('Content-Type: text/html');
        echo file_get_contents(__DIR__ . '/../resources/views/index.html');
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found']);
    exit;
}

list($handler, $params) = $match;
list($controllerName, $methodName) = $handler;

// Instantiate controller and call method
$controllerClass = "App\\Http\\Controllers\\{$controllerName}";
$controller = new $controllerClass();

// Convert string params to integers where appropriate
$reflectionMethod = new ReflectionMethod($controller, $methodName);
$reflectionParams = $reflectionMethod->getParameters();
$args = [];

foreach ($reflectionParams as $reflectionParam) {
    $paramName = $reflectionParam->getName();
    if (isset($params[$paramName])) {
        $value = $params[$paramName];
        // Cast to int if parameter type is int
        if ($reflectionParam->hasType()) {
            $type = $reflectionParam->getType();
            if ($type && $type->getName() === 'int') {
                $value = (int) $value;
            }
        }
        $args[] = $value;
    }
}

call_user_func_array([$controller, $methodName], $args);
