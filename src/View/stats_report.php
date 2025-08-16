<?php
// This file is included from Stats.php, so it has access to all the variables.
?>
<style>
/* ==================== التصميم المودرن للإحصائيات ==================== */
.stats-modern-container {
    background-color: #f8f9fa;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
}

.period-selector {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.period-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.period-tab {
    background: #e9ecef;
    border: 2px solid transparent;
    color: #495057;
    padding: 8px 16px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 14px;
    text-align: center;
}
.period-tab:hover {
    background: #dee2e6;
    transform: translateY(-2px);
    color: #212529;
    text-decoration: none;
}
.period-tab.active {
    background: #0d6efd;
    color: white;
    font-weight: bold;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px 15px;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.stat-icon {
    font-size: 2.2rem;
    margin-bottom: 12px;
    display: block;
    color: #0d6efd;
}

.stat-value {
    font-size: 1.9rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #212529;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.custom-date-input {
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 10px;
    color: #333;
    width: 100%;
}

.btn-apply {
    background: #0d6efd;
    color: white;
    border: 2px solid #0d6efd;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s ease;
    cursor: pointer;
}
.btn-apply:hover {
    background: #0b5ed7;
    border-color: #0b5ed7;
}

.employee-comparison, .top-lists-container {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.employee-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}
.employee-card:hover {
    transform: translateX(3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}
.employee-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.employee-name { font-size: 1.1rem; font-weight: bold; color: #343a40; }
.employee-role { background: #e9ecef; color: #495057; padding: 3px 8px; border-radius: 15px; font-size: 0.8rem; }
.employee-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; }
.employee-stat { text-align: center; }
.employee-stat-value { font-size: 1.3rem; font-weight: bold; margin-bottom: 3px; color: #0d6efd; }
.employee-stat-label { font-size: 0.75rem; color: #6c757d; }

.top-list-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
}
.top-list-header { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; color: #343a40; }
.top-item { display: flex; align-items: center; padding: 10px; margin-bottom: 8px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef; transition: all 0.3s ease; }
.top-item:hover { transform: scale(1.02); }
.top-item-rank { background: #0d6efd; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-left: 12px; }
.top-item-details { flex: 1; }
.top-item-name { font-weight: bold; margin-bottom: 3px; color: #495057; }
.top-item-value { color: #6c757d; font-size: 0.9rem; }

</style>

<div class="stats-modern-container">
    <!-- فلاتر الفترة والموظف -->
    <div class="period-selector">
        <h4 class="mb-3 text-dark">📊 <?= htmlspecialchars($period_label) ?></h4>
        <form id="statsFilterForm" method="GET" class="needs-validation" novalidate>
            <input type="hidden" name="tab" value="stats">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">فترة الإحصائيات</label>
                    <div class="period-tabs">
                        <a href="?tab=stats&period=daily" class="period-tab <?= $period === 'daily' ? 'active' : '' ?>">يومي</a>
                        <a href="?tab=stats&period=weekly" class="period-tab <?= $period === 'weekly' ? 'active' : '' ?>">أسبوعي</a>
                        <a href="?tab=stats&period=monthly" class="period-tab <?= $period === 'monthly' ? 'active' : '' ?>">شهري</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="customDate" class="form-label">اختر تاريخ</label>
                    <input type="date" id="customDate" name="custom_date" value="<?= htmlspecialchars($custom_date) ?>" class="form-control custom-date-input">
                </div>
                <div class="col-md-3">
                    <label for="statsEmployee" class="form-label">اختر موظف</label>
                    <select id="statsEmployee" name="stats_employee" class="form-select custom-date-input">
                        <option value="">جميع الموظفين</option>
                        <?php foreach ($employees_list as $emp): ?>
                            <option value="<?= $emp['employee_id'] ?>" <?= $emp['employee_id'] == $selected_employee ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-apply w-100">تطبيق</button>
                </div>
            </div>
        </form>
    </div>

    <!-- عرض الإحصائيات العامة -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon">📋</span>
            <div class="stat-value"><?= number_format($stats['total_orders'] ?? 0) ?></div>
            <div class="stat-label">إجمالي الطلبات</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">✅</span>
            <div class="stat-value"><?= number_format($stats['completed_orders'] ?? 0) ?></div>
            <div class="stat-label">طلبات مكتملة</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">⏳</span>
            <div class="stat-value"><?= number_format($stats['pending_orders'] ?? 0) ?></div>
            <div class="stat-label">طلبات قيد التنفيذ</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">💰</span>
            <div class="stat-value"><?= number_format($stats['total_revenue'] ?? 0) ?></div>
            <div class="stat-label">إجمالي الإيرادات</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">💵</span>
            <div class="stat-value"><?= number_format($stats['paid_amount'] ?? 0) ?></div>
            <div class="stat-label">المبلغ المدفوع</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">📈</span>
            <div class="stat-value"><?= ($stats['total_orders'] ?? 0) > 0 ? round(($stats['completed_orders'] ?? 0) / $stats['total_orders'] * 100, 1) : 0 ?>%</div>
            <div class="stat-label">معدل الإنجاز</div>
        </div>
    </div>

    <!-- مقارنة أداء الموظفين -->
    <?php if (!empty($employee_stats) && empty($selected_employee)): ?>
    <div class="employee-comparison">
        <h3 class="mb-4 text-dark">👥 مقارنة أداء الموظفين</h3>
        <?php foreach ($employee_stats as $emp): ?>
            <div class="employee-card">
                <div class="employee-header">
                    <div class="employee-name"><?= htmlspecialchars($emp['name']) ?></div>
                    <div class="employee-role"><?= htmlspecialchars($emp['role']) ?></div>
                </div>
                <div class="employee-stats">
                    <div class="employee-stat">
                        <div class="employee-stat-value"><?= number_format($emp['total_tasks'] ?? 0) ?></div>
                        <div class="employee-stat-label">إجمالي المهام</div>
                    </div>
                    <div class="employee-stat">
                        <div class="employee-stat-value"><?= number_format($emp['completed_tasks'] ?? 0) ?></div>
                        <div class="employee-stat-label">مهام مكتملة</div>
                    </div>
                    <div class="employee-stat">
                        <div class="employee-stat-value"><?= ($emp['total_tasks'] ?? 0) > 0 ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100, 1) : 0 ?>%</div>
                        <div class="employee-stat-label">معدل الإنجاز</div>
                    </div>
                    <div class="employee-stat">
                        <div class="employee-stat-value"><?= number_format($emp['total_revenue'] ?? 0) ?></div>
                        <div class="employee-stat-label">الإيرادات</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- قوائم الأفضل -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="top-lists-container">
                <h3 class="mb-4 text-dark">🏆 أفضل العملاء</h3>
                <?php if (!empty($top_clients)): ?>
                    <?php foreach ($top_clients as $index => $client): ?>
                        <div class="top-item">
                            <div class="top-item-rank"><?= $index + 1 ?></div>
                            <div class="top-item-details">
                                <div class="top-item-name"><?= htmlspecialchars($client['company_name']) ?></div>
                                <div class="top-item-value"><?= $client['orders_count'] ?> طلب • <?= number_format($client['total_spent']) ?> ريال</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">لا توجد بيانات لعرضها.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="top-lists-container">
                <h3 class="mb-4 text-dark">🎯 أفضل المنتجات</h3>
                <?php if (!empty($top_products)): ?>
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="top-item">
                            <div class="top-item-rank"><?= $index + 1 ?></div>
                            <div class="top-item-details">
                                <div class="top-item-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="top-item-value"><?= $product['orders_count'] ?> طلب • <?= number_format($product['total_quantity']) ?> وحدة</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">لا توجد بيانات لعرضها.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // للتأكد من أن النقر على تبويبات الفترة لا يقدم النموذج
    const periodTabs = document.querySelectorAll('.period-tab');
    periodTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(window.location);
            const newPeriod = new URL(this.href).searchParams.get('period');
            
            url.searchParams.set('period', newPeriod);
            url.searchParams.set('custom_date', document.getElementById('customDate').value);
            url.searchParams.set('stats_employee', document.getElementById('statsEmployee').value);
            
            window.location.href = url.toString();
        });
    });
});
</script>
