<?php
include 'auth_check.php';
include 'db_connection.php';

header('Content-Type: application/json');

// التحقق من صلاحية إضافة عميل
if (!has_permission('client_add')) {
    echo json_encode(['success' => false, 'message' => 'ليس لديك الصلاحية لإضافة عميل.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$company_name = $data['company_name'] ?? '';
$contact_person = $data['contact_person'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';

if (empty($company_name)) {
    echo json_encode(['success' => false, 'message' => 'اسم المؤسسة حقل إجباري.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);

if ($stmt->execute()) {
    $new_client_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'تمت إضافة العميل بنجاح!',
        'client' => [
            'client_id' => $new_client_id,
            'company_name' => htmlspecialchars($company_name)
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.']);
}