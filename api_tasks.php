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
require_once __DIR__ . '/src/Core/Permissions.php';
require_once __DIR__ . '/src/Core/Helpers.php';
require_once __DIR__ . '/src/Core/InitialTasksQuery.php';

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    die('Not an AJAX request.');
}

// Database connection
try {
    $db = new \App\Core\Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!\App\Core\AuthCheck::isLoggedIn($conn)) {
    die('Unauthorized');
}

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_employee = $_GET['employee'] ?? '';
$filter_payment = $_GET['payment'] ?? '';
$filter_search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'latest';

// Fetch tasks
$res = \App\Core\InitialTasksQuery::fetch_tasks($conn, $filter_status, $filter_employee, $filter_payment, $filter_search, $sort_by);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo '<div class="col-md-6 col-lg-4">';
        $task_details = $row;
        $actions = \App\Core\Helpers::get_next_actions($row, $_SESSION['user_role'], $_SESSION['user_id'], $conn, 'dashboard');
        include __DIR__ . '/src/View/task/card.php';
        echo '</div>';
    }
} else {
    echo '<div class="col-12"><div class="alert alert-info text-center">لا توجد مهام تطابق معايير البحث.</div></div>';
}

$conn->close();
?>
