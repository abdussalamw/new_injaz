<?php
$servername = "localhost";
$username = "root";
$password = ""; // ضع هنا كلمة المرور إذا كان لديك
$dbname = "injaz";
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>
