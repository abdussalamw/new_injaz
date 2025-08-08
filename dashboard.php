<?php
declare(strict_types=1);

// Start session first
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/vendor/autoload.php'; // Load Composer's autoloader
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->load();

require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
require_once __DIR__ . '/src/Core/Permissions.php';
require_once __DIR__ . '/src/Core/Helpers.php';
require_once __DIR__ . '/src/Core/InitialTasksQuery.php';

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

// Check if user is logged in
if (!\App\Core\AuthCheck::isLoggedIn($conn)) {
    header('Location: /new_injaz/login.php');
    exit;
}

// Load dashboard
require_once __DIR__ . '/src/header.php';
require_once __DIR__ . '/src/View/dashboard.php';
require_once __DIR__ . '/src/footer.php';
?>
