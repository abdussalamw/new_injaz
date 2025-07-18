<?php
// تحديد الفلاتر
$start_date = $_GET['report_start_date'] ?? date('Y-m-01');
$end_date = $_GET['report_end_date'] ?? date('Y-m-t');
$selected_client = $_GET['report_client'] ?? '';
$payment_status_filter = $_GET['report_payment_status'] ?? '';

// التحقق من صحة التواريخ
if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

// جلب قائمة العملاء للفلتر
$clients_query = "SELECT client_id, company_name FROM clients ORDER BY company_name";
$clients_result = $conn->query($clients_query);
$clients_list = $clients_result->fetch_all(MYSQLI_ASSOC);

// بناء الاستعلام الأساسي
$base_query = "
    SELECT 
        o.order_id,
        o.order_date,
        o.due_date,
        o.status,
        o.total_amount,
        o.deposit_amount,
        o.payment_status,
        o.notes,
        c.company_name,
        c.contact_person,
        c.phone,
        c.email,
        c.client_id,
        e.name as designer_name,
        COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
    FROM orders o
    JOIN clients c ON o.client_id = c.client_id
    LEFT JOIN employees e ON o.designer_id = e.employee_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_date BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

// إضافة فلتر العميل
if (!empty($selected_client)) {
    $base_query .= " AND o.client_id = ?";
    $params[] = $selected_client;
    $types .= "i";
}

// إضافة فلتر حالة الدفع
if (!empty($payment_status_filter)) {
    $base_query .= " AND o.payment_status = ?";
    $params[] = $payment_status_filter;
    $types .= "s";
}

$base_query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $conn->prepare($base_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

// حساب الملخص المالي
$financial_summary = [
    'total_orders' => count($orders),
    'total_amount' => 0,
    'total_deposits' => 0,
    'total_remaining' => 0,
    'paid_orders' => 0,
    'partially_paid_orders' => 0,
    'unpaid_orders' => 0
];

foreach ($orders as $order) {
    $financial_summary['total_amount'] += $order['total_amount'];
    $financial_summary['total_deposits'] += $order['deposit_amount'] ?? 0;
    $financial_summary['total_remaining'] += ($order['total_amount'] - ($order['deposit_amount'] ?? 0));
    
    switch ($order['payment_status']) {
        case 'مدفوع':
            $financial_summary['paid_orders']++;
            break;
        case 'مدفوع جزئياً':
            $financial_summary['partially_paid_orders']++;
            break;
        case 'غير مدفوع':
            $financial_summary['unpaid_orders']++;
            break;
    }
}

// إحصائيات العملاء (إذا لم يتم تحديد عميل معين)
$client_stats = [];
if (empty($selected_client)) {
    $client_stats_query = "
        SELECT 
            c.client_id,
            c.company_name,
            c.contact_person,
            c.phone,
            COUNT(o.order_id) as total_orders,
            SUM(o.total_amount) as total_amount,
            SUM(o.deposit_amount) as total_deposits,
            SUM(o.total_amount - COALESCE(o.deposit_amount, 0)) as remaining_balance,
            COUNT(CASE WHEN o.payment_status = 'مدفوع' THEN 1 END) as paid_orders,
            COUNT(CASE WHEN o.payment_status = 'مدفوع جزئياً' THEN 1 END) as partial_orders,
            COUNT(CASE WHEN o.payment_status = 'غير مدفوع' THEN 1 END) as unpaid_orders
        FROM clients c
        LEFT JOIN orders o ON c.client_id = o.client_id AND o.order_date BETWEEN ? AND ?
        GROUP BY c.client_id, c.company_name, c.contact_person, c.phone
        HAVING total_orders > 0
        ORDER BY remaining_balance DESC";
    
    $stmt_clients = $conn->prepare($client_stats_query);
    $stmt_clients->bind_param("ss", $start_date, $end_date);
    $stmt_clients->execute();
    $client_stats = $stmt_clients->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container-fluid py-4">
    <!-- فلاتر التقارير المالية -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> فلاتر التقارير المالية</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="tab" value="reports">
                        <div class="col-md-2">
                            <label for="report_start_date" class="form-label">من تاريخ</label>
                            <input type="date" class="form-control" id="report_start_date" name="report_start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="report_end_date" class="form-label">إلى تاريخ</label>
                            <input type="date" class="form-control" id="report_end_date" name="report_end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="report_client" class="form-label">العميل</label>
                            <select class="form-select" id="report_client" name="report_client">
                                <option value="">جميع العملاء</option>
                                <?php foreach ($clients_list as $client): ?>
                                    <option value="<?= $client['client_id'] ?>" <?= $selected_client == $client['client_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($client['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="report_payment_status" class="form-label">حالة الدفع</label>
                            <select class="form-select" id="report_payment_status" name="report_payment_status">
                                <option value="">الكل</option>
                                <option value="مدفوع" <?= $payment_status_filter == 'مدفوع' ? 'selected' : '' ?>>مدفوع</option>
                                <option value="مدفوع جزئياً" <?= $payment_status_filter == 'مدفوع جزئياً' ? 'selected' : '' ?>>مدفوع جزئياً</option>
                                <option value="غير مدفوع" <?= $payment_status_filter == 'غير مدفوع' ? 'selected' : '' ?>>غير مدفوع</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success me-2">تطبيق الفلتر</button>
                            <a href="?tab=reports" class="btn btn-outline-secondary me-2">إعادة تعيين</a>
                            <button type="button" class="btn btn-info" onclick="exportToExcel()">تصدير Excel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- الملخص المالي -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h5><?= number_format($financial_summary['total_orders']) ?></h5>
                    <small>إجمالي الطلبات</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-info">
                <div class="card-body text-center">
                    <h5><?= number_format($financial_summary['total_amount'], 0) ?></h5>
                    <small>إجمالي المبالغ</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h5><?= number_format($financial_summary['total_deposits'], 0) ?></h5>
                    <small>إجمالي العربون</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <h5><?= number_format($financial_summary['total_remaining'], 0) ?></h5>
                    <small>المبالغ المتبقية</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h5><?= $financial_summary['paid_orders'] ?></h5>
                    <small>مدفوعة كاملة</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-danger">
                <div class="card-body text-center">
                    <h5><?= $financial_summary['unpaid_orders'] ?></h5>
                    <small>غير مدفوعة</small>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($selected_client)): ?>
    <!-- جدول إحصائيات العملاء -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> ملخص العملاء</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="clientsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>العميل</th>
                                    <th>الشخص المسؤول</th>
                                    <th>الهاتف</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي المبلغ</th>
                                    <th>العربون المدفوع</th>
                                    <th>الرصيد المتبقي</th>
                                    <th>حالة الدفع</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($client_stats as $client): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($client['company_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($client['contact_person']) ?></td>
                                    <td><?= htmlspecialchars($client['phone']) ?></td>
                                    <td><span class="badge bg-primary"><?= $client['total_orders'] ?></span></td>
                                    <td><?= number_format($client['total_amount'], 0) ?> ر.س</td>
                                    <td><?= number_format($client['total_deposits'], 0) ?> ر.س</td>
                                    <td>
                                        <span class="badge <?= $client['remaining_balance'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                                            <?= number_format($client['remaining_balance'], 0) ?> ر.س
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <span class="badge bg-success"><?= $client['paid_orders'] ?></span>
                                            <span class="badge bg-warning"><?= $client['partial_orders'] ?></span>
                                            <span class="badge bg-danger"><?= $client['unpaid_orders'] ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="?tab=reports&report_client=<?= $client['client_id'] ?>&report_start_date=<?= $start_date ?>&report_end_date=<?= $end_date ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> التفاصيل
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- جدول تفاصيل الطلبات -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> 
                        <?php if (!empty($selected_client)): ?>
                            تفاصيل طلبات العميل: <?= htmlspecialchars($orders[0]['company_name'] ?? 'غير محدد') ?>
                        <?php else: ?>
                            تفاصيل جميع الطلبات
                        <?php endif; ?>
                    </h5>
                    <?php if (!empty($selected_client)): ?>
                        <a href="?tab=reports&report_start_date=<?= $start_date ?>&report_end_date=<?= $end_date ?>" 
                           class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة لجميع العملاء
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="ordersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>التاريخ</th>
                                    <th>العميل</th>
                                    <th>المنتجات</th>
                                    <th>المصمم</th>
                                    <th>الحالة</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>العربون</th>
                                    <th>المتبقي</th>
                                    <th>حالة الدفع</th>
                                    <th>الاستحقاق</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_id'] ?></strong></td>
                                    <td><?= date('Y-m-d', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($order['company_name']) ?></strong>
                                        <?php if (!empty($order['phone'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($order['phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($order['products_summary']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($order['designer_name'] ?? 'غير محدد') ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($order['status']) {
                                            case 'مكتمل': $status_class = 'bg-success'; break;
                                            case 'قيد التصميم': $status_class = 'bg-info'; break;
                                            case 'قيد التنفيذ': $status_class = 'bg-warning'; break;
                                            case 'جاهز للتسليم': $status_class = 'bg-primary'; break;
                                            case 'ملغي': $status_class = 'bg-danger'; break;
                                            default: $status_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= $order['status'] ?></span>
                                    </td>
                                    <td><strong><?= number_format($order['total_amount'], 0) ?> ر.س</strong></td>
                                    <td><?= number_format($order['deposit_amount'] ?? 0, 0) ?> ر.س</td>
                                    <td>
                                        <?php $remaining = $order['total_amount'] - ($order['deposit_amount'] ?? 0); ?>
                                        <span class="badge <?= $remaining > 0 ? 'bg-warning' : 'bg-success' ?>">
                                            <?= number_format($remaining, 0) ?> ر.س
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_class = '';
                                        switch ($order['payment_status']) {
                                            case 'مدفوع': $payment_class = 'bg-success'; break;
                                            case 'مدفوع جزئياً': $payment_class = 'bg-warning'; break;
                                            case 'غير مدفوع': $payment_class = 'bg-danger'; break;
                                            default: $payment_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $payment_class ?>"><?= $order['payment_status'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($order['due_date']): ?>
                                            <?php 
                                            $due_date = new DateTime($order['due_date']);
                                            $today = new DateTime();
                                            $diff = $today->diff($due_date);
                                            $is_overdue = $today > $due_date;
                                            ?>
                                            <span class="badge <?= $is_overdue ? 'bg-danger' : 'bg-info' ?>">
                                                <?= $due_date->format('Y-m-d') ?>
                                                <?php if ($is_overdue): ?>
                                                    <br><small>متأخر <?= $diff->days ?> يوم</small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>

<style>
/* تحسين أزرار الفرز */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
    color: #495057;
    margin: 10px 0;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #fff;
    color: #495057;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #007bff;
    border-color: #007bff;
    color: #fff;
}

table.dataTable thead th {
    border-bottom: 2px solid #dee2e6;
    position: relative;
}

table.dataTable thead .sorting,
table.dataTable thead .sorting_asc,
table.dataTable thead .sorting_desc {
    cursor: pointer;
    position: relative;
    padding-right: 30px;
}

table.dataTable thead .sorting:before,
table.dataTable thead .sorting_asc:before,
table.dataTable thead .sorting_desc:before {
    content: "";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 4px solid #adb5bd;
}

table.dataTable thead .sorting:after,
table.dataTable thead .sorting_asc:after,
table.dataTable thead .sorting_desc:after {
    content: "";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%) translateY(2px);
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 4px solid #adb5bd;
}

table.dataTable thead .sorting_asc:before {
    border-bottom-color: #007bff;
}

table.dataTable thead .sorting_desc:after {
    border-top-color: #007bff;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    margin-left: 0.5rem;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    margin: 0 0.5rem;
}
</style>

<script>
$(document).ready(function() {
    // إعدادات DataTables المحسنة
    const tableConfig = {
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json',
            search: "البحث:",
            lengthMenu: "عرض _MENU_ عنصر",
            info: "عرض _START_ إلى _END_ من _TOTAL_ عنصر",
            infoEmpty: "عرض 0 إلى 0 من 0 عنصر",
            infoFiltered: "(مفلتر من _MAX_ عنصر إجمالي)",
            paginate: {
                first: "الأول",
                last: "الأخير",
                next: "التالي",
                previous: "السابق"
            },
            emptyTable: "لا توجد بيانات متاحة في الجدول",
            zeroRecords: "لم يتم العثور على نتائج مطابقة"
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "الكل"]],
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        columnDefs: [
            { targets: '_all', className: 'text-center' }
        ]
    };

    // تطبيق الإعدادات على جدول العملاء
    if ($('#clientsTable').length) {
        $('#clientsTable').DataTable({
            ...tableConfig,
            order: [[6, 'desc']], // ترتيب حسب الرصيد المتبقي
            columnDefs: [
                { targets: [3, 4, 5, 6], className: 'text-center' },
                { targets: [0, 1, 2], className: 'text-right' }
            ]
        });
    }

    // تطبيق الإعدادات على جدول الطلبات
    if ($('#ordersTable').length) {
        $('#ordersTable').DataTable({
            ...tableConfig,
            order: [[1, 'desc']], // ترتيب حسب التاريخ
            columnDefs: [
                { targets: [0, 5, 6, 7, 8, 9, 10], className: 'text-center' },
                { targets: [1, 2, 3, 4], className: 'text-right' }
            ]
        });
    }
});

// دالة تصدير Excel
function exportToExcel() {
    const table = document.getElementById('ordersTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "التقارير المالية"});
    XLSX.writeFile(wb, 'financial_report_<?= date("Y-m-d") ?>.xlsx');
}
</script>

<!-- مكتبة تصدير Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
