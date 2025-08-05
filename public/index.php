<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// 1. Load base configuration and database connection
require_once __DIR__ . '/../src/Core/config.php';
use App\Core\Database;

$db = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn = $db->getConnection();

// 2. Include header
require_once __DIR__ . '/../src/header.php';

// 3. Routing System
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/new_injaz'; // Define your base path here

if (str_starts_with($request_uri, $base_path)) {
    $request_uri = substr($request_uri, strlen($base_path));
}
if (empty($request_uri)) {
    $request_uri = '/';
}

$request_method = $_SERVER['REQUEST_METHOD'];

// Normalize URI
if ($request_uri !== '/' && str_ends_with($request_uri, '/')) {
    $request_uri = rtrim($request_uri, '/');
}
if (!str_starts_with($request_uri, '/')) {
    $request_uri = '/' . $request_uri;
}

$routes = require __DIR__ . '/../src/routes.php';

$route_found = false;

// Check if the requested URI exists in the routes array
if (array_key_exists($request_uri, $routes)) {
    // Check if the request method is defined for this URI
    if (array_key_exists($request_method, $routes[$request_uri])) {
        $route_config = $routes[$request_uri][$request_method];
        $route_found = true;

        // Handle authentication
        if (isset($route_config['auth']) && $route_config['auth'] === true) {
            if (!\App\Core\AuthCheck::isLoggedIn($conn)) {
                \App\Core\AuthCheck::redirect('/login');
            }
        }

        // Load the appropriate file or controller
        if (isset($route_config['file'])) {
            require_once __DIR__ . '/../' . $route_config['file'];
        } elseif (isset($route_config['controller'])) {
            $controller_class = $route_config['controller'][0];
            $controller_method = $route_config['controller'][1];

            $controller = new $controller_class($conn);
            $controller->$controller_method();
        } elseif ($request_uri === '/login' && $request_method === 'POST') {
            $login = new \App\Auth\Login($conn);
            $login->handle();
        } else {
            http_response_code(500);
            echo "<h1>Internal Server Error: Route definition incomplete.</h1>";
        }
    }
}

if (!$route_found) {
    http_response_code(404);
    // Debug info
    echo "<h1>404 Not Found</h1>";
    echo "<p>The requested URL '{$request_uri}' with method '{$request_method}' was not found on this server.</p>";
    echo "<h3>Debug Info:</h3>";
    echo "<p>Available routes:</p><ul>";
    foreach ($routes as $path => $methods) {
        foreach (array_keys($methods) as $method) {
            echo "<li>{$method} {$path}</li>";
        }
    }
    echo "</ul>";
    echo "<p>Original GET url parameter: " . ($_GET['url'] ?? 'NOT SET') . "</p>";
}

// 4. Include footer
require_once __DIR__ . '/../src/footer.php';
