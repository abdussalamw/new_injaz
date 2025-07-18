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
        'قيد التصميم' => 'status-design',
        'قيد التنفيذ' => 'status-execution',
        'جاهز للتسليم' => 'status-ready',
        'مكتمل' => 'status-completed',
        'ملغي' => 'status-cancelled',
    ];
    return $classes[trim($status)] ?? 'status-default';
}

/**
 * دالة لتحديد لون حالة الدفع
 * @param string $payment_status
 * @param float $total_amount
 * @param float $deposit_amount
 * @return string
 */
function get_payment_status_display($payment_status_from_db, $total_amount, $deposit_amount) {
    // إعادة حساب الحالة بشكل فوري لضمان العرض الصحيح دائماً، بغض النظر عن القيمة المخزنة
    // هذا يحل مشكلة الطلبات القديمة التي لم يتم تحديث حالتها
    $recalculated_status = '';
    if ($total_amount <= 0) {
        $recalculated_status = 'غير مدفوع';
    } elseif ($deposit_amount >= $total_amount) {
        $recalculated_status = 'مدفوع';
    } elseif ($deposit_amount > 0) {
        $recalculated_status = 'مدفوع جزئياً';
    } else {
        $recalculated_status = 'غير مدفوع';
    }

    if ($recalculated_status === 'مدفوع') {
        return '<div class="progress" style="height: 20px;" title="مدفوع بالكامل"><div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">مدفوع: ' . number_format($total_amount, 2) . '</div></div>';
    }

    if ($recalculated_status === 'غير مدفوع') {
        // تمييز الطلبات المجانية في التلميح
        $title = $total_amount <= 0 ? 'غير مدفوع (إجمالي صفر)' : 'غير مدفوع';
        return '<div class="progress" style="height: 20px;" title="' . $title . '"><div class="progress-bar bg-danger" role="progressbar" style="width: 100%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">المتبقي: ' . number_format($total_amount, 2) . '</div></div>';
    }

    if ($recalculated_status === 'مدفوع جزئياً') {
        $paid_percentage = ($deposit_amount / $total_amount) * 100; // آمن لأننا تأكدنا أن المبلغ الإجمالي أكبر من صفر
        $remaining_percentage = 100 - $paid_percentage;
        $remaining_amount = $total_amount - $deposit_amount;
        return '<div class="progress" style="height: 20px;" title="مدفوع جزئياً: ' . number_format($paid_percentage, 0) . '%">'
             . '<div class="progress-bar bg-success" role="progressbar" style="width: ' . $paid_percentage . '%;" aria-valuenow="' . $paid_percentage . '" aria-valuemin="0" aria-valuemax="100">' . number_format($deposit_amount, 2) . '</div>'
             . '<div class="progress-bar bg-warning text-dark" role="progressbar" style="width: ' . $remaining_percentage . '%;" aria-valuenow="' . $remaining_percentage . '" aria-valuemin="0" aria-valuemax="100">' . number_format($remaining_amount, 2) . '</div>'
             . '</div>';
    }

    // كحل احتياطي، في حال لم تنجح أي من الحالات أعلاه
    return '<span class="badge bg-secondary">' . htmlspecialchars($payment_status_from_db) . '</span>';
}

/**
 * دالة لتحديد الإجراءات المتاحة بناءً على المرحلة والدور
 * @param array $order
 * @param string $user_role
 * @param int $user_id
 * @return array
 */
function get_next_actions($order, $user_role, $user_id, $conn, $context = 'dashboard') {
    // إذا كان السياق هو صفحة الطلبات، لا تعرض أي أزرار إجراءات على الإطلاق.
    if ($context === 'orders_page') {
        return [];
    }

    $actions = [];
    $status = trim($order['status'] ?? '');
    $is_delivered = !empty($order['delivered_at']);
    $is_paid = !empty($order['payment_settled_at']);
    $is_creator = ($order['created_by'] == $user_id);
    $is_designer = ($order['designer_id'] == $user_id);

    // الإجراءات لا تعتمد دائماً على الحالة، بل على الأحداث (Milestones)
    // 1. تأكيد الدفع - منطق مختلف حسب الدور
    if (!$is_paid) {
        if ($user_role === 'محاسب' && has_permission('order_financial_settle', $conn)) {
            // للمحاسب: زر واحد فقط لتحديث حالة الدفع
            $actions['update_payment'] = ['label' => 'تحديث حالة الدفع', 'class' => 'btn-success', 'icon' => 'bi-cash-coin'];
        } elseif ($user_role === 'مدير' && has_permission('order_financial_settle', $conn)) {
            // للمدير: زر منفصل لتأكيد الدفع الكامل (بالإضافة لزر الإجراءات)
            $actions['confirm_payment'] = ['label' => 'تأكيد الدفع الكامل', 'class' => 'btn-success', 'icon' => 'bi-cash-coin'];
        }
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
                // المدير يستطيع إرسال أي طلب، والمصمم يرسل فقط الطلبات المسندة إليه
                if ($user_role === 'مدير' || ($user_role === 'مصمم' && $is_designer)) {
                    $status_changes['قيد التنفيذ'] = [
                        'label' => 'إرسال للتنفيذ',
                        'confirm_message' => 'تأكد بأنك قمت بمراجعة جميع التصاميم المطلوبة وإرسالها للمعمل للتنفيذ؟'
                    ];
                }
                break;
            case 'قيد التنفيذ':
                if (in_array($user_role, ['مدير', 'معمل'])) {
                    // التحقق من وجود رقم جوال للعميل قبل عرض إجراء الواتساب
                    $client_phone = trim($order['client_phone'] ?? '');
                    if (!empty($client_phone)) {
                        $status_changes['جاهز للتسليم'] = [
                            'label' => 'تحديد كـ "جاهز للتسليم"',
                            'confirm_message' => 'هل أنت متأكد من أن الطلب جاهز بالكامل للتسليم للعميل؟ سيتم إرسال إشعار للعميل عبر واتساب.',
                            'whatsapp_action' => true
                        ];
                    } else {
                        // إذا لم يكن هناك رقم، يتم عرض الإجراء بدون ميزة الواتساب
                        $status_changes['جاهز للتسليم'] = [
                            'label' => 'تحديد كـ "جاهز للتسليم"',
                            'confirm_message' => 'هل أنت متأكد؟ (لا يمكن إرسال واتساب لعدم وجود رقم جوال للعميل)'
                        ];
                    }
                }
                break;
        }
    }

    // دمج إجراءات تغيير الحالة في مصفوفة الإجراءات (يظهر في البطاقات فقط)
    if (!empty($status_changes)) {
        $actions['change_status'] = [
            'label' => $status, // نص الزر هو اسم الحالة الحالية
            'class' => get_status_class($status),
            'options' => $status_changes
        ];
    }

    return $actions;
}

/**
 * دالة لجلب بيانات الرسوم البيانية
 * @param string $chart_type
 * @param mysqli $conn
 * @return array
 */
function get_chart_data_helper($chart_type, $conn) {
    switch ($chart_type) {
        case 'top_products':
            $sql = "SELECT p.name, COUNT(oi.product_id) as sales_count 
                   FROM order_items oi
                   JOIN products p ON oi.product_id = p.product_id
                   GROUP BY oi.product_id
                   ORDER BY sales_count DESC
                   LIMIT 5";
            break;
        case 'clients':
            $sql = "SELECT c.company_name, COUNT(o.client_id) as orders_count
                   FROM orders o
                   JOIN clients c ON o.client_id = c.client_id
                   GROUP BY o.client_id
                   ORDER BY orders_count DESC
                   LIMIT 5";
            break;
        case 'employees':
            $sql = "SELECT e.name, COUNT(o.designer_id) as tasks_count
                   FROM orders o
                   JOIN employees e ON o.designer_id = e.employee_id
                   WHERE o.status = 'مكتمل' AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                   GROUP BY o.designer_id
                   ORDER BY tasks_count DESC
                   LIMIT 5";
            break;
        default:
            return [];
    }

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}


/**
 * Helper function to format seconds into a human-readable string.
 * @param int $seconds
 * @return string
 */
function format_duration($seconds) {
    if ($seconds < 0) {
        $seconds = 0;
    }
    if ($seconds < 60) {
        return "أقل من دقيقة";
    }

    $days = floor($seconds / 86400);
    $seconds %= 86400;
    $hours = floor($seconds / 3600);
    $seconds %= 3600;
    $minutes = floor($seconds / 60);

    $parts = [];
    if ($days > 0) {
        $parts[] = $days . " يوم";
    }
    if ($hours > 0) {
        $parts[] = $hours . " ساعة";
    }
    if ($minutes > 0 && $days == 0) { // عرض الدقائق فقط إذا لم تكن هناك أيام
        $parts[] = $minutes . " دقيقة";
    }

    return empty($parts) ? "لحظات" : implode(' و ', array_slice($parts, 0, 2));
}

/**
 * Generates an HTML progress bar representing the order's lifecycle timeline.
 * @param array $order The order data row from the database.
 * @return string HTML for the timeline bar.
 */
function generate_timeline_bar($order) {
    try {
        $order_date = new DateTime($order['order_date']);
        $now = new DateTime();
        $stages = [];

        // --- معالجة الطلبات القديمة التي لا تحتوي على توقيتات ---
        if ($order['status'] === 'قيد التنفيذ' && empty($order['design_completed_at'])) {
            $duration = $now->getTimestamp() - $order_date->getTimestamp();
            $label = 'إجمالي الوقت: ' . format_duration($duration);
            $title = 'بيانات المراحل غير متوفرة لهذا الطلب القديم';
            return '<div class="progress" style="height: 18px; font-size: 0.7rem;">'
                 . '<div class="progress-bar bg-secondary" role="progressbar" style="width: 100%;" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($label) . '</div>'
                 . '</div>';
        }

        // --- المنطق الطبيعي للطلبات الجديدة ---

        // Stage 1: Design
        if (!empty($order['design_completed_at'])) {
            // Design is complete
            $design_end = new DateTime($order['design_completed_at']);
            $duration = $design_end->getTimestamp() - $order_date->getTimestamp();
            if ($duration > 0) {
                $stages[] = ['label' => 'تصميم: ' . format_duration($duration), 'duration' => $duration, 'class' => 'bg-info', 'title' => 'مرحلة التصميم: ' . format_duration($duration)];
            }
        } elseif ($order['status'] === 'قيد التصميم') {
            // Design is the current, active stage
            $duration = $now->getTimestamp() - $order_date->getTimestamp();
            if ($duration > 0) {
                $stages[] = ['label' => 'تصميم (حالي): ' . format_duration($duration), 'duration' => $duration, 'class' => 'bg-info', 'title' => 'المرحلة الحالية (تصميم): ' . format_duration($duration)];
            }
        }

        // Stage 2: Execution
        // This stage can only be processed if the design stage is complete.
        if (!empty($order['design_completed_at'])) {
            $design_end = new DateTime($order['design_completed_at']);

            if (!empty($order['execution_completed_at'])) {
                // Execution is complete
                $exec_end = new DateTime($order['execution_completed_at']);
                $duration = $exec_end->getTimestamp() - $design_end->getTimestamp();
                if ($duration > 0) {
                    $stages[] = ['label' => 'تنفيذ: ' . format_duration($duration), 'duration' => $duration, 'class' => 'bg-primary', 'title' => 'مرحلة التنفيذ: ' . format_duration($duration)];
                }
            } elseif ($order['status'] === 'قيد التنفيذ') {
                // Execution is the current, active stage
                $duration = $now->getTimestamp() - $design_end->getTimestamp();
                if ($duration > 0) {
                    $stages[] = ['label' => 'تنفيذ (حالي): ' . format_duration($duration), 'duration' => $duration, 'class' => 'bg-primary', 'title' => 'المرحلة الحالية (تنفيذ): ' . format_duration($duration)];
                }
            }
        }

        if (empty($stages)) {
            return ''; // No relevant stages to show
        }

        // The total duration for percentage calculation is the sum of durations of visible stages
        $total_visible_duration = array_sum(array_column($stages, 'duration'));
        if ($total_visible_duration <= 0) {
            return '';
        }

        $html = '<div class="progress" style="height: 18px; font-size: 0.7rem;">';
        $stage_count = count($stages);
        foreach ($stages as $stage) {
            $percentage = ($stage['duration'] / $total_visible_duration) * 100;
            // إضافة فاصل بصري بين المراحل
            $border_style = ($stage_count > 1 && $percentage < 99) ? 'border-left: 2px solid white;' : '';
            // عرض الشريط فقط إذا كانت النسبة معقولة
            if ($percentage > 1) {
                $html .= '<div class="progress-bar ' . $stage['class'] . '" role="progressbar" style="width: ' . $percentage . '%;' . $border_style . '" title="' . htmlspecialchars($stage['title']) . '">' . htmlspecialchars($stage['label']) . '</div>';
            }
        }
        $html .= '</div>';
        return $html;
    } catch (Exception $e) {
        // Log error if needed: error_log($e->getMessage());
        return ''; // Return empty string on date errors
    }
}

/**
 * Formats a Saudi phone number for a WhatsApp link.
 * @param string $phone_number The 10-digit phone number (e.g., 05xxxxxxxx).
 * @param string $message Optional message to include in the link.
 * @return string The formatted WhatsApp URL.
 */
function format_whatsapp_link($phone_number, $message = '') {
    if (empty($phone_number)) {
        return '#'; // Return a non-functional link if no number
    }
    // Remove all non-numeric characters
    $cleaned_phone = preg_replace('/[^0-9]/', '', $phone_number);
    // Get the last 9 digits (to remove leading 0 if present)
    $saudi_number = substr($cleaned_phone, -9);
    // Prepend the country code
    $international_number = '966' . $saudi_number;
    
    $url = 'https://wa.me/' . $international_number;
    if (!empty($message)) {
        $url .= '?text=' . urlencode($message);
    }
    return $url;
}
