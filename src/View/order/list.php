<?php
// src/View/order/list.php
?>
<style>
    /* تصحيح اتجاه حقول التاريخ */
    .date-input-rtl::-webkit-calendar-picker-indicator {
        right: auto;
        left: 0.5rem;
    }
    .date-input-rtl {
        text-align: right;
        direction: ltr;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>
                        الطلبات
                    </h5>
                    <!-- شريط البحث السريع -->
                    <form method="get" class="d-flex align-items-center">
                        <div class="input-group input-group-sm me-3" style="width: 300px;">
                            <input type="text" name="search" id="quickSearchInput" class="form-control" placeholder="البحث السريع..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($_GET['search'])): ?>
                                <a href="/new_injaz/orders" class="btn btn-outline-light" title="مسح البحث">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?= \App\Core\MessageSystem::displayMessages() ?>
                    <div id="status-update-feedback" class="mb-3"></div>
                    
                    <!-- أزرار الإجراءات -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <a href="/new_injaz/orders/add" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>
                            إضافة طلب جديد
                        </a>
                        </div>

                    <!-- الفلاتر المتقدمة -->
                    <?php if (\App\Core\Permissions::has_permission('order_view_all', $conn) || \App\Core\Permissions::has_permission('order_view_own', $conn)): ?>
                    <div class="collapse show mb-4" id="advancedFilters">
                        <div class="card card-body bg-light">
                            <form id="filter-form" class="d-flex flex-nowrap align-items-center gap-3">
                                
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <label for="date_from">من تاريخ</label>
                                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm date-input-rtl" value="<?= htmlspecialchars($filter_date_from ?? '') ?>">
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <label for="date_to">إلى تاريخ</label>
                                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm date-input-rtl" value="<?= htmlspecialchars($filter_date_to ?? '') ?>">
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <label for="status_filter">الحالة</label>
                                        <select name="status" id="status_filter" class="form-select form-select-sm" <?= !empty($filter_employee) ? 'disabled' : '' ?>>
                                            <option value="">الكل</option>
                                            <option value="قيد التصميم" <?= $filter_status == 'قيد التصميم' ? 'selected' : '' ?>>قيد التصميم</option>
                                            <option value="قيد التنفيذ" <?= $filter_status == 'قيد التنفيذ' ? 'selected' : '' ?>>قيد التنفيذ</option>
                                            <option value="جاهز للتسليم" <?= $filter_status == 'جاهز للتسليم' ? 'selected' : '' ?>>جاهز للتسليم</option>
                                            <option value="مكتمل" <?= $filter_status == 'مكتمل' ? 'selected' : '' ?>>مكتمل</option>
                                            <option value="ملغي" <?= $filter_status == 'ملغي' ? 'selected' : '' ?>>ملغي</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <?php if (\App\Core\Permissions::has_permission('order_view_all', $conn)): ?>
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <label for="employee_filter">الموظف</label>
                                        <select name="employee" id="employee_filter" class="form-select form-select-sm">
                                            <option value="">الكل</option>
                                            <?php foreach ($employees_list as $employee): ?>
                                                <option value="<?= $employee['employee_id'] ?>" <?= $filter_employee == $employee['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?> (<?= htmlspecialchars($employee['role']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <label for="payment_filter">الدفع</label>
                                        <select name="payment" id="payment_filter" class="form-select form-select-sm">
                                            <option value="">الكل</option>
                                            <option value="مدفوع" <?= $filter_payment == 'مدفوع' ? 'selected' : '' ?>>مدفوع</option>
                                            <option value="مدفوع جزئياً" <?= $filter_payment == 'مدفوع جزئياً' ? 'selected' : '' ?>>مدفوع جزئياً</option>
                                            <option value="غير مدفوع" <?= $filter_payment == 'غير مدفوع' ? 'selected' : '' ?>>غير مدفوع</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <label for="sort_by">الترتيب حسب</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="sort_by" id="sort_by" class="form-select form-select-sm flex-grow-1">
                                                <option value="latest" <?= ($sort_by == 'latest') ? 'selected' : '' ?>>الأحدث</option>
                                                <option value="oldest" <?= ($sort_by == 'oldest') ? 'selected' : '' ?>>الأقدم</option>
                                                <option value="payment" <?= ($sort_by == 'payment') ? 'selected' : '' ?>>الدفع</option>
                                                <?php if (\App\Core\Permissions::has_permission('order_view_all', $conn)): ?>
                                                <option value="employee" <?= ($sort_by == 'employee') ? 'selected' : '' ?>>الموظف</option>
                                                <?php endif; ?>
                                            </select>
                                            <button type="button" id="reset-filters-btn" class="btn btn-sm btn-outline-secondary">إلغاء الفلاتر</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center table-sm" id="ordersMainTable">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">
                                        <?= \App\Core\Helpers::generate_sort_link('order_id', 'رقم الطلب', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 12%;">
                                        <?= \App\Core\Helpers::generate_sort_link('client_name', 'اسم العميل', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 15%;">
                                        <?= \App\Core\Helpers::generate_non_sort_column('ملخص المنتجات') ?>
                                    </th>
                                    <th style="width: 10%;">
                                        <?= \App\Core\Helpers::generate_sort_link('designer_name', 'المصمم المسؤول', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 10%;">
                                        <?= \App\Core\Helpers::generate_sort_link('status', 'حالة الطلب', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 10%;">
                                        <?= \App\Core\Helpers::generate_sort_link('payment_status', 'حالة الدفع', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 10%;">
                                        <?= \App\Core\Helpers::generate_sort_link('total_amount', 'المبلغ الإجمالي', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 10%;">
                                        <?= \App\Core\Helpers::generate_sort_link('order_date', 'تاريخ الإنشاء', $sort_column_key ?? 'order_id', $sort_order ?? 'desc') ?>
                                    </th>
                                    <th style="width: 15%;">
                                        <?= \App\Core\Helpers::generate_non_sort_column('الإجراءات') ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($res && $res->num_rows > 0): ?>
                                    <?php while($row = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-primary">#<?= $row['order_id'] ?></td>
                                        <td class="text-start"><?= htmlspecialchars($row['client_name']) ?></td>
                                        <td class="text-start" style="max-width: 200px;"><?= \App\Core\Helpers::display_products_summary($row['products_summary']) ?></td>
                                        <td><?= htmlspecialchars($row['designer_name']) ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch($row['status']) {
                                                case 'قيد التصميم': $status_class = 'bg-info'; break;
                                                case 'قيد التنفيذ': $status_class = 'bg-warning'; break;
                                                case 'جاهز للتسليم': $status_class = 'bg-primary'; break;
                                                case 'مكتمل': $status_class = 'bg-success'; break;
                                                case 'ملغي': $status_class = 'bg-danger'; break;
                                                default: $status_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
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
                                            <span class="badge <?= $payment_class ?>"><?= htmlspecialchars($row['payment_status']) ?></span>
                                        </td>
                                        <td class="fw-bold"><?= number_format($row['total_amount'],2) ?> ر.س</td>
                                        <td><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                                        <td>
                                            <?php
                                                $actions = \App\Core\Helpers::get_next_actions($row, $user_role, $user_id, $conn, 'orders_page');
                                            ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="/new_injaz/orders/edit?id=<?= $row['order_id'] ?>" class="btn btn-outline-secondary btn-sm" title="تفاصيل">
                                                    <i class="bi bi-eye"></i> تفاصيل
                                                </a>

                                                <?php foreach ($actions as $action_key => $action_details): ?>
                                                    <?php if ($action_key === 'change_status'): ?>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm <?= htmlspecialchars($action_details['class']) ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="تغيير الحالة">
                                                                <i class="bi bi-arrow-repeat"></i>
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
                                                                data-confirm-message="هل أنت متأكد من '<?= htmlspecialchars($action_details['label']) ?>'؟"
                                                                title="<?= htmlspecialchars($action_details['label']) ?>">
                                                            <i class="bi <?= htmlspecialchars($action_details['icon']) ?>"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>

                                                <?php if (\App\Core\Permissions::has_permission('order_delete', $conn)): ?>
                                                    <form method="POST" action="/new_injaz/orders/delete" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب نهائياً؟ لا يمكن التراجع عن هذا الإجراء.');">
                                                        <input type="hidden" name="id" value="<?= $row['order_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                            <p class="text-muted mt-2">لا توجد طلبات تطابق معايير البحث.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-header {
    background: linear-gradient(135deg, #fd7e14, #ffc107) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(253, 126, 20, 0.1) !important;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.payment-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    const ordersTable = document.getElementById('ordersMainTable');
    const resetBtn = document.getElementById('reset-filters-btn');
    const quickSearchInput = document.getElementById('quickSearchInput');

    // البحث السريع
    if (quickSearchInput) {
        let quickSearchTimeout;
        quickSearchInput.addEventListener('input', function() {
            clearTimeout(quickSearchTimeout);
            quickSearchTimeout = setTimeout(function() {
                const searchValue = quickSearchInput.value.trim();
                const currentUrl = new URL(window.location);
                
                if (searchValue) {
                    currentUrl.searchParams.set('search', searchValue);
                } else {
                    currentUrl.searchParams.delete('search');
                }
                
                window.location.href = currentUrl.toString();
            }, 500);
        });
    }

    function applyFilters() {
        if (!filterForm || !ordersTable) return;
        
        const formData = new FormData(filterForm);
        const urlParams = new URLSearchParams(formData);
        
        urlParams.delete('sort');
        urlParams.delete('order');

        ordersTable.querySelector('tbody').innerHTML = '<tr><td colspan="9" class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></td></tr>';

        const newUrl = '/new_injaz/orders?' + urlParams.toString();
        window.history.pushState({}, '', newUrl);

        fetch(newUrl, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const newTableBody = doc.querySelector('#ordersMainTable tbody');
            
            if (newTableBody) {
                ordersTable.querySelector('tbody').innerHTML = newTableBody.innerHTML;
                bindActionButtons(); 
            } else {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            ordersTable.querySelector('tbody').innerHTML = '<tr><td colspan="9" class="text-center"><div class="alert alert-danger">حدث خطأ أثناء تحديث البيانات.</div></td></tr>';
        });
    }

    if (filterForm) {
        filterForm.querySelectorAll('select, input').forEach(element => {
            if (element.type === 'text') {
                let timeout = null;
                element.addEventListener('keyup', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        applyFilters();
                    }, 500); 
                });
            } else {
                element.addEventListener('change', applyFilters);
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            filterForm.reset();
            document.getElementById('status_filter').disabled = false;
            applyFilters();
        });
    }

    function bindActionButtons() {
        document.querySelectorAll('.action-btn').forEach(button => {
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
                
                const whatsappPhone = btn.dataset.whatsappPhone;
                const whatsappOrderId = btn.dataset.whatsappOrderId;

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

                        fetch('/new_injaz/ajax_order_actions.php', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json', 
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ order_id: orderId, action: action, value: value })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (whatsappPhone && whatsappOrderId) {
                                    const whatsappMessage = `العميل العزيز، طلبكم رقم ${whatsappOrderId} جاهز للتسليم. شكراً لتعاملكم معنا.`;
                                    const encodedMessage = encodeURIComponent(whatsappMessage);
                                    const internationalPhone = '966' + whatsappPhone.substring(1);
                                    const whatsappUrl = `https://wa.me/${internationalPhone}?text=${encodedMessage}`;
                                    
                                    Swal.fire({
                                        title: 'تم بنجاح!',
                                        text: data.message + ' سيتم الآن فتح واتساب.',
                                        icon: 'success',
                                        timer: 2500,
                                        timerProgressBar: true
                                    }).then(() => {
                                        window.open(whatsappUrl, '_blank');
                                        applyFilters();
                                    });
                                } else {
                                    Swal.fire('تم بنجاح!', data.message, 'success').then(() => {
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

    function showPaymentUpdateModal(orderId) {
        fetch(`/new_injaz/api/orders/details?id=${orderId}`)
            .then(response => response.json())
            .then(apiResponse => {
                if (!apiResponse.success) {
                    Swal.fire('خطأ!', apiResponse.message || 'فشل في جلب بيانات الطلب.', 'error');
                    return;
                }
                const orderData = apiResponse.data;
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

                        fetch('/api/orders/payment', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('تم بنجاح!', data.message, 'success').then(() => {
                                    applyFilters();
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

    bindActionButtons();
});
</script>
