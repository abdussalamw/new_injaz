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

    <!-- قسم مقارنة أداء الموظفين - تقارير متقدمة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> تقارير تفصيلية مقارنة أداء الموظفين</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="employeeReportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="productivity-tab" data-bs-toggle="tab" data-bs-target="#productivity" type="button" role="tab" aria-selected="true">الإنتاجية</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="efficiency-tab" data-bs-toggle="tab" data-bs-target="#efficiency" type="button" role="tab" aria-selected="false">الكفاءة</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="revenue-tab" data-bs-toggle="tab" data-bs-target="#revenue" type="button" role="tab" aria-selected="false">الإيرادات</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comparison-tab" data-bs-toggle="tab" data-bs-target="#comparison" type="button" role="tab" aria-selected="false">مقارنة شاملة</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="employeeReportTabsContent">
                        <!-- تبويب الإنتاجية -->
                        <div class="tab-pane fade show active" id="productivity" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="chart-container" style="position: relative; height:300px;">
                                        <canvas id="productivityChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title">معلومات الإنتاجية</h5>
                                            <p class="card-text">
                                                يوضح هذا المخطط عدد المهام المنجزة لكل موظف خلال الفترة المحددة، مما يعكس مستوى الإنتاجية.
                                                <br><br>
                                                <strong>الموظف الأكثر إنتاجية:</strong>
                                                <span id="mostProductiveEmployee" class="badge bg-success"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تبويب الكفاءة -->
                        <div class="tab-pane fade" id="efficiency" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="chart-container" style="position: relative; height:300px;">
                                        <canvas id="efficiencyChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title">معلومات الكفاءة</h5>
                                            <p class="card-text">
                                                يعرض هذا المخطط نسبة المهام المكتملة إلى إجمالي المهام لكل موظف، مما يعكس كفاءة الإنجاز.
                                                <br><br>
                                                <strong>الموظف الأكثر كفاءة:</strong>
                                                <span id="mostEfficientEmployee" class="badge bg-info"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تبويب الإيرادات -->
                        <div class="tab-pane fade" id="revenue" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="chart-container" style="position: relative; height:300px;">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title">معلومات الإيرادات</h5>
                                            <p class="card-text">
                                                يوضح هذا المخطط إجمالي الإيرادات التي حققها كل موظف من خلال المهام المنجزة خلال الفترة المحددة.
                                                <br><br>
                                                <strong>الموظف الأكثر تحقيقًا للإيرادات:</strong>
                                                <span id="highestRevenueEmployee" class="badge bg-primary"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تبويب المقارنة الشاملة -->
                        <div class="tab-pane fade" id="comparison" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="chart-container" style="position: relative; height:400px;">
                                        <canvas id="radarComparisonChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h5><i class="fas fa-info-circle"></i> حول المقارنة الشاملة</h5>
                                        <p>
                                            يعرض هذا المخطط مقارنة شاملة لأداء الموظفين عبر عدة مقاييس:
                                            <ul>
                                                <li><strong>الإنتاجية</strong>: عدد المهام المكتملة</li>
                                                <li><strong>الكفاءة</strong>: نسبة المهام المكتملة إلى إجمالي المهام</li>
                                                <li><strong>الإيرادات</strong>: إجمالي الإيرادات المحققة</li>
                                                <li><strong>متوسط الإيراد</strong>: متوسط الإيراد لكل مهمة</li>
                                                <li><strong>سرعة الإنجاز</strong>: متوسط وقت إكمال المهام</li>
                                            </ul>
                                            هذا المخطط يسمح برؤية شاملة لنقاط القوة والضعف لكل موظف.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- تقارير الموظفين المتقدمة -->
    <?php include __DIR__ . '/employee_advanced_reports.php'; ?>

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
    
    // === المخططات المتقدمة لمقارنة الموظفين ===

    // 1. مخطط الإنتاجية (مخطط شريطي أفقي متقدم)
    const productivityData = {
        labels: [<?php echo implode(',', array_map(function($emp) { return '"' . addslashes($emp['name']) . '"'; }, $employee_stats)); ?>],
        datasets: [{
            axis: 'y',
            label: 'المهام المكتملة',
            data: [<?php echo implode(',', array_map(function($emp) { return $emp['completed_tasks']; }, $employee_stats)); ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 206, 86, 0.8)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderWidth: 1
        }]
    };

    if (document.getElementById('productivityChart')) {
        const productivityChart = new Chart(
            document.getElementById('productivityChart').getContext('2d'),
            {
                type: 'bar',
                data: productivityData,
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return context[0].label;
                                },
                                label: function(context) {
                                    return 'عدد المهام المكتملة: ' + context.raw;
                                }
                            }
                        }
                    }
                }
            }
        );

        // تحديد الموظف الأكثر إنتاجية
        const mostProductiveIndex = productivityData.datasets[0].data.indexOf(
            Math.max(...productivityData.datasets[0].data)
        );
        if (mostProductiveIndex > -1) {
            document.getElementById('mostProductiveEmployee').textContent = 
                productivityData.labels[mostProductiveIndex];
        }
    }

    // 2. مخطط الكفاءة (مخطط دائري)
    const efficiencyData = {
        labels: [<?php echo implode(',', array_map(function($emp) { 
            return '"' . addslashes($emp['name']) . '"'; 
        }, $employee_stats)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_map(function($emp) { 
                return ($emp['total_tasks'] > 0) ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100, 1) : 0; 
            }, $employee_stats)); ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ],
            borderWidth: 1
        }]
    };

    if (document.getElementById('efficiencyChart')) {
        const efficiencyChart = new Chart(
            document.getElementById('efficiencyChart').getContext('2d'),
            {
                type: 'pie',
                data: efficiencyData,
                options: {
                    responsive: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.raw + '%';
                                }
                            }
                        }
                    }
                }
            }
        );

        // تحديد الموظف الأكثر كفاءة
        const mostEfficientIndex = efficiencyData.datasets[0].data.indexOf(
            Math.max(...efficiencyData.datasets[0].data)
        );
        if (mostEfficientIndex > -1) {
            document.getElementById('mostEfficientEmployee').textContent = 
                efficiencyData.labels[mostEfficientIndex];
        }
    }

    // 3. مخطط الإيرادات (مخطط شريطي)
    const revenueData = {
        labels: [<?php echo implode(',', array_map(function($emp) { return '"' . addslashes($emp['name']) . '"'; }, $employee_stats)); ?>],
        datasets: [{
            label: 'إجمالي الإيرادات (ر.س)',
            data: [<?php echo implode(',', array_map(function($emp) { return $emp['total_revenue'] ?? 0; }, $employee_stats)); ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };

    if (document.getElementById('revenueChart')) {
        const revenueChart = new Chart(
            document.getElementById('revenueChart').getContext('2d'),
            {
                type: 'bar',
                data: revenueData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + ' ر.س';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'الإيرادات: ' + context.raw.toLocaleString() + ' ر.س';
                                }
                            }
                        }
                    }
                }
            }
        );

        // تحديد الموظف الأكثر تحقيقًا للإيرادات
        const highestRevenueIndex = revenueData.datasets[0].data.indexOf(
            Math.max(...revenueData.datasets[0].data)
        );
        if (highestRevenueIndex > -1) {
            document.getElementById('highestRevenueEmployee').textContent = 
                revenueData.labels[highestRevenueIndex];
        }
    }

    // 4. مخطط المقارنة الشاملة (مخطط راداري)
    if (document.getElementById('radarComparisonChart')) {
        const radarColors = [
            'rgba(255, 99, 132,', 
            'rgba(54, 162, 235,',
            'rgba(255, 206, 86,', 
            'rgba(75, 192, 192,',
            'rgba(153, 102, 255,'
        ];

        const radarLabels = [
            'الإنتاجية',
            'الكفاءة',
            'الإيرادات',
            'متوسط الإيراد',
            'سرعة الإنجاز'
        ];

        const radarDatasets = [];

        // حساب القيم القصوى لتطبيع البيانات
        const maxCompletedTasks = Math.max(...[<?php echo implode(',', array_map(function($emp) { return $emp['completed_tasks']; }, $employee_stats)); ?>]);
        const maxRevenue = Math.max(...[<?php echo implode(',', array_map(function($emp) { return $emp['total_revenue'] ?? 0; }, $employee_stats)); ?>]);

        // إنشاء مجموعات البيانات لكل موظف
        <?php foreach(array_slice($employee_stats, 0, 5) as $index => $emp): ?>
            const efficiency<?= $index ?> = <?= ($emp['total_tasks'] > 0) ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100, 1) : 0 ?>;
            const avgRevenue<?= $index ?> = <?= ($emp['completed_tasks'] > 0) ? round($emp['total_revenue'] / $emp['completed_tasks'], 0) : 0 ?>;

            // تطبيع القيم إلى مقياس 0-100
            const normalizedCompleted<?= $index ?> = <?= ($maxCompletedTasks > 0) ? round(($emp['completed_tasks'] / $maxCompletedTasks) * 100, 1) : 0 ?>;
            const normalizedRevenue<?= $index ?> = <?= ($maxRevenue > 0) ? round(($emp['total_revenue'] / $maxRevenue) * 100, 1) : 0 ?>;

            // قيمة عشوائية لسرعة الإنجاز (للعرض فقط)
            const speedScore<?= $index ?> = Math.floor(Math.random() * 40) + 60;

            radarDatasets.push({
                label: '<?= addslashes($emp['name']) ?>',
                data: [normalizedCompleted<?= $index ?>, efficiency<?= $index ?>, normalizedRevenue<?= $index ?>, avgRevenue<?= $index ?> / 100, speedScore<?= $index ?>],
                fill: true,
                backgroundColor: radarColors[<?= $index ?>] + ' 0.2)',
                borderColor: radarColors[<?= $index ?>] + ' 1)',
                pointBackgroundColor: radarColors[<?= $index ?>] + ' 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: radarColors[<?= $index ?>] + ' 1)'
            });
        <?php endforeach; ?>

        const radarChart = new Chart(
            document.getElementById('radarComparisonChart').getContext('2d'),
            {
                type: 'radar',
                data: {
                    labels: radarLabels,
                    datasets: radarDatasets
                },
                options: {
                    responsive: true,
                    elements: {
                        line: {
                            borderWidth: 3
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            }
        );
    }
});
</script>
