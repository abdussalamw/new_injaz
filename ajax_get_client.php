<?php
include 'auth_check.php';
include 'db_connection.php';

header('Content-Type: application/json');

// أي مستخدم لديه صلاحية إضافة طلب يمكنه عرض تفاصيل العميل لهذا الغرض
if (!has_permission('order_add')) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك.']);
    exit;
}

$client_id = intval($_GET['id'] ?? 0);

if (empty($client_id)) {
    echo json_encode(['success' => false, 'message' => 'معرف العميل غير صحيح.']);
    exit;
}

$stmt = $conn->prepare("SELECT company_name, contact_person, phone, email FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($client = $result->fetch_assoc()) {
        // Sanitize output
        foreach ($client as $key => $value) {
            $client[$key] = htmlspecialchars($value);
        }
        echo json_encode(['success' => true, 'client' => $client]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على العميل.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاستعلام.']);
}