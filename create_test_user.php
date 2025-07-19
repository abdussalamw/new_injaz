<?php
// ملف مؤقت لإنشاء مستخدم تجريبي
include 'db_connection_secure.php';

$test_username = 'test';
$test_password = 'test123';
$test_name = 'مستخدم تجريبي';
$test_role = 'مدير';
$test_email = 'test@test.com';

// تشفير كلمة المرور
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);

// التحقق من وجود المستخدم
$check_stmt = $conn->prepare("SELECT employee_id FROM employees WHERE email = ? OR name = ?");
$check_stmt->bind_param("ss", $test_email, $test_username);
$check_stmt->execute();
$existing_user = $check_stmt->get_result()->fetch_assoc();

if ($existing_user) {
    echo "المستخدم التجريبي موجود بالفعل.<br>";
    echo "اسم المستخدم: test<br>";
    echo "كلمة المرور: test123<br>";
} else {
    // إنشاء المستخدم الجديد
    $insert_stmt = $conn->prepare("INSERT INTO employees (name, role, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $test_phone = '0500000000';
    $insert_stmt->bind_param("sssss", $test_name, $test_role, $test_email, $test_phone, $hashed_password);
    
    if ($insert_stmt->execute()) {
        echo "تم إنشاء المستخدم التجريبي بنجاح!<br>";
        echo "اسم المستخدم: test<br>";
        echo "كلمة المرور: test123<br>";
        echo "الدور: مدير<br>";
        echo "<br><a href='login.php'>انتقل إلى صفحة تسجيل الدخول</a>";
    } else {
        echo "خطأ في إنشاء المستخدم: " . $conn->error;
    }
}
?>
