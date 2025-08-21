<?php
declare(strict_types=1);

// Start session first
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/vendor/autoload.php'; // Load Composer's autoloader
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->load();

require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
require_once __DIR__ . '/src/Core/OrderUpdater.php'; // Include the new updater class
require_once __DIR__ . '/src/Core/RoleHelper.php';
require_once __DIR__ . '/src/Core/Permissions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Not an AJAX request']);
    exit;
}

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

// Get POST data
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

$order_id = intval($input['order_id'] ?? 0);
$action = $input['action'] ?? '';

$response = ['success' => false, 'message' => 'حدث خطأ غير متوقع.'];

try {
    if (empty($action)) {
        throw new Exception('نوع العملية غير محدد.');
    }
    if ($order_id <= 0) {
        throw new Exception('معرف الطلب غير صحيح.');
    }

    // Get order details for context
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception('الطلب غير موجود');
    }

    $message = '';

    switch ($action) {
        case 'change_status':
            if (!\App\Core\Permissions::has_permission('order_edit_status', $conn)) {
                throw new Exception('ليس لديك الصلاحية لتغيير حالة الطلب.');
            }
            $new_status = $input['value'] ?? '';
            if (empty($new_status)) {
                throw new Exception('الحالة الجديدة غير محددة');
            }
            $update_result = \App\Core\OrderUpdater::updateStatus($conn, $order_id, $new_status);
            if (!$update_result['success']) {
                throw new Exception($update_result['message']);
            }
            $message = $update_result['message'];
            break;
            
        case 'confirm_delivery':
            if (!\App\Core\RoleHelper::isManager() && !\App\Core\Permissions::has_permission('order_edit_status', $conn)) {
                throw new Exception('ليس لديك الصلاحية لتأكيد التسليم.');
            }
            
            // Use the centralized updater. It now handles the delivered_at timestamp.
            $update_result = \App\Core\OrderUpdater::updateStatus($conn, $order_id, 'مكتمل');
            if (!$update_result['success']) {
                throw new Exception($update_result['message']);
            }

            $is_paid = ($order['payment_status'] === 'مدفوع');
            if ($is_paid) {
                $message = 'تم تأكيد الاستلام. سيتم إخفاء الطلب من لوحة المهام لأنه مكتمل ومدفوع.';
            } else {
                $message = 'تم تأكيد استلام العميل. سيبقى الطلب ظاهراً في المهام لحين تسوية الدفع.';
            }
            break;
            
        case 'confirm_payment':
            if (!\App\Core\RoleHelper::isManager() && !\App\Core\Permissions::has_permission('order_financial_settle', $conn)) {
                throw new Exception('ليس لديك الصلاحية لتسوية الطلبات مالياً.');
            }

            // Use the centralized payment settlement method
            $update_result = \App\Core\OrderUpdater::settlePayment($conn, $order_id);
            if (!$update_result['success']) {
                throw new Exception($update_result['message']);
            }
            $message = $update_result['message'];
            break;
            
        case 'close_order':
            if (!\App\Core\Permissions::has_permission('order_edit_status', $conn)) {
                throw new Exception('ليس لديك الصلاحية لإغلاق الطلب.');
            }

            $update_result = \App\Core\OrderUpdater::updateStatus($conn, $order_id, 'مكتمل');
            if (!$update_result['success']) {
                throw new Exception($update_result['message']);
            }
            $message = 'تم إغلاق الطلب نهائياً';
            break;
            
        default:
            throw new Exception('إجراء غير معروف');
    }
    
    $response['success'] = true;
    $response['message'] = $message;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>
