<?php
/**
 * ملف تشخيص Timeline للاستضافة
 * ضع هذا الملف في الاستضافة وافتحه في المتصفح للتشخيص
 */
session_start();

echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'>";
echo "<title>تشخيص Timeline على الاستضافة</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }
    .section { border: 2px solid #007bff; margin: 20px 0; padding: 15px; border-radius: 8px; }
    .success { background-color: #d4edda; border-color: #28a745; }
    .warning { background-color: #fff3cd; border-color: #ffc107; }
    .error { background-color: #f8d7da; border-color: #dc3545; }
    .highlight { background-color: yellow; padding: 3px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 تشخيص شامل لـ Timeline على الاستضافة</h1>";
echo "<p><strong>التاريخ والوقت:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // تحميل .env
    require_once 'vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    echo "<div class='section success'>";
    echo "<h2>✅ تم تحميل Autoloader و .env بنجاح</h2>";
    echo "<strong>BASE_PATH:</strong> " . ($_ENV['BASE_PATH'] ?? 'غير محدد') . "<br>";
    echo "<strong>DB_NAME:</strong> " . ($_ENV['DB_NAME'] ?? 'غير محدد') . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ خطأ في تحميل .env</h2>";
    echo "<p>الخطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

try {
    // الاتصال بقاعدة البيانات
    $database = new App\Core\Database($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $conn = $database->getConnection();
    
    echo "<div class='section success'>";
    echo "<h2>✅ تم الاتصال بقاعدة البيانات بنجاح</h2>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ خطأ في الاتصال بقاعدة البيانات</h2>";
    echo "<p>الخطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}

// فحص الجلسة والمستخدم
echo "<div class='section'>";
echo "<h2>👤 فحص حالة المستخدم</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ <strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>";
    echo "<p>✅ <strong>User Name:</strong> " . ($_SESSION['user_name'] ?? 'غير محدد') . "</p>";
    echo "<p>✅ <strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'غير محدد') . "</p>";
} else {
    echo "<p>⚠️ المستخدم غير مسجل دخول - سيتم استخدام أول مدير للاختبار</p>";
    
    // البحث عن مدير للاختبار
    $admin_result = $conn->query("SELECT employee_id, name, role FROM employees WHERE role = 'مدير' LIMIT 1");
    if ($admin = $admin_result->fetch_assoc()) {
        $_SESSION['user_id'] = $admin['employee_id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_role'] = $admin['role'];
        echo "<p>✅ تم تسجيل دخول تجريبي كـ: <strong>{$admin['name']}</strong></p>";
    } else {
        echo "<p>❌ لم يتم العثور على مدير في النظام!</p>";
    }
}
echo "</div>";

// فحص صلاحيات المستخدم
echo "<div class='section'>";
echo "<h2>🔐 فحص الصلاحيات</h2>";
try {
    $has_dashboard_permission = \App\Core\Permissions::has_permission('dashboard_reports_view', $conn);
    if ($has_dashboard_permission) {
        echo "<p>✅ المستخدم لديه صلاحية <code>dashboard_reports_view</code></p>";
    } else {
        echo "<p>❌ المستخدم لا يملك صلاحية <code>dashboard_reports_view</code></p>";
    }
} catch (Exception $e) {
    echo "<p>❌ خطأ في فحص الصلاحيات: " . $e->getMessage() . "</p>";
}
echo "</div>";

// فحص الموظفين
echo "<div class='section'>";
echo "<h2>👥 فحص بيانات الموظفين</h2>";

// كل الموظفين
$all_employees = $conn->query("SELECT employee_id, name, role FROM employees ORDER BY name");
echo "<h3>جميع الموظفين في النظام:</h3>";
echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'><th>ID</th><th>الاسم</th><th>الدور</th><th>مؤهل للفلتر؟</th></tr>";
while ($emp = $all_employees->fetch_assoc()) {
    $qualified = in_array($emp['role'], ['مصمم', 'معمل']);
    $is_hossam = strpos($emp['name'], 'حسام') !== false;
    $highlight_class = $is_hossam ? "class='highlight'" : "";
    $qualified_text = $qualified ? "✅ نعم" : "❌ لا";
    
    echo "<tr $highlight_class>";
    echo "<td>{$emp['employee_id']}</td>";
    echo "<td>{$emp['name']}</td>";
    echo "<td>{$emp['role']}</td>";
    echo "<td>$qualified_text</td>";
    echo "</tr>";
}
echo "</table>";

// الموظفين المؤهلين للفلتر فقط
echo "<h3>الموظفين المؤهلين للفلتر (مصمم + معمل):</h3>";
$qualified_employees = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'معمل') ORDER BY name");
$qualified_count = $qualified_employees->num_rows;

if ($qualified_count > 0) {
    echo "<p>✅ عدد الموظفين المؤهلين: <strong>$qualified_count</strong></p>";
    echo "<ul>";
    while ($emp = $qualified_employees->fetch_assoc()) {
        $is_hossam = strpos($emp['name'], 'حسام') !== false;
        $highlight = $is_hossam ? "class='highlight'" : "";
        echo "<li $highlight>ID: {$emp['employee_id']} - {$emp['name']} ({$emp['role']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ لا يوجد موظفين مؤهلين للفلتر!</p>";
}
echo "</div>";

// البحث المحدد عن حسام
echo "<div class='section'>";
echo "<h2>🔍 البحث المحدد عن حسام</h2>";

$search_patterns = [
    '%حسام%' => 'البحث العادي بالعربية',
    '%hossam%' => 'البحث بالإنجليزية صغيرة',
    '%Hossam%' => 'البحث بالإنجليزية كبيرة',
    '%HOSSAM%' => 'البحث بالإنجليزية كاملة كبيرة'
];

foreach ($search_patterns as $pattern => $description) {
    $stmt = $conn->prepare("SELECT employee_id, name, role FROM employees WHERE name LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h4>$description (<code>$pattern</code>):</h4>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p>✅ وُجد: ID: {$row['employee_id']}, Name: '{$row['name']}', Role: '{$row['role']}'</p>";
        }
    } else {
        echo "<p>❌ لم يتم العثور على نتائج</p>";
    }
}
echo "</div>";

// اختبار الفلتر مع حسام
echo "<div class='section'>";
echo "<h2>🧪 اختبار الفلتر مع حسام</h2>";

$hossam_result = $conn->query("SELECT employee_id, name, role FROM employees WHERE name LIKE '%حسام%' AND role IN ('مصمم', 'معمل') LIMIT 1");

if ($hossam = $hossam_result->fetch_assoc()) {
    echo "<p>✅ تم العثور على حسام: <strong>{$hossam['name']}</strong> (ID: {$hossam['employee_id']}, Role: {$hossam['role']})</p>";
    
    // اختبار عدد الطلبات
    $filter_employee = $hossam['employee_id'];
    $count_sql = "SELECT COUNT(*) as total FROM orders o WHERE (o.designer_id = ? OR o.workshop_id = ?)";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ii", $filter_employee, $filter_employee);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    
    echo "<p>📊 عدد الطلبات المرتبطة بحسام: <strong>{$count_row['total']}</strong></p>";
    
    if ($count_row['total'] > 0) {
        echo "<h4>أول 5 طلبات لحسام:</h4>";
        $sample_sql = "SELECT o.order_id, o.status, c.company_name, e.name as designer, w.name as workshop 
                       FROM orders o 
                       LEFT JOIN clients c ON o.client_id = c.client_id
                       LEFT JOIN employees e ON o.designer_id = e.employee_id
                       LEFT JOIN employees w ON o.workshop_id = w.employee_id
                       WHERE (o.designer_id = ? OR o.workshop_id = ?)
                       ORDER BY o.order_date DESC LIMIT 5";
        
        $sample_stmt = $conn->prepare($sample_sql);
        $sample_stmt->bind_param("ii", $filter_employee, $filter_employee);
        $sample_stmt->execute();
        $sample_result = $sample_stmt->get_result();
        
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'><th>Order ID</th><th>العميل</th><th>المصمم</th><th>المعمل</th><th>الحالة</th></tr>";
        while ($order = $sample_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$order['order_id']}</td>";
            echo "<td>{$order['company_name']}</td>";
            echo "<td>{$order['designer']}</td>";
            echo "<td>{$order['workshop']}</td>";
            echo "<td>{$order['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // رابط الاختبار
        $test_url = $_ENV['BASE_PATH'] . '/reports/timeline?employee=' . $filter_employee;
        echo "<h4>رابط الاختبار:</h4>";
        echo "<p><a href='$test_url' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔗 اختبار فلتر حسام</a></p>";
        echo "<p><small>الرابط الكامل: <code>$test_url</code></small></p>";
        
    } else {
        echo "<p>⚠️ لا توجد طلبات مرتبطة بحسام</p>";
    }
} else {
    echo "<p>❌ لم يتم العثور على موظف اسمه حسام بدور مؤهل للفلتر</p>";
}
echo "</div>";

// فحص الترميز
echo "<div class='section'>";
echo "<h2>🔤 فحص الترميز</h2>";
$charset_result = $conn->query("SELECT @@character_set_database, @@collation_database");
$charset_info = $charset_result->fetch_assoc();
echo "<p><strong>Character Set:</strong> {$charset_info['@@character_set_database']}</p>";
echo "<p><strong>Collation:</strong> {$charset_info['@@collation_database']}</p>";

// فحص نص عربي تجريبي
$test_arabic = $conn->query("SELECT 'حسام الشيخ' as test_name");
$test_result = $test_arabic->fetch_assoc();
echo "<p><strong>اختبار النص العربي:</strong> '{$test_result['test_name']}'</p>";
echo "</div>";

echo "<div class='section success'>";
echo "<h2>✅ انتهى التشخيص</h2>";
echo "<p>إذا كان كل شيء يظهر بشكل صحيح هنا ولكن الفلتر لا يعمل في Timeline، قد تكون المشكلة في:</p>";
echo "<ul>";
echo "<li>صلاحيات المستخدم الفعلي (غير المستخدم التجريبي)</li>";
echo "<li>مشكلة في JavaScript أو تحديث الصفحة</li>";
echo "<li>تخزين الجلسة (session) مؤقت</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";

$conn->close();
?>
