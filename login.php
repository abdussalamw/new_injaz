<?php
declare(strict_types=1);

// Start session first
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/src/Core/config.php';
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
require_once __DIR__ . '/src/Auth/Login.php';

// Database connection
try {
    $db = new \App\Core\Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
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
