<?php
include 'db_connection_secure.php';
include 'auth_check.php'; // للتأكد من أن المستخدم مسجل دخوله
include 'helpers.php'; // تضمين الدوال المساعدة

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // إذا لم يكن الطلب AJAX، قم بإعادة التوجيه أو عرض خطأ
    die('وصول غير مسموح به');
}

$filter_status = $_GET['status'] ?? '';
$filter_employee = $_GET['employee'] ?? '';
$filter_payment = $_GET['payment'] ?? '';

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? 'guest';

$sql = "SELECT o.*, c.company_name AS client_name, c.phone as client_phone, e.name AS designer_name, 
        COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary,
        o.design_completed_at, o.execution_completed_at, c.client_id
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id";

$where_clauses = [];
$params = [];
$types = "";

if (has_permission('order_view_all', $conn)) {
    // المدير يرى جميع المهام النشطة
    $where_clauses[] = "TRIM(o.status) NOT IN ('مكتمل', 'ملغي')";
    
    // تطبيق الفلاتر
    if (!empty($filter_status)) {
        $where_clauses[] = "o.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    if (!empty($filter_employee)) {
        $where_clauses[] = "(o.designer_id = ? OR o.workshop_id = ?)";
        $params[] = $filter_employee;
        $params[] = $filter_employee;
        $types .= "ii";
    }
    if (!empty($filter_payment)) {
        $where_clauses[] = "o.payment_status = ?";
        $params[] = $filter_payment;
        $types .= "s";
    }
} elseif (has_permission('order_view_own', $conn)) {
    $where_clauses = ["TRIM(o.status) NOT IN ('مكتمل', 'ملغي')"];
    
        // إذا تم تحديد موظف في الفلتر، اعرض مهام ذلك الموظف
        if (!empty($filter_employee)) {
            $where_clauses[] = "(o.designer_id = ? OR o.workshop_id = ?)";
            $params[] = $filter_employee;
            $params[] = $filter_employee;
            $types .= "ii";
        
        // تطبيق فلاتر إضافية
        if (!empty($filter_status)) {
            $where_clauses[] = "o.status = ?";
            $params[] = $filter_status;
            $types .= "s";
        }
        if (!empty($filter_payment)) {
            $where_clauses[] = "o.payment_status = ?";
            $params[] = $filter_payment;
            $types .= "s";
        }
    } else {
        // إذا لم يتم تحديد موظف، اعرض المهام حسب دور المستخدم الحالي
        switch ($user_role) {
            case 'مصمم':
                $where_clauses[] = "o.designer_id = ?";
                $params[] = $user_id;
                $types .= "i";
                $where_clauses[] = "TRIM(o.status) = 'قيد التصميم'";
                break;
            case 'معمل':
                $where_clauses[] = "TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم') AND o.delivered_at IS NULL";
                break;
            case 'محاسب':
                $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                break;
            default:
                $where_clauses[] = "1=0";
                break;
        }
    }
} else {
    $where_clauses[] = "1=0";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY o.order_id ORDER BY o.due_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

ob_start();
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo '<div class="col-md-6 col-lg-4">';
        $task_details = $row;
        // تحديد الإجراءات المتاحة بناءً على حالة المهمة ودور المستخدم
        $actions = get_next_actions($row, $user_role, $user_id, $conn, 'dashboard'); 
        include 'task_card.php';
        echo '</div>';
    }
} else {
    echo '<div class="col-12"><div class="alert alert-info text-center">لا توجد مهام تطابق معايير البحث.</div></div>';
}
$output = ob_get_clean();
echo $output;

$conn->close();
?>
