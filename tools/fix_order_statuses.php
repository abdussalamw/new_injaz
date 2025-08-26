<?php
// سكريبت تصحيح حالات الطلبات والمدفوعات في قاعدة البيانات
// لا حاجة لأي مكتبة خارجية
$mysqli = new mysqli('localhost', 'root', '', 'injaz'); // عدل بيانات الاتصال إذا لزم
if ($mysqli->connect_errno) {
    die('فشل الاتصال بقاعدة البيانات: ' . $mysqli->connect_error);
}

// القيم المعتمدة لمسار الإدارة
$admin_statuses = [
    'قيد التصميم',
    'قيد التنفيذ',
    'جاهز للتسليم',
    'مكتمل',
    'ملغي'
];
// القيم المعتمدة لمسار المال
$payment_statuses = [
    'غير مدفوع',
    'مدفوع جزئياً',
    'مدفوع'
];

// تحديث حالات الطلبات المخالفة
$query = "UPDATE orders SET status = 'ملغي' WHERE status NOT IN ('" . implode("','", $admin_statuses) . "')";
$mysqli->query($query);

// تحديث حالات المدفوعات المخالفة
$query2 = "UPDATE orders SET payment_status = 'غير مدفوع' WHERE payment_status NOT IN ('" . implode("','", $payment_statuses) . "')";
$mysqli->query($query2);

// تصحيح الطلبات ذات القيمة 0
$query3 = "UPDATE orders SET payment_status = 'غير مدفوع' WHERE total_amount = 0";
$mysqli->query($query3);

echo "تم تصحيح جميع الحالات بنجاح";
$mysqli->close();
