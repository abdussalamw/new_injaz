<?php
declare(strict_types=1);

namespace App\Api;

use App\Core\InitialTasksQuery;
use App\Core\Helpers;

// Note: The router handles session_start, db_connection, and auth checks.

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    die('Not an AJAX request.');
}

$filter_status = $_GET['status'] ?? '';
// تنقية معرّف الموظف لمنع محارف مخفية على الاستضافة تؤدي لتفسيره كـ 0
$raw_employee = $_GET['employee'] ?? '';
if (is_string($raw_employee)) { $raw_employee = trim($raw_employee); }
if ($raw_employee !== '' && preg_match('/^\d+$/u', $raw_employee)) {
    $filter_employee = $raw_employee; // قيمة رقمية سليمة كنص
} else {
    $filter_employee = ''; // إهمال أي قيمة غير رقمية صافية
}
$filter_payment = $_GET['payment'] ?? '';
$filter_search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'latest';

$res = InitialTasksQuery::fetch_tasks($conn, $filter_status, $filter_employee, $filter_payment, $filter_search, $sort_by);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo '<div class="col-md-6 col-lg-4">';
        $task_details = $row;
        $actions = Helpers::get_next_actions($row, \App\Core\RoleHelper::getCurrentUserRole(), \App\Core\RoleHelper::getCurrentUserId(), $conn, 'dashboard');
        include __DIR__ . '/../View/task/card.php';
        echo '</div>';
    }
} else {
    echo '<div class="col-12"><div class="alert alert-info text-center">لا توجد مهام تطابق معايير البحث.</div></div>';
}

$conn->close();
