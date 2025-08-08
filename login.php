<?php
declare(strict_types=1);

// Start session first
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/vendor/autoload.php'; // Load Composer's autoloader
// Manual environment variable loading since Dotenv is not available
$env_file = __DIR__ . "/.env";
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), "#") === 0) {
            continue;
        }

        list($name, $value) = explode("=", $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
require_once __DIR__ . '/src/Auth/Login.php';

// Database connection
try {
    $db = new \App\Core\Database(
        $_ENV['DB_HOST'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        $_ENV['DB_NAME']
    );
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is already logged in
if (\App\Core\AuthCheck::isLoggedIn($conn)) {
    header('Location: /new_injaz/dashboard.php');
    exit;
}

// Handle login
$login = new \App\Auth\Login($conn);
$request_method = $_SERVER['REQUEST_METHOD'];

if ($request_method === 'POST') {
    $login->handle();
} else {
    $login->show();
}
?>
