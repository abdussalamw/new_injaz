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

// --- إضافة معالجة فلاتر الشهور والأسابيع ---
$selected_matrix_month = $_GET['matrix_month'] ?? '';
$selected_week = $_GET['week'] ?? '';

// تجهيز قائمة الأشهر المتاحة (آخر 12 شهر حسب البيانات + بعض الأشهر الإضافية)
$matrix_available_months = [];

// جلب الأشهر من قاعدة البيانات
$months_res = $conn->query("SELECT DISTINCT DATE_FORMAT(order_date,'%Y-%m') AS ym FROM orders ORDER BY ym DESC LIMIT 12");
if ($months_res) { 
    $matrix_available_months = array_column($months_res->fetch_all(MYSQLI_ASSOC), 'ym'); 
}

// إضافة الأشهر الحالية والسابقة (للتأكد من وجود خيارات كافية)
for ($i = 0; $i < 6; $i++) {
    $month_to_add = date('Y-m', strtotime("-$i months"));
    if (!in_array($month_to_add, $matrix_available_months)) {
        $matrix_available_months[] = $month_to_add;
    }
}

// ترتيب الأشهر تنازلياً (الأحدث أولاً)
rsort($matrix_available_months);

// إذا لم تكن هناك أشهر متاحة، أضف الشهر الحالي
if (empty($matrix_available_months)) {
    $matrix_available_months[] = date('Y-m');
}

// إذا لم يتم اختيار شهر، استخدم الشهر الحالي (افتراضي)
if (empty($selected_matrix_month)) { 
    $selected_matrix_month = date('Y-m'); 
}

// تحقق من صحة تنسيق الشهر المُرسل
if (!preg_match('/^\\d{4}-\\d{2}$/', $selected_matrix_month)) { 
    $selected_matrix_month = date('Y-m'); 
}

// أضف الشهر المختار للقائمة إذا لم يكن موجوداً
if (!in_array($selected_matrix_month, $matrix_available_months, true)) { 
    $matrix_available_months[] = $selected_matrix_month;
    rsort($matrix_available_months); // إعادة ترتيب
}

// --- معالجة فلتر الأسبوع ---
$weeks_list = [];

// إنشاء قائمة أسابيع الشهر المختار
if (!empty($selected_matrix_month)) {
    $year = (int)substr($selected_matrix_month, 0, 4);
    $month = (int)substr($selected_matrix_month, 5, 2);
    
    // أول يوم في الشهر وآخر يوم
    $first_day = new DateTime("$year-$month-01");
    $last_day = new DateTime($first_day->format('Y-m-t'));
    
    // إنشاء الأسابيع
    $current = clone $first_day;
    $week_number = 1;
    
    while ($current <= $last_day && $week_number <= 6) {
        // بداية الأسبوع
        $week_start = clone $current;
        
        // نهاية الأسبوع (6 أيام من البداية أو آخر يوم في الشهر)
        $week_end = clone $week_start;
        $week_end->modify('+6 days');
        if ($week_end > $last_day) {
            $week_end = clone $last_day;
        }
        
        // تأكد أن بداية الأسبوع لا تتجاوز نهاية الشهر
        if ($week_start > $last_day) {
            break;
        }
        
        $weeks_list[] = [
            'number' => $week_number,
            'start' => $week_start->format('Y-m-d'),
            'end' => $week_end->format('Y-m-d'),
            'label' => 'الأسبوع ' . $week_number . ' (' . $week_start->format('j') . '-' . $week_end->format('j') . ' ' . $first_day->format('M') . ')'
        ];
        
        // الانتقال للأسبوع التالي
        $current = clone $week_end;
        $current->modify('+1 day');
        $week_number++;
    }
}

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

// في وضع الإحصاءات: المطلوب أن ترتبط جميع البطاقات والجدول بفلترة الشهر المختار
// إذا وُجد selected_matrix_month صالح نلغي أي فترة أخرى ونستخدم حدوده الشهرية
if (!empty($selected_matrix_month) && preg_match('/^\d{4}-\d{2}$/', $selected_matrix_month)) {
    $start_date = $selected_matrix_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $period_label = 'شهر ' . date('m/Y', strtotime($start_date));
}


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

// ====== بناء مصفوفة الشهر التفصيلية (compact matrix) ======
$compact_columns = [];
$compact_matrix = [];
$compact_totals = [];
$is_current_month = false;
$current_week_row = '';

if (!empty($selected_matrix_month)) {
    $matrix_month_start = $selected_matrix_month . '-01';
    $matrix_month_end = date('Y-m-t', strtotime($matrix_month_start));

    $active_orders_stmt = $conn->prepare("SELECT order_id, order_date, status, designer_id, workshop_id, created_by FROM orders WHERE status NOT IN ('مكتمل','ملغي') AND order_date BETWEEN ? AND ?");
    if ($active_orders_stmt) {
        $active_orders_stmt->bind_param('ss', $matrix_month_start, $matrix_month_end);
        $active_orders_stmt->execute();
        $active_orders = $active_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else { $active_orders = []; }

    $month_orders_stmt = $conn->prepare("SELECT order_id, order_date, status, designer_id, workshop_id, created_by FROM orders WHERE order_date BETWEEN ? AND ? AND status != 'ملغي'");
    if ($month_orders_stmt) {
        $month_orders_stmt->bind_param('ss', $matrix_month_start, $matrix_month_end);
        $month_orders_stmt->execute();
        $month_orders = $month_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else { $month_orders = []; }

    $today = new DateTime();
    $is_current_month = ($today->format('Y-m') === $selected_matrix_month);
    if ($is_current_month) {
        $today_day = (int)$today->format('j');
        if ($today_day <= 7) { $current_week_row = 'الأسبوع الأول'; }
        elseif ($today_day <= 14) { $current_week_row = 'الأسبوع الثاني'; }
        elseif ($today_day <= 21) { $current_week_row = 'الأسبوع الثالث'; }
        elseif ($today_day <= 28) { $current_week_row = 'الأسبوع الرابع'; }
        else { $current_week_row = 'الأسبوع الخامس'; }
    }

    $col_res = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم','معمل','مدير','محاسب') ORDER BY role, name");
    if ($col_res) {
        while ($emp = $col_res->fetch_assoc()) {
            $compact_columns[$emp['employee_id']] = [ 'label' => $emp['name'], 'role' => $emp['role'] ];
        }
    }

    if (!function_exists('compact_order_matches_employee')) {
        function compact_order_matches_employee(array $order, int $empId, string $role): bool {
            if ($role === 'مصمم') { return (int)($order['designer_id'] ?? 0) === $empId; }
            if ($role === 'معمل' || $role === 'معمل التنفيذ' || $role === 'المعمل التنفيذي') { return (int)($order['workshop_id'] ?? 0) === $empId; }
            if ($role === 'مدير' || $role === 'محاسب') { return (int)($order['designer_id'] ?? 0) === $empId || (int)($order['workshop_id'] ?? 0) === $empId || (int)($order['created_by'] ?? 0) === $empId; }
            return false;
        }
    }

    $compact_matrix = [ 'نشطة' => [] ];
    $compact_matrix['الأسبوع الأول'] = [];
    $compact_matrix['الأسبوع الثاني'] = [];
    $compact_matrix['الأسبوع الثالث'] = [];
    $days_in_month = (int)date('t', strtotime($matrix_month_start));
    if ($days_in_month >= 22) { $compact_matrix['الأسبوع الرابع'] = []; }
    if ($days_in_month >= 29) { $compact_matrix['الأسبوع الخامس'] = []; }
    $compact_matrix['الشهر'] = [];

    foreach ($active_orders as $o) {
        foreach ($compact_columns as $empId => $meta) {
            if (compact_order_matches_employee($o, (int)$empId, $meta['role'])) {
                $compact_matrix['نشطة'][$empId] = ($compact_matrix['نشطة'][$empId] ?? 0) + 1;
            }
        }
    }

    foreach ($month_orders as $o) {
        $od = $o['order_date'];
        $dayInMonth = (int)date('j', strtotime($od));
        $rowKeys = ['الشهر'];
        if ($dayInMonth <= 7) { $rowKeys[] = 'الأسبوع الأول'; }
        elseif ($dayInMonth <= 14) { $rowKeys[] = 'الأسبوع الثاني'; }
        elseif ($dayInMonth <= 21) { $rowKeys[] = 'الأسبوع الثالث'; }
        elseif ($dayInMonth <= 28) { if (isset($compact_matrix['الأسبوع الرابع'])) $rowKeys[] = 'الأسبوع الرابع'; }
        else { if (isset($compact_matrix['الأسبوع الخامس'])) $rowKeys[] = 'الأسبوع الخامس'; }

        foreach ($rowKeys as $rk) {
            foreach ($compact_columns as $empId => $meta) {
                if (compact_order_matches_employee($o, (int)$empId, $meta['role'])) {
                    $compact_matrix[$rk][$empId] = ($compact_matrix[$rk][$empId] ?? 0) + 1;
                }
            }
        }
    }

    $empHasAny = [];
    foreach ($compact_matrix as $row => $vals) {
        foreach ($vals as $empId => $cnt) { if ($cnt > 0) { $empHasAny[$empId] = true; } }
    }
    foreach (array_keys($compact_columns) as $empId) { if (!isset($empHasAny[$empId])) { unset($compact_columns[$empId]); } }

    foreach ($compact_matrix as $row => $vals) {
        $sum = 0; foreach ($compact_columns as $empId => $_meta) { $sum += $vals[$empId] ?? 0; }
        $compact_totals[$row] = $sum;
    }
}

// ====== نهاية بناء المصفوفة ======

// --- تضمين ملف العرض ---
require_once __DIR__ . '/../View/stats_report.php';