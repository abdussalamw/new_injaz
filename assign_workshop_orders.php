<?php
require_once 'src/Core/config.php';
require_once 'src/Core/Database.php';

$database = new \App\Core\Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn = $database->getConnection();

echo "<h1>تعيين الطلبات للمعمل</h1>";

// البحث عن حسام
$result = $conn->query("SELECT employee_id, name FROM employees WHERE role = 'معمل'");
$workshop_employees = [];
while($row = $result->fetch_assoc()) {
    $workshop_employees[] = $row;
    echo "<p>موظف معمل: " . $row['name'] . " (ID: " . $row['employee_id'] . ")</p>";
}

if (empty($workshop_employees)) {
    echo "<p style='color: red;'>لا يوجد موظفين في المعمل!</p>";
    exit;
}

// تعيين الطلبات التي في حالة "قيد التنفيذ" للمعمل
$husam_id = 4; // ID حسام

echo "<h2>تعيين الطلبات في حالة 'قيد التنفيذ' لحسام:</h2>";

$update_sql = "UPDATE orders SET workshop_id = ? WHERE status = 'قيد التنفيذ' AND workshop_id IS NULL";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $husam_id);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo "<p style='color: green;'>✅ تم تعيين $affected_rows طلب لحسام في المعمل</p>";
} else {
    echo "<p style='color: red;'>❌ خطأ في تعيين الطلبات: " . $stmt->error . "</p>";
}

// عرض الطلبات المُعيّنة لحسام الآن
echo "<h2>الطلبات المُعيّنة لحسام الآن:</h2>";
$result = $conn->query("SELECT o.order_id, o.status, o.workshop_id, c.company_name 
                       FROM orders o 
                       JOIN clients c ON o.client_id = c.client_id 
                       WHERE o.workshop_id = $husam_id");

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>رقم الطلب</th><th>العميل</th><th>الحالة</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#" . $row['order_id'] . "</td>";
        echo "<td>" . $row['company_name'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>لا توجد طلبات مُعيّنة لحسام</p>";
}

echo "<hr>";
echo "<p><a href='/new_injaz/orders'>عرض الطلبات</a></p>";
?>
