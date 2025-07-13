<?php
// --- AJAX Handler for Status Update ---
// هذا الجزء من الكود سيعمل فقط عند إرسال طلب تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'auth_check.php';
    include 'db_connection.php';

    header('Content-Type: application/json');
    check_permission('order_edit_status');

    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? 0;
    $status = $data['status'] ?? '';
    $allowed_statuses = ['جديد', 'قيد التنفيذ', 'جاهز للتسليم', 'مكتمل', 'ملغي'];

    if (empty($order_id) || empty($status) || !in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
        exit;
    }

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

if (has_permission('order_view_all')) {
    // لا يوجد شرط إضافي، اعرض كل الطلبات
    $sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC";
    $res = $conn->query($sql);
} elseif (has_permission('order_view_own')) {
    $sql .= " WHERE o.designer_id = ? GROUP BY o.order_id ORDER BY o.order_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
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
                <td>
                    <?php if (has_permission('order_edit_status')): ?>
                        <select class="form-select form-select-sm status-select" data-order-id="<?= $row['order_id'] ?>">
                            <option value="جديد" <?= $row['status'] == 'جديد' ? 'selected' : '' ?>>جديد</option>
                            <option value="قيد التنفيذ" <?= $row['status'] == 'قيد التنفيذ' ? 'selected' : '' ?>>قيد التنفيذ</option>
                            <option value="جاهز للتسليم" <?= $row['status'] == 'جاهز للتسليم' ? 'selected' : '' ?>>جاهز للتسليم</option>
                            <option value="مكتمل" <?= $row['status'] == 'مكتمل' ? 'selected' : '' ?>>مكتمل</option>
                            <option value="ملغي" <?= $row['status'] == 'ملغي' ? 'selected' : '' ?>>ملغي</option>
                        </select>
                    <?php else: ?>
                        <?= htmlspecialchars($row['status']) ?>
                    <?php endif; ?>
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
    const statusSelects = document.querySelectorAll('.status-select');
    const feedbackDiv = document.getElementById('status-update-feedback');

    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;

            feedbackDiv.innerHTML = `<div class="alert alert-info">جاري تحديث الحالة...</div>`;

            fetch('orders.php', { // تم تغيير الرابط ليشير إلى نفس الصفحة
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedbackDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
                setTimeout(() => { feedbackDiv.innerHTML = ''; }, 3000);
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>
<?php include 'footer.php'; ?>
