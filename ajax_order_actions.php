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

// تحسين التحقق من صحة البيانات
if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'نوع العملية غير محدد.']);
    exit;
}

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صحيح.']);
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

$user_id = \App\Core\RoleHelper::getCurrentUserId();
$user_role = \App\Core\RoleHelper::getCurrentUserRole();

$response = ['success' => false, 'message' => 'حدث خطأ غير متوقع.'];

try {
    // Start a transaction to ensure all database operations are atomic
    $conn->begin_transaction();

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
                    // البحث عن أفضل موظف معمل متاح (أولوية للمعامل، ثانياً المديرين)
                    $workshop_stmt = $conn->prepare("
                        SELECT employee_id, name, role 
                        FROM employees 
                        WHERE role IN ('معمل', 'مدير') 
                        ORDER BY CASE WHEN role = 'معمل' THEN 1 ELSE 2 END, employee_id 
                        LIMIT 1
                    ");
                    $workshop_stmt->execute();
                    $workshop_result = $workshop_stmt->get_result();
                    
                    if ($workshop_row = $workshop_result->fetch_assoc()) {
                        $workshop_id = $workshop_row['employee_id'];
                        $workshop_name = $workshop_row['name'];
                        $assign_stmt = $conn->prepare("UPDATE orders SET workshop_id = ? WHERE order_id = ?");
                        $assign_stmt->bind_param("ii", $workshop_id, $order_id);
                        $assign_stmt->execute();
                        
                        $message .= " وتم تعيين المعمل ($workshop_name) تلقائياً.";
                    } else {
                        $message .= " تحذير: لم يتم العثور على معمل متاح للتعيين.";
                    }
                }
            } elseif ($new_status === 'جاهز للتسليم' && empty($order['execution_completed_at'])) {
                $stmt = $conn->prepare("UPDATE orders SET execution_completed_at = NOW() WHERE order_id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
            }
            
            $message = "تم تحديث حالة الطلب إلى: $new_status";
            break;
            
        case 'confirm_delivery':
            // تبسيط فحص الصلاحيات - المدير يمكنه تأكيد التسليم دائماً
            if (!\App\Core\RoleHelper::isManager() && !\App\Core\Permissions::has_permission('order_edit_status', $conn)) {
                throw new Exception('ليس لديك الصلاحية لتأكيد التسليم.');
            }

            // تحسين رسالة التأكيد بناءً على حالة الدفع
            $is_paid = ($order['payment_status'] === 'مدفوع');

            $stmt = $conn->prepare("UPDATE orders SET delivered_at = NOW(), status = 'مكتمل', updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            if ($is_paid) {
                $message = 'تم تأكيد الاستلام. سيتم إخفاء الطلب من لوحة المهام لأنه مكتمل ومدفوع.';
            } else {
                $message = 'تم تأكيد استلام العميل. سيبقى الطلب ظاهراً في المهام لحين تسوية الدفع.';
            }
            
            break;
            
        case 'confirm_payment':
            // تبسيط فحص الصلاحيات - المدير يمكنه تأكيد الدفع دائماً
            if (!\App\Core\RoleHelper::isManager() && !\App\Core\Permissions::has_permission('order_financial_settle', $conn)) {
                throw new Exception('ليس لديك الصلاحية لتسوية الطلبات مالياً.');
            }

            // To make this action robust, we will settle all amounts automatically.
            // The $order variable is already fetched at the top of the script.
            $total_amount = $order['total_amount'];

            // Ensure the order has a total amount to settle.
            if (is_null($total_amount) || !is_numeric($total_amount) || floatval($total_amount) <= 0) {
                throw new Exception('لا يمكن تأكيد الدفع. المبلغ الإجمالي للطلب غير محدد أو يساوي صفر.');
            }

            $stmt = $conn->prepare("
                UPDATE orders SET 
                    deposit_amount = total_amount, 
                    remaining_amount = 0,
                    payment_status = 'مدفوع', 
                    payment_settled_at = NOW(), 
                    updated_at = NOW() 
                WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // تحسين رسالة التأكيد بناءً على حالة التسليم
            if ($order['status'] === 'مكتمل') {
                $message = 'تم تأكيد الدفع الكامل. سيتم إخفاء الطلب من لوحة المهام لأنه مكتمل ومدفوع.';
            } else {
                $message = 'تم تأكيد الدفع الكامل وتسوية المبلغ بنجاح. سيبقى الطلب ظاهراً لحين تأكيد استلام العميل.';
            }

            break;
            
        case 'close_order':
            if (!\App\Core\Permissions::has_permission('order_edit_status', $conn)) { // Or a more specific permission
                throw new Exception('ليس لديك الصلاحية لإغلاق الطلب.');
            }

            $stmt = $conn->prepare("UPDATE orders SET status = 'مكتمل', updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $message = 'تم إغلاق الطلب نهائياً';
            break;
            
        default:
            throw new Exception('إجراء غير معروف');
    }
    
    // If we reached here, all operations were successful. Commit the transaction.
    $conn->commit();
    $response['success'] = true;
    $response['message'] = $message;
    
} catch (Exception $e) {
    // If any operation failed, roll back the entire transaction.
    $conn->rollback();
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>
