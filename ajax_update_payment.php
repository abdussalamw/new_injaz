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

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get POST data (assuming JSON input for consistency)
$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$payment_amount = floatval($input['payment_amount'] ?? 0);
$payment_method = trim($input['payment_method'] ?? '');
$notes = trim($input['notes'] ?? '');

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صحيح']);
    exit;
}

if ($payment_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'مبلغ الدفعة يجب أن يكون أكبر من صفر']);
    exit;
}

if (empty($payment_method)) {
    echo json_encode(['success' => false, 'message' => 'يجب تحديد طريقة الدفع']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Fetch current order data (and lock the row for update)
    $order_query = "SELECT total_amount, deposit_amount, notes FROM orders WHERE order_id = ? FOR UPDATE";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception('الطلب غير موجود');
    }

    // **Fundamental Check: Prevent any financial action on an order without a value**
    if (is_null($order['total_amount']) || !is_numeric($order['total_amount']) || floatval($order['total_amount']) <= 0) {
        throw new Exception('لا يمكن إضافة دفعة. المبلغ الإجمالي للطلب غير محدد أو يساوي صفر. يرجى تحديث بيانات الطلب أولاً.');
    }

    $total_amount = floatval($order['total_amount']);
    $current_deposit = floatval($order['deposit_amount']);
    $new_deposit = $current_deposit + $payment_amount;

    // Check for overpayment
    if ($new_deposit > $total_amount) {
        throw new Exception('مبلغ الدفعة يتجاوز المبلغ المتبقي. المتبقي: ' . number_format($total_amount - $current_deposit, 2) . ' ر.س');
    }

    // Determine new payment status
    $new_payment_status = '';
    $payment_settled_at = null;

    if (abs($new_deposit - $total_amount) < 0.01) { // Use a small tolerance for float comparison
        $new_payment_status = 'مدفوع';
        $payment_settled_at = date('Y-m-d H:i:s');
        $new_deposit = $total_amount; // Correct any floating point inaccuracies
    } elseif ($new_deposit > 0) {
        $new_payment_status = 'مدفوع جزئياً';
    } else {
        $new_payment_status = 'غير مدفوع';
    }

    // Prepare notes update
    $notes_text = "دفعة جديدة: " . number_format($payment_amount, 2) . " ر.س عبر " . $payment_method;
    if (!empty($notes)) {
        $notes_text .= " - " . $notes;
    }

    // Efficiently update order data in a single query
    $update_query = "UPDATE orders SET
                     deposit_amount = ?,
                     remaining_amount = ?,
                     payment_status = ?,
                     payment_method = ?,
                     payment_settled_at = ?,
                     notes = CONCAT_WS('\\n', notes, ?),
                     updated_at = NOW()
                     WHERE order_id = ?";

    $remaining_amount = $total_amount - $new_deposit;
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ddssssi", $new_deposit, $remaining_amount, $new_payment_status, $payment_method, $payment_settled_at, $notes_text, $order_id);

    if (!$stmt->execute()) {
        throw new Exception('فشل في تحديث بيانات الطلب');
    }

    // Send notification to managers
    $user_name = $_SESSION['user_name'] ?? 'مستخدم';
    $notification_message = "💰 المحاسب {$user_name} حدث حالة الدفع للطلب #{$order_id} - الحالة الجديدة: {$new_payment_status}";
    $notification_link = "/new_injaz/dashboard";

    $managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
    $stmt_notify = $conn->prepare("INSERT INTO notifications (employee_id, message, link) VALUES (?, ?, ?)");
    while ($manager = $managers_res->fetch_assoc()) {
        // لا نرسل إشعار للشخص الذي قام بالإجراء
        if ($manager['employee_id'] != $_SESSION['user_id']) {
            $stmt_notify->bind_param("iss", $manager['employee_id'], $notification_message, $notification_link);
            $stmt_notify->execute();
        }
    }
    $stmt_notify->close();

    // Commit the transaction
    $conn->commit();

    // Send response
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث حالة الدفع بنجاح',
        'new_payment_status' => $new_payment_status,
        'new_deposit' => $new_deposit,
        'remaining_amount' => $remaining_amount,
        'is_fully_paid' => ($new_payment_status === 'مدفوع')
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
