<?php
// ========================================
// الحل الثاني: API مبسط للبحث بدون مصادقة
// ========================================
// ملف: simple_client_search.php

session_start();

// التحقق السريع من الجلسة (بدون إعادة توجيه)
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => true,
        'message' => 'Authentication required',
        'redirect' => '/login'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new \App\Core\Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_USERNAME'], 
    $_ENV['DB_PASSWORD'],
    $_ENV['DB_NAME']
);
$conn = $db->getConnection();

header('Content-Type: application/json; charset=utf-8');

try {
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 1) {
        echo json_encode([]);
        exit;
    }
    
    $search_param = "%" . $query . "%";
    $stmt = $conn->prepare("
        SELECT client_id, company_name, phone, email 
        FROM clients 
        WHERE company_name LIKE ? OR phone LIKE ? 
        ORDER BY company_name ASC 
        LIMIT 10
    ");
    
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $clients = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($clients, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'حدث خطأ في البحث'
    ], JSON_UNESCAPED_UNICODE);
}
?>
