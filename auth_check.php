<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include_once 'permissions.php';

// التحقق من تسجيل الدخول، باستثناء صفحة تسجيل الدخول نفسها
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit;
}

/**
 * دالة مختصرة للتحقق من الصلاحية وإيقاف التنفيذ في حال عدم وجودها
 * @param string $action الصلاحية المطلوبة
 */
function check_permission($action, $conn) {
    if (!has_permission($action, $conn)) {
        // يمكنك إنشاء صفحة خاصة لعرض رسالة "غير مصرح لك"
        die('<div class="container"><div class="alert alert-danger mt-4">ليس لديك الصلاحية للوصول إلى هذه الصفحة أو تنفيذ هذا الإجراء.</div></div>');
    }
}
