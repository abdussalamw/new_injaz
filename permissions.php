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
        'الموظفون' => [
            'employee_view' => 'عرض',
            'employee_add' => 'إضافة',
            'employee_edit' => 'تعديل',
            'employee_delete' => 'حذف',
        ]
    ];
}

/**
 * دالة للتحقق مما إذا كان للمستخدم الحالي صلاحية للقيام بإجراء معين
 * @param string $action الصلاحية المطلوبة (مثال: 'client_add')
 * @return bool
 */
function has_permission($action) {
    $role = $_SESSION['user_role'] ?? 'guest';
    $user_id = $_SESSION['user_id'] ?? 0;

    // المدير له كل الصلاحيات دائماً
    if ($role === 'مدير') {
        return true;
    }

    // التحقق من صلاحيات المستخدم من قاعدة البيانات
    if (!isset($_SESSION['user_permissions'])) {
        // نحتاج للوصول إلى متغير الاتصال بقاعدة البيانات
        // الطريقة الأفضل هي تمريره كمعامل، ولكن للتبسيط سنستخدم global هنا
        global $conn; 
        if (!$conn) { include 'db_connection.php'; } // التأكد من وجود الاتصال
        $stmt = $conn->prepare("SELECT permission_key FROM employee_permissions WHERE employee_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $_SESSION['user_permissions'] = [];
        while ($row = $result->fetch_assoc()) {
            $_SESSION['user_permissions'][] = $row['permission_key'];
        }
    }

    // التحقق من وجود الصلاحية المحددة للمستخدم
    return in_array($action, $_SESSION['user_permissions']);
}