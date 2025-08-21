<?php
// نسخة تجريبية من صفحة لوحة التحكم (تبويب المهام فقط) لاختبارات مستقلة
$page_title = 'المهام - نسخة تجريبية';

$initial_filter_status = $_GET['status'] ?? '';
$initial_filter_employee = $_GET['employee'] ?? '';
$initial_filter_payment = $_GET['payment'] ?? '';
$initial_filter_search = $_GET['search'] ?? '';
$initial_sort_by = $_GET['sort_by'] ?? 'latest';

// نفس منهجية جلب الموظفين كما في الصفحة الأصلية
if (\App\Core\Permissions::has_permission('order_view_all', $conn)) {
    $employees_res = $conn->query("SELECT employee_id, name, role FROM employees ORDER BY name");
    $employees_list = $employees_res->fetch_all(MYSQLI_ASSOC);
} else {
    $employees_list = [];
}

$res = \App\Core\InitialTasksQuery::fetch_tasks($conn, $initial_filter_status, $initial_filter_employee, $initial_filter_payment, $initial_filter_search, $initial_sort_by);
?>
<div class="container-fluid">
    <div class="mb-3 p-3 border rounded bg-white">
        <label class="form-label fw-bold mb-1">تجربة أشكال أزرار الإجراءات:</label>
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <select id="actionStyleSelect" class="form-select form-select-sm">
                    <option value="1">النمط 1: شريط أفقي</option>
                    <option value="2">النمط 2: كبسولات (Pills)</option>
                    <option value="3">النمط 3: أيقونات فقط</option>
                    <option value="4">النمط 4: زر رئيسي + دوائر</option>
                    <option value="5">النمط 5: شريط سفلي</option>
                    <option value="6">النمط 6: بلاطات مصغّرة</option>
                </select>
            </div>
            <div class="col-md-8 small text-muted">
                هذا المعمل بصري فقط على نسخة الاختبار (لا تغيير وظيفي). غيّر النمط وشاهد تأثيره فوراً.
            </div>
        </div>
    </div>

    <style>
    /* أساسيات موحدة */
    body[class^='action-style-'] .task-actions button,
    body[class^='action-style-'] .task-actions a,
    body[class^='action-style-'] .task-actions .btn { transition: all .25s ease; }
    .task-actions { position: relative; }
    .task-actions .row { --gap: .5rem; }

    /* إخفاء حدود الأعمدة في التبديلات */
    body[class^='action-style-'] .task-actions .col-6 { padding:2px 4px; }

    /* النمط 1: شريط أفقي (أيقونة فوق نص) */
    body.action-style-1 .task-actions .row { display:flex; flex-wrap:nowrap; }
    body.action-style-1 .task-actions .col-6 { flex:1 1 0; max-width:unset; }
    body.action-style-1 .task-actions .btn { flex-direction:column; height:64px !important; font-size:11px; line-height:1.1; }
    body.action-style-1 .task-actions .btn i { margin:0 0 4px 0 !important; font-size:18px; }

    /* النمط 2 (مُحدث): كبسولات بحواف صغيرة وألوان عكسية */
    body.action-style-2 .task-actions .btn { 
        border-radius:12px; 
        height:40px !important; 
        font-size:12px; 
        background:#fff !important; 
        color:var(--c,#333) !important; 
        border:1px solid var(--c,#0d6efd) !important; 
        box-shadow:none; 
    }
    body.action-style-2 .task-actions .btn i { font-size:16px; }
    /* تحديد الألوان لكل زر عبر متغير --c */
    body.action-style-2 .task-actions .btn.btn-outline-primary { --c:#0d6efd; }
    body.action-style-2 .task-actions .btn.btn-warning { --c:#f7b731; }
    body.action-style-2 .task-actions .btn.btn-info { --c:#17a2b8; }
    body.action-style-2 .task-actions .btn.btn-success { --c:#25D366; }
    body.action-style-2 .task-actions .btn.btn-primary { --c:#0d6efd; }
    body.action-style-2 .task-actions .btn.btn-danger { --c:#dc3545; }
    body.action-style-2 .task-actions .btn.btn-secondary { --c:#6c757d; }
    body.action-style-2 .task-actions .btn:hover { background:var(--c,#0d6efd) !important; color:#fff !important; }

    /* النمط 3: أيقونات فقط (مربعات) */
    body.action-style-3 .task-actions .btn span.small { display:none !important; }
    body.action-style-3 .task-actions .btn { width:42px; height:42px !important; padding:0; justify-content:center; }
    body.action-style-3 .task-actions .row { display:flex; flex-wrap:wrap; }
    body.action-style-3 .task-actions .col-6 { flex:0 0 auto; width:auto; }
    body.action-style-3 .task-actions .btn i { margin:0 !important; font-size:20px; }

    /* النمط 4: زر رئيسي + دوائر */
    body.action-style-4 .task-actions .row { display:flex; flex-wrap:wrap; }
    body.action-style-4 .task-actions .col-6 { flex:0 0 auto; }
    body.action-style-4 .task-actions .col-6:first-child { flex:1 1 auto; }
    body.action-style-4 .task-actions .col-6:first-child .btn { height:40px !important; }
    body.action-style-4 .task-actions .col-6:not(:first-child) .btn { width:40px; height:40px !important; padding:0; border-radius:50%; }
    body.action-style-4 .task-actions .col-6:not(:first-child) .btn span.small { display:none; }
    body.action-style-4 .task-actions .col-6:not(:first-child) .btn i { margin:0 !important; }

    /* النمط 5: شريط سفلي داخل البطاقة */
    body.action-style-5 .task-actions { position:absolute; left:0; right:0; bottom:0; padding:4px 6px; background:#fafafa; border-top:1px solid #eee; }
    body.action-style-5 .task-actions .row { margin:0; }
    body.action-style-5 .task-actions .btn { height:34px !important; font-size:11px; }
    body.action-style-5 .card-body { padding-bottom:80px !important; }

    /* النمط 6: بلاطات مصغرة (شبكة) */
    body.action-style-6 .task-actions .row { display:grid; grid-template-columns:repeat(auto-fit,minmax(90px,1fr)); gap:6px; }
    body.action-style-6 .task-actions .col-6 { width:auto; padding:0 !important; }
    body.action-style-6 .task-actions .btn { height:60px !important; flex-direction:column; font-size:11px; }
    body.action-style-6 .task-actions .btn i { margin:0 0 4px 0 !important; font-size:18px; }

    /* تلوين موحد خفيف + تحسين hover لكل الأنماط */
    body[class^='action-style-'] .task-actions .btn { box-shadow:0 0 0 0 rgba(0,0,0,0); }
    body[class^='action-style-'] .task-actions .btn:hover { box-shadow:0 2px 6px rgba(0,0,0,.12); transform:translateY(-2px); }

    /* توافق RTL: إزالة الهوامش الفارغة في الأنماط التي تخفي النص */
    body.action-style-3 .task-actions .btn i,
    body.action-style-4 .task-actions .col-6:not(:first-child) .btn i { margin-inline:0 !important; }
    </style>
    <h3 class="mb-3" style="color:#D44759;">قائمة المهام (نسخة اختبارية)</h3>
    <form id="filter-form" class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
        <div class="col-md-3">
            <label for="search_filter" class="form-label">بحث</label>
            <input type="text" name="search" id="search_filter" class="form-control form-control-sm" value="<?= htmlspecialchars($initial_filter_search) ?>" placeholder="ابحث برقم الطلب، اسم العميل،...">
        </div>
        <div class="col-md-2">
            <label for="status_filter" class="form-label">الحالة</label>
            <select name="status" id="status_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="قيد التصميم" <?= $initial_filter_status == 'قيد التصميم' ? 'selected' : '' ?>>قيد التصميم</option>
                <option value="قيد التنفيذ" <?= $initial_filter_status == 'قيد التنفيذ' ? 'selected' : '' ?>>قيد التنفيذ</option>
                <option value="جاهز للتسليم" <?= $initial_filter_status == 'جاهز للتسليم' ? 'selected' : '' ?>>جاهز للتسليم</option>
            </select>
        </div>
        <?php if (\App\Core\Permissions::has_permission('order_view_all', $conn)): ?>
        <div class="col-md-2">
            <label for="employee_filter" class="form-label">الموظف</label>
            <select name="employee" id="employee_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <?php foreach ($employees_list as $employee): ?>
                    <option value="<?= $employee['employee_id'] ?>" <?= $initial_filter_employee == $employee['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-md-2">
            <label for="payment_filter" class="form-label">الدفع</label>
            <select name="payment" id="payment_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="مدفوع" <?= $initial_filter_payment == 'مدفوع' ? 'selected' : '' ?>>مدفوع</option>
                <option value="مدفوع جزئياً" <?= $initial_filter_payment == 'مدفوع جزئياً' ? 'selected' : '' ?>>مدفوع جزئياً</option>
                <option value="غير مدفوع" <?= $initial_filter_payment == 'غير مدفوع' ? 'selected' : '' ?>>غير مدفوع</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="sort_by" class="form-label">الترتيب حسب</label>
            <select name="sort_by" id="sort_by" class="form-select form-select-sm">
                <option value="latest" <?= ($initial_sort_by == 'latest') ? 'selected' : '' ?>>الأحدث</option>
                <option value="oldest" <?= ($initial_sort_by == 'oldest') ? 'selected' : '' ?>>الأقدم</option>
                <option value="payment" <?= ($initial_sort_by == 'payment') ? 'selected' : '' ?>>الدفع</option>
                <?php if (\App\Core\Permissions::has_permission('order_view_all', $conn)): ?>
                <option value="employee" <?= ($initial_sort_by == 'employee') ? 'selected' : '' ?>>الموظف</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-1 align-self-end">
            <button type="button" id="reset-filters-btn" class="btn btn-sm btn-outline-secondary w-100">إلغاء</button>
        </div>
    </form>
    <div class="row g-3" id="tasks2-container">
        <?php if($res && $res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <?php 
                    $task_details = $row;
                    $actions = \App\Core\Helpers::get_next_actions($row, \App\Core\RoleHelper::getCurrentUserRole(), \App\Core\RoleHelper::getCurrentUserId(), $conn, 'dashboard'); 
                    // استعمال نسخة خاصة للبطاقات بدون حالة الدفع
                    include __DIR__ . '/task/card_tasks2.php';
                    ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info text-center">لا توجد مهام حالياً.</div></div>
        <?php endif; ?>
    </div>
</div>
<script>
(function(){
    const form = document.getElementById('filter-form');
    const container = document.getElementById('tasks2-container');
    const resetBtn = document.getElementById('reset-filters-btn');

    function applyFilters(){
        const fd = new FormData(form);
        const params = new URLSearchParams(fd);
        container.innerHTML = '<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';
        fetch('<?= $_ENV['BASE_PATH'] ?>/api/tasks?' + params.toString(), { headers: { 'X-Requested-With':'XMLHttpRequest' } })
            .then(r=>r.text())
            .then(html=>{ container.innerHTML = html; })
            .catch(()=>{ container.innerHTML = '<div class="col-12"><div class="alert alert-danger">خطأ في التحديث.</div></div>'; });
    }

    form.querySelectorAll('select').forEach(sel=> sel.addEventListener('change', applyFilters));
    form.querySelectorAll('input[type=text]').forEach(inp=>{
        let t; inp.addEventListener('keyup', ()=>{ clearTimeout(t); t=setTimeout(applyFilters,400); });
    });
    resetBtn.addEventListener('click', ()=>{ form.reset(); applyFilters(); });
    // ---- تبديل الأنماط ----
    const actionStyleSelect = document.getElementById('actionStyleSelect');
    const applyActionStyle = (val)=>{
        // إزالة أي class سابق action-style-X
        document.body.className = document.body.className
            .split(/\s+/)
            .filter(c=>!c.startsWith('action-style-'))
            .join(' ');
        document.body.classList.add('action-style-' + val);
        localStorage.setItem('tasks2_action_style', val);
    };
    if(actionStyleSelect){
    const saved = localStorage.getItem('tasks2_action_style') || '2'; // الافتراضي الآن النمط 2
        actionStyleSelect.value = saved;
        applyActionStyle(saved);
        actionStyleSelect.addEventListener('change', e=> applyActionStyle(e.target.value));
    }

})();
</script>
