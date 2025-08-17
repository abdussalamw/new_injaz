<?php
declare(strict_types=1);

// أداة إدارية لإصلاح بيانات المعامل في الطلبات
session_start();

// تحميل إعدادات قاعدة البيانات
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// التحقق من الدخول كمدير
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
require_once __DIR__ . '/src/Core/RoleHelper.php';

$db = new \App\Core\Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    $_ENV['DB_NAME']
);
$conn = $db->getConnection();

// التحقق من تسجيل الدخول والصلاحيات
if (!\App\Core\AuthCheck::isLoggedIn($conn)) {
    die('يجب تسجيل الدخول أولاً');
}

if (!\App\Core\RoleHelper::isManager()) {
    die('هذه الأداة للمديرين فقط');
}

$action = $_GET['action'] ?? 'show';

echo "<h2>أداة إصلاح بيانات المعامل</h2>";

if ($action === 'fix') {
    echo "<h3>جاري إصلاح البيانات...</h3>";
    
    try {
        $conn->begin_transaction();
        
        // 1. البحث عن معمل متاح (أولوية للمعامل، ثانياً المديرين)
        $workshop_stmt = $conn->query("
            SELECT employee_id, name, role 
            FROM employees 
            WHERE role IN ('معمل', 'مدير') 
            ORDER BY CASE WHEN role = 'معمل' THEN 1 ELSE 2 END, employee_id 
            LIMIT 1
        ");
        
        $workshop = $workshop_stmt->fetch_assoc();
        
        if (!$workshop) {
            echo "<p style='color: red;'>❌ لا يوجد موظفين معامل أو مديرين في النظام</p>";
            echo "<p>يجب إضافة موظف بدور 'معمل' أو 'مدير' أولاً</p>";
            $conn->rollback();
            exit;
        }
        
        echo "<p>✅ تم العثور على معمل: " . htmlspecialchars($workshop['name']) . " (" . htmlspecialchars($workshop['role']) . ")</p>";
        
        // 2. إصلاح الطلبات التي لديها تصميم مكتمل لكن بدون معمل معين
        $fix_stmt = $conn->prepare("
            UPDATE orders 
            SET workshop_id = ? 
            WHERE design_completed_at IS NOT NULL 
            AND status != 'قيد التصميم' 
            AND (workshop_id IS NULL OR workshop_id = 0)
        ");
        
        $fix_stmt->bind_param("i", $workshop['employee_id']);
        $fix_stmt->execute();
        
        $affected_orders = $fix_stmt->affected_rows;
        
        echo "<p>✅ تم إصلاح $affected_orders طلب</p>";
        
        // 3. إصلاح المصممين المفقودين أيضاً (إذا لزم الأمر)
        $designer_stmt = $conn->query("
            SELECT employee_id, name 
            FROM employees 
            WHERE role IN ('مصمم', 'مدير') 
            ORDER BY CASE WHEN role = 'مصمم' THEN 1 ELSE 2 END, employee_id 
            LIMIT 1
        ");
        
        $designer = $designer_stmt->fetch_assoc();
        
        if ($designer) {
            $fix_designer_stmt = $conn->prepare("
                UPDATE orders 
                SET designer_id = ? 
                WHERE designer_id IS NULL OR designer_id = 0
            ");
            
            $fix_designer_stmt->bind_param("i", $designer['employee_id']);
            $fix_designer_stmt->execute();
            
            $affected_designer_orders = $fix_designer_stmt->affected_rows;
            echo "<p>✅ تم إصلاح $affected_designer_orders طلب بدون مصمم</p>";
        }
        
        $conn->commit();
        echo "<p style='color: green;'><strong>تمت عملية الإصلاح بنجاح!</strong></p>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red;'>❌ خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

// عرض الحالة الحالية
echo "<h3>الحالة الحالية:</h3>";

// فحص الموظفين
echo "<h4>الموظفين المتاحين:</h4>";
$employees_result = $conn->query("
    SELECT employee_id, name, role 
    FROM employees 
    WHERE role IN ('مصمم', 'معمل', 'مدير')
    ORDER BY role, name
");

if ($employees_result && $employees_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>الاسم</th><th>الدور</th></tr>";
    while ($emp = $employees_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($emp['employee_id']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['name']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ لا يوجد موظفين مصممين أو معامل!</p>";
}

// فحص الطلبات المشكوك فيها
echo "<h4>الطلبات التي تحتاج إصلاح:</h4>";
$problem_orders = $conn->query("
    SELECT 
        o.order_id,
        o.status,
        o.design_completed_at,
        o.execution_completed_at,
        o.designer_id,
        o.workshop_id,
        e1.name as designer_name,
        e2.name as workshop_name
    FROM orders o
    LEFT JOIN employees e1 ON o.designer_id = e1.employee_id
    LEFT JOIN employees e2 ON o.workshop_id = e2.employee_id
    WHERE 
        (o.designer_id IS NULL OR o.designer_id = 0 OR e1.name IS NULL)
        OR 
        (o.design_completed_at IS NOT NULL AND o.status != 'قيد التصميم' AND (o.workshop_id IS NULL OR o.workshop_id = 0 OR e2.name IS NULL))
    ORDER BY o.order_id DESC
    LIMIT 10
");

if ($problem_orders && $problem_orders->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Order ID</th><th>Status</th><th>Designer</th><th>Workshop</th><th>مشكلة</th></tr>";
    while ($order = $problem_orders->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>" . htmlspecialchars($order['designer_name'] ?? 'غير معين') . "</td>";
        echo "<td>" . htmlspecialchars($order['workshop_name'] ?? 'غير معين') . "</td>";
        
        $issues = [];
        if (empty($order['designer_name'])) $issues[] = 'مصمم مفقود';
        if (!empty($order['design_completed_at']) && $order['status'] !== 'قيد التصميم' && empty($order['workshop_name'])) {
            $issues[] = 'معمل مفقود';
        }
        echo "<td style='color: red;'>" . implode(', ', $issues) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='?action=fix' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 إصلاح المشاكل</a></p>";
} else {
    echo "<p style='color: green;'>✅ جميع الطلبات سليمة</p>";
}

echo "<hr>";
echo "<p><a href='src/Reports/Timeline.php'>← العودة للجدول الزمني</a></p>";
?>
