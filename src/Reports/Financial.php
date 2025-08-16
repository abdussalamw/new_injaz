<?php
declare(strict_types=1);

namespace App\Reports;

use DateTime;

// Note: The router handles session_start, db_connection, and auth checks.

$start_date = $_GET['report_start_date'] ?? date('Y-m-01');
$end_date = $_GET['report_end_date'] ?? date('Y-m-t');
$selected_client = $_GET['report_client'] ?? '';
$payment_status_filter = $_GET['report_payment_status'] ?? '';

if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

$clients_query = "SELECT client_id, company_name FROM clients ORDER BY company_name";
$clients_result = $conn->query($clients_query);
$clients_list = $clients_result->fetch_all(MYSQLI_ASSOC);

$base_query = "
    SELECT 
        o.order_id,
        o.order_date,
        o.due_date,
        o.status,
        o.total_amount,
        o.deposit_amount,
        o.payment_status,
        o.notes,
        c.company_name,
        c.phone,
        c.email,
        c.client_id,
        e.name as designer_name,
        COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
    FROM orders o
    JOIN clients c ON o.client_id = c.client_id
    LEFT JOIN employees e ON o.designer_id = e.employee_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_date BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

if (!empty($selected_client)) {
    $base_query .= " AND o.client_id = ?";
    $params[] = $selected_client;
    $types .= "i";
}

if (!empty($payment_status_filter)) {
    $base_query .= " AND o.payment_status = ?";
    $params[] = $payment_status_filter;
    $types .= "s";
}

$base_query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $conn->prepare($base_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

$financial_summary = [
    'total_orders' => count($orders),
    'total_amount' => 0,
    'total_deposits' => 0,
    'total_remaining' => 0,
    'paid_orders' => 0,
    'partially_paid_orders' => 0,
    'unpaid_orders' => 0
];

foreach ($orders as $order) {
    $financial_summary['total_amount'] += $order['total_amount'];
    $financial_summary['total_deposits'] += $order['deposit_amount'] ?? 0;
    $financial_summary['total_remaining'] += ($order['total_amount'] - ($order['deposit_amount'] ?? 0));
    
    switch ($order['payment_status']) {
        case 'مدفوع':
            $financial_summary['paid_orders']++;
            break;
        case 'مدفوع جزئياً':
            $financial_summary['partially_paid_orders']++;
            break;
        case 'غير مدفوع':
            $financial_summary['unpaid_orders']++;
            break;
    }
}

$client_stats = [];
if (empty($selected_client)) {
    $client_stats_query = "
        SELECT 
            c.client_id,
            c.company_name,
            c.phone,
            COUNT(o.order_id) as total_orders,
            SUM(o.total_amount) as total_amount,
            SUM(o.deposit_amount) as total_deposits,
            SUM(o.total_amount - COALESCE(o.deposit_amount, 0)) as remaining_balance,
            COUNT(CASE WHEN o.payment_status = 'مدفوع' THEN 1 END) as paid_orders,
            COUNT(CASE WHEN o.payment_status = 'مدفوع جزئياً' THEN 1 END) as partial_orders,
            COUNT(CASE WHEN o.payment_status = 'غير مدفوع' THEN 1 END) as unpaid_orders
        FROM clients c
        LEFT JOIN orders o ON c.client_id = o.client_id AND o.order_date BETWEEN ? AND ?
        GROUP BY c.client_id, c.company_name, c.phone
        HAVING total_orders > 0
        ORDER BY remaining_balance DESC";
    
    $stmt_clients = $conn->prepare($client_stats_query);
    $stmt_clients->bind_param("ss", $start_date, $end_date);
    $stmt_clients->execute();
    $client_stats = $stmt_clients->get_result()->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/../View/financial_report.php';