<?php
$page_title = 'الطلبات';
include 'db_connection.php';
include 'header.php';

// --- Fetch employees for filtering ---
// --- Fetch employees for filtering (designers and managers only) ---
$employees_res = $conn->query("SELECT employee_id, name FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
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
    $where_clauses[] = "o.designer_id = ?";
    $params[] = $filter_employee;
    $types .= "i";
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

$sql .= " GROUP BY o.order_id ORDER BY FIELD(o.status, 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم', 'مكتمل', 'ملغي'), o.due_date ASC, o.order_id DESC";



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
<style>
    .filter-form .form-select,
    .filter-form .btn {
        min-width: 150px;
    }

    .table-actions .btn,
    .table-actions .btn-group {
        margin-left: 5px;
        margin-bottom: 5px;
    }
</style>
<div class="container">
    <div id="status-update-feedback" class="mb-3"></div>
    <?php if (has_permission('order_add', $conn)): ?><a href="add_order.php" class="btn btn-success mb-3">إضافة طلب جديد</a><?php endif; ?>

    <!-- نموذج الفلترة -->
    <form method="GET" id="filter-form" class="row g-3 align-items-center mb-4 p-3 border rounded bg-light filter-form">
        <div class="col-md-auto">
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

        <div class="col-md-auto">
            <label for="employee_filter" class="form-label">الموظف المسؤول</label>
            <select name="employee" id="employee_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <?php foreach ($employees_list as $employee): ?>
                    <option value="<?= $employee['employee_id'] ?>" <?= $filter_employee == $employee['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-auto">
            <label for="payment_filter" class="form-label">حالة الدفع</label>
            <select name="payment" id="payment_filter" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="مدفوع" <?= $filter_payment == 'مدفوع' ? 'selected' : '' ?>>مدفوع</option>
                <option value="مدفوع جزئياً" <?= $filter_payment == 'مدفوع جزئياً' ? 'selected' : '' ?>>مدفوع جزئياً</option>
                <option value="غير مدفوع" <?= $filter_payment == 'غير مدفوع' ? 'selected' : '' ?>>غير مدفوع</option>
            </select>
        </div>

        <div class="col-md-auto align-self-end">
            <?php if (!empty($filter_status) || !empty($filter_employee) || !empty($filter_payment)): ?>
                <a href="orders.php" class="btn btn-sm btn-outline-secondary">إلغاء الفلترة</a>
            <?php endif; ?>
        </div>
    </form>

    <table class="table table-bordered table-striped text-center">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>ملخص المنتجات</th>
                <th>المصمم</th>
                <th>الحالة</th>
                <th>حالة الدفع</th>
                <th>المبلغ</th>
                <th>تاريخ الإنشاء</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['order_id'] ?></td>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td><?= display_products_summary($row['products_summary']) ?></td>
                    <td><?= htmlspecialchars($row['designer_name']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td style="min-width: 120px;"><?= get_payment_status_display($row['payment_status'], $row['total_amount'], $row['deposit_amount']) ?></td>
                    <td><?= number_format($row['total_amount'],2) ?></td>
                    <td><?= date('Y-m-d', strtotime($row['order_date'])) ?></td>
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
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }

    // --- معالج أزرار الإجراءات الشامل (الحل للمشكلة) ---
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // منع السلوك الافتراضي للرابط

            const btn = this;
            const orderId = btn.dataset.orderId;
            const action = btn.dataset.action;
            const value = btn.dataset.value || null; // للحالات المتغيرة
            const confirmMessage = btn.dataset.confirmMessage;
            
            // بيانات واتساب (إن وجدت)
            const whatsappPhone = btn.dataset.whatsappPhone;
            const whatsappOrderId = btn.dataset.whatsappOrderId;

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
                    
                    // إظهار مؤشر التحميل
                    Swal.fire({
                        title: 'الرجاء الانتظار...',
                        text: 'جاري تنفيذ الإجراء.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('ajax_order_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            action: action,
                            value: value
                        })
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
                                    timer: 2500, // انتظر ثانيتين ونصف
                                    timerProgressBar: true
                                }).then(() => {
                                    window.open(whatsappUrl, '_blank');
                                    location.reload(); // تحديث الصفحة الأصلية
                                });
                            } else {
                                // لا يوجد إجراء واتساب، فقط أظهر نجاح وحدّث الصفحة
                                Swal.fire('تم بنجاح!', data.message, 'success').then(() => location.reload());
                            }
                        } else {
                            Swal.fire('خطأ!', data.message, 'error');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        Swal.fire('خطأ فني!', 'حدث خطأ غير متوقع. الرجاء مراجعة الـ Console.', 'error');
                    });
                }
            });
        });
    });
});
</script>
