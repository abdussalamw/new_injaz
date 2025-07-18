<?php
$page_title = 'الطلبات';
include 'db_connection.php';
include 'header.php';

check_permission('order_view', $conn);

// --- Sorting Logic ---
$sort_column_key = $_GET['sort'] ?? 'order_id';
$sort_order = $_GET['order'] ?? 'DESC';

$column_map = [
    'order_id' => 'o.order_id',
    'client_name' => 'c.company_name',
    'designer_name' => 'e.name',
    'status' => 'o.status',
    'payment_status' => 'o.payment_status',
    'total_amount' => 'o.total_amount',
    'order_date' => 'o.order_date'
];
$allowed_sort_columns = array_keys($column_map);
if (!in_array($sort_column_key, $allowed_sort_columns)) {
    $sort_column_key = 'order_id';
}
if (strtoupper($sort_order) !== 'ASC' && strtoupper($sort_order) !== 'DESC') {
    $sort_order = 'DESC';
}
$sort_column_sql = $column_map[$sort_column_key];

function generate_sort_link($column_key, $display_text, $current_sort_key, $current_order) {
    $next_order = ($current_sort_key === $column_key && strtoupper($current_order) === 'ASC') ? 'DESC' : 'ASC';
    $query_params = $_GET;
    $query_params['sort'] = $column_key;
    $query_params['order'] = $next_order;
    $url = 'orders.php?' . http_build_query($query_params);
    
    $icon = '';
    if ($current_sort_key === $column_key) {
        $icon = (strtoupper($current_order) === 'ASC') ? ' <i class="fas fa-sort-up text-dark"></i>' : ' <i class="fas fa-sort-down text-dark"></i>';
    } else {
        $icon = ' <i class="fas fa-sort text-dark" style="opacity: 0.7;"></i>';
    }
    
    return '<a href="' . htmlspecialchars($url) . '" class="text-decoration-none text-dark d-flex align-items-center justify-content-center" style="cursor: pointer;">' . 
           '<span>' . htmlspecialchars($display_text) . '</span>' . $icon . '</a>';
}

function generate_non_sort_column($display_text) {
    return '<div class="d-flex align-items-center justify-content-center text-dark">' . 
           '<span>' . htmlspecialchars($display_text) . '</span>' . 
           ' <i class="fas fa-sort text-dark" style="opacity: 0.3;"></i></div>';
}
// --- End Sorting Logic ---

// --- Fetch employees for filtering ---
$employees_res = $conn->query("SELECT employee_id, name FROM employees ORDER BY name");
$employees_list = $employees_res->fetch_all(MYSQLI_ASSOC);

// --- استلام قيم الفلاتر من GET ---
$filter_status = $_GET['status'] ?? '';
$filter_employee = $_GET['employee'] ?? '';
$filter_payment = $_GET['payment'] ?? '';

// --- بناء الاستعلام الأساسي ---
$sql = "SELECT o.*, c.company_name AS client_name, e.name AS designer_name,
        COALESCE(GROUP_CONCAT(DISTINCT p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary, c.phone AS client_phone
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id";

// --- بناء شروط WHERE ---
$where_clauses = [];
$params = [];
$types = "";

// 1. Apply role restrictions first
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? 'guest';

if (!has_permission('order_view_all', $conn)) {
    // إذا لم يكن مديراً، طبق قيود الدور
    switch ($user_role) {
        case 'مصمم':
            $where_clauses[] = "o.designer_id = ?";
            $params[] = $user_id;
            $types .= "i";
            break;
        case 'معمل':
            $where_clauses[] = "TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم', 'مكتمل')";
            break;
        case 'محاسب':
            $where_clauses[] = "TRIM(o.status) != 'ملغي'";
            break;
        default:
            $where_clauses[] = "1=0";
            break;
    }
}

// 2. Apply filters selected by the user
if (!empty($filter_status)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (!empty($filter_employee)) {
    $where_clauses[] = "(o.designer_id = ? OR o.workshop_id = ?)";
    $params[] = $filter_employee;
    $params[] = $filter_employee;
    $types .= "ii";
}
if (!empty($filter_payment)) {
    $where_clauses[] = "o.payment_status = ?";
    $params[] = $filter_payment;
    $types .= "s";
}

// --- Assemble the final query ---
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY o.order_id ORDER BY $sort_column_sql $sort_order";

// --- تنفيذ الاستعلام ---
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
?>
<?php
// دالة لعرض "لا يوجد منتجات" إذا كان ملخص المنتجات فارغًا
function display_products_summary($summary) { return empty($summary) ? 'لا يوجد منتجات' : htmlspecialchars($summary); }
?>
<div class="container">
    <div id="status-update-feedback" class="mb-3"></div>
    

    <div class="mb-3">
        <?php if (has_permission('order_add', $conn)): ?>
            <a href="add_order.php" class="btn btn-success mb-2">إضافة طلب جديد</a>
        <?php endif; ?>
    </div>

    <?php if (has_permission('order_view_all', $conn)): ?>
    <form id="filter-form" class="row g-3 align-items-center mb-4 p-3 border rounded bg-light">
        <div class="col-md-3">
            <label for="status_filter" class="form-label">الحالة</label>
            <select name="status" id="status_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="قيد التصميم" <?= $filter_status == 'قيد التصميم' ? 'selected' : '' ?>>قيد التصميم</option>
                <option value="قيد التنفيذ" <?= $filter_status == 'قيد التنفيذ' ? 'selected' : '' ?>>قيد التنفيذ</option>
                <option value="جاهز للتسليم" <?= $filter_status == 'جاهز للتسليم' ? 'selected' : '' ?>>جاهز للتسليم</option>
                <option value="مكتمل" <?= $filter_status == 'مكتمل' ? 'selected' : '' ?>>مكتمل</option>
                <option value="ملغي" <?= $filter_status == 'ملغي' ? 'selected' : '' ?>>ملغي</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="employee_filter" class="form-label">الموظف</label>
            <select name="employee" id="employee_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <?php foreach ($employees_list as $employee): ?>
                    <option value="<?= $employee['employee_id'] ?>" <?= $filter_employee == $employee['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="payment_filter" class="form-label">الدفع</label>
            <select name="payment" id="payment_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="مدفوع" <?= $filter_payment == 'مدفوع' ? 'selected' : '' ?>>مدفوع</option>
                <option value="مدفوع جزئياً" <?= $filter_payment == 'مدفوع جزئياً' ? 'selected' : '' ?>>مدفوع جزئياً</option>
                <option value="غير مدفوع" <?= $filter_payment == 'غير مدفوع' ? 'selected' : '' ?>>غير مدفوع</option>
            </select>
        </div>
        <div class="col-md-3 align-self-end">
            <button type="button" id="reset-filters-btn" class="btn btn-sm btn-outline-secondary w-100">إلغاء الفلترة</button>
        </div>
    </form>
    <?php endif; ?>

    <table class="table table-bordered table-striped text-center" id="ordersMainTable">
        <thead style="background-color: #198754;">
            <tr>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('order_id', 'رقم الطلب', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('client_name', 'اسم العميل', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_non_sort_column('ملخص المنتجات') ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('designer_name', 'المصمم المسؤول', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('status', 'حالة الطلب', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('payment_status', 'حالة الدفع', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('total_amount', 'المبلغ الإجمالي', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_sort_link('order_date', 'تاريخ الإنشاء', $sort_column_key, $sort_order) ?>
                </th>
                <th style="background-color: #198754; color: black; border: none;">
                    <?= generate_non_sort_column('الإجراءات المتاحة') ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?= $row['order_id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td style="max-width: 200px; text-align: right;"><?= display_products_summary($row['products_summary']) ?></td>
                    <td><?= htmlspecialchars($row['designer_name']) ?></td>
                    <td>
                        <?php
                        $status_class = '';
                        switch($row['status']) {
                            case 'قيد التصميم': $status_class = 'status-design'; break;
                            case 'قيد التنفيذ': $status_class = 'status-execution'; break;
                            case 'جاهز للتسليم': $status_class = 'status-ready'; break;
                            case 'مكتمل': $status_class = 'status-completed'; break;
                            case 'ملغي': $status_class = 'status-cancelled'; break;
                            default: $status_class = 'status-default';
                        }
                        ?>
                        <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                    </td>
                    <td>
                        <?php
                        $payment_class = '';
                        switch($row['payment_status']) {
                            case 'مدفوع': $payment_class = 'bg-success text-white'; break;
                            case 'مدفوع جزئياً': $payment_class = 'bg-warning text-dark'; break;
                            case 'غير مدفوع': $payment_class = 'bg-danger text-white'; break;
                            default: $payment_class = 'bg-secondary text-white';
                        }
                        ?>
                        <span class="payment-badge <?= $payment_class ?>"><?= htmlspecialchars($row['payment_status']) ?></span>
                    </td>
                    <td><strong><?= number_format($row['total_amount'],2) ?> ر.س</strong></td>
                    <td><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                    <td class="table-actions" style="min-width: 250px;">
                        <?php
                            $actions = get_next_actions($row, $user_role, $user_id, $conn, 'orders_page');
                        ?>
                        <!-- زر عرض التفاصيل/التعديل -->
                        <a href="edit_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i> تفاصيل</a>

                        <!-- عرض الإجراءات المتاحة -->
                        <?php foreach ($actions as $action_key => $action_details): ?>
                            <?php if ($action_key === 'change_status'): ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm <?= htmlspecialchars($action_details['class']) ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?= htmlspecialchars($action_details['label']) ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($action_details['options'] as $next_status => $status_details): ?>
                                            <li><a class="dropdown-item action-btn" href="#" 
                                                   data-action="change_status" 
                                                   data-value="<?= htmlspecialchars($next_status) ?>" 
                                                   data-order-id="<?= $row['order_id'] ?>"
                                                    data-confirm-message="<?= htmlspecialchars($status_details['confirm_message']) ?>"
                                                    <?php if (isset($status_details['whatsapp_action']) && $status_details['whatsapp_action']): ?>
                                                        data-whatsapp-phone="<?= htmlspecialchars($row['client_phone']) ?>"
                                                        data-whatsapp-order-id="<?= $row['order_id'] ?>"
                                                    <?php endif; ?>
                                                    >
                                                    <?= htmlspecialchars($status_details['label']) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-sm <?= htmlspecialchars($action_details['class']) ?> action-btn" 
                                        data-action="<?= htmlspecialchars($action_key) ?>" 
                                        data-order-id="<?= $row['order_id'] ?>"
                                        data-confirm-message="هل أنت متأكد من '<?= htmlspecialchars($action_details['label']) ?>'؟">
                                    <i class="bi <?= htmlspecialchars($action_details['icon']) ?>"></i> <?= htmlspecialchars($action_details['label']) ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- زر الحذف -->
                        <?php if (has_permission('order_delete', $conn)): ?>
                            <a href="delete_order.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">لا توجد طلبات تطابق معايير البحث.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- AJAX Filtering ---
    const filterForm = document.getElementById('filter-form');
    const ordersTable = document.getElementById('ordersMainTable');
    const resetBtn = document.getElementById('reset-filters-btn');

    function applyFilters() {
        if (!filterForm || !ordersTable) return;
        
        const formData = new FormData(filterForm);
        const urlParams = new URLSearchParams(formData);
        
        // Add current sorting parameters to maintain sort order
        const currentUrl = new URL(window.location);
        if (currentUrl.searchParams.get('sort')) {
            urlParams.set('sort', currentUrl.searchParams.get('sort'));
        }
        if (currentUrl.searchParams.get('order')) {
            urlParams.set('order', currentUrl.searchParams.get('order'));
        }
        
        // Show loading state
        ordersTable.querySelector('tbody').innerHTML = '<tr><td colspan="9" class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></td></tr>';

        // Update URL without page reload
        const newUrl = 'orders.php?' + urlParams.toString();
        window.history.pushState({}, '', newUrl);

        fetch(newUrl, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(data => {
            // Parse the response to extract table content
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const newTableBody = doc.querySelector('#ordersMainTable tbody');
            
            if (newTableBody) {
                ordersTable.querySelector('tbody').innerHTML = newTableBody.innerHTML;
                // Re-bind action buttons for the new content
                bindActionButtons(); 
            } else {
                // Fallback: reload the page if AJAX parsing fails
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            ordersTable.querySelector('tbody').innerHTML = '<tr><td colspan="9" class="text-center"><div class="alert alert-danger">حدث خطأ أثناء تحديث البيانات.</div></td></tr>';
        });
    }

    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', applyFilters);
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

                const btn = this;
                const orderId = btn.dataset.orderId;
                const action = btn.dataset.action;
                const value = btn.dataset.value || null;
                const confirmMessage = btn.dataset.confirmMessage;
                
                // بيانات واتساب (إن وجدت)
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

                        fetch('ajax_order_actions.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ order_id: orderId, action: action, value: value })
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
                                        timer: 2500,
                                        timerProgressBar: true
                                    }).then(() => {
                                        window.open(whatsappUrl, '_blank');
                                        applyFilters(); // Refresh the table instead of full page reload
                                    });
                                } else {
                                    Swal.fire('تم بنجاح!', data.message, 'success').then(() => {
                                        applyFilters(); // Refresh the table instead of full page reload
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
                                    applyFilters(); // تحديث الجدول
                                });
                            } else {
                                Swal.fire('خطأ!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ فني!', 'حدث خطأ أثناء حفظ الدفعة.', 'error');
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
});
</script>
