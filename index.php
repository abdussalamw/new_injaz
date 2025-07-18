<?php
// --- كود مؤقت لإظهار الأخطاء ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- نهاية الكود المؤقت ---

$page_title = 'لوحة التحكم';
include 'db_connection.php';
include 'header.php';

// دالة لجلب بيانات الرسوم البيانية
function get_chart_data($type, $conn) {
    switch($type) {
        case 'top_products':
            $sql = "SELECT p.name, COUNT(oi.product_id) as sales_count 
                    FROM products p 
                    LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                    LEFT JOIN orders o ON oi.order_id = o.order_id 
                    WHERE o.status != 'ملغي' OR o.status IS NULL
                    GROUP BY p.product_id, p.name 
                    ORDER BY sales_count DESC 
                    LIMIT 5";
            break;
        case 'clients':
            $sql = "SELECT c.company_name, COUNT(o.order_id) as orders_count 
                    FROM clients c 
                    LEFT JOIN orders o ON c.client_id = o.client_id 
                    WHERE o.status != 'ملغي' OR o.status IS NULL
                    GROUP BY c.client_id, c.company_name 
                    ORDER BY orders_count DESC 
                    LIMIT 5";
            break;
        case 'employees':
            $sql = "SELECT e.name, COUNT(o.order_id) as tasks_count 
                    FROM employees e 
                    LEFT JOIN orders o ON e.employee_id = o.designer_id 
                    WHERE o.status = 'مكتمل'
                    GROUP BY e.employee_id, e.name 
                    ORDER BY tasks_count DESC 
                    LIMIT 5";
            break;
        default:
            return [];
    }
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

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

// --- جلب البيانات اللازمة للفلاتر (المصممون والمدراء فقط) ---
$employees_res = $conn->query("SELECT employee_id, name FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
$employees_list = $employees_res->fetch_all(MYSQLI_ASSOC);

// --- استلام قيم الفلاتر من GET ---
$filter_status = $_GET['status'] ?? '';
$filter_employee = $_GET['employee'] ?? '';
$filter_payment = $_GET['payment'] ?? '';


// جلب الطلبات حسب دور المستخدم
$user_id = $_SESSION['user_id'] ?? 0; // استخدام ?? لتجنب الخطأ
$user_role = $_SESSION['user_role'] ?? 'guest'; // استخدام ?? لتجنب الخطأ

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

if (has_permission('order_view_all', $conn)) { // المدير
    $where_clauses[] = "TRIM(o.status) NOT IN ('مكتمل', 'ملغي')";

    // تطبيق الفلاتر
    if (!empty($filter_status)) {
        $where_clauses[] = "o.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    if (!empty($filter_employee)) {
        $where_clauses[] = "o.designer_id = ?";
        $params[] = $filter_employee;
        $types .= "i";
    }
    if (!empty($filter_payment)) {
        $where_clauses[] = "o.payment_status = ?";
        $params[] = $filter_payment;
        $types .= "s";
    }

} elseif (has_permission('order_view_own', $conn)) { // بقية الأدوار
    $where_clauses = ["TRIM(o.status) NOT IN ('مكتمل', 'ملغي')"];

    switch ($user_role) {
        case 'مصمم':
            // المصمم يرى كل مهامه المفتوحة التي تم إسنادها إليه
            $where_clauses[] = "o.designer_id = ?";
            $params[] = $user_id;
            $types .= "i";
            // تعديل: إظهار المهام التي في مرحلة التصميم فقط للمصمم
            $where_clauses[] = "TRIM(o.status) = 'قيد التصميم'";
            break;
        case 'معمل':
            // المعمل يرى كل المهام في مرحلة التنفيذ أو الجاهزة للتسليم
            // وتختفي المهمة بعد تأكيد استلام العميل لها
            $where_clauses[] = "TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم') AND o.delivered_at IS NULL";
            break;
        case 'محاسب':
            // المحاسب يرى كل المهام التي لم يتم تأكيد تسويتها المالية بعد
            // ويستثني الطلبات التي إجماليها صفر
            $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
            break;
        default:
            // كإجراء احتياطي، إذا كان للمستخدم دور آخر، فلن يرى أي طلبات
            // لتجنب عرض بيانات غير صحيحة.
            $where_clauses[] = "1=0"; // شرط لا يتحقق أبداً
            break;
    }
} else {
    // No permissions to view any tasks
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

// تحديد العنوان بناءً على الصلاحية
$dashboard_title = has_permission('order_view_own', $conn) && !has_permission('order_view_all', $conn) ? 'المهام الموكلة إليك' : 'أحدث المهام النشطة';

// تحديد أي تبويب يجب أن يكون نشطاً بشكل افتراضي
$default_active_tab = 'Tasks'; // الافتراضي هو المهام
if (has_permission('dashboard_reports_view', $conn)) {
    $default_active_tab = 'StatsReports'; // إذا كان مديراً، الافتراضي هو الإحصائيات
}
?>
<style>
    /* حاوية التبويبات الرئيسية */
    .tab-container {
        width: 100%;
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden; /* لإخفاء الزوايا الحادة للأبناء */
    }

    /* شريط أزرار التبويبات */
    .tab-buttons {
        overflow: hidden;
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }

    /* تصميم أزرار التبويبات */
    .tab-buttons button {
        background-color: inherit;
        float: right; /* ليتناسب مع التخطيط من اليمين لليسار */
        border: none;
        outline: none;
        cursor: pointer;
        padding: 14px 16px;
        transition: background-color 0.3s, color 0.3s;
        font-size: 17px;
        color: #495057;
    }

    /* تغيير لون الخلفية عند مرور الماوس */
    .tab-buttons button:hover {
        background-color: #e9ecef;
    }

    /* تصميم الزر النشط (التبويب المفتوح حالياً) */
    .tab-buttons button.active {
        background-color: #fff;
        font-weight: bold;
        color: #D44759;
        border-top: 2px solid #D44759;
    }

    /* تصميم محتوى التبويب */
    .tab-content { display: none; padding: 20px; }
</style>
<div class="container">
    <div id="status-update-feedback" class="mb-3"></div>
    <div class="tab-container shadow-sm">
        <!-- أزرار التبويبات -->
        <div class="tab-buttons">
            <?php if (has_permission('dashboard_reports_view', $conn)): ?>
                <button class="tab-link <?= $default_active_tab === 'StatsReports' ? 'active' : '' ?>" onclick="openTab(event, 'StatsReports')">الاحصاءات والتقارير</button>
            <?php endif; ?>
             <button class="tab-link <?= $default_active_tab === 'Tasks' ? 'active' : '' ?>" onclick="openTab(event, 'Tasks')">المهام</button>
             
             <?php if (has_permission('client_balance_report_view', $conn)): ?>
                <button class="tab-link" onclick="openTab(event, 'CustomReports')">التقارير</button>
            <?php endif; ?>
        </div>

        <?php if (has_permission('dashboard_reports_view', $conn)): ?>
        <!-- محتوى تبويب الاحصاءات والتقارير -->
        <div id="StatsReports" class="tab-content" style="<?= $default_active_tab === 'StatsReports' ? 'display: block;' : '' ?>">
            <div class="row g-4 mb-5">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="p-3 text-white" style="background-color: #F37D47;">
                        <i class="bi bi-box-seam" style="font-size: 2.5rem;"></i>
                        </div><div class="px-3"><div class="text-muted">إجمالي الطلبات</div>
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
                        </div><div class="px-3"><div class="text-muted">العملاء</div>
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
                        </div><div class="px-3"><div class="text-muted">الموظفون</div>
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
                        </div><div class="px-3"><div class="text-muted">المنتجات</div>
                        <div class="fs-4 fw-bold"><?= $products_count ?></div>
                    </div>
                </div>
            </div>
        </div>
            </div>

            <!-- قسم الإحصائيات والتقارير الجديد -->
            <div class="mb-5">
        <h4 style="color:#D44759;" class="mt-4 mb-3">ملخص أداء الموظفين</h4>
        <div class="row g-4 row-cols-1 row-cols-md-3 row-cols-xl-5">
            <?php if (!empty($employee_stats)): ?>
                <?php foreach ($employee_stats as $stat): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center d-flex flex-column p-2">
                            <h6 class="card-title mb-3 fw-bold"><?= htmlspecialchars($stat['name']) ?></h6>
                            <div class="row my-auto">
                                <div class="col-4 border-end">
                                    <i class="bi bi-folder2-open fs-4 text-primary"></i>
                                    <div class="fw-bold fs-5"><?= $stat['total_open_tasks'] ?></div>
                                    <div class="text-muted small">مفتوحة</div>
                                </div>
                                <div class="col-4 border-end">
                                    <i class="bi bi-calendar-day fs-4 text-warning"></i>
                                    <div class="fw-bold fs-5"><?= $stat['tasks_due_today'] ?? 0 ?></div>
                                    <div class="text-muted small">تسليم اليوم</div>
                                </div>
                                <div class="col-4">
                                    <i class="bi bi-check2-circle fs-4 text-success"></i>
                                    <div class="fw-bold fs-5"><?= $stat['monthly_closed_tasks'] ?></div>
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
            <!-- الرسوم البيانية -->
            <div class="mb-5">
                <h4 style="color:#D44759;" class="mt-4 mb-3">رسوم بيانية</h4>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header fw-bold">المنتجات الأكثر مبيعاً</div>
                            <div class="card-body">
                                <canvas id="topProductsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header fw-bold">العملاء</div>
                            <div class="card-body">
                                <canvas id="clientsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header fw-bold">الموظفين</div>
                            <div class="card-body">
                                <canvas id="employeesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // جلب بيانات الرسوم البيانية
            $top_products = get_chart_data('top_products', $conn);
            $clients = get_chart_data('clients', $conn);
            $employees = get_chart_data('employees', $conn);
            ?>
            <script>
            // البيانات الخاصة بالمنتجات الأكثر مبيعاً
            const topProductsData = {
                labels: <?= json_encode(array_column($top_products, 'name')) ?>,
                datasets: [{
                    label: 'عدد المبيعات',
                    data: <?= json_encode(array_column($top_products, 'sales_count')) ?>,
                    backgroundColor: ['#D44759', '#F37D47', '#fabb46', '#644D4D', '#28a745'],
                }]
            };

            // البيانات الخاصة بالعملاء
            const clientsData = {
                labels: <?= json_encode(array_column($clients, 'company_name')) ?>,
                datasets: [{
                    label: 'عدد الطلبات',
                    data: <?= json_encode(array_column($clients, 'orders_count')) ?>,
                    backgroundColor: ['#D44759', '#F37D47', '#fabb46', '#644D4D', '#28a745'],
                }]
            };

            // البيانات الخاصة بالموظفين
            const employeesData = {
                labels: <?= json_encode(array_column($employees, 'name')) ?>,
                datasets: [{
                    label: 'عدد المهام المنجزة',
                    data: <?= json_encode(array_column($employees, 'tasks_count')) ?>,
                    backgroundColor: ['#D44759', '#F37D47', '#fabb46', '#644D4D', '#28a745'],
                    borderColor: ['#D44759', '#F37D47', '#fabb46', '#644D4D', '#28a745'],
                    borderWidth: 2,
                    fill: false
                }]
            };

            // إنشاء الرسوم البيانية مع التحقق من وجود البيانات
            if (topProductsData.labels.length > 0) {
                const topProductsChart = new Chart(document.getElementById('topProductsChart'), { 
                    type: 'pie', 
                    data: topProductsData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            } else {
                document.getElementById('topProductsChart').parentElement.innerHTML = '<p class="text-center text-muted">لا توجد بيانات لعرضها</p>';
            }

            if (clientsData.labels.length > 0) {
                const clientsChart = new Chart(document.getElementById('clientsChart'), { 
                    type: 'bar', 
                    data: clientsData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                document.getElementById('clientsChart').parentElement.innerHTML = '<p class="text-center text-muted">لا توجد بيانات لعرضها</p>';
            }

            if (employeesData.labels.length > 0) {
                const employeesChart = new Chart(document.getElementById('employeesChart'), { 
                    type: 'line', 
                    data: employeesData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                document.getElementById('employeesChart').parentElement.innerHTML = '<p class="text-center text-muted">لا توجد بيانات لعرضها</p>';
            }
            </script>

        </div>
        <?php endif; ?>

        <?php if (has_permission('client_balance_report_view', $conn)): ?>
        <!-- محتوى تبويب التقارير المخصصة -->
        <div id="CustomReports" class="tab-content">
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">تقرير أرصدة العملاء المتبقية</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">يعرض هذا التقرير العملاء الذين لديهم مبالغ متبقية (غير مدفوعة بالكامل) على طلباتهم غير الملغية.</p>
                    <?php
                    $report_sql = "
                        SELECT
                            c.client_id,
                            c.company_name,
                            c.phone,
                            COUNT(o.order_id) as total_orders,
                            SUM(o.total_amount) as total_billed,
                            SUM(o.deposit_amount) as total_paid,
                            (SUM(o.total_amount) - SUM(o.deposit_amount)) as remaining_balance
                        FROM
                            clients c
                        JOIN
                            orders o ON c.client_id = o.client_id
                        WHERE
                            o.status != 'ملغي'
                        GROUP BY
                            c.client_id, c.company_name, c.phone
                        HAVING
                            remaining_balance > 0.01
                        ORDER BY
                            remaining_balance DESC;
                    ";
                    $report_result = $conn->query($report_sql);
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم العميل</th>
                                    <th>الجوال</th>
                                    <th>إجمالي الفواتير</th>
                                    <th>إجمالي المدفوع</th>
                                    <th class="table-danger">المبلغ المتبقي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($report_result && $report_result->num_rows > 0): ?>
                                    <?php while($report_row = $report_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $report_row['client_id'] ?></td>
                                        <td><a href="edit_client.php?id=<?= $report_row['client_id'] ?>"><?= htmlspecialchars($report_row['company_name']) ?></a></td>
                                        <td><?= htmlspecialchars($report_row['phone']) ?></td>
                                        <td><?= number_format($report_row['total_billed'], 2) ?> ر.س</td>
                                        <td><?= number_format($report_row['total_paid'], 2) ?> ر.س</td>
                                        <td class="fw-bold"><?= number_format($report_row['remaining_balance'], 2) ?> ر.س</td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">لا توجد أرصدة متبقية على العملاء حالياً.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (has_permission('employee_edit', $conn)): ?>
        <!-- محتوى تبويب تعديل الموظفين -->
        <div id="EditEmployee" class="tab-content">
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">تعديل بيانات الموظف</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">هنا يمكنك تعديل بيانات الموظف.</p>
                    <?php
                    // تضمين صفحة تعديل الموظف هنا
                    $employee_id = intval($_GET['id'] ?? 0);
                    if ($employee_id > 0) {
                        include 'edit_employee.php';
                    } else {
                        echo '<div class="alert alert-info">الرجاء تحديد موظف لتعديل بياناته.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>







        <!-- محتوى تبويب المهام -->
        <div id="Tasks" class="tab-content" style="<?= $default_active_tab === 'Tasks' ? 'display: block;' : '' ?>">
            <?php if (has_permission('order_view_all', $conn)): ?>
            <!-- نموذج الفلترة -->
            <form method="GET" id="filter-form" class="row g-3 align-items-center mb-4 p-3 border rounded bg-light filter-form">
        <div class="col-md-auto">
            <label for="status_filter" class="form-label">فلترة حسب الحالة</label>
            <select name="status" id="status_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="قيد التصميم" <?= $filter_status == 'قيد التصميم' ? 'selected' : '' ?>>قيد التصميم</option>
                <option value="قيد التنفيذ" <?= $filter_status == 'قيد التنفيذ' ? 'selected' : '' ?>>قيد التنفيذ</option>
                <option value="جاهز للتسليم" <?= $filter_status == 'جاهز للتسليم' ? 'selected' : '' ?>>جاهز للتسليم</option>
            </select>
        </div>

        <div class="col-md-auto">
            <label for="employee_filter" class="form-label">فلترة حسب الموظف</label>
            <select name="employee" id="employee_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <?php foreach ($employees_list as $employee): ?>
                    <option value="<?= $employee['employee_id'] ?>" <?= $filter_employee == $employee['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-auto">
            <label for="payment_filter" class="form-label">فلترة حسب الدفع</label>
            <select name="payment" id="payment_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="مدفوع" <?= $filter_payment == 'مدفوع' ? 'selected' : '' ?>>مدفوع</option>
                <option value="مدفوع جزئياً" <?= $filter_payment == 'مدفوع جزئياً' ? 'selected' : '' ?>>مدفوع جزئياً</option>
                <option value="غير مدفوع" <?= $filter_payment == 'غير مدفوع' ? 'selected' : '' ?>>غير مدفوع</option>
            </select>
        </div>

        <div class="col-md-auto align-self-end">
            <?php if (!empty($filter_status) || !empty($filter_employee) || !empty($filter_payment)): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">إلغاء الفلترة</a>
            <?php endif; ?>
        </div>
            </form>
            <?php endif; ?>

            <h4 style="color:#D44759;" class="mt-4 mb-3"><?= $dashboard_title ?></h4>
            <div class="row g-4">
        <?php if($res && $res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                            <?php include 'task_card.php'; ?>  <!-- تضمين ملف البطاقة الموحد -->
            </div>
            <?php endwhile; ?>
        <?php else: ?>

            <div class="col-12">
                <div class="alert alert-info text-center">لا توجد مهام لعرضها حالياً.</div>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </div> <!-- نهاية حاوية التبويبات -->
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
    });

    function openTab(evt, tabName) {
        let i, tabContent, tabLinks;
        tabContent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabContent.length; i++) {
            tabContent[i].style.display = "none";
        }
        tabLinks = document.getElementsByClassName("tab-link");
        for (i = 0; i < tabLinks.length; i++) {
            tabLinks[i].className = tabLinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

</script>

<script>

document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }

    // --- معالج أزرار الإجراءات الشامل (الحل للمشكلة) ---
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // منع السلوك الافتراضي للرابط

            const btn = this;
            const orderId = btn.dataset.orderId;
            const action = btn.dataset.action;
            const value = btn.dataset.value || null; // للحالات المتغيرة
            const confirmMessage = btn.dataset.confirmMessage;
            
            // بيانات واتساب (إن وجدت)
            const whatsappPhone = btn.dataset.whatsappPhone;
            const whatsappOrderId = btn.dataset.whatsappOrderId;

            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: confirmMessage,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم, نفّذ الإجراء!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    // إظهار مؤشر التحميل
                    Swal.fire({
                        title: 'الرجاء الانتظار...',
                        text: 'جاري تنفيذ الإجراء.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('ajax_order_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            action: action,
                            value: value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (whatsappPhone && whatsappOrderId) {
                                // تم النجاح، جهز رسالة واتساب
                                const whatsappMessage = `العميل العزيز، تم تحديث حالة طلبكم رقم ${whatsappOrderId}. شكراً لتعاملكم معنا.`;
                                const encodedMessage = encodeURIComponent(whatsappMessage);
                                // تعديل الرقم ليتوافق مع المعيار الدولي (966) وحذف الصفر الأول
                                const internationalPhone = '966' + whatsappPhone.substring(1);
                                const whatsappUrl = `https://wa.me/${internationalPhone}?text=${encodedMessage}`;
                                
                                // أظهر رسالة نجاح ثم افتح واتساب
                                Swal.fire({
                                    title: 'تم بنجاح!',
                                    text: data.message + ' سيتم الآن فتح واتساب.',
                                    icon: 'success',
                                    timer: 2500, // انتظر ثانيتين ونصف
                                    timerProgressBar: true
                                }).then(() => {
                                    window.open(whatsappUrl, '_blank');
                                    location.reload(); // تحديث الصفحة الأصلية
                                });
                            } else {
                                // لا يوجد إجراء واتساب، فقط أظهر نجاح وحدّث الصفحة
                                Swal.fire('تم بنجاح!', data.message, 'success').then(() => location.reload());
                            }
                        } else {
                            Swal.fire('خطأ!', data.message, 'error');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        Swal.fire('خطأ فني!', 'حدث خطأ غير متوقع. الرجاء مراجعة الـ Console.', 'error');
                    });
                }
            });
        });
    });
});

</script>
<?php include 'footer.php'; ?>
