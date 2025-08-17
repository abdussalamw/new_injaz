<?php
/**
 * ملف إصلاح مشاكل Timeline على الاستضافة
 * استخدم هذا الملف لإصلاح أي مشاكل في البيانات أو الصلاحيات
 */
session_start();

echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'>";
echo "<title>إصلاح مشاكل Timeline</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }
    .section { border: 2px solid #007bff; margin: 20px 0; padding: 15px; border-radius: 8px; }
    .success { background-color: #d4edda; border-color: #28a745; }
    .warning { background-color: #fff3cd; border-color: #ffc107; }
    .error { background-color: #f8d7da; border-color: #dc3545; }
    .action-btn { background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔧 إصلاح مشاكل Timeline على الاستضافة</h1>";

try {
    require_once 'vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    $database = new App\Core\Database($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $conn = $database->getConnection();
    
    echo "<div class='section success'>";
    echo "<h2>✅ تم الاتصال بقاعدة البيانات</h2>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ خطأ في الاتصال</h2>";
    echo "<p>الخطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

// فحص إذا كان هناك إجراء مطلوب
$action = $_GET['action'] ?? '';

if ($action === 'fix_hossam') {
    echo "<div class='section warning'>";
    echo "<h2>🔄 إصلاح بيانات الموظف حسام</h2>";
    
    // البحث عن حسام
    $hossam_check = $conn->query("SELECT employee_id, name, role FROM employees WHERE name LIKE '%حسام%'");
    
    if ($hossam_check->num_rows === 0) {
        // إنشاء موظف حسام إذا لم يوجد
        $insert_hossam = $conn->prepare("INSERT INTO employees (name, role, username, password) VALUES (?, 'معمل', 'hossam', ?)");
        $hossam_name = 'حسام الشيخ';
        $hossam_password = password_hash('123456', PASSWORD_DEFAULT);
        $insert_hossam->bind_param("ss", $hossam_name, $hossam_password);
        
        if ($insert_hossam->execute()) {
            echo "<p>✅ تم إنشاء الموظف حسام بنجاح</p>";
            echo "<p>اسم المستخدم: hossam | كلمة المرور: 123456</p>";
        } else {
            echo "<p>❌ فشل في إنشاء الموظف حسام</p>";
        }
    } else {
        $hossam = $hossam_check->fetch_assoc();
        echo "<p>✅ الموظف حسام موجود: {$hossam['name']} (ID: {$hossam['employee_id']}, Role: {$hossam['role']})</p>";
        
        // التأكد من أن دوره "معمل"
        if ($hossam['role'] !== 'معمل') {
            $update_role = $conn->prepare("UPDATE employees SET role = 'معمل' WHERE employee_id = ?");
            $update_role->bind_param("i", $hossam['employee_id']);
            if ($update_role->execute()) {
                echo "<p>✅ تم تحديث دور حسام إلى 'معمل'</p>";
            }
        }
    }
    echo "</div>";
}

if ($action === 'assign_orders') {
    echo "<div class='section warning'>";
    echo "<h2>🔄 تخصيص طلبات للموظف حسام</h2>";
    
    $hossam_result = $conn->query("SELECT employee_id FROM employees WHERE name LIKE '%حسام%' LIMIT 1");
    if ($hossam = $hossam_result->fetch_assoc()) {
        $hossam_id = $hossam['employee_id'];
        
        // تخصيص بعض الطلبات الحديثة لحسام كمعمل
        $update_orders = $conn->prepare("UPDATE orders SET workshop_id = ? WHERE workshop_id IS NULL AND status IN ('قيد التنفيذ', 'جاهز للتسليم') LIMIT 10");
        $update_orders->bind_param("i", $hossam_id);
        
        if ($update_orders->execute()) {
            $affected = $conn->affected_rows;
            echo "<p>✅ تم تخصيص $affected طلب للموظف حسام كمعمل</p>";
        } else {
            echo "<p>❌ فشل في تخصيص الطلبات</p>";
        }
    } else {
        echo "<p>❌ لم يتم العثور على الموظف حسام</p>";
    }
    echo "</div>";
}

if ($action === 'fix_permissions') {
    echo "<div class='section warning'>";
    echo "<h2>🔄 إصلاح صلاحيات المستخدمين</h2>";
    
    // إعطاء صلاحيات التقارير لجميع المديرين
    $managers = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
    while ($manager = $managers->fetch_assoc()) {
        $manager_id = $manager['employee_id'];
        
        // إضافة صلاحية dashboard_reports_view
        $check_permission = $conn->prepare("SELECT * FROM employee_permissions WHERE employee_id = ? AND permission_key = 'dashboard_reports_view'");
        $check_permission->bind_param("i", $manager_id);
        $check_permission->execute();
        
        if ($check_permission->get_result()->num_rows === 0) {
            $add_permission = $conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, 'dashboard_reports_view')");
            $add_permission->bind_param("i", $manager_id);
            $add_permission->execute();
            echo "<p>✅ تم إضافة صلاحية التقارير للمدير ID: $manager_id</p>";
        }
    }
    echo "</div>";
}

if ($action === 'test_timeline') {
    echo "<div class='section'>";
    echo "<h2>🧪 اختبار Timeline مع حسام</h2>";
    
    // محاكاة Timeline مع فلتر حسام
    $hossam_result = $conn->query("SELECT employee_id, name FROM employees WHERE name LIKE '%حسام%' LIMIT 1");
    if ($hossam = $hossam_result->fetch_assoc()) {
        $filter_employee = $hossam['employee_id'];
        
        // تطبيق نفس استعلام Timeline
        $sql = "SELECT o.order_id, o.order_date, o.status, c.company_name as client_name,
                       e.name as designer_name, w.name as workshop_name
                FROM orders o
                JOIN clients c ON o.client_id = c.client_id
                LEFT JOIN employees e ON o.designer_id = e.employee_id
                LEFT JOIN employees w ON o.workshop_id = w.employee_id
                WHERE (o.designer_id = ? OR o.workshop_id = ?)
                ORDER BY o.order_date DESC
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $filter_employee, $filter_employee);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p>✅ نتائج فلتر حسام ({$hossam['name']}):</p>";
        if ($result->num_rows > 0) {
            echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
            echo "<tr style='background: #f8f9fa;'><th>Order ID</th><th>التاريخ</th><th>العميل</th><th>المصمم</th><th>المعمل</th><th>الحالة</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['order_id']}</td>";
                echo "<td>" . date('Y-m-d', strtotime($row['order_date'])) . "</td>";
                echo "<td>{$row['client_name']}</td>";
                echo "<td>{$row['designer_name']}</td>";
                echo "<td>{$row['workshop_name']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ لا توجد طلبات مرتبطة بحسام</p>";
        }
    }
    echo "</div>";
}

// إظهار الخيارات المتاحة
if (empty($action)) {
    echo "<div class='section'>";
    echo "<h2>🛠️ خيارات الإصلاح المتاحة</h2>";
    echo "<p>اختر أحد الخيارات التالية لإصلاح المشاكل:</p>";
    
    echo "<a href='?action=fix_hossam' class='action-btn'>1. إصلاح بيانات الموظف حسام</a>";
    echo "<a href='?action=assign_orders' class='action-btn'>2. تخصيص طلبات لحسام</a>";
    echo "<a href='?action=fix_permissions' class='action-btn'>3. إصلاح صلاحيات المديرين</a>";
    echo "<a href='?action=test_timeline' class='action-btn'>4. اختبار فلتر Timeline</a>";
    
    echo "<br><br>";
    echo "<a href='hosting_timeline_diagnostic.php' class='action-btn' style='background: #28a745;'>🔍 تشغيل التشخيص أولاً</a>";
    echo "</div>";
    
    // عرض معلومات سريعة
    echo "<div class='section'>";
    echo "<h2>📊 معلومات سريعة</h2>";
    
    $stats = [
        'إجمالي الموظفين' => $conn->query("SELECT COUNT(*) as c FROM employees")->fetch_assoc()['c'],
        'المصممين' => $conn->query("SELECT COUNT(*) as c FROM employees WHERE role = 'مصمم'")->fetch_assoc()['c'],
        'المعامل' => $conn->query("SELECT COUNT(*) as c FROM employees WHERE role = 'معمل'")->fetch_assoc()['c'],
        'المديرين' => $conn->query("SELECT COUNT(*) as c FROM employees WHERE role = 'مدير'")->fetch_assoc()['c'],
        'إجمالي الطلبات' => $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'],
    ];
    
    foreach ($stats as $label => $count) {
        echo "<p><strong>$label:</strong> $count</p>";
    }
    
    // فحص وجود حسام
    $hossam_check = $conn->query("SELECT name, role FROM employees WHERE name LIKE '%حسام%'")->fetch_assoc();
    if ($hossam_check) {
        echo "<p><strong>الموظف حسام:</strong> ✅ موجود ({$hossam_check['name']} - {$hossam_check['role']})</p>";
    } else {
        echo "<p><strong>الموظف حسام:</strong> ❌ غير موجود</p>";
    }
    echo "</div>";
}

echo "<div class='section success'>";
echo "<h2>📝 ملاحظات مهمة</h2>";
echo "<ul>";
echo "<li>تأكد من رفع هذا الملف إلى الاستضافة</li>";
echo "<li>قم بتشغيل التشخيص أولاً لفهم المشكلة</li>";
echo "<li>استخدم خيارات الإصلاح حسب الحاجة</li>";
echo "<li>احذف هذا الملف بعد انتهاء الإصلاح لأسباب أمنية</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
