<?php
// --- AJAX Handler for Status Update ---
// هذا الجزء من الكود سيعمل فقط عند إرسال طلب تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db_connection.php';
    include 'auth_check.php';
    include 'helpers.php'; // نحتاج للدوال المساعدة هنا

    header('Content-Type: application/json');
    check_permission('order_edit_status', $conn);

    // --- بداية منطق التحقق من سير العمل ---
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? 0;
    $status = $data['status'] ?? '';

    // جلب الحالة الحالية للطلب ودور المستخدم
    $user_role = $_SESSION['user_role'] ?? 'guest';
    $stmt_current = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
    $stmt_current->bind_param("i", $order_id);
    $stmt_current->execute();
    $result_current = $stmt_current->get_result();
    if (!$current_order = $result_current->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'الطلب غير موجود.']);
        exit;
    }
    $current_status = trim($current_order['status']); // تنظيف القيمة من أي مسافات إضافية

    // التحقق من صلاحية الانتقال للمرحلة التالية
    $allowed_actions = get_next_actions($current_status, $user_role);

    if (!array_key_exists($status, $allowed_actions)) {
        echo json_encode(['success' => false, 'message' => 'الإجراء غير مصرح به لهذا الدور أو لهذه المرحلة.']);
        exit;
    }
    // --- نهاية منطق التحقق من سير العمل ---
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة الطلب #' . $order_id . ' بنجاح.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.']);
    }
    exit; // إيقاف التنفيذ بعد معالجة طلب AJAX
}
// --- نهاية معالج AJAX ---

$page_title = 'الطلبات';
include 'db_connection.php';
include 'header.php'; // هذا السطر سيتم الوصول إليه فقط في العرض العادي للصفحة

// استعلام الطلبات مع أسماء العملاء والمنتجات والمصمم
$user_id = $_SESSION['user_id'] ?? 0;

$sql = "SELECT o.*, c.company_name AS client_name, e.name AS designer_name, 
        GROUP_CONCAT(p.name SEPARATOR ', ') as products_summary
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id ";

$res = null; // تهيئة المتغير
if (has_permission('order_view_all', $conn)) { // المدير
    $sql .= " GROUP BY o.order_id ORDER BY FIELD(o.status, 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم', 'بانتظار الإغلاق', 'مكتمل', 'ملغي'), o.due_date ASC";
    $res = $conn->query($sql);
} elseif (has_permission('order_view_own', $conn)) { // بقية الأدوار
    $where_clauses = [];
    $params = [];
    $types = "";
    $user_role = $_SESSION['user_role'] ?? 'guest';

    switch ($user_role) {
        case 'مصمم':
            // في صفحة الطلبات، المصمم يرى كل الطلبات المسندة إليه بغض النظر عن حالتها
            $where_clauses[] = "o.designer_id = ?";
            $params[] = $user_id;
            $types .= "i";
            break;
        case 'معمل':
            // المعمل يرى كل الطلبات التي وصلت لمرحلة التنفيذ وما بعدها
            $where_clauses[] = "TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم', 'بانتظار الإغلاق', 'مكتمل')";
            break;
        case 'محاسب':
            // المحاسب يرى كل الطلبات التي وصلت لمرحلة التسليم وما بعدها
            $where_clauses[] = "TRIM(o.status) IN ('جاهز للتسليم', 'بانتظار الإغلاق', 'مكتمل')";
            break;
        default:
            // كإجراء احتياطي، إذا كان للمستخدم دور آخر، فلن يرى أي طلبات
            // لتجنب عرض بيانات غير صحيحة.
            $where_clauses[] = "1=0"; // شرط لا يتحقق أبداً
            break;
    }
    
    $sql .= " WHERE " . implode(" AND ", $where_clauses) . " GROUP BY o.order_id ORDER BY o.due_date ASC";
    if (!empty($where_clauses)) {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
    }
} else {
    check_permission('order_view_all', $conn); // سيؤدي إلى إظهار رسالة "غير مصرح لك"
}
?>
<div class="container">
    <div id="status-update-feedback" class="mb-3"></div>
    <?php if (has_permission('order_add', $conn)): ?><a href="add_order.php" class="btn btn-success mb-3">إضافة طلب جديد</a><?php endif; ?>
    <table class="table table-bordered table-striped text-center">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>ملخص المنتجات</th>
                <th>المصمم</th>
                <th>الحالة</th>
                <th>حالة الدفع</th>
                <th>المبلغ</th>
                <th>تاريخ الإنشاء</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['order_id'] ?></td>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['products_summary']) ?></td>
                <td><?= htmlspecialchars($row['designer_name']) ?></td>
                <td style="min-width: 150px;">
                    <?php
                        $current_status = trim($row['status']); // تنظيف القيمة من أي مسافات إضافية
                        $user_role = $_SESSION['user_role'] ?? 'guest'; // user_id is already defined above
                        $actions = get_next_actions($row, $user_role, $user_id, $conn);
                    ?>
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle w-100 <?= get_status_class($current_status) ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false" <?= !has_permission('order_edit_status', $conn) || empty($actions) ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($current_status) ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($actions as $next_status => $action_label): ?>
                                <li><a class="dropdown-item status-change-btn" href="#" data-order-id="<?= $row['order_id'] ?>" data-status="<?= $next_status ?>"><?= htmlspecialchars($action_label) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
                <td style="min-width: 120px;"><?= get_payment_status_display($row['payment_status'], $row['total_amount'], $row['deposit_amount']) ?></td>
                <td><?= number_format($row['total_amount'],2) ?></td>
                <td><?= date('Y-m-d', strtotime($row['order_date'])) ?></td>
                <td>
                    <?php if (has_permission('order_edit', $conn)): ?>
                        <a href="edit_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('order_delete', $conn)): ?>
                        <a href="delete_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
