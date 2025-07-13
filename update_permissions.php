<?php
include 'auth_check.php';
include 'db_connection.php';

header('Content-Type: application/json');

// التأكد من أن المدير فقط هو من يستطيع تغيير الصلاحيات
if (($_SESSION['user_role'] ?? '') !== 'مدير') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$employee_id = $data['employee_id'] ?? 0;
$permission_key = $data['permission_key'] ?? '';
$has_permission = $data['has_permission'] ?? false;

if (empty($employee_id) || empty($permission_key)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة.']);
    exit;
}

if ($has_permission) {
    // إضافة صلاحية
    $stmt = $conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?) ON DUPLICATE KEY UPDATE permission_key=permission_key");
    $stmt->bind_param("is", $employee_id, $permission_key);
} else {
    // إزالة صلاحية
    $stmt = $conn->prepare("DELETE FROM employee_permissions WHERE employee_id = ? AND permission_key = ?");
    $stmt->bind_param("is", $employee_id, $permission_key);
}

if ($stmt->execute()) {
    // ملاحظة: هذا السطر يقوم بمسح صلاحيات المدير (المستخدم الحالي) من الجلسة،
    // مما يجبر النظام على إعادة تحميلها في طلبه القادم.
    // الموظف الذي تم تعديل صلاحياته سيحتاج لتسجيل الخروج والدخول مجدداً لتطبيق التغييرات.
    unset($_SESSION['user_permissions']);
    echo json_encode(['success' => true, 'message' => 'تم تحديث الصلاحية بنجاح.']);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.']);
}