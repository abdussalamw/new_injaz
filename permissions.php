<?php
if (session_status() == PHP_SESSION_NONE) session_start();

/**
 * مصفوفة لتحديد صلاحيات كل دور في النظام
 * يمكنك التعديل عليها بسهولة لإعطاء أو منع صلاحيات
 * هذه المصفوفة أصبحت الآن تستخدم فقط لتعريف الصلاحيات المتاحة في النظام
 */
function get_all_permissions() {
    return [
        'عام' => [
            'dashboard_reports_view' => 'عرض الإحصائيات والتقارير',
            'dashboard_view' => 'عرض لوحة التحكم',
        ],
        'الطلبات' => [
            'order_view_all' => 'عرض كل الطلبات',
            'order_view_own' => 'عرض طلباته فقط',
            'order_add' => 'إضافة',
            'order_edit' => 'تعديل',
            'order_edit_status' => 'تغيير حالة الطلب',
            'order_delete' => 'حذف',
        ],
        'بطاقة المهام' => [
            'task_card_view_summary' => 'عرض ملخص المنتجات',
            'task_card_view_client' => 'عرض اسم العميل',
            'task_card_view_payment' => 'عرض حالة الدفع',
            'task_card_view_designer' => 'عرض اسم المصمم',
            'task_card_view_countdown' => 'عرض العد التنازلي',
            'task_card_edit' => 'تعديل/عرض تفاصيل البطاقة',
            'task_card_actions' => 'استخدام أزرار الإجراءات',
            'task_card_whatsapp' => 'استخدام زر الواتساب',
        ],
        'العملاء' => [
            'client_view' => 'عرض',
            'client_add' => 'إضافة',
            'client_edit' => 'تعديل',
            'client_delete' => 'حذف',
            'client_import' => 'استيراد',
            'client_export' => 'تصدير',
        ],
        'المنتجات' => [
            'product_view' => 'عرض',
            'product_add' => 'إضافة',
            'product_edit' => 'تعديل',
            'product_delete' => 'حذف',
        ],
        'المالية' => [
            'order_view_financials' => 'عرض كل الطلبات للمتابعة المالية',
            'order_financial_settle' => 'تسوية الطلبات مالياً (تأكيد الدفع الكامل)',
        ],
        'الموظفون' => [
            'employee_view' => 'عرض',
            'employee_add' => 'إضافة',
            'employee_edit' => 'تعديل',
            'employee_delete' => 'حذف',
            'employee_password_reset' => 'إعادة تعيين كلمة المرور',
            'employee_permissions_edit' => 'تعديل صلاحيات الموظفين',
        ],
        'التقارير' => [
            'client_balance_report_view' => 'عرض تقرير أرصدة العملاء',
            'financial_reports_view' => 'عرض التقارير المالية الشاملة',
        ]
    ];
}

/**
 * دالة للتحقق مما إذا كان للمستخدم الحالي صلاحية للقيام بإجراء معين
 * @param string $action الصلاحية المطلوبة (مثال: 'client_add')
 * @return bool
 */
function has_permission($action, $conn) {
    $role = $_SESSION['user_role'] ?? 'guest';
    $user_id = $_SESSION['user_id'] ?? 0;

    // المدير يمتلك كل الصلاحيات دائماً.
    if ($role === 'مدير') {
        return true;
    }

    // المصمم له صلاحيات أساسية ثابتة لا يمكن تعديلها لضمان سير العمل
    if ($role === 'مصمم') {
        $designer_core_permissions = [
            'dashboard_view',
            'order_view_own', // يستطيع رؤية طلباته فقط
            'order_add',      // يستطيع إضافة طلب جديد
            'order_edit',     // يستطيع تعديل تفاصيل الطلب
            'order_edit_status' // يستطيع تغيير حالة الطلب (مثلاً من "قيد التصميم" إلى "جاهز للتنفيذ")
        ];
        if (in_array($action, $designer_core_permissions)) {
            return true;
        }
    }

    // يتم الآن جلب الصلاحيات في كل مرة لضمان أن التغييرات تنعكس فوراً
    $stmt = $conn->prepare("SELECT permission_key FROM employee_permissions WHERE employee_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_permissions = [];
    while ($row = $result->fetch_assoc()) {
        $user_permissions[] = $row['permission_key'];
    }
    $stmt->close();
    $_SESSION['user_permissions'] = $user_permissions; // تحديث الجلسة

    // التحقق من وجود الصلاحية المحددة للمستخدم ضمن الصلاحيات المخصصة
    return in_array($action, $user_permissions);
}
