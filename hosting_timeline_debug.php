<?php
// ملف تشخيص شامل لمشكلة Timeline على الاستضافة
session_start();
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $database = new App\Core\Database($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $conn = $database->getConnection();
    
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><title>تشخيص Timeline</title>";
    echo "<style>body{font-family:Arial;margin:20px;} .result{border:1px solid #ccc;margin:10px 0;padding:10px;background:#f9f9f9;}</style></head><body>";
    
    echo "<h1>تشخيص مشكلة Timeline - فلتر الموظف حسام</h1>";
    echo "<p><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>البيئة:</strong> " . ($_ENV['APP_ENV'] ?? 'غير محدد') . "</p>";
    
    // 1. فحص قاعدة البيانات
    echo "<h2>1. فحص قاعدة البيانات:</h2>";
    echo "<div class='result'>";
    echo "<strong>اسم قاعدة البيانات:</strong> " . $_ENV['DB_NAME'] . "<br>";
    echo "<strong>المضيف:</strong> " . $_ENV['DB_HOST'] . "<br>";
    echo "<strong>المستخدم:</strong> " . $_ENV['DB_USERNAME'] . "<br>";
    echo "</div>";
    
    // 2. فحص الجداول
    echo "<h2>2. فحص الجداول المتاحة:</h2>";
    echo "<div class='result'>";
    $result = $conn->query('SHOW TABLES');
    $tables = [];
    while($row = $result->fetch_array()) { 
        $tables[] = $row[0];
    }
    echo "الجداول: " . implode(', ', $tables);
    echo "</div>";
    
    // 3. فحص بيانات الموظفين
    echo "<h2>3. فحص بيانات الموظفين:</h2>";
    echo "<div class='result'>";
    $result = $conn->query("SELECT employee_id, name, role FROM employees ORDER BY name");
    while ($row = $result->fetch_assoc()) {
        $highlight = (strpos($row['name'], 'حسام') !== false) ? "style='background-color: yellow;'" : "";
        echo "<div $highlight>ID: {$row['employee_id']}, Name: '{$row['name']}', Role: '{$row['role']}'</div>";
    }
    echo "</div>";
    
    // 4. البحث عن حسام بطرق مختلفة
    echo "<h2>4. البحث عن الموظف حسام:</h2>";
    echo "<div class='result'>";
    
    $search_patterns = ['%حسام%', '%hossam%', '%Hossam%', '%HOSSAM%'];
    foreach ($search_patterns as $pattern) {
        $stmt = $conn->prepare("SELECT employee_id, name, role FROM employees WHERE name LIKE ?");
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        echo "<strong>البحث بـ '$pattern':</strong><br>";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "- وُجد: ID: {$row['employee_id']}, Name: '{$row['name']}', Role: '{$row['role']}'<br>";
            }
        } else {
            echo "- لم يتم العثور على نتائج<br>";
        }
    }
    echo "</div>";
    
    // 5. فحص الترميز
    echo "<h2>5. فحص الترميز:</h2>";
    echo "<div class='result'>";
    $result = $conn->query("SELECT @@character_set_database, @@collation_database");
    $charset_info = $result->fetch_assoc();
    echo "<strong>Character Set:</strong> " . $charset_info['@@character_set_database'] . "<br>";
    echo "<strong>Collation:</strong> " . $charset_info['@@collation_database'] . "<br>";
    echo "</div>";
    
    // 6. اختبار الفلتر مع أول موظف معمل
    echo "<h2>6. اختبار الفلتر مع أول موظف له دور 'معمل':</h2>";
    echo "<div class='result'>";
    $result = $conn->query("SELECT employee_id, name, role FROM employees WHERE role = 'معمل' LIMIT 1");
    if ($workshop_employee = $result->fetch_assoc()) {
        echo "<strong>موظف المعمل الأول:</strong> ID: {$workshop_employee['employee_id']}, Name: '{$workshop_employee['name']}'<br><br>";
        
        // تطبيق نفس فلتر Timeline
        $filter_employee = $workshop_employee['employee_id'];
        $sql = "SELECT COUNT(*) as total_orders FROM orders o WHERE (o.designer_id = ? OR o.workshop_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $filter_employee, $filter_employee);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        
        echo "<strong>عدد الطلبات المرتبطة:</strong> {$count_row['total_orders']}<br>";
        
        if ($count_row['total_orders'] > 0) {
            echo "<strong>أول 3 طلبات:</strong><br>";
            $sql = "SELECT o.order_id, o.status, c.company_name as client_name, 
                           e.name as designer_name, w.name as workshop_name
                    FROM orders o
                    JOIN clients c ON o.client_id = c.client_id
                    LEFT JOIN employees e ON o.designer_id = e.employee_id
                    LEFT JOIN employees w ON o.workshop_id = w.employee_id
                    WHERE (o.designer_id = ? OR o.workshop_id = ?)
                    ORDER BY o.order_date DESC
                    LIMIT 3";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $filter_employee, $filter_employee);
            $stmt->execute();
            $orders_result = $stmt->get_result();
            
            while ($order = $orders_result->fetch_assoc()) {
                echo "- Order {$order['order_id']}: {$order['client_name']} | Designer: {$order['designer_name']} | Workshop: {$order['workshop_name']}<br>";
            }
        }
    } else {
        echo "لم يتم العثور على موظفين بدور 'معمل'";
    }
    echo "</div>";
    
    // 7. فحص المعاملات في URL
    echo "<h2>7. فحص المعاملات المرسلة:</h2>";
    echo "<div class='result'>";
    echo "<strong>GET Parameters:</strong><br>";
    foreach ($_GET as $key => $value) {
        echo "- $key: '$value'<br>";
    }
    if (empty($_GET)) {
        echo "لا توجد معاملات GET";
    }
    echo "</div>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<h1>خطأ في الاتصال بقاعدة البيانات:</h1>";
    echo "<div style='background: #ffcccc; padding: 10px; border: 1px solid red;'>";
    echo "رسالة الخطأ: " . $e->getMessage();
    echo "</div>";
}
?>
