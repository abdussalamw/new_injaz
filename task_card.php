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
        return has_permission($permission, $conn);
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
        <!-- اسم المسؤول الحالي في أعلى يمين البطاقة -->
        <div class="position-absolute top-0 end-0 mt-2 me-2">
            <small class="badge bg-secondary"><?= htmlspecialchars(get_current_responsible($task_details, $conn)) ?></small>
        </div>

        <?php if (has_card_permission('task_card_view_summary', $conn)): ?>
            <div class="mt-4 mb-2">
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

        <?php if (has_permission('dashboard_reports_view', $conn)): ?>
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
                <div class="countdown p-2 rounded text-center bg-light mb-3" data-order-date="<?= $task_details['order_date'] ?>">
                    <span class="fs-5">جاري حساب الوقت المنقضي...</span>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex flex-wrap justify-content-start" style="gap: 0.5rem;">
                    <?php if (has_card_permission('task_card_edit', $conn)): ?>
                        <a href="edit_order.php?id=<?= $task_details['order_id'] ?>" class="btn btn-sm btn-outline-secondary mb-1 me-1"><i class="bi bi-pencil-square"></i> تفاصيل</a>
                    <?php endif; ?>

                    <?php if (has_card_permission('task_card_actions', $conn)): ?>
                        <?php foreach ($actions as $action_key => $action_details): ?>
                            <?php if ($action_key === 'change_status'): ?>
                                <div class="btn-group mb-1 me-1">
                                    <button type="button" class="btn btn-sm <?= htmlspecialchars($action_details['class']) ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?= htmlspecialchars($action_details['label']) ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($action_details['options'] as $next_status => $status_details): ?>
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
                                <button class="btn btn-sm <?= htmlspecialchars($action_details['class']) ?> action-btn mb-1 me-1" 
                                        data-action="<?= htmlspecialchars($action_key) ?>" 
                                        data-order-id="<?= $task_details['order_id'] ?>"
                                        data-confirm-message="هل أنت متأكد من '<?= htmlspecialchars($action_details['label']) ?>'؟">
                                    <i class="bi <?= htmlspecialchars($action_details['icon']) ?>"></i> <?= htmlspecialchars($action_details['label']) ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (has_card_permission('task_card_whatsapp', $conn)): ?>
                    <a href="<?= format_whatsapp_link($task_details['client_phone']) ?>" target="_blank" class="btn btn-sm" style="background-color: #25D366; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                        </svg>
                        واتساب
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
