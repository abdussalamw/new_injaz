<?php
// البحث في العملاء - يعمل مع نظام التوجيه
// This file is called from the router, which provides $conn

header('Content-Type: application/json; charset=utf-8');

try {
    // التحقق من اتصال قاعدة البيانات
    if (!isset($conn) || !$conn) {
        echo json_encode(['error' => 'اتصال قاعدة البيانات غير متوفر']);
        exit;
    }
    
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 1) {
        echo json_encode([]);
        exit;
    }
    
    // البحث في قاعدة البيانات
    $search_param = "%" . $query . "%";
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
    
    $stmt->bind_param("ss", $search_param, $search_param);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تنفيذ الاستعلام: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $clients = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($clients, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("SearchClient Error: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => 'حدث خطأ في البحث'
    ], JSON_UNESCAPED_UNICODE);
}
