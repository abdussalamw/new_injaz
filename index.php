<?php
include 'header.php';
include 'db_connection.php';

// دالة لتحديد لون الأولوية
function get_priority_class($priority) {
    switch ($priority) {
        case 'عاجل جداً': return 'border-danger';
        case 'عالي': return 'border-warning';
        case 'متوسط': return 'border-info';
        case 'منخفض': return 'border-secondary';
        default: return 'border-light';
    }
}

// العدادات
$orders_count = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$clients_count = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
$employees_count = $conn->query("SELECT COUNT(*) FROM employees")->fetch_row()[0];
$products_count = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];

// جلب الطلبات حسب دور المستخدم
$user_id = $_SESSION['user_id'] ?? 0; // استخدام ?? لتجنب الخطأ
$user_role = $_SESSION['user_role'] ?? 'guest'; // استخدام ?? لتجنب الخطأ

$sql = "SELECT o.*, c.company_name as client_name, c.phone as client_phone, e.name as designer_name,
        (SELECT p.name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = o.order_id LIMIT 1) as product_name
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id";

$res = null; // تهيئة المتغير
if (has_permission('order_view_all')) {
    $sql .= " WHERE o.status NOT IN ('مكتمل', 'ملغي') ORDER BY o.due_date ASC LIMIT 10";
    $res = $conn->query($sql);
} elseif (has_permission('order_view_own')) {
    $sql .= " WHERE o.designer_id = ? AND o.status NOT IN ('مكتمل', 'ملغي') ORDER BY o.due_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
}

// تحديد العنوان بناءً على الصلاحية
$dashboard_title = has_permission('order_view_own') && !has_permission('order_view_all') ? 'المهام الموكلة إليك' : 'أحدث المهام النشطة';
?>
<div class="container">
    <h1 class="mb-4" style="color:#D44759;">لوحة التحكم</h1>
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 text-center p-3" style="background:#F37D47;color:#fff;">
                <div style="font-size:40px">📦</div>
                <div class="fs-5 mb-1">إجمالي الطلبات</div>
                <div class="fs-3"><?= $orders_count ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 text-center p-3" style="background:#D44759;color:#fff;">
                <div style="font-size:40px">👥</div>
                <div class="fs-5 mb-1">العملاء</div>
                <div class="fs-3"><?= $clients_count ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 text-center p-3" style="background:#644D4D;color:#fff;">
                <div style="font-size:40px">🧑‍💻</div>
                <div class="fs-5 mb-1">الموظفون</div>
                <div class="fs-3"><?= $employees_count ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm rounded-3 text-center p-3" style="background:#fabb46;color:#fff;">
                <div style="font-size:40px">🎨</div>
                <div class="fs-5 mb-1">المنتجات</div>
                <div class="fs-3"><?= $products_count ?></div>
            </div>
        </div>
    </div>
    <h4 style="color:#D44759;" class="mt-4 mb-3"><?= $dashboard_title ?></h4>
    <div class="row g-4">
        <?php if($res && $res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm <?= get_priority_class($row['priority']) ?>" style="border-width: 4px; border-style: solid; border-top:0; border-right:0; border-bottom:0;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                            <?php if ($row['designer_name']): ?>
                                <span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= htmlspecialchars($row['designer_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill"><?= htmlspecialchars($row['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h6 class="card-subtitle mb-2 text-muted">للعميل: <?= htmlspecialchars($row['client_name']) ?></h6>
                        <p class="card-text small">الأولوية: <span class="fw-bold"><?= htmlspecialchars($row['priority']) ?></span></p>
                        
                        <div class="mt-auto">
                            <div class="countdown p-2 rounded text-center bg-light mb-3" data-order-date="<?= $row['order_date'] ?>">
                                <span class="fs-5">جاري حساب الوقت المنقضي...</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="edit_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-primary">عرض التفاصيل</a>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['client_phone']) ?>" target="_blank" class="btn btn-success btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                        <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                                    </svg>
                                    واتساب
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">لا توجد مهام لعرضها حالياً.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
