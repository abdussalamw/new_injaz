<?php
declare(strict_types=1);

session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->load();

require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
require_once __DIR__ . '/src/Core/Permissions.php';
require_once __DIR__ . '/src/Core/OrderUpdater.php'; // Include the centralized updater

// Set JSON header
header('Content-Type: application/json');

// Database connection
try {
    $db = new \App\Core\Database(
        $_ENV['DB_HOST'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        $_ENV['DB_NAME']
    );
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if user is logged in
if (!\App\Core\AuthCheck::isLoggedIn($conn)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permissions
if (!\App\Core\Permissions::has_permission('order_financial_settle', $conn)) {
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لتحديث حالة الدفع']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$payment_amount = floatval($input['payment_amount'] ?? 0);
$payment_method = trim($input['payment_method'] ?? '');
$notes = trim($input['notes'] ?? '');

$response = ['success' => false, 'message' => 'حدث خطأ غير متوقع.'];

try {
    if ($order_id <= 0) {
        throw new Exception('معرف الطلب غير صحيح');
    }
    if ($payment_amount <= 0) {
        throw new Exception('مبلغ الدفعة يجب أن يكون أكبر من صفر');
    }
    if (empty($payment_method)) {
        throw new Exception('يجب تحديد طريقة الدفع');
    }

    // Call the centralized updater method
    $result = \App\Core\OrderUpdater::addPayment(
        $conn,
        $order_id,
        $payment_amount,
        $payment_method,
        $notes,
        $_SESSION['user_id'],
        $_SESSION['user_name']
    );

    if (!$result['success']) {
        throw new Exception($result['message']);
    }

    // Prepare successful response from the updater result
    $payment_data = $result['data'];
    $response = [
        'success' => true,
        'message' => $result['message'],
        'new_payment_status' => $payment_data['new_payment_status'],
        'new_deposit' => $payment_data['new_deposit'],
        'remaining_amount' => $payment_data['remaining_amount'],
        'is_fully_paid' => $payment_data['is_fully_paid']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);