<?php
declare(strict_types=1);

session_start();

// تدمير جميع بيانات الجلسة
session_unset();
session_destroy();

// حذف ملف تعريف الارتباط إذا كان موجوداً
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: /?page=login");
exit;
?>