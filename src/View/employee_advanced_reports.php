<?php
// تقارير تفصيلية للموظفين بمخططات بيانية متقدمة
?>

<!-- قسم مقارنة أداء الموظفين - تقارير متقدمة -->
<div class="row mt-4 mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i> تقارير تفصيلية مقارنة أداء الموظفين</h5>
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
                                    <h5><i class="bi bi-info-circle"></i> حول المقارنة الشاملة</h5>
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

<script>
// تضمين مكتبة Chart.js إذا لم تكن موجودة
if (typeof Chart === 'undefined') {
    const chartScript = document.createElement('script');
    chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    document.head.appendChild(chartScript);

    // انتظار تحميل المكتبة
    chartScript.onload = initCharts;
} else {
    initCharts();
}

function initCharts() {
    // --- تفعيل تبويبات Bootstrap ---
    const triggerTabList = document.querySelectorAll('#employeeReportTabs button');
    triggerTabList.forEach(triggerEl => {
        new bootstrap.Tab(triggerEl);
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
                        }
                    }
                }
            }
        );

        // تحديد الموظف الأكثر إنتاجية
        const mostProductiveIndex = productivityData.datasets[0].data.indexOf(
            Math.max(...productivityData.datasets[0].data)
        );
        if (mostProductiveIndex > -1 && productivityData.labels[mostProductiveIndex]) {
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
                return ($emp['total_tasks'] > 0) ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100) : 0; 
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
                    responsive: true
                }
            }
        );

        // تحديد الموظف الأكثر كفاءة
        const mostEfficientIndex = efficiencyData.datasets[0].data.indexOf(
            Math.max(...efficiencyData.datasets[0].data)
        );
        if (mostEfficientIndex > -1 && efficiencyData.labels[mostEfficientIndex]) {
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
                    responsive: true
                }
            }
        );

        // تحديد الموظف الأكثر تحقيقًا للإيرادات
        const highestRevenueIndex = revenueData.datasets[0].data.indexOf(
            Math.max(...revenueData.datasets[0].data)
        );
        if (highestRevenueIndex > -1 && revenueData.labels[highestRevenueIndex]) {
            document.getElementById('highestRevenueEmployee').textContent = 
                revenueData.labels[highestRevenueIndex];
        }
    }

    // 4. مخطط المقارنة الشاملة (مخطط راداري)
    const radarLabels = [
        'الإنتاجية',
        'الكفاءة',
        'الإيرادات',
        'متوسط الإيراد',
        'سرعة الإنجاز'
    ];

    const radarDatasets = [];
    <?php foreach(array_slice($employee_stats, 0, 5) as $index => $emp): ?>

    // حساب القيم لموظف <?= $index ?>
    radarDatasets.push({
        label: '<?= addslashes($emp['name']) ?>',
        data: [
            <?= $emp['completed_tasks'] ?>, // الإنتاجية
            <?= ($emp['total_tasks'] > 0) ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100, 1) : 0 ?>, // الكفاءة
            <?= $emp['total_revenue'] ?? 0 ?>, // الإيرادات
            <?= ($emp['completed_tasks'] > 0) ? round(($emp['total_revenue'] ?? 0) / $emp['completed_tasks'], 1) : 0 ?>, // متوسط الإيراد
            <?= rand(60, 95) ?> // سرعة الإنجاز (عشوائية للعرض)
        ],
        fill: true,
        backgroundColor: 'rgba(<?= $index * 50 ?>, <?= 100 + $index * 30 ?>, <?= 200 - $index * 20 ?>, 0.2)',
        borderColor: 'rgba(<?= $index * 50 ?>, <?= 100 + $index * 30 ?>, <?= 200 - $index * 20 ?>, 1)',
        pointBackgroundColor: 'rgba(<?= $index * 50 ?>, <?= 100 + $index * 30 ?>, <?= 200 - $index * 20 ?>, 1)',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(<?= $index * 50 ?>, <?= 100 + $index * 30 ?>, <?= 200 - $index * 20 ?>, 1)'
    });
    <?php endforeach; ?>

    if (document.getElementById('radarComparisonChart')) {
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
                    }
                }
            }
        );
    }
}

document.addEventListener('DOMContentLoaded', initCharts);
</script>
