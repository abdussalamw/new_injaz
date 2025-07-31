<?php
declare(strict_types=1);

namespace App\Reports;

use DateTime;

// Note: The router handles session_start, db_connection, and auth checks.

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$selected_employee = $_GET['stats_employee'] ?? '';

if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'مكتمل' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status IN ('قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم') THEN 1 END) as active_orders,
        COUNT(CASE WHEN status = 'ملغي' THEN 1 END) as cancelled_orders,
        SUM(total_amount) as total_revenue,
        SUM(deposit_amount) as total_deposits,
        SUM(total_amount - COALESCE(deposit_amount, 0)) as remaining_amounts
    FROM orders 
    WHERE order_date BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

$employee_role = '';
if (!empty($selected_employee)) {
    $role_stmt = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
    $role_stmt->bind_param("i", $selected_employee);
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();
    if ($role_row = $role_result->fetch_assoc()) {
        $employee_role = $role_row['role'];
    }
    $role_stmt->close();
}

if (!empty($selected_employee)) {
    switch ($employee_role) {
        case 'مصمم':
        case 'مدير':
            $stats_query .= " AND designer_id = ?";
            break;
        case 'معمل':
            $stats_query .= " AND workshop_id = ?";
            break;
        case 'محاسب':
            break;
    }
    
    if (in_array($employee_role, ['مصمم', 'مدير', 'معمل'])) {
        $params[] = $selected_employee;
        $types .= "i";
    }
}

$stmt = $conn->prepare($stats_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$employee_stats_query = "
    SELECT 
        e.employee_id,
        e.name,
        e.role,
        COUNT(DISTINCT CASE 
            WHEN e.role IN ('مصمم', 'مدير') AND o.designer_id = e.employee_id THEN o.order_id
            WHEN e.role = 'معمل' AND o.workshop_id = e.employee_id THEN o.order_id
            WHEN e.role = 'محاسب' THEN o.order_id
        END) as total_tasks,
        COUNT(DISTINCT CASE 
            WHEN e.role = 'مصمم' AND o.designer_id = e.employee_id AND o.status IN ('قيد التنفيذ', 'جاهز للتسليم', 'مكتمل') THEN o.order_id
            WHEN e.role = 'مدير' AND o.designer_id = e.employee_id AND o.status = 'مكتمل' AND o.payment_status = 'مدفوع' THEN o.order_id
            WHEN e.role = 'معمل' AND o.workshop_id = e.employee_id AND o.status = 'مكتمل' THEN o.order_id
            WHEN e.role = 'محاسب' AND o.payment_status = 'مدفوع' THEN o.order_id
        END) as completed_tasks,
        COUNT(DISTINCT CASE 
            WHEN e.role = 'مصمم' AND o.designer_id = e.employee_id AND o.status = 'قيد التصميم' THEN o.order_id
            WHEN e.role = 'مدير' AND o.designer_id = e.employee_id AND (o.status != 'مكتمل' OR o.payment_status != 'مدفوع') THEN o.order_id
            WHEN e.role = 'معمل' AND o.workshop_id = e.employee_id AND o.status IN ('قيد التنفيذ', 'جاهز للتسليم') THEN o.order_id
            WHEN e.role = 'محاسب' AND o.payment_status IN ('غير مدفوع', 'مدفوع جزئياً') THEN o.order_id
        END) as active_tasks,
        SUM(DISTINCT CASE 
            WHEN e.role IN ('مصمم', 'مدير') AND o.designer_id = e.employee_id THEN o.total_amount
            WHEN e.role = 'معمل' AND o.workshop_id = e.employee_id THEN o.total_amount
            WHEN e.role = 'محاسب' THEN o.total_amount
            ELSE 0
        END) as total_revenue
    FROM employees e
    LEFT JOIN orders o ON (
        (e.role IN ('مصمم', 'مدير') AND o.designer_id = e.employee_id) OR
        (e.role = 'معمل' AND o.workshop_id = e.employee_id) OR
        (e.role = 'محاسب')
    ) AND o.order_date BETWEEN ? AND ?
    WHERE e.role IN ('مصمم', 'معمل', 'محاسب', 'مدير')";

$emp_params = [$start_date, $end_date];
$emp_types = "ss";

if (!empty($selected_employee)) {
    $employee_stats_query .= " AND e.employee_id = ?";
    $emp_params[] = $selected_employee;
    $emp_types .= "i";
}

$employee_stats_query .= "
    GROUP BY e.employee_id, e.name, e.role
    ORDER BY completed_tasks DESC";

$stmt_emp = $conn->prepare($employee_stats_query);
$stmt_emp->bind_param($emp_types, ...$emp_params);
$stmt_emp->execute();
$employee_stats = $stmt_emp->get_result()->fetch_all(MYSQLI_ASSOC);

$top_clients_query = "
    SELECT 
        c.company_name,
        COUNT(o.order_id) as orders_count,
        SUM(o.total_amount) as total_spent
    FROM clients c
    JOIN orders o ON c.client_id = o.client_id
    WHERE o.order_date BETWEEN ? AND ? AND o.status != 'ملغي'
    GROUP BY c.client_id, c.company_name
    ORDER BY orders_count DESC
    LIMIT 10";

$stmt_clients = $conn->prepare($top_clients_query);
$stmt_clients->bind_param("ss", $start_date, $end_date);
$stmt_clients->execute();
$top_clients = $stmt_clients->get_result()->fetch_all(MYSQLI_ASSOC);

$top_products_query = "
    SELECT 
        p.name,
        COUNT(oi.product_id) as orders_count,
        SUM(oi.quantity) as total_quantity
    FROM products p
    JOIN order_items oi ON p.product_id = oi.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_date BETWEEN ? AND ? AND o.status != 'ملغي'
    GROUP BY p.product_id, p.name
    ORDER BY orders_count DESC
    LIMIT 10";

$stmt_products = $conn->prepare($top_products_query);
$stmt_products->bind_param("ss", $start_date, $end_date);
$stmt_products->execute();
$top_products = $stmt_products->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../View/stats_report.php';