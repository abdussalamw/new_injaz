<?php
$page_title = 'لوحة التحكم';

// دالة لجلب بيانات الرسوم البيانية (تأكد من أنها معرفة مرة واحدة فقط)
if (!function_exists('get_chart_data')) {
    function get_chart_data($type, $conn) {
        $limit = "LIMIT 5";
        switch($type) {
            case 'top_products':
                $sql = "SELECT p.name, COUNT(oi.product_id) as sales_count 
                        FROM products p 
                        LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                        LEFT JOIN orders o ON oi.order_id = o.order_id 
                        WHERE o.status != 'ملغي'
                        GROUP BY p.product_id, p.name 
                        ORDER BY sales_count DESC {$limit}";
                break;
            case 'clients':
                $sql = "SELECT c.company_name, COUNT(o.order_id) as orders_count 
                        FROM clients c 
                        LEFT JOIN orders o ON c.client_id = o.client_id 
                        WHERE o.status != 'ملغي'
                        GROUP BY c.client_id, c.company_name 
                        ORDER BY orders_count DESC {$limit}";
                break;
            case 'employees':
                $sql = "SELECT e.name, COUNT(o.order_id) as tasks_count 
                        FROM employees e 
                        LEFT JOIN orders o ON e.employee_id = o.designer_id 
                        WHERE o.status = 'مكتمل'
                        GROUP BY e.employee_id, e.name 
                        ORDER BY tasks_count DESC {$limit}";
                break;
            default:
                return [];
        }
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// --- جلب البيانات الأولية للصفحة ---

// 1. العدادات
$orders_count = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$clients_count = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
$employees_count = $conn->query("SELECT COUNT(*) FROM employees")->fetch_row()[0];
$products_count = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];

// 2. إحصائيات وتقارير للمدراء
$employee_stats = [];
$overall_stats = ['open' => 0, 'closed' => 0, 'total' => 0];
if (\App\Core\Permissions::has_permission('dashboard_reports_view', $conn)) {
    // إحصائيات الموظفين (المهام المفتوحة والمهام المستحقة اليوم)
    $stmt_employees = $conn->prepare("
        SELECT e.employee_id, e.name,
               COUNT(CASE WHEN o.status NOT IN ('مكتمل', 'ملغي') THEN o.order_id END) AS total_open_tasks,
               SUM(CASE WHEN o.due_date = CURDATE() AND o.status NOT IN ('مكتمل', 'ملغي') THEN 1 ELSE 0 END) AS tasks_due_today
        FROM employees e
        LEFT JOIN orders o ON e.employee_id = o.designer_id
        GROUP BY e.employee_id, e.name ORDER BY e.name");
    $stmt_employees->execute();
    $employee_stats = $stmt_employees->get_result()->fetch_all(MYSQLI_ASSOC);

    // المهام المنجزة شهرياً
    $stmt_monthly = $conn->prepare("SELECT designer_id, COUNT(*) as monthly_closed_tasks FROM orders WHERE status = 'مكتمل' AND order_date >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY designer_id");
    $stmt_monthly->execute();
    $monthly_tasks_map = array_column($stmt_monthly->get_result()->fetch_all(MYSQLI_ASSOC), 'monthly_closed_tasks', 'designer_id');

    foreach ($employee_stats as &$stat) {
        $stat['monthly_closed_tasks'] = $monthly_tasks_map[$stat['employee_id']] ?? 0;
    }
    unset($stat);

    // الإحصائيات الإجمالية
    $stmt_overall = $conn->prepare("SELECT SUM(CASE WHEN status = 'مكتمل' THEN 1 ELSE 0 END) as closed_count, SUM(CASE WHEN status NOT IN ('مكتمل', 'ملغي') THEN 1 ELSE 0 END) as open_count FROM orders");
    $stmt_overall->execute();
    $overall_res = $stmt_overall->get_result()->fetch_assoc();
    $overall_stats['closed'] = $overall_res['closed_count'] ?? 0;
    $overall_stats['open'] = $overall_res['open_count'] ?? 0;
    $overall_stats['total'] = $overall_stats['closed'] + $overall_stats['open'];
}

// 3. بيانات الفلاتر
$employees_res = $conn->query("SELECT employee_id, name FROM employees ORDER BY name");
$employees_list = $employees_res->fetch_all(MYSQLI_ASSOC);

// 4. جلب المهام الأولية (بدون فلترة)
$initial_filter_status = $_GET['status'] ?? '';
$initial_filter_employee = $_GET['employee'] ?? '';
$initial_filter_payment = $_GET['payment'] ?? '';
$initial_filter_search = $_GET['search'] ?? '';
$initial_sort_by = $_GET['sort_by'] ?? 'latest';

$res = \App\Core\InitialTasksQuery::fetch_tasks($conn, $initial_filter_status, $initial_filter_employee, $initial_filter_payment, $initial_filter_search, $initial_sort_by);



// 5. تحديد العنوان والتبويب النشط
$dashboard_title = \App\Core\Permissions::has_permission('order_view_own', $conn) && !\App\Core\Permissions::has_permission('order_view_all', $conn) ? 'المهام الموكلة إليك' : 'أحدث المهام النشطة';
$active_tab = $_GET['tab'] ?? 'tasks';
$default_active_tab = $active_tab === 'stats' ? 'StatsReports' : ($active_tab === 'reports' ? 'CustomReports' : 'Tasks');
?>

<style>
    .tab-container { 
        width: 100%; 
        background-color: #fff; 
        border-radius: 8px; 
        overflow: hidden; 
        margin: 0; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .tab-buttons { 
        overflow: hidden; 
        border-bottom: 1px solid #dee2e6; 
        background-color: #f8f9fa; 
        margin: 0;
        padding: 0;
    }
    .tab-buttons button { 
        background-color: inherit; 
        float: right; 
        border: none; 
        outline: none; 
        cursor: pointer; 
        padding: 14px 20px; 
        transition: background-color 0.3s, color 0.3s; 
        font-size: 17px; 
        color: #495057; 
        min-width: 120px;
    }
    .tab-buttons button:hover { background-color: #e9ecef; }
    .tab-buttons button.active { 
        background-color: #fff; 
        font-weight: bold; 
        color: #D44759; 
        border-top: 3px solid #D44759; 
    }
    .tab-content { 
        display: none !important; 
        padding: 15px; 
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .tab-content.active { 
        display: block !important; 
        width: 100% !important;
    }
    #tasks-container .spinner-border { width: 3rem; height: 3rem; }

    /* تحسين البطاقات للتوسع الكامل */
    .dashboard-cards {
        margin: 0 !important;
        width: 100% !important;
    }
    .dashboard-cards .col-md-6,
    .dashboard-cards .col-lg-3 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* تحسين النماذج للتوسع */
    #filter-form {
        margin: 0 0 20px 0 !important;
        padding: 15px !important;
        width: 100%;
        max-width: 100%;
    }
</style>

<div class="container-fluid">
    <div id="status-update-feedback" class="mb-3"></div>
    <div class="tab-container shadow-sm">
        <div class="tab-buttons">
            <button class="tab-link <?= $default_active_tab === 'Tasks' ? 'active' : '' ?>" onclick="openTab(event, 'Tasks')">المهام</button>
            <?php if (\App\Core\Permissions::has_permission('dashboard_reports_view', $conn)): ?>
                <button class="tab-link <?= $default_active_tab === 'StatsReports' ? 'active' : '' ?>" onclick="openTab(event, 'StatsReports')">الإحصائيات</button>
            <?php endif; ?>
            <?php if (\App\Core\Permissions::has_permission('financial_reports_view', $conn)): ?>
                <button class="tab-link <?= $default_active_tab === 'CustomReports' ? 'active' : '' ?>" onclick="openTab(event, 'CustomReports')">التقارير المالية</button>
            <?php endif; ?>
        </div>

        <!-- ==================== تبويب المهام ==================== -->
        <div id="Tasks" class="tab-content <?= $default_active_tab === 'Tasks' ? 'active' : '' ?>">
            <?php if (\App\Core\Permissions::has_permission('order_view_all', $conn) || \App\Core\Permissions::has_permission('order_view_own', $conn)): ?>
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
            <?php endif; ?>

            <h4 style="color:#D44759;" class="mt-4 mb-3"><?= $dashboard_title ?></h4>
            
            
            <div class="row g-3 dashboard-cards" id="tasks-container">
                <?php if($res && $res->num_rows > 0): ?>
                    <?php while($row = $res->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-3">
                            <?php 
                            $task_details = $row;
                            // تحديد الإجراءات المتاحة بناءً على حالة المهمة ودور المستخدم
                            $actions = \App\Core\Helpers::get_next_actions($row, $_SESSION['user_role'], $_SESSION['user_id'], $conn, 'dashboard'); 
                            include __DIR__ . '/task/card.php'; 
                            ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-info text-center">لا توجد مهام لعرضها حالياً.</div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== تبويب الإحصائيات ==================== -->
        <?php if (\App\Core\Permissions::has_permission('dashboard_reports_view', $conn)): ?>
        <div id="StatsReports" class="tab-content <?= $default_active_tab === 'StatsReports' ? 'active' : '' ?>">
            <?php include __DIR__ . '/../Reports/Stats.php'; ?>
        </div>
        <?php endif; ?>

        <!-- ==================== تبويب التقارير المالية ==================== -->
        <?php if (\App\Core\Permissions::has_permission('financial_reports_view', $conn)): ?>
        <div id="CustomReports" class="tab-content <?= $default_active_tab === 'CustomReports' ? 'active' : '' ?>">
            <?php include __DIR__ . '/../Reports/Financial.php'; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Tabs ---
    function openTab(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(tab => tab.classList.remove("active"));
        document.querySelectorAll(".tab-link").forEach(link => link.classList.remove("active"));
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }
    
    // Make it globally accessible
    window.openTab = openTab;

    // --- AJAX Filtering ---
    const filterForm = document.getElementById('filter-form');
    const tasksContainer = document.getElementById('tasks-container');
    const resetBtn = document.getElementById('reset-filters-btn');

    function applyFilters() {
        if (!filterForm || !tasksContainer) return;
        
        const formData = new FormData(filterForm);
        const urlParams = new URLSearchParams(formData);
        
        tasksContainer.innerHTML = '<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></div>';

        fetch('<?= $_ENV['BASE_PATH'] ?>/api/tasks?' + urlParams.toString(), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(data => {
            tasksContainer.innerHTML = data;
            // Re-bind action buttons for the new content
            bindActionButtons(); 
        })
        .catch(error => {
            console.error('Error:', error);
            tasksContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger text-center">حدث خطأ أثناء تحديث المهام.</div></div>';
        });
    }

    if (filterForm) {
        filterForm.querySelectorAll('select, input').forEach(element => {
            if (element.type === 'text') {
                // تأخير بسيط قبل إرسال الطلب لتقليل الضغط على الخادم أثناء الكتابة
                let timeout = null;
                element.addEventListener('keyup', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        applyFilters();
                    }, 500); // 500ms delay
                });
            } else {
                element.addEventListener('change', applyFilters);
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            filterForm.reset();
            applyFilters();
        });
    }

    // --- Action Buttons (SweetAlert) ---
    function bindActionButtons() {
        document.querySelectorAll('.action-btn').forEach(button => {
            // Remove existing listener to prevent duplicates
            button.replaceWith(button.cloneNode(true));
        });

        document.querySelectorAll('.action-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation(); // منع أي مستمعي أحداث آخرين من التداخل
                const btn = this;
                const orderId = btn.dataset.orderId;
                const action = btn.dataset.action;
                const value = btn.dataset.value || null;
                const confirmMessage = btn.dataset.confirmMessage;

                const whatsappPhone = btn.dataset.whatsappPhone;
                const whatsappOrderId = btn.dataset.whatsappOrderId;

                // معالجة خاصة لزر تحديث الدفع (للمحاسب)
                if (action === 'update_payment') {
                    showPaymentUpdateModal(orderId);
                    return;
                }

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
                        Swal.fire({
                            title: 'الرجاء الانتظار...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                            // تحديد المسار المناسب لكل إجراء
                            let apiUrl = '<?= $_ENV['BASE_PATH'] ?>/api/orders/status';
                            if (action === 'confirm_payment') {
                                apiUrl = '<?= $_ENV['BASE_PATH'] ?>/api/orders/confirm-payment';
                            } else if (action === 'confirm_delivery') {
                                apiUrl = '<?= $_ENV['BASE_PATH'] ?>/api/orders/confirm-delivery';
                            }

                            const requestData = { order_id: orderId };
                            // فقط تغيير الحالة يحتاج value و action
                            if (apiUrl.endsWith('/status')) {
                                requestData.action = action;
                                requestData.value = value;
                            }

                            fetch(apiUrl, {
                                method: 'POST',
                                headers: { 
                                    'Content-Type': 'application/json', 
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify(requestData)
                            })
                        .then(response => {
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                if (whatsappPhone && whatsappOrderId) {
                                    const whatsappMessage = `العميل العزيز، طلبكم رقم ${whatsappOrderId} جاهز للتسليم. شكراً لتعاملكم معنا.`;
                                    const encodedMessage = encodeURIComponent(whatsappMessage);
                                    const internationalPhone = '966' + whatsappPhone.substring(1);
                                    const whatsappUrl = `https://wa.me/${internationalPhone}?text=${encodedMessage}`;

                                    Swal.fire({
                                        title: 'تم بنجاح!',
                                        text: data.message,
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonText: 'فتح واتساب',
                                        cancelButtonText: 'إغلاق',
                                        confirmButtonColor: '#25d366'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.open(whatsappUrl, '_blank');
                                        }
                                        // Refresh only the tasks list for better UX
                                        applyFilters(); 
                                    });
                                } else {
                                    Swal.fire('تم بنجاح!', data.message, 'success').then(() => {
                                        // Refresh only the tasks list for better UX
                                        applyFilters(); 
                                    });
                                }
                            } else {
                                Swal.fire('خطأ!', data.message, 'error');
                            }
                        }).catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ فني!', 'حدث خطأ غير متوقع.', 'error');
                        });
                    }
                });
            });
        });
    }

    // --- نافذة تحديث الدفع ---
    function showPaymentUpdateModal(orderId) {
        // جلب بيانات الطلب أولاً
        fetch(`edit_order.php?id=${orderId}&ajax=1`)
            .then(response => response.json())
            .then(orderData => {
                const totalAmount = parseFloat(orderData.total_amount || 0);
                const currentDeposit = parseFloat(orderData.deposit_amount || 0);
                const remainingAmount = totalAmount - currentDeposit;

                Swal.fire({
                    title: 'تحديث حالة الدفع',
                    html: `
                        <div class="text-start mb-3">
                            <div class="row mb-2">
                                <div class="col-6"><strong>المبلغ الإجمالي:</strong></div>
                                <div class="col-6">${totalAmount.toFixed(2)} ر.س</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>المدفوع حالياً:</strong></div>
                                <div class="col-6">${currentDeposit.toFixed(2)} ر.س</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><strong>المتبقي:</strong></div>
                                <div class="col-6 text-danger">${remainingAmount.toFixed(2)} ر.س</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_amount" class="form-label">مبلغ الدفعة الجديدة:</label>
                            <input type="number" id="payment_amount" class="form-control" 
                                   min="0.01" max="${remainingAmount}" step="0.01" 
                                   placeholder="أدخل مبلغ الدفعة">
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">طريقة الدفع:</label>
                            <select id="payment_method" class="form-select">
                                <option value="">اختر طريقة الدفع</option>
                                <option value="نقدي">نقدي</option>
                                <option value="تحويل بنكي">تحويل بنكي</option>
                                <option value="فوري">فوري</option>
                                <option value="غيره">غيره</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_notes" class="form-label">ملاحظات (اختياري):</label>
                            <textarea id="payment_notes" class="form-control" rows="2" 
                                      placeholder="أي ملاحظات إضافية..."></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'حفظ الدفعة',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    preConfirm: () => {
                        const paymentAmount = document.getElementById('payment_amount').value;
                        const paymentMethod = document.getElementById('payment_method').value;
                        const paymentNotes = document.getElementById('payment_notes').value;

                        if (!paymentAmount || parseFloat(paymentAmount) <= 0) {
                            Swal.showValidationMessage('يجب إدخال مبلغ صحيح أكبر من صفر');
                            return false;
                        }

                        if (parseFloat(paymentAmount) > remainingAmount) {
                            Swal.showValidationMessage(`المبلغ يتجاوز المتبقي (${remainingAmount.toFixed(2)} ر.س)`);
                            return false;
                        }

                        if (!paymentMethod) {
                            Swal.showValidationMessage('يجب اختيار طريقة الدفع');
                            return false;
                        }

                        return {
                            payment_amount: paymentAmount,
                            payment_method: paymentMethod,
                            notes: paymentNotes
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // إرسال البيانات لتحديث الدفع
                        const formData = new FormData();
                        formData.append('order_id', orderId);
                        formData.append('payment_amount', result.value.payment_amount);
                        formData.append('payment_method', result.value.payment_method);
                        formData.append('notes', result.value.notes);

                        Swal.fire({
                            title: 'جاري الحفظ...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        fetch('ajax_update_payment.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('تم بنجاح!', data.message, 'success').then(() => {
                                    applyFilters(); // تحديث قائمة المهام
                                });
                            } else {
                                Swal.fire('خطأ!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ فني!', 'حدث خطأ غير متوقع.', 'error');
                        });
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching order data:', error);
                Swal.fire('خطأ!', 'فشل في جلب بيانات الطلب.', 'error');
            });
    }

    // Initial bind
    bindActionButtons();
    
    // --- اختبار تشخيصي بسيط ---
    const testButton = document.getElementById('cline-test-button');
    if (testButton) {
        testButton.addEventListener('click', function() {
            alert('🎉 ممتاز! الكود الجديد يعمل بشكل صحيح. هذا يعني أن المشكلة ليست في التخزين المؤقت.');
        });
    }
});

</script>
