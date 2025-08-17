<?php
declare(strict_types=1);

namespace App\Reports;

use App\Core\Permissions;
use App\Core\RoleBasedQuery;
use App\Core\RoleHelper;
use DateTime;

// Note: The router handles session_start, db_connection, and auth checks.

$page_title = 'الجدول الزمني للمراحل';
require_once __DIR__ . '/../header.php';

if (!Permissions::has_permission('dashboard_reports_view', $conn) && !Permissions::has_permission('order_view_own', $conn)) {
    header('Location: /');
    exit;
}

$user_id = RoleHelper::getCurrentUserId();
$user_role = RoleHelper::getCurrentUserRole();

$employees = [];
if (Permissions::has_permission('dashboard_reports_view', $conn)) {
    $emp_result = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'معمل') ORDER BY name");
    while ($emp_row = $emp_result->fetch_assoc()) {
        $employees[] = $emp_row;
    }
}

// فلتر رقم الموظف (تنقية لحل مشكلة محتملة على الاستضافة إذا أُرسل معرّف يحتوي محارف مخفية)
$filter_employee_raw = $_GET['employee'] ?? '';
if (is_string($filter_employee_raw)) {
    $filter_employee_raw = trim($filter_employee_raw);
}
// نقبل أرقام صحيحة فقط، وأي شيء آخر يُهمل حتى لا يرتبط الاستعلام بقيمة 0 خطأً
if ($filter_employee_raw !== '' && preg_match('/^\d+$/u', $filter_employee_raw)) {
    $filter_employee = (int)$filter_employee_raw; // قيمة رقمية نظيفة
} else {
    $filter_employee = ''; // لا يوجد فلتر
}

// فلترة بسيطة للخط الزمني - يظهر جميع الطلبات (مكتملة وغير مكتملة)
$where_clauses = [];
$params = [];
$types = "";

if (Permissions::has_permission('dashboard_reports_view', $conn)) {
    // للمديرين - يمكنهم فلترة بأي موظف
    if ($filter_employee !== '') { // استخدمنا !== للحفاظ على تمييز 0 عن الفارغ
        // البحث في المصمم أو المعمل
        $where_clauses[] = "(o.designer_id = ? OR o.workshop_id = ?)";
        $params[] = (int)$filter_employee;
        $params[] = (int)$filter_employee;
        $types .= "ii";
    }
} else {
    // للموظفين - يرون طلباتهم فقط
    if (\App\Core\RoleHelper::isWorkshop()) {
        $where_clauses[] = "o.workshop_id = ?";
        $params[] = $user_id;
        $types .= "i";
    } else {
        $where_clauses[] = "o.designer_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
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
               o.designer_id, o.workshop_id, -- إضافة IDs للتشخيص
               o.total_amount, o.deposit_amount, o.payment_status,
               COALESCE(GROUP_CONCAT(DISTINCT p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id
        LEFT JOIN employees w ON o.workshop_id = w.employee_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        $where_clause
        GROUP BY o.order_id, o.order_date, o.status, o.design_completed_at, o.execution_completed_at,
                 o.delivered_at, o.design_rating, o.execution_rating, c.company_name,
                 e.name, w.name, o.designer_id, o.workshop_id,
                 o.total_amount, o.deposit_amount, o.payment_status
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Define base_path for JavaScript in timeline_report.php  
$base_path = rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/');

require_once __DIR__ . '/../View/timeline_report.php';

require_once __DIR__ . '/../footer.php';
