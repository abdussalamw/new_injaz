<?php
declare(strict_types=1);

// Start session first
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/src/Core/config.php';
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/AuthCheck.php';
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
    $db = new \App\Core\Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
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
$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$action = $input['action'] ?? '';

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صحيح']);
    exit;
}

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

try {
    switch ($action) {
        case 'change_status':
            $new_status = $input['value'] ?? '';
            if (empty($new_status)) {
                throw new Exception('الحالة الجديدة غير محددة');
            }
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            
            // Update stage completion timestamps and assign workshop
            if ($new_status === 'قيد التنفيذ' && empty($order['design_completed_at'])) {
                $stmt = $conn->prepare("UPDATE orders SET design_completed_at = NOW() WHERE order_id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                // تعيين المعمل تلقائياً إذا لم يكن معيّن
                if (empty($order['workshop_id'])) {
                    // البحث عن أول موظف معمل متاح
                    $workshop_stmt = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'معمل' ORDER BY employee_id LIMIT 1");
                    $workshop_stmt->execute();
                    $workshop_result = $workshop_stmt->get_result();
                    
                    if ($workshop_row = $workshop_result->fetch_assoc()) {
                        $workshop_id = $workshop_row['employee_id'];
                        $assign_stmt = $conn->prepare("UPDATE orders SET workshop_id = ? WHERE order_id = ?");
                        $assign_stmt->bind_param("ii", $workshop_id, $order_id);
                        $assign_stmt->execute();
                    }
                }
            } elseif ($new_status === 'جاهز للتسليم' && empty($order['execution_completed_at'])) {
                $stmt = $conn->prepare("UPDATE orders SET execution_completed_at = NOW() WHERE order_id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => "تم تحديث حالة الطلب إلى: $new_status"]);
            break;
            
        case 'confirm_delivery':
            $stmt = $conn->prepare("UPDATE orders SET delivered_at = NOW(), updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'تم تأكيد استلام العميل']);
            break;
            
        case 'confirm_payment':
            $stmt = $conn->prepare("UPDATE orders SET payment_settled_at = NOW(), payment_status = 'مدفوع', updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'تم تأكيد الدفع الكامل']);
            break;
            
        case 'close_order':
            $stmt = $conn->prepare("UPDATE orders SET status = 'مكتمل', updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'تم إغلاق الطلب نهائياً']);
            break;
            
        default:
            throw new Exception('إجراء غير معروف');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
