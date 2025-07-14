<?php
// --- AJAX Handler for Status Update ---
// هذا الجزء من الكود سيعمل فقط عند إرسال طلب تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'auth_check.php';
    include 'db_connection.php';

    header('Content-Type: application/json');
    check_permission('order_edit_status');

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
    $current_status = $current_order['status'];

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

// دالة لتحديد لون زر الحالة
function get_status_class($status) {
    $classes = [
        'قيد التصميم' => 'btn-info text-dark',
        'قيد التنفيذ' => 'btn-primary',
        'جاهز للتسليم' => 'btn-warning text-dark',
        'بانتظار الإغلاق' => 'btn-dark',
        'مكتمل' => 'btn-success',
        'ملغي' => 'btn-danger'
    ];
    return $classes[$status] ?? 'btn-light';
}

// دالة لتحديد لون حالة الدفع
function get_payment_status_class($status) {
    switch ($status) {
        case 'مدفوع': return 'bg-success';
        case 'مدفوع جزئياً': return 'bg-warning text-dark';
        case 'غير مدفوع': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// دالة لتحديد الإجراءات المتاحة بناءً على المرحلة والدور
function get_next_actions($current_status, $user_role) {
    $actions = [];
    
    // المدير يمكنه إلغاء الطلب في أي وقت
    if ($user_role === 'مدير' && !in_array($current_status, ['مكتمل', 'ملغي'])) {
        $actions['ملغي'] = 'إلغاء الطلب';
    }

    switch ($current_status) {
        case 'قيد التصميم':
            if (in_array($user_role, ['مدير', 'مصمم'])) {
                $actions['قيد التنفيذ'] = 'إرسال للتنفيذ';
            }
            break;
        case 'قيد التنفيذ':
            if (in_array($user_role, ['مدير', 'معمل'])) {
                $actions['جاهز للتسليم'] = 'إرسال للتسليم والمحاسبة';
            }
            break;
        case 'جاهز للتسليم':
            if (in_array($user_role, ['مدير', 'محاسب'])) {
                $actions['بانتظار الإغلاق'] = 'تأكيد التسوية المالية';
            }
            break;
        case 'بانتظار الإغلاق':
            if ($user_role === 'مدير') {
                $actions['مكتمل'] = 'إغلاق الطلب (نهائي)';
            }
            break;
    }
    return $actions;
}

include 'header.php'; // هذا السطر سيتم الوصول إليه فقط في العرض العادي للصفحة
include 'db_connection.php';

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
if (has_permission('order_view_all')) { // المدير
    $sql .= " GROUP BY o.order_id ORDER BY FIELD(o.status, 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم', 'بانتظار الإغلاق', 'مكتمل', 'ملغي'), o.due_date ASC";
    $res = $conn->query($sql);
} elseif (has_permission('order_view_own')) { // بقية الأدوار
    $where_clauses = [];
    $params = [];
    $types = "";
    $user_role = $_SESSION['user_role'] ?? 'guest';

    switch ($user_role) {
        case 'مصمم':
            $where_clauses[] = "o.designer_id = ?";
            $where_clauses[] = "o.status = 'قيد التصميم'";
            $params[] = $user_id;
            $types .= "i";
            break;
        case 'معمل':
            $where_clauses[] = "o.status = 'قيد التنفيذ'";
            break;
        case 'محاسب':
            $where_clauses[] = "o.status = 'جاهز للتسليم'";
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
    check_permission('order_view_all'); // سيؤدي إلى إظهار رسالة "غير مصرح لك"
}
?>
<div class="container">
    <div id="status-update-feedback" class="mb-3"></div>
    <h2 style="color:#D44759;" class="mb-4">الطلبات</h2>
    <?php if (has_permission('order_add')): ?><a href="add_order.php" class="btn btn-success mb-3">إضافة طلب جديد</a><?php endif; ?>
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
                        $current_status = $row['status'];
                        $user_role = $_SESSION['user_role'] ?? 'guest';
                        $actions = get_next_actions($current_status, $user_role);
                    ?>
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle w-100 <?= get_status_class($current_status) ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false" <?= !has_permission('order_edit_status') || empty($actions) ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($current_status) ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($actions as $next_status => $action_label): ?>
                                <li><a class="dropdown-item status-change-btn" href="#" data-order-id="<?= $row['order_id'] ?>" data-status="<?= $next_status ?>"><?= htmlspecialchars($action_label) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
                <td>
                    <span class="badge <?= get_payment_status_class($row['payment_status']) ?>"><?= htmlspecialchars($row['payment_status']) ?></span>
                </td>
                <td><?= number_format($row['total_amount'],2) ?></td>
                <td><?= date('Y-m-d', strtotime($row['order_date'])) ?></td>
                <td>
                    <?php if (has_permission('order_edit')): ?>
                        <a href="edit_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('order_delete')): ?>
                        <a href="delete_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackDiv = document.getElementById('status-update-feedback');
    const tableBody = document.querySelector('.table tbody');

    if (tableBody) {
        tableBody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('status-change-btn')) {
                e.preventDefault();

                const orderId = e.target.dataset.orderId;
                const newStatus = e.target.dataset.status;

                if (!confirm(`هل أنت متأكد من تغيير حالة الطلب #${orderId} إلى "${newStatus}"؟`)) {
                    return;
                }

                feedbackDiv.innerHTML = `<div class="alert alert-info">جاري تحديث الحالة...</div>`;

                fetch('orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        feedbackDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        setTimeout(() => { feedbackDiv.innerHTML = ''; }, 4000);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }
});
</script>
<?php include 'footer.php'; ?>
