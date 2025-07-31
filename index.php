<?php
declare(strict_types=1);

// Start session
session_start();

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
// Note: You would use a library like Dotenv in a real project
// For now, we'll manually include the config.
require_once __DIR__ . '/src/Core/config.php';

// Database Connection
$dbConnection = new \App\Core\Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn = $dbConnection->getConnection();

// Simple Router
$requestUri = strtok($_SERVER["REQUEST_URI"], '?');
$requestMethod = $_SERVER["REQUEST_METHOD"];

// Define routes
$routes = require_once __DIR__ . '/src/routes.php';

// Match route
$handler = $routes[$requestUri] ?? null;

if ($handler) {
    // Check for authentication if required
    if (isset($handler['auth']) && $handler['auth'] === true) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    // Load the appropriate controller or view
    if (isset($handler['file'])) {
        require_once __DIR__ . '/' . $handler['file'];
    } elseif (isset($handler['controller'])) {
        list($controllerClass, $method) = $handler['controller'];
        $controller = new $controllerClass($conn);
        $controller->$method();
    }
} else {
    // Handle 404 Not Found
    http_response_code(404);
    echo "Page not found";
}