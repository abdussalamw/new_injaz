<?php
// This file is included from Stats.php, so it has access to all the variables.
?>
<div class="container-fluid py-4">
    <!-- فلاتر الإحصائيات -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> فلاتر الإحصائيات</h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="statsFilterForm" class="row g-3">
                        <input type="hidden" name="tab" value="stats">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">من تاريخ</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">إلى تاريخ</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="stats_employee" class="form-label">الموظف</label>
                            <select class="form-select" id="stats_employee" name="stats_employee">
                                <option value="">جميع الموظفين</option>
                                <?php 
                                $all_employees_res = $conn->query("SELECT employee_id, name FROM employees ORDER BY name");
                                $all_employees_list = $all_employees_res->fetch_all(MYSQLI_ASSOC);
                                foreach ($all_employees_list as $employee): ?>
                                    <option value="<?= $employee['employee_id'] ?>" <?= $selected_employee == $employee['employee_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($employee['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <a href="/reports/stats" class="btn btn-outline-secondary">إعادة تعيين الفلاتر</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- الإحصائيات العامة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['total_orders'] ?? 0) ?></h4>
                            <p class="mb-0">إجمالي الطلبات</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['completed_orders'] ?? 0) ?></h4>
                            <p class="mb-0">طلبات مكتملة</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['active_orders'] ?? 0) ?></h4>
                            <p class="mb-0">طلبات نشطة</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= number_format($stats['total_revenue'] ?? 0, 2) ?> ر.س</h4>
                            <p class="mb-0">إجمالي الإيرادات</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">توزيع الطلبات حسب الحالة</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">أداء الموظفين (المهام المكتملة)</h5>
                </div>
                <div class="card-body">
                    <canvas id="employeeChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- جداول تفصيلية -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">أداء الموظفين</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info alert-sm mb-3">
                        <small>
                            <strong>منطق الإكمال:</strong><br>
                            • <span class="badge bg-info">المصمم</span>: مكتمل عند إرسال للتنفيذ<br>
                            • <span class="badge bg-warning">المعمل</span>: مكتمل عند تأكيد استلام العميل<br>
                            • <span class="badge bg-success">المحاسب</span>: مكتمل عند استلام كامل المبلغ<br>
                            • <span class="badge bg-primary">المدير</span>: مكتمل عند استلام العميل + استلام كامل المبلغ
                        </small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="employeeStatsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>الموظف</th>
                                    <th>الدور</th>
                                    <th>مكتملة</th>
                                    <th>نشطة</th>
                                    <th>الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employee_stats as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['name']) ?></td>
                                    <td>
                                        <?php
                                        $role_class = '';
                                        switch ($emp['role']) {
                                            case 'مصمم': $role_class = 'bg-info'; break;
                                            case 'معمل': $role_class = 'bg-warning'; break;
                                            case 'محاسب': $role_class = 'bg-success'; break;
                                            case 'مدير': $role_class = 'bg-primary'; break;
                                            default: $role_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $role_class ?>"><?= htmlspecialchars($emp['role']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success" title="<?php
                                        switch ($emp['role']) {
                                            case 'مصمم': echo 'مكتمل عند إرسال للتنفيذ'; break;
                                            case 'معمل': echo 'مكتمل عند تأكيد استلام العميل'; break;
                                            case 'محاسب': echo 'مكتمل عند استلام كامل المبلغ'; break;
                                            case 'مدير': echo 'مكتمل عند استلام العميل + استلام كامل المبلغ'; break;
                                        }
                                        ?>"><?= $emp['completed_tasks'] ?></span>
                                    </td>
                                    <td><span class="badge bg-warning"><?= $emp['active_tasks'] ?></span></td>
                                    <td><?= number_format($emp['total_revenue'] ?? 0, 0) ?> ر.س</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">أفضل العملاء</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="topClientsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>اسم العميل</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_clients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['company_name']) ?></td>
                                    <td><span class="badge bg-info"><?= $client['orders_count'] ?></span></td>
                                    <td><?= number_format($client['total_spent'] ?? 0, 0) ?> ر.س</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">أكثر المنتجات طلباً</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="topProductsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>اسم المنتج</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي الكمية</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><span class="badge bg-primary"><?= $product['orders_count'] ?></span></td>
                                    <td><?= $product['total_quantity'] ?></td>
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

<!-- DataTables CSS & JS للجداول -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('statsFilterForm');
    const formElements = filterForm.querySelectorAll('input, select');

    formElements.forEach(element => {
        element.addEventListener('change', () => {
            filterForm.submit();
        });
    });

    if ($('#employeeStatsTable').length) {
        $('#employeeStatsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json',
                search: "البحث:",
                lengthMenu: "عرض _MENU_ عنصر",
                info: "عرض _START_ إلى _END_ من _TOTAL_ عنصر",
                paginate: {
                    next: "التالي",
                    previous: "السابق"
                }
            },
            pageLength: 10,
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "الكل"]],
            order: [[2, 'desc']],
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            columnDefs: [
                { targets: [1, 2, 3, 4], className: 'text-center' },
                { targets: [0], className: 'text-right' }
            ]
        });
    }

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['مكتملة', 'نشطة', 'ملغية'],
            datasets: [{
                data: [
                    <?= $stats['completed_orders'] ?? 0 ?>,
                    <?= $stats['active_orders'] ?? 0 ?>,
                    <?= $stats['cancelled_orders'] ?? 0 ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    const employeeCtx = document.getElementById('employeeChart').getContext('2d');
    new Chart(employeeCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($emp) { return '"' . addslashes($emp['name']) . '"'; }, array_slice($employee_stats, 0, 5))); ?>],
            datasets: [{
                label: 'المهام المكتملة',
                data: [<?php echo implode(',', array_map(function($emp) { return $emp['completed_tasks']; }, array_slice($employee_stats, 0, 5))); ?>],
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
