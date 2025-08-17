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

.compact-matrix-wrapper{
    margin-top: 0;
    margin-bottom: 25px;
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    width: 100%;
}
.compact-matrix {
    width: 100%;
    margin-top: 15px;
    border-collapse: collapse;
    min-width: 800px;
}
.compact-matrix thead th{
    position:sticky;
    top:0;
    z-index:2; 
    background: #f8f9fa;
    border-bottom-width: 2px !important;
    font-weight: 600;
}
.compact-matrix td, .compact-matrix th{
    font-size:13px; 
    text-align: center; 
    vertical-align: middle;
    padding: 0.6rem 0.4rem;
}
.compact-matrix .compact-row-label { 
    text-align: right; 
    font-weight: 500;
}
.compact-matrix .zero {
    color: #adb5bd;
}
.compact-total{ 
    font-weight: bold; 
    color: #0d6efd; 
    background-color: rgba(13,110,253,0.05);
} 

.current-week-row{
    outline: 2px solid #ff9800;
    outline-offset: -2px;
}
.current-week-row td {
    background-color: #fffcf5 !important;
}
.current-week-row .compact-row-label, .current-week-row .compact-total {
    font-weight: bold;
    color: #e65100;
}

.employee-details {
    border-top: 1px solid #e9ecef;
    padding-top: 8px;
}
.employee-details small {
    font-size: 0.75rem;
    line-height: 1.3;
    color: #6c757d;
}

.top-cards-grid-clients {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 8px;
    margin-bottom: 20px;
}

.top-cards-grid-products {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

/* استجابة للشاشات الصغيرة */
@media (max-width: 768px) {
    .top-cards-grid-clients {
        grid-template-columns: repeat(5, 1fr);
        gap: 6px;
    }
    .top-cards-grid-products {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .top-cards-grid-clients {
        grid-template-columns: repeat(3, 1fr);
    }
    .top-cards-grid-products {
        grid-template-columns: repeat(2, 1fr);
    }
}

.top-mini-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.top-mini-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}

.top-rank {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #0d6efd;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}

.top-content {
    padding-top: 8px;
}

.top-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.top-stats {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.top-stats small {
    font-size: 0.7rem;
    color: #6c757d;
}
</style>

<div class="stats-modern-container">
    <!-- فلاتر الفترة والموظف -->
    <div class="period-selector">
        <h4 class="mb-1 text-dark">📊 إحصائيات الأداء</h4>
        <p class="text-muted mb-3">
            <small>🗓️ الفترة المختارة: <strong><?= htmlspecialchars($period_label) ?></strong></small>
        </p>
        <form id="statsFilterForm" method="GET" class="needs-validation" novalidate>
            <input type="hidden" name="tab" value="stats">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">اختر الشهر</label>
                    <select name="matrix_month" class="form-select custom-date-input">
                        <?php if (!empty($matrix_available_months)):
                            foreach ($matrix_available_months as $ym): ?>
                            <option value="<?= htmlspecialchars($ym) ?>" <?= ($ym === ($selected_matrix_month ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(substr($ym,5,2) . '/' . substr($ym,0,4)) ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">اختر الأسبوع (اختياري)</label>
                    <select name="week" class="form-select custom-date-input">
                        <option value="">كامل الشهر</option>
                        <?php if (!empty($weeks_list)):
                            foreach ($weeks_list as $week): ?>
                            <option value="<?= $week['number'] ?>" <?= ($week['number'] == $selected_week) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($week['label']) ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
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
                <div class="col-md-2">
                    <?php if (!empty($selected_employee) || !empty($selected_week)): ?>
                        <a href="?tab=stats&matrix_month=<?= urlencode($selected_matrix_month) ?>" class="btn btn-outline-secondary w-100">
                            <?= !empty($selected_week) ? 'إعادة تعيين' : 'إعادة تعيين الموظف' ?>
                        </a>
                    <?php endif; ?>
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

    <!-- الجدول المفصل -->
    <div class="compact-matrix-wrapper">
        <?php if (!empty($selected_matrix_month) && !empty($compact_columns)): ?>
        <h4 class="mt-0">📌 تفاصيل شهر <?= htmlspecialchars(substr($selected_matrix_month,5,2) . '/' . substr($selected_matrix_month,0,4)) ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered compact-matrix">
                <thead>
                    <tr>
                        <th>الفترة</th>
                        <?php foreach ($compact_columns as $empId => $meta): ?>
                            <th><span class="badge-col-header"><?= htmlspecialchars($meta['label']) ?></span></th>
                        <?php endforeach; ?>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compact_matrix as $rowLabel => $vals): 
                        $rowClass = ($is_current_month ?? false) && isset($current_week_row) && $current_week_row === $rowLabel ? 'current-week-row' : '';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="compact-row-label"><?= htmlspecialchars($rowLabel) ?></td>
                            <?php foreach ($compact_columns as $empId => $meta): $v = $vals[$empId] ?? 0; ?>
                                <td><?= $v > 0 ? $v : '<span class="zero">-</span>' ?></td>
                            <?php endforeach; ?>
                            <td class="compact-total"><?= $compact_totals[$rowLabel] ?? 0 ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <small class="text-muted d-block mt-2" style="font-size:11px;line-height:1.5;">
            • نشطة: طلبات الشهر المختار غير المكتملة وغير الملغاة.<br>
            • الأسابيع: تقسيم ديناميكي حتى 5 أسابيع عند الحاجة (1–7 / 8–14 / 15–21 / 22–28 / 29–نهاية الشهر).<br>
            • الأسبوع الحالي يظهر فقط للشهر الجاري.<br>
            • الشهر: جميع طلبات الشهر (مكتملة أو نشطة، عدا الملغاة).<br>
            • لا تُعرض أدوار بلا مهام.
        </small>
        <?php elseif (!empty($selected_matrix_month) && empty($compact_columns)): ?>
            <div class="alert alert-warning py-2 mb-0">لا توجد مهام في هذا الشهر.</div>
        <?php elseif (empty($selected_matrix_month)): ?>
            <div class="text-muted text-center py-4">
                <h5>📊 اختر شهراً لعرض الجدول المفصل</h5>
                <p>يمكنك اختيار شهر من القائمة أعلاه لعرض تفاصيل المهام والأسابيع</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- مقارنة أداء الموظفين -->
    <?php if (!empty($employee_stats) && empty($selected_employee)): ?>
    <div class="mb-4">
        <h3 class="mb-4 text-dark">👥 مقارنة أداء الموظفين</h3>
        <div class="stats-grid">
            <?php foreach ($employee_stats as $emp): ?>
                <div class="stat-card">
                    <span class="stat-icon">👤</span>
                    <div class="stat-value" style="font-size: 1.1rem;"><?= htmlspecialchars($emp['name']) ?></div>
                    <div class="stat-label"><?= htmlspecialchars($emp['role']) ?></div>
                    <div class="employee-details mt-2">
                        <small class="d-block">
                            <strong><?= number_format($emp['total_tasks'] ?? 0) ?></strong> مهمة | 
                            <strong><?= number_format($emp['completed_tasks'] ?? 0) ?></strong> مكتملة |
                            <strong><?= ($emp['total_tasks'] ?? 0) > 0 ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100, 1) : 0 ?>%</strong>
                        </small>
                        <div class="mt-1">
                            <a href="?tab=stats&debug_employee=<?= $emp['employee_id'] ?>&matrix_month=<?= urlencode($selected_matrix_month ?? '') ?><?= !empty($selected_week) ? '&week=' . urlencode($selected_week) : '' ?>" 
                               style="font-size: 10px; color: #666; text-decoration: none;">🔍 تفاصيل</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- قوائم الأفضل بتصميم البطاقات -->
    <div class="row">
        <div class="col-12 mb-4">
            <h3 class="mb-4 text-dark">🏆 أفضل العملاء</h3>
            <?php if (!empty($top_clients)): ?>
                <div class="top-cards-grid-clients">
                    <?php foreach ($top_clients as $index => $client): ?>
                        <div class="top-mini-card">
                            <div class="top-rank"><?= $index + 1 ?></div>
                            <div class="top-content">
                                <div class="top-name"><?= htmlspecialchars($client['company_name']) ?></div>
                                <div class="top-stats">
                                    <small><?= $client['orders_count'] ?> طلب</small>
                                    <small><?= number_format($client['total_spent']) ?> ر.س</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3">لا توجد بيانات لعرضها.</p>
            <?php endif; ?>
        </div>
        <div class="col-12 mb-4">
            <h3 class="mb-4 text-dark">🎯 أفضل المنتجات</h3>
            <?php if (!empty($top_products)): ?>
                <div class="top-cards-grid-products">
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="top-mini-card">
                            <div class="top-rank"><?= $index + 1 ?></div>
                            <div class="top-content">
                                <div class="top-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="top-stats">
                                    <small><?= $product['orders_count'] ?> طلب</small>
                                    <small><?= number_format($product['total_quantity']) ?> وحدة</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3">لا توجد بيانات لعرضها.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // فلترة ديناميكية تلقائية
    const monthSelect = document.querySelector('select[name="matrix_month"]');
    const weekSelect = document.querySelector('select[name="week"]');
    const employeeSelect = document.querySelector('select[name="stats_employee"]');
    
    if (monthSelect) {
        monthSelect.addEventListener('change', function() {
            // إضافة تأثير Loading
            this.style.opacity = '0.7';
            
            // إرسال النموذج فوراً بدون تعطيل العنصر
            this.form.submit();
        });
    }
    
    if (weekSelect) {
        weekSelect.addEventListener('change', function() {
            // إضافة تأثير Loading
            this.style.opacity = '0.7';
            
            // إرسال النموذج فوراً
            this.form.submit();
        });
    }
    
    if (employeeSelect) {
        employeeSelect.addEventListener('change', function() {
            // إضافة تأثير Loading
            this.style.opacity = '0.7';
            this.disabled = true;
            
            // إرسال النموذج
            this.form.submit();
        });
    }
});
</script>
