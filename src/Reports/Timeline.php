<?php
declare(strict_types=1);

namespace App\Reports;

use App\Core\Permissions;
use DateTime;

// Note: The router handles session_start, db_connection, and auth checks.

$page_title = 'الجدول الزمني للمراحل';
require_once __DIR__ . '/../header.php';

if (!Permissions::has_permission('dashboard_reports_view', $conn) && !Permissions::has_permission('order_view_own', $conn)) {
    header('Location: /');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? 'guest';

$employees = [];
if (Permissions::has_permission('dashboard_reports_view', $conn)) {
    $emp_result = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'معمل') ORDER BY name");
    while ($emp_row = $emp_result->fetch_assoc()) {
        $employees[] = $emp_row;
    }
}

$filter_employee = $_GET['employee'] ?? '';

$where_clauses = [];
$params = [];
$types = "";

if (Permissions::has_permission('dashboard_reports_view', $conn)) {
    if (!empty($filter_employee)) {
        $stmt_emp_role = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
        $stmt_emp_role->bind_param("i", $filter_employee);
        $stmt_emp_role->execute();
        $result_emp_role = $stmt_emp_role->get_result();
        $employee_role = $result_emp_role->fetch_assoc()['role'] ?? '';

        if ($employee_role === 'معمل') {
            $where_clauses[] = "o.workshop_id = ?";
        } else {
            $where_clauses[] = "o.designer_id = ?";
        }
        $params[] = $filter_employee;
        $types .= "i";
    }
} else {
    if ($user_role === 'معمل') {
        $where_clauses[] = "o.workshop_id = ?";
    } else {
        $where_clauses[] = "o.designer_id = ?";
    }
    $params[] = $user_id;
    $types .= "i";
}

// استخدام الدوال من Helpers class
use App\Core\Helpers;

$where_clause = "";
if (!empty($where_clauses)) {
    $where_clause = "WHERE " . implode(" AND ", $where_clauses);
}

$sql = "SELECT o.order_id, o.order_date, o.status, o.design_completed_at, o.execution_completed_at,
               o.delivered_at, o.design_rating, o.execution_rating, c.company_name as client_name,
               e.name as designer_name, w.name as workshop_name,
               o.total_amount, o.deposit_amount, o.payment_status,
               COALESCE(GROUP_CONCAT(DISTINCT p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id
        LEFT JOIN employees w ON o.workshop_id = w.employee_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        $where_clause
        GROUP BY o.order_id
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

require_once __DIR__ . '/../View/timeline_report.php';

require_once __DIR__ . '/../footer.php';
