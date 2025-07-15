<?php
include 'db_connection.php';
include 'auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$employee_id = $_SESSION['user_id'];
$endpoint = $data['endpoint'] ?? null;
$p256dh = $data['keys']['p256dh'] ?? null;
$auth = $data['keys']['auth'] ?? null;

if (!$endpoint || !$p256dh || !$auth) {
    echo json_encode(['success' => false, 'message' => 'Invalid subscription data.']);
    exit;
}

// حفظ أو تحديث الاشتراك في قاعدة البيانات
$stmt = $conn->prepare("INSERT INTO push_subscriptions (employee_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)");
$stmt->bind_param("isss", $employee_id, $endpoint, $p256dh, $auth);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save subscription.']);
}
?>