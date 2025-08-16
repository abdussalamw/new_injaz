<?php
declare(strict_types=1);

namespace App\Reports;

use App\Core\RoleHelper;
use DateTime;
use DateInterval;

// --- معالجة الفلاتر والتواريخ ---
$period = $_GET['period'] ?? 'monthly';
$selected_employee = $_GET['stats_employee'] ?? '';
$custom_date = $_GET['custom_date'] ?? date('Y-m-d');

/**
 * دالة لحساب تواريخ البدء والانتهاء بناءً على الفترة المحددة
 * تدعم الأسبوع العربي (السبت - الخميس)
 */
if (!function_exists('calculatePeriodDates')) {
    function calculatePeriodDates(string $period, string $custom_date): array {
        $date = new DateTime($custom_date);
        
        switch ($period) {
            case 'daily':
                return [
                    'start' => $date->format('Y-m-d'),
                    'end' => $date->format('Y-m-d'),
                    'label' => 'إحصائيات يوم ' . $date->format('Y-m-d')
                ];
                
            case 'weekly':
                $dayOfWeek = (int)$date->format('w'); // 0=الأحد, 6=السبت
                $daysToSaturday = ($dayOfWeek === 6) ? 0 : ($dayOfWeek + 1);
                $startDate = (clone $date)->sub(new DateInterval("P{$daysToSaturday}D"));
                $endDate = (clone $startDate)->add(new DateInterval('P5D'));
                
                return [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'label' => 'الأسبوع من ' . $startDate->format('d/m') . ' إلى ' . $endDate->format('d/m/Y')
                ];
                
            case 'monthly':
            default:
                $startDate = new DateTime($date->format('Y-m-01'));
                $endDate = new DateTime($date->format('Y-m-t'));
                
                return [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'label' => 'شهر ' . $date->format('m/Y')
                ];
        }
    }
}

// تحديد التواريخ النهائية المستخدمة في الاستعلامات
$period_data = calculatePeriodDates($period, $custom_date);
$start_date = $period_data['start'];
$end_date = $period_data['end'];
$period_label = $period_data['label'];


// --- الاستعلامات المحسنة ---

/**
 * 1. الاستعلام الرئيسي للإحصائيات العامة
 */
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'مكتمل' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status IN ('قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم') THEN 1 END) as pending_orders,
        SUM(total_amount) as total_revenue,
        SUM(CASE WHEN payment_status = 'مدفوع' THEN total_amount WHEN payment_status = 'مدفوع جزئياً' THEN deposit_amount ELSE 0 END) as paid_amount
    FROM orders 
    WHERE order_date BETWEEN ? AND ?";

$stats_params = [$start_date, $end_date];
$stats_types = "ss";

if (!empty($selected_employee)) {
    $stats_query .= " AND (designer_id = ? OR workshop_id = ?)";
    $stats_params[] = $selected_employee;
    $stats_params[] = $selected_employee;
    $stats_types .= "ii";
}

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param($stats_types, ...$stats_params);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();


/**
 * 2. استعلام إحصائيات الموظفين
 */
$employee_stats_query = "
    SELECT 
        e.employee_id,
        e.name,
        e.role,
        COUNT(DISTINCT o.order_id) as total_tasks,
        COUNT(DISTINCT CASE WHEN o.status = 'مكتمل' THEN o.order_id END) as completed_tasks,
        COUNT(DISTINCT CASE WHEN o.status IN ('قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم') THEN o.order_id END) as active_tasks,
        SUM(o.total_amount) as total_revenue
    FROM employees e
    LEFT JOIN orders o ON (e.employee_id = o.designer_id OR e.employee_id = o.workshop_id)
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY e.employee_id, e.name, e.role
    ORDER BY completed_tasks DESC, total_revenue DESC";

$stmt_emp = $conn->prepare($employee_stats_query);
$stmt_emp->bind_param("ss", $start_date, $end_date);
$stmt_emp->execute();
$employee_stats = $stmt_emp->get_result()->fetch_all(MYSQLI_ASSOC);


/**
 * 3. استعلام أفضل العملاء
 */
$top_clients_query = "
    SELECT 
        c.company_name,
        COUNT(o.order_id) as orders_count,
        SUM(o.total_amount) as total_spent
    FROM clients c
    JOIN orders o ON c.client_id = o.client_id
    WHERE o.order_date BETWEEN ? AND ? AND o.status != 'ملغي'
    GROUP BY c.client_id, c.company_name
    ORDER BY orders_count DESC, total_spent DESC
    LIMIT 5";

$stmt_clients = $conn->prepare($top_clients_query);
$stmt_clients->bind_param("ss", $start_date, $end_date);
$stmt_clients->execute();
$top_clients = $stmt_clients->get_result()->fetch_all(MYSQLI_ASSOC);


/**
 * 4. استعلام أفضل المنتجات
 */
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
    ORDER BY orders_count DESC, total_quantity DESC
    LIMIT 5";

$stmt_products = $conn->prepare($top_products_query);
$stmt_products->bind_param("ss", $start_date, $end_date);
$stmt_products->execute();
$top_products = $stmt_products->get_result()->fetch_all(MYSQLI_ASSOC);


/**
 * 5. جلب قائمة الموظفين للفلتر
 */
$employees_query = "SELECT employee_id, name FROM employees WHERE role IN ('مصمم', 'معمل', 'مدير', 'محاسب') ORDER BY name";
$employees_result = $conn->query($employees_query);
$employees_list = $employees_result->fetch_all(MYSQLI_ASSOC);


// --- تضمين ملف العرض ---
require_once __DIR__ . '/../View/stats_report.php';