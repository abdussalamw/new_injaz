<?php
include 'db_connection_secure.php';
include 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['name']) || empty(trim($input['name']))) {
    echo json_encode(['success' => false, 'message' => 'اسم المنتج مطلوب']);
    exit;
}

$name = trim($input['name']);
$default_size = trim($input['default_size'] ?? '');
$default_material = trim($input['default_material'] ?? '');
$default_details = trim($input['default_details'] ?? '');

try {
    // التحقق من عدم وجود منتج بنفس الاسم
    $check_stmt = $conn->prepare("SELECT product_id FROM products WHERE name = ?");
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'يوجد منتج بهذا الاسم مسبقاً']);
        exit;
    }

    // إضافة المنتج الجديد
    $stmt = $conn->prepare("INSERT INTO products (name, default_size, default_material, default_details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $default_size, $default_material, $default_details);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'تم إضافة المنتج بنجاح',
            'product' => [
                'product_id' => $product_id,
                'name' => $name,
                'default_size' => $default_size,
                'default_material' => $default_material
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في إضافة المنتج']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>
