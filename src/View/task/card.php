<?php
/**
 * @var array $task_details  // يحتوي على تفاصيل المهمة
 * @var string $user_role     // دور المستخدم الحالي
 * @var int $user_id         // معرف المستخدم الحالي
 * @var mysqli $conn         // اتصال قاعدة البيانات
 */

// دالة مساعدة للتحقق من صلاحية عنصر معين في البطاقة
if (!function_exists('has_card_permission')) {
    function has_card_permission($permission, $conn) {
        // يمكنك تعديل هذه الدالة إذا كان لديك نظام صلاحيات أكثر تعقيداً
        // حالياً، تتحقق فقط مما إذا كان المستخدم يمتلك صلاحية عامة
        return \App\Core\Permissions::has_permission($permission, $conn);
    }
}

// دوال مساعدة من Helpers
if (!function_exists('get_priority_class')) {
    function get_priority_class($priority) {
        return \App\Core\Helpers::get_priority_class($priority);
    }
}

if (!function_exists('get_payment_status_display')) {
    function get_payment_status_display($payment_status, $total_amount, $deposit_amount) {
        return \App\Core\Helpers::get_payment_status_display($payment_status, $total_amount, $deposit_amount);
    }
}

if (!function_exists('generate_timeline_bar')) {
    function generate_timeline_bar($order) {
        return \App\Core\Helpers::generate_timeline_bar($order);
    }
}

if (!function_exists('format_whatsapp_link')) {
    function format_whatsapp_link($phone, $message = '') {
        return \App\Core\Helpers::format_whatsapp_link($phone, $message);
    }
}

// تأكد من وجود متغير user_role
$user_role = $_SESSION['user_role'] ?? 'غير محدد';

// دالة لتحديد المسؤول الحالي حسب حالة الطلب
if (!function_exists('get_current_responsible')) {
    function get_current_responsible($task_details, $conn) {
        $status = trim($task_details['status'] ?? '');
        
        switch ($status) {
            case 'قيد التصميم':
                // المصمم مسؤول عن التصميم
                return $task_details['designer_name'] ?? 'غير محدد';
                
            case 'قيد التنفيذ':
                // المعمل مسؤول عن التنفيذ - نحتاج لجلب اسم المعمل
                if (!empty($task_details['workshop_id'])) {
                    $workshop_query = $conn->prepare("SELECT name FROM employees WHERE employee_id = ?");
                    $workshop_query->bind_param("i", $task_details['workshop_id']);
                    $workshop_query->execute();
                    $workshop_result = $workshop_query->get_result();
                    if ($workshop_row = $workshop_result->fetch_assoc()) {
                        return $workshop_row['name'];
                    }
                }
                // إذا لم يكن هناك معمل محدد، نبحث عن موظف بدور "معمل"
                $workshop_query = $conn->query("SELECT name FROM employees WHERE role = 'معمل' LIMIT 1");
                if ($workshop_query && $workshop_row = $workshop_query->fetch_assoc()) {
                    return $workshop_row['name'];
                }
                return 'المعمل';
                
            case 'جاهز للتسليم':
                // المعمل أو المدير مسؤول عن التسليم
                if (!empty($task_details['workshop_id'])) {
                    $workshop_query = $conn->prepare("SELECT name FROM employees WHERE employee_id = ?");
                    $workshop_query->bind_param("i", $task_details['workshop_id']);
                    $workshop_query->execute();
                    $workshop_result = $workshop_query->get_result();
                    if ($workshop_row = $workshop_result->fetch_assoc()) {
                        return $workshop_row['name'];
                    }
                }
                return 'المعمل/المدير';
                
            case 'مكتمل':
                return 'مكتمل';
                
            case 'ملغي':
                return 'ملغي';
                
            default:
                return $task_details['designer_name'] ?? 'غير محدد';
        }
    }
}
?>

<div class="card h-100 shadow-sm <?= get_priority_class($task_details['priority']) ?>" style="border-width: 4px; border-style: solid; border-top:0; border-right:0; border-bottom:0;">
    <div class="card-body d-flex flex-column position-relative">
        <!-- المسؤول الحالي والمصمم في أعلى يمين البطاقة -->
        <div class="position-absolute top-0 end-0 mt-2 me-2 text-end">
            <small class="badge bg-secondary d-block mb-1"><?= htmlspecialchars(get_current_responsible($task_details, $conn)) ?></small>
            <?php if (has_card_permission('task_card_view_designer', $conn) && !empty($task_details['designer_name'])): ?>
                <small class="badge bg-info text-dark d-block"><?= htmlspecialchars($task_details['designer_name']) ?></small>
            <?php endif; ?>
        </div>

        <?php if (has_card_permission('task_card_view_summary', $conn)): ?>
            <div class="mb-2">
                <?php 
                // تقسيم المنتجات وعرضها تحت بعض مع تكبير الخط
                $products = explode(', ', $task_details['products_summary']);
                foreach ($products as $product): 
                ?>
                    <div class="mb-1 fs-5 fw-bold"><?= htmlspecialchars(trim($product)) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (has_card_permission('task_card_view_client', $conn)): ?>
            <h6 class="card-subtitle mb-2 text-muted">للعميل: <?= htmlspecialchars($task_details['client_name']) ?></h6>
        <?php endif; ?>

        <?php if ($user_role === 'محاسب' && has_card_permission('task_card_view_payment', $conn)): ?>
            <div class="mb-3">
                <small class="text-muted d-block mb-1">حالة الدفع</small>
                <?= get_payment_status_display($task_details['payment_status'], $task_details['total_amount'], $task_details['deposit_amount']) ?>
            </div>
        <?php endif; ?>

        <?php if (\App\Core\Permissions::has_permission('dashboard_reports_view', $conn)): ?>
            <div class="mb-3">
                <small class="text-muted d-block mb-1" style="font-size: 0.8rem;">الجدول الزمني للمراحل</small>
                <?= generate_timeline_bar($task_details) ?>
            </div>
            <div class="mb-3">
                <small class="text-muted d-block mb-1" style="font-size: 0.8rem;">حالة الدفع</small>
                <?= get_payment_status_display($task_details['payment_status'], $task_details['total_amount'], $task_details['deposit_amount']) ?>
            </div>
        <?php endif; ?>

        <div class="mt-auto">
            <?php if (has_card_permission('task_card_view_countdown', $conn)): ?>
                <?php
                // تحديد تاريخ بدء العداد بناءً على دور المستخدم وحالة المهمة
                $countdown_start_date = $task_details['order_date']; // التاريخ الافتراضي
                if ($user_role === 'معمل' && !empty($task_details['design_completed_at'])) {
                    // إذا كان المستخدم معملاً والمهمة قد بدأت مرحلة التنفيذ، ابدأ العداد من وقت انتهاء التصميم
                    $countdown_start_date = $task_details['design_completed_at'];
                }
                ?>
                <div class="countdown p-2 rounded text-center bg-light mb-3" data-order-date="<?= $countdown_start_date ?>">
                    <span class="fs-6">جاري حساب الوقت المنقضي...</span>
                </div>
            <?php endif; ?>

            <!-- شبكة الأيقونات 2x2 -->
            <div class="row g-2">
                <!-- الصف الأول -->
                <div class="col-6">
                    <?php if (has_card_permission('task_card_edit', $conn)): ?>
                        <a href="/new_injaz/orders/edit?id=<?= $task_details['order_id'] ?>" class="btn btn-outline-primary btn-sm w-100 d-flex align-items-center justify-content-center" style="height: 35px;">
                            <i class="bi bi-pencil-square me-1"></i>
                            <span class="small">تفاصيل</span>
                        </a>
                    <?php else: ?>
                        <div class="btn btn-outline-secondary btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height: 35px;">
                            <i class="bi bi-lock me-1"></i>
                            <span class="small">محظور</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <?php if (has_card_permission('task_card_whatsapp', $conn)): ?>
                        <a href="<?= format_whatsapp_link($task_details['client_phone']) ?>" target="_blank" class="btn btn-sm w-100 d-flex align-items-center justify-content-center" style="background-color: #25D366; color: white; height: 35px;">
                            <i class="bi bi-whatsapp me-1"></i>
                            <span class="small">واتساب</span>
                        </a>
                    <?php else: ?>
                        <div class="btn btn-outline-secondary btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height: 35px;">
                            <i class="bi bi-chat-dots me-1"></i>
                            <span class="small">محظور</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- الصف الثاني -->
                <div class="col-6">
                    <?php 
                    $payment_action = null;
                    foreach ($actions as $action_key => $action_details) {
                        if ($action_key === 'confirm_payment') {
                            $payment_action = $action_details;
                            break;
                        }
                    }
                    ?>
                    <?php if ($payment_action): ?>
                        <button class="btn btn-warning btn-sm w-100 action-btn d-flex align-items-center justify-content-center" 
                                data-action="confirm_payment" 
                                data-order-id="<?= $task_details['order_id'] ?>"
                                data-confirm-message="هل أنت متأكد من تأكيد استلام الدفع؟"
                                style="height: 35px;">
                            <i class="bi bi-cash-coin me-1"></i>
                            <span class="small">دفع</span>
                        </button>
                    <?php else: ?>
                        <div class="btn btn-outline-secondary btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height: 35px;">
                            <i class="bi bi-cash me-1"></i>
                            <span class="small">مدفوع</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <?php 
                    $status_action = null;
                    $confirm_delivery_action = null;

                    foreach ($actions as $action_key => $action_details) {
                        if ($action_key === 'change_status') {
                            $status_action = $action_details;
                        } elseif ($action_key === 'confirm_delivery') {
                            $confirm_delivery_action = $action_details;
                        }
                    }
                    ?>
                    <?php if ($confirm_delivery_action): ?>
                        <button class="btn <?= $confirm_delivery_action['class'] ?> btn-sm w-100 action-btn d-flex align-items-center justify-content-center" 
                                data-action="confirm_delivery" 
                                data-order-id="<?= $task_details['order_id'] ?>"
                                data-confirm-message="هل أنت متأكد من تأكيد استلام العميل للطلب؟"
                                style="height: 35px;">
                            <i class="<?= $confirm_delivery_action['icon'] ?> me-1"></i>
                            <span class="small"><?= $confirm_delivery_action['label'] ?></span>
                        </button>
                    <?php elseif ($status_action): ?>
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-info btn-sm dropdown-toggle w-100 d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false" style="height: 35px;">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                <span class="small">حالة</span>
                            </button>
                            <ul class="dropdown-menu">
                                <?php foreach ($status_action['options'] as $next_status => $status_details): ?>
                                    <li><a class="dropdown-item action-btn" href="#" 
                                           data-action="change_status" 
                                           data-value="<?= htmlspecialchars($next_status) ?>" 
                                           data-order-id="<?= $task_details['order_id'] ?>"
                                            data-confirm-message="<?= htmlspecialchars($status_details['confirm_message']) ?>"
                                            <?php if (isset($status_details['whatsapp_action']) && $status_details['whatsapp_action']): ?>
                                                data-whatsapp-phone="<?= htmlspecialchars($task_details['client_phone']) ?>"
                                                data-whatsapp-order-id="<?= $task_details['order_id'] ?>"
                                            <?php endif; ?>
                                            >
                                            <?= htmlspecialchars($status_details['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="btn btn-success btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height: 35px;">
                            <i class="bi bi-check-circle me-1"></i>
                            <span class="small">مكتمل</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
