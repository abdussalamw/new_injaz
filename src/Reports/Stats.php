<?php
declare(strict_types=1);

namespace App\Reports;

use App\Core\RoleHelper;
use App\Core\UnifiedRoleLogic;
use DateTime;
use DateInterval;

// --- معالجة الفلاتر ---
$selected_employee = $_GET['stats_employee'] ?? '';

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

// الآن نحدد الشهر المختار - بعد تجهيز القائمة
$selected_matrix_month = $_GET['matrix_month'] ?? '';

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
$selected_week = $_GET['week'] ?? '';
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

// تحديد نطاق التواريخ للإحصائيات العامة
if (!empty($selected_week) && !empty($weeks_list)) {
    // البحث عن الأسبوع المختار
    $selected_week_data = null;
    foreach ($weeks_list as $week) {
        if ($week['number'] == $selected_week) {
            $selected_week_data = $week;
            break;
        }
    }
    
    if ($selected_week_data) {
        // فلترة حسب الأسبوع المختار
        $start_date = $selected_week_data['start'];
        $end_date = $selected_week_data['end'];
        $period_label = $selected_week_data['label'] . ' - شهر ' . substr($selected_matrix_month,5,2) . '/' . substr($selected_matrix_month,0,4);
    } else {
        // إذا لم يوجد الأسبوع، استخدم كامل الشهر
        $start_date = $selected_matrix_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        $period_label = 'شهر ' . substr($selected_matrix_month,5,2) . '/' . substr($selected_matrix_month,0,4);
    }
} else {
    // كامل الشهر (لم يتم اختيار أسبوع)
    $start_date = $selected_matrix_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $period_label = 'شهر ' . substr($selected_matrix_month,5,2) . '/' . substr($selected_matrix_month,0,4);
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
 * 2. استعلام إحصائيات الموظفين (منطق موحد محسن)
 */
$employee_stats_query = UnifiedRoleLogic::buildEmployeeStatsQuery($start_date, $end_date);

$stmt_emp = $conn->prepare($employee_stats_query);
$stmt_emp->bind_param("ss", $start_date, $end_date);
$stmt_emp->execute();
$employee_stats = $stmt_emp->get_result()->fetch_all(MYSQLI_ASSOC);

// تشخيص تفصيلي للموظف (إذا طُلب)
if (isset($_GET['debug_employee']) && !empty($_GET['debug_employee'])) {
    $debug_emp_id = (int)$_GET['debug_employee'];
    
    echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px; border: 2px solid #007cba; border-radius: 5px;'>";
    echo "<h4 style='color: #007cba;'>🔍 تشخيص مفصل للموظف:</h4>";
    
    // جلب تفاصيل الموظف
    $emp_name_query = "SELECT name, role FROM employees WHERE employee_id = ?";
    $emp_stmt = $conn->prepare($emp_name_query);
    $emp_stmt->bind_param("i", $debug_emp_id);
    $emp_stmt->execute();
    $emp_info = $emp_stmt->get_result()->fetch_assoc();
    
    if ($emp_info) {
        echo "<strong>الموظف:</strong> " . htmlspecialchars($emp_info['name']) . " (" . htmlspecialchars($emp_info['role']) . ")<br>";
        echo "<strong>الفترة:</strong> " . htmlspecialchars($period_label) . "<br>";
        
        // إظهار منطق الدور الموحد
        $role_info = UnifiedRoleLogic::getRoleInfo($emp_info['role']);
        
        echo "<strong>نوع المهام:</strong> " . htmlspecialchars($role_info['relationship_description']) . "<br>";
        echo "<strong>منطق المهام المكتملة:</strong> " . htmlspecialchars($role_info['completed_tasks_description']) . "<br>";
        echo "<strong>منطق المهام النشطة:</strong> " . htmlspecialchars($role_info['active_tasks_description']) . "<br>";
        echo "<hr>";
        
        // جلب الطلبات التفصيلية
        $debug_query = "SELECT order_id, status, designer_id, workshop_id, created_by, 
                              CASE 
                                WHEN designer_id = ? THEN 'مصمم'
                                WHEN workshop_id = ? THEN 'ورشة'  
                                WHEN created_by = ? THEN 'منشئ'
                                ELSE 'غير محدد'
                              END as role_type
                       FROM orders 
                       WHERE (designer_id = ? OR workshop_id = ? OR created_by = ?)
                       AND order_date BETWEEN ? AND ?
                       ORDER BY status, order_id";
        
        $debug_stmt = $conn->prepare($debug_query);
        $debug_stmt->bind_param("iiiiiiiss", $debug_emp_id, $debug_emp_id, $debug_emp_id, 
                                $debug_emp_id, $debug_emp_id, $debug_emp_id, $start_date, $end_date);
        $debug_stmt->execute();
        $debug_orders = $debug_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $status_counts = [];
        foreach ($debug_orders as $order) {
            $status_counts[$order['status']] = ($status_counts[$order['status']] ?? 0) + 1;
        }
        
        echo "<strong>إحصائيات الحالات:</strong><br>";
        foreach ($status_counts as $status => $count) {
            $icon = ($status === 'مكتمل') ? '✅' : (in_array($status, ['قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم']) ? '🔄' : '❌');
            echo "- $icon $status: $count طلب<br>";
        }
        
        $total = count($debug_orders);
        $completed = $status_counts['مكتمل'] ?? 0;
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        echo "<hr><strong>الخلاصة:</strong><br>";
        echo "إجمالي المهام: $total | مكتملة: $completed | النسبة: $percentage%";
    }
    echo "</div>";
}


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

// ================== الجدول المفصل حسب الشهر المختار ==================

$compact_columns = $compact_matrix = $compact_totals = [];

if ($selected_matrix_month !== '') {
    // نطاق الشهر المختار
    $matrix_month_start = $selected_matrix_month . '-01';
    $matrix_month_end = date('Y-m-t', strtotime($matrix_month_start));

    // الطلبات النشطة (غير مكتمل وغير ملغي) داخل الشهر المختار
    $active_orders_stmt = $conn->prepare("SELECT order_id, order_date, status, designer_id, workshop_id, created_by FROM orders WHERE status NOT IN ('مكتمل','ملغي') AND order_date BETWEEN ? AND ?");
    $active_orders_stmt->bind_param('ss', $matrix_month_start, $matrix_month_end);
    $active_orders_stmt->execute();
    $active_orders = $active_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // جميع طلبات الشهر (عدا الملغي)
    $month_orders_stmt = $conn->prepare("SELECT order_id, order_date, status, designer_id, workshop_id, created_by FROM orders WHERE order_date BETWEEN ? AND ? AND status != 'ملغي'");
    $month_orders_stmt->bind_param('ss', $matrix_month_start, $matrix_month_end);
    $month_orders_stmt->execute();
    $month_orders = $month_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // تحديد أي أسبوع نحن فيه حالياً لتمييزه بصرياً
    $today = new \DateTime();
    $is_current_month = ($today->format('Y-m') === $selected_matrix_month);
    $current_week_row = '';
    if ($is_current_month) {
        $today_day = (int)$today->format('j');
        if ($today_day <= 7) { $current_week_row = 'الأسبوع الأول'; }
        elseif ($today_day <= 14) { $current_week_row = 'الأسبوع الثاني'; }
        elseif ($today_day <= 21) { $current_week_row = 'الأسبوع الثالث'; }
        elseif ($today_day <= 28) { $current_week_row = 'الأسبوع الرابع'; }
        else { $current_week_row = 'الأسبوع الخامس'; }
    }

    // الأعمدة (الموظفون) حسب الأدوار
    $designers_res = $conn->query("SELECT employee_id, name FROM employees WHERE role='مصمم' ORDER BY name LIMIT 2");
    if ($designers_res) {
        while ($d = $designers_res->fetch_assoc()) { $compact_columns[$d['employee_id']] = ['label' => $d['name'], 'role' => 'مصمم']; }
    }
    $workshop_res = $conn->query("SELECT employee_id, name FROM employees WHERE role IN ('معمل','معمل التنفيذ','المعمل التنفيذي') ORDER BY name LIMIT 1");
    if ($workshop_res && ($w = $workshop_res->fetch_assoc())) { $compact_columns[$w['employee_id']] = ['label' => $w['name'], 'role' => 'معمل']; }
    $accountant_res = $conn->query("SELECT employee_id, name FROM employees WHERE role='محاسب' ORDER BY name LIMIT 1");
    if ($accountant_res && ($a = $accountant_res->fetch_assoc())) { $compact_columns[$a['employee_id']] = ['label' => $a['name'], 'role' => 'محاسب']; }
    $manager_res = $conn->query("SELECT employee_id, name FROM employees WHERE role='مدير' ORDER BY name LIMIT 1");
    if ($manager_res && ($m = $manager_res->fetch_assoc())) { $compact_columns[$m['employee_id']] = ['label' => $m['name'], 'role' => 'مدير']; }

    if (!function_exists('compact_order_matches_employee')) {
        function compact_order_matches_employee(array $order, int $empId, string $role): bool {
            if ($role === 'مصمم') { return (int)($order['designer_id'] ?? 0) === $empId; }
            if ($role === 'معمل') { return (int)($order['workshop_id'] ?? 0) === $empId; }
            if ($role === 'محاسب' || $role === 'مدير') {
                return (int)($order['designer_id'] ?? 0) === $empId || (int)($order['workshop_id'] ?? 0) === $empId || (int)($order['created_by'] ?? 0) === $empId;
            }
            return false;
        }
    }

    // إعداد الصفوف الأساسية (قد نضيف الأسبوع الرابع والخامس لاحقاً)
    $compact_matrix = [
        'نشطة' => [],
    ];
    $compact_matrix['الأسبوع الأول'] = [];
    $compact_matrix['الأسبوع الثاني'] = [];
    $compact_matrix['الأسبوع الثالث'] = [];
    // سنضيف 'الأسبوع الرابع' و 'الأسبوع الخامس' إذا احتجنا حسب عدد الأيام
    $days_in_month = (int)date('t', strtotime($matrix_month_start));
    if ($days_in_month >= 22) { $compact_matrix['الأسبوع الرابع'] = []; }
    if ($days_in_month >= 29) { $compact_matrix['الأسبوع الخامس'] = []; }
    $compact_matrix['الشهر'] = [];

    // صف نشطة (الشهر المختار فقط)
    foreach ($active_orders as $o) {
        foreach ($compact_columns as $empId => $meta) {
            if (compact_order_matches_employee($o, (int)$empId, $meta['role'])) {
                $compact_matrix['نشطة'][$empId] = ($compact_matrix['نشطة'][$empId] ?? 0) + 1;
            }
        }
    }

    // بقية الصفوف (الشهر الكامل + تقسيم الأسابيع)
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

    // إزالة الأعمدة الفارغة
    $empHasAny = [];
    foreach ($compact_matrix as $row => $vals) {
        foreach ($vals as $empId => $cnt) { if ($cnt > 0) { $empHasAny[$empId] = true; } }
    }
    foreach (array_keys($compact_columns) as $empId) { if (!isset($empHasAny[$empId])) { unset($compact_columns[$empId]); } }

    // إجماليات الصفوف
    $compact_totals = [];
    foreach ($compact_matrix as $row => $vals) {
        $sum = 0; foreach ($compact_columns as $empId => $_) { $sum += $vals[$empId] ?? 0; }
        $compact_totals[$row] = $sum;
    }
}
// ================== نهاية الجدول المفصل ==================

// --- تضمين ملف العرض ---
require_once __DIR__ . '/../View/stats_report.php';