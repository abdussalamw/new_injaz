<?php
// --- كود مؤقت لإظهار الأخطاء ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- نهاية الكود المؤقت ---

$page_title = 'لوحة التحكم';
include 'db_connection.php';
include 'header.php';

// العدادات
$orders_count = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$clients_count = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
$employees_count = $conn->query("SELECT COUNT(*) FROM employees")->fetch_row()[0];
$products_count = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];

// --- إحصائيات وتقارير للمدراء ---
$employee_stats = [];
$overall_stats = ['open' => 0, 'closed' => 0, 'total' => 0];
$top_designers = [];

if (has_permission('dashboard_reports_view', $conn)) {
    // 1. إحصائيات لكل مصمم
    $stmt_employees = $conn->prepare("
        SELECT
            e.employee_id,
            e.name,
            COUNT(o.order_id) AS total_open_tasks,
            SUM(CASE WHEN o.due_date = CURDATE() THEN 1 ELSE 0 END) AS tasks_due_today
        FROM
            employees e
        LEFT JOIN
            orders o ON e.employee_id = o.designer_id AND o.status NOT IN ('مكتمل', 'ملغي')
        GROUP BY e.employee_id, e.name
        ORDER BY e.name
    ");
    $stmt_employees->execute();
    $employee_stats = $stmt_employees->get_result()->fetch_all(MYSQLI_ASSOC);

    // تحسين: جلب المهام المنجزة شهرياً في استعلام منفصل ومحسن
    $stmt_monthly = $conn->prepare("SELECT designer_id, COUNT(*) as monthly_closed_tasks FROM orders WHERE status = 'مكتمل' AND order_date >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY designer_id");
    $stmt_monthly->execute();
    $monthly_results = $stmt_monthly->get_result()->fetch_all(MYSQLI_ASSOC);
    $monthly_tasks_map = array_column($monthly_results, 'monthly_closed_tasks', 'designer_id');

    // دمج النتائج
    foreach ($employee_stats as &$stat) {
        $stat['monthly_closed_tasks'] = $monthly_tasks_map[$stat['employee_id']] ?? 0;
    }
    unset($stat); // لكسر المرجع

    // 2. إحصائيات إجمالية للمقارنات
    $stmt_overall = $conn->prepare("SELECT SUM(CASE WHEN status = 'مكتمل' THEN 1 ELSE 0 END) as closed_count, SUM(CASE WHEN status NOT IN ('مكتمل', 'ملغي') THEN 1 ELSE 0 END) as open_count FROM orders");
    $stmt_overall->execute();
    $overall_res = $stmt_overall->get_result()->fetch_assoc();
    $overall_stats['closed'] = $overall_res['closed_count'] ?? 0;
    $overall_stats['open'] = $overall_res['open_count'] ?? 0;
    $overall_stats['total'] = $overall_stats['closed'] + $overall_stats['open'];
}

// جلب الطلبات حسب دور المستخدم
$user_id = $_SESSION['user_id'] ?? 0; // استخدام ?? لتجنب الخطأ
$user_role = $_SESSION['user_role'] ?? 'guest'; // استخدام ?? لتجنب الخطأ

$sql = "SELECT o.*, c.company_name AS client_name, c.phone as client_phone, e.name AS designer_name, 
        COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id";

$res = null; // تهيئة المتغير
if (has_permission('order_view_all', $conn)) { // المدير
    $sql .= " WHERE TRIM(o.status) NOT IN ('مكتمل', 'ملغي') GROUP BY o.order_id ORDER BY o.due_date ASC";
    $res = $conn->query($sql);
} elseif (has_permission('order_view_own', $conn)) { // بقية الأدوار
    $where_clauses = ["TRIM(o.status) NOT IN ('مكتمل', 'ملغي')"];
    $params = [];
    $types = "";

    switch ($user_role) {
        case 'مصمم':
            // المصمم يرى كل مهامه المفتوحة التي تم إسنادها إليه
            $where_clauses[] = "o.designer_id = ?";
            $params[] = $user_id;
            $types .= "i";
            break;
        case 'معمل':
            // المعمل يرى كل المهام في مرحلة التنفيذ
            $where_clauses[] = "TRIM(o.status) = 'قيد التنفيذ'";
            break;
        case 'محاسب':
            // المحاسب يرى كل المهام في مرحلة التسليم
            $where_clauses[] = "TRIM(o.status) = 'جاهز للتسليم'";
            break;
        default:
            // كإجراء احتياطي، إذا كان للمستخدم دور آخر، فلن يرى أي طلبات
            // لتجنب عرض بيانات غير صحيحة.
            $where_clauses[] = "1=0"; // شرط لا يتحقق أبداً
            break;
    }
    
    $sql .= " WHERE " . implode(" AND ", $where_clauses) . " GROUP BY o.order_id ORDER BY o.due_date ASC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
}

// تحديد العنوان بناءً على الصلاحية
$dashboard_title = has_permission('order_view_own', $conn) && !has_permission('order_view_all', $conn) ? 'المهام الموكلة إليك' : 'أحدث المهام النشطة';
?>
<div class="container">
    <div id="status-update-feedback" class="mb-3"></div>
    <?php if (has_permission('dashboard_reports_view', $conn)): ?>
    <div class="row g-4 mb-5">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="p-3 text-white" style="background-color: #F37D47;">
                        <i class="bi bi-box-seam" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="px-3">
                        <div class="text-muted">إجمالي الطلبات</div>
                        <div class="fs-4 fw-bold"><?= $orders_count ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="p-3 text-white" style="background-color: #D44759;">
                        <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="px-3">
                        <div class="text-muted">العملاء</div>
                        <div class="fs-4 fw-bold"><?= $clients_count ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="p-3 text-white" style="background-color: #644D4D;">
                        <i class="bi bi-person-badge" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="px-3">
                        <div class="text-muted">الموظفون</div>
                        <div class="fs-4 fw-bold"><?= $employees_count ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="p-3 text-white" style="background-color: #fabb46;">
                        <i class="bi bi-palette-fill" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="px-3">
                        <div class="text-muted">المنتجات</div>
                        <div class="fs-4 fw-bold"><?= $products_count ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قسم الإحصائيات والتقارير الجديد -->
    <div class="mb-5">
        <h4 style="color:#D44759;" class="mt-4 mb-3">ملخص أداء الموظفين</h4>
        <div class="row g-4">
            <?php if (!empty($employee_stats)): ?>
                <?php foreach ($employee_stats as $stat): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title mb-4"><?= htmlspecialchars($stat['name']) ?></h5>
                            <div class="row my-auto">
                                <div class="col-4 border-end">
                                    <i class="bi bi-folder2-open fs-2 text-primary"></i>
                                    <div class="fw-bold fs-3"><?= $stat['total_open_tasks'] ?></div>
                                    <div class="text-muted small">مفتوحة</div>
                                </div>
                                <div class="col-4 border-end">
                                    <i class="bi bi-calendar-day fs-2 text-warning"></i>
                                    <div class="fw-bold fs-3"><?= $stat['tasks_due_today'] ?? 0 ?></div>
                                    <div class="text-muted small">تسليم اليوم</div>
                                </div>
                                <div class="col-4">
                                    <i class="bi bi-check2-circle fs-2 text-success"></i>
                                    <div class="fw-bold fs-3"><?= $stat['monthly_closed_tasks'] ?></div>
                                    <div class="text-muted small">منجز شهرياً</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-info">لا يوجد موظفون لعرض إحصائياتهم.</div></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-5">
        <h4 style="color:#D44759;" class="mt-4 mb-3">مقارنات الأداء</h4>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header fw-bold">حالة الطلبات الإجمالية</div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <?php $closed_percentage = ($overall_stats['total'] > 0) ? ($overall_stats['closed'] / $overall_stats['total']) * 100 : 0; ?>
                        <p class="mb-2"><strong>الإجمالي:</strong> <?= $overall_stats['total'] ?> طلب (لا يشمل الملغاة)</p>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $closed_percentage ?>%" title="مكتمل: <?= $overall_stats['closed'] ?>">مكتمل (<?= round($closed_percentage) ?>%)</div>
                            <div class="progress-bar bg-info text-dark" role="progressbar" style="width: <?= 100 - $closed_percentage ?>%" title="مفتوح: <?= $overall_stats['open'] ?>">مفتوح (<?= round(100 - $closed_percentage) ?>%)</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header fw-bold">الموظفون الأكثر إنجازاً (هذا الشهر)</div>
                    <div class="card-body">
                        <?php
                        usort($employee_stats, fn($a, $b) => $b['monthly_closed_tasks'] <=> $a['monthly_closed_tasks']);
                        ?>
                        <ol class="list-group list-group-numbered">
                            <?php foreach (array_slice($employee_stats, 0, 5) as $stat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto"><?= htmlspecialchars($stat['name']) ?></div>
                                    <span class="badge bg-success rounded-pill"><?= $stat['monthly_closed_tasks'] ?> مهمة</span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <h4 style="color:#D44759;" class="mt-4 mb-3"><?= $dashboard_title ?></h4>
    <div class="row g-4">
        <?php if($res && $res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm <?= get_priority_class($row['priority']) ?>" style="border-width: 4px; border-style: solid; border-top:0; border-right:0; border-bottom:0;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($row['products_summary']) ?></h5>
                            <?php if ($row['designer_name']): ?>
                                <span class="badge bg-info-subtle text-info-emphasis rounded-pill" title="المصمم المسؤول"><?= htmlspecialchars($row['designer_name']) ?></span>
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
                                <?php
                                    $current_status = trim($row['status']); // تنظيف القيمة من أي مسافات إضافية
                                    $actions = get_next_actions($row, $user_role, $user_id, $conn);
                                ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle <?= get_status_class($current_status) ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false" <?= !has_permission('order_edit_status', $conn) || empty($actions) ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($current_status) ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($actions as $next_status => $action_label): ?>
                                            <li><a class="dropdown-item status-change-btn" href="#" data-order-id="<?= $row['order_id'] ?>" data-status="<?= $next_status ?>"><?= htmlspecialchars($action_label) ?></a></li>
                                        <?php endforeach; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="edit_order.php?id=<?= $row['order_id'] ?>">عرض التفاصيل</a></li>
                                    </ul>
                                </div>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['client_phone']) ?>" target="_blank" class="btn btn-sm" style="background-color: #25D366; color: white;">
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
