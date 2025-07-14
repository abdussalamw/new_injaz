<?php

/**
 * دالة لتحديد لون حدود البطاقة بناءً على الأولوية
 * @param string $priority
 * @return string
 */
function get_priority_class($priority) {
    switch ($priority) {
        case 'عاجل جداً': return 'border-danger';
        case 'عالي': return 'border-warning';
        case 'متوسط': return 'border-info';
        case 'منخفض': return 'border-secondary';
        default: return 'border-light';
    }
}

/**
 * دالة لتحديد لون زر الحالة
 * @param string $status
 * @return string
 */
function get_status_class($status) {
    $classes = [
        'قيد التصميم' => 'btn-info text-dark',
        'قيد التنفيذ' => 'btn-primary',
        'جاهز للتسليم' => 'btn-secondary',
        'بانتظار التسوية المالية' => 'btn-warning text-dark',
        'بانتظار الإغلاق النهائي' => 'btn-dark',
        'مكتمل' => 'btn-success',
        'ملغي' => 'btn-danger'
    ];
    return $classes[trim($status)] ?? 'btn-light';
}

/**
 * دالة لتحديد لون حالة الدفع
 * @param string $payment_status
 * @param float $total_amount
 * @param float $deposit_amount
 * @return string
 */
function get_payment_status_display($payment_status, $total_amount, $deposit_amount) {
    if ($payment_status === 'مدفوع') {
        return '<div class="progress" style="height: 20px;" title="مدفوع بالكامل"><div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">مدفوع</div></div>';
    }

    if ($payment_status === 'غير مدفوع') {
        // تمييز الطلبات المجانية في التلميح
        $title = $total_amount <= 0 ? 'غير مدفوع (إجمالي صفر)' : 'غير مدفوع';
        return '<div class="progress" style="height: 20px;" title="' . $title . '"><div class="progress-bar bg-danger" role="progressbar" style="width: 100%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">غير مدفوع</div></div>';
    }

    if ($payment_status === 'مدفوع جزئياً') {
        // بما أن المنطق في صفحات الإضافة والتعديل يضمن أن المبلغ الإجمالي أكبر من صفر هنا، يمكننا القسمة بأمان
        $paid_percentage = ($deposit_amount / $total_amount) * 100;
        $remaining_percentage = 100 - $paid_percentage;
        return '<div class="progress" style="height: 20px;" title="مدفوع جزئياً: ' . number_format($paid_percentage, 0) . '%"><div class="progress-bar bg-success" role="progressbar" style="width: ' . $paid_percentage . '%;" aria-valuenow="' . $paid_percentage . '" aria-valuemin="0" aria-valuemax="100"></div><div class="progress-bar bg-warning" role="progressbar" style="width: ' . $remaining_percentage . '%;" aria-valuenow="' . $remaining_percentage . '" aria-valuemin="0" aria-valuemax="100"></div></div>';
    }

    return '<span class="badge bg-secondary">' . htmlspecialchars($payment_status) . '</span>';
}

/**
 * دالة لتحديد الإجراءات المتاحة بناءً على المرحلة والدور
 * @param array $order
 * @param string $user_role
 * @param int $user_id
 * @return array
 */
function get_next_actions($order, $user_role, $user_id, $conn) {
    $actions = [];
    $status = trim($order['status'] ?? '');
    $is_delivered = !empty($order['delivered_at']);
    $is_paid = !empty($order['payment_settled_at']);
    $is_creator = ($order['created_by'] == $user_id);
    $is_designer = ($order['designer_id'] == $user_id);

    // الإجراءات لا تعتمد دائماً على الحالة، بل على الأحداث (Milestones)
    // 1. تأكيد الدفع (للمحاسب والمدير)
    if (!$is_paid && in_array($user_role, ['مدير', 'محاسب'])) {
        $actions['confirm_payment'] = ['label' => 'تأكيد الدفع الكامل', 'class' => 'btn-success', 'icon' => 'bi-cash-coin'];
    }

    // 2. تأكيد التسليم (للمعمل، منشئ الطلب، المدير)
    if (!$is_delivered && $status === 'جاهز للتسليم' && (in_array($user_role, ['مدير', 'معمل']) || $is_creator)) {
        $actions['confirm_delivery'] = ['label' => 'تأكيد استلام العميل', 'class' => 'btn-primary', 'icon' => 'bi-box-arrow-in-down'];
    }

    // 3. إغلاق الطلب (للمدير فقط، بعد اكتمال المسارين)
    if ($is_delivered && $is_paid && $status !== 'مكتمل' && $user_role === 'مدير') {
        $actions['close_order'] = ['label' => 'إغلاق الطلب نهائياً', 'class' => 'btn-dark', 'icon' => 'bi-archive-fill'];
    }

    // 4. إجراءات تغيير الحالة (المسار الإنتاجي)
    $status_changes = [];
    if ($status !== 'مكتمل' && $status !== 'ملغي') {
        switch ($status) {
            case 'قيد التصميم':
                if ((in_array($user_role, ['مدير', 'مصمم'])) && $is_designer) {
                    $status_changes['قيد التنفيذ'] = 'إرسال للتنفيذ';
                }
                break;
            case 'قيد التنفيذ':
                if (in_array($user_role, ['مدير', 'معمل'])) {
                    $status_changes['جاهز للتسليم'] = 'تحديد كـ "جاهز للتسليم"';
                }
                break;
        }
    }

    // دمج إجراءات تغيير الحالة في مصفوفة الإجراءات
    if (!empty($status_changes)) {
        $actions['change_status'] = [
            'label' => $status, // The button label is the current status
            'class' => get_status_class($status),
            'options' => $status_changes
        ];
    }

    return $actions;
}
