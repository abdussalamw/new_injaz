<?php
// direct_search.php - نقطة وصول مباشرة للبحث في العملاء
// يتبع القاعدة الذهبية: لا نغير الأكواد الموجودة، نضيف ملف منفصل

// إعدادات الأمان والترميز
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'طريقة طلب غير مسموحة']);
    exit;
}

try {
    // تحميل المتطلبات
    require_once __DIR__ . '/vendor/autoload.php';
    
    // تحميل متغيرات البيئة
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
    
    // إعداد اتصال قاعدة البيانات
    $conn = new mysqli(
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_USERNAME'] ?? 'root', 
        $_ENV['DB_PASSWORD'] ?? '',
        $_ENV['DB_NAME'] ?? 'new_injaz'
    );
    
    // التحقق من الاتصال
    if ($conn->connect_error) {
        throw new Exception('فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error);
    }
    
    // تعيين الترميز
    $conn->set_charset("utf8mb4");
    
    // الحصول على نص البحث
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    // التحقق من صحة المدخل
    if (strlen($query) < 1) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // تنظيف وتحضير البحث
    $search_param = "%" . $query . "%";
    
    // إعداد الاستعلام الآمن
    $stmt = $conn->prepare("
        SELECT client_id, company_name, phone, email 
        FROM clients 
        WHERE company_name LIKE ? OR phone LIKE ? 
        ORDER BY company_name ASC 
        LIMIT 10
    ");
    
    if (!$stmt) {
        throw new Exception('فشل في إعداد الاستعلام: ' . $conn->error);
    }
    
    // ربط المعاملات وتنفيذ الاستعلام
    $stmt->bind_param("ss", $search_param, $search_param);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تنفيذ الاستعلام: ' . $stmt->error);
    }
    
    // الحصول على النتائج
    $result = $stmt->get_result();
    $clients = [];
    
    while ($row = $result->fetch_assoc()) {
        $clients[] = [
            'client_id' => $row['client_id'],
            'company_name' => $row['company_name'],
            'phone' => $row['phone'],
            'email' => $row['email']
        ];
    }
    
    // إغلاق الاستعلام والاتصال
    $stmt->close();
    $conn->close();
    
    // إرجاع النتائج
    echo json_encode($clients, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // تسجيل الخطأ
    error_log("Direct Search Error: " . $e->getMessage());
    
    // إرجاع رسالة خطأ للمستخدم
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'حدث خطأ في البحث'
    ], JSON_UNESCAPED_UNICODE);
}
?>
