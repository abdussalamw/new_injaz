<?php
include 'db_connection.php';
include 'auth_check.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id'] ?? 0);
$action = $data['action'] ?? '';
$value = $data['value'] ?? null; // يُستخدم لتمرير قيم إضافية مثل الحالة الجديدة

if (!$order_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// جلب تفاصيل الطلب للتحقق من الصلاحيات والحالة الحالية
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'الطلب غير موجود.']);
    exit;
}

// --- معالج الإجراءات المركزي ---
try {
    $conn->begin_transaction();
    $message = '';

    switch ($action) {
        case 'change_status':
            // TODO: يمكن إضافة منطق تحقق أكثر تفصيلاً هنا إذا لزم الأمر
            $new_status = $value;
            $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $update_stmt->bind_param("si", $new_status, $order_id);
            $update_stmt->execute();
            $message = "تم تغيير حالة الطلب إلى '$new_status'.";
            break;

        case 'confirm_delivery':
            if (!in_array($user_role, ['مدير', 'معمل'])) {
                 throw new Exception('غير مصرح لك بتأكيد التسليم.');
            }
            $update_stmt = $conn->prepare("UPDATE orders SET delivered_at = NOW() WHERE order_id = ? AND delivered_at IS NULL");
            $update_stmt->bind_param("i", $order_id);
            $update_stmt->execute();
            $message = 'تم تأكيد استلام الطلب من قبل العميل بنجاح.';
            break;

        case 'confirm_payment':
            if (!in_array($user_role, ['مدير', 'محاسب'])) {
                 throw new Exception('غير مصرح لك بتأكيد الدفع.');
            }
            $update_stmt = $conn->prepare("UPDATE orders SET payment_settled_at = NOW() WHERE order_id = ? AND payment_settled_at IS NULL");
            $update_stmt->bind_param("i", $order_id);
            $update_stmt->execute();
            $message = 'تم تأكيد الدفع الكامل للطلب بنجاح.';
            break;
        
        case 'close_order':
            if ($user_role !== 'مدير') {
                throw new Exception('فقط المدير يمكنه إغلاق الطلب.');
            }
            if (empty($order['delivered_at']) || empty($order['payment_settled_at'])) {
                throw new Exception('لا يمكن إغلاق الطلب قبل تسليمه وتسوية مدفوعاته.');
            }
            $update_stmt = $conn->prepare("UPDATE orders SET status = 'مكتمل' WHERE order_id = ?");
            $update_stmt->bind_param("i", $order_id);
            $update_stmt->execute();
            $message = 'تم إغلاق الطلب بنجاح.';
            break;

        default:
            throw new Exception('إجراء غير معروف.');
    }

    if ($update_stmt->affected_rows === 0 && !in_array($action, ['change_status', 'close_order'])) {
        // هذا يمنع تأكيد إجراء تم تأكيده مسبقاً
        throw new Exception('هذا الإجراء تم تنفيذه بالفعل.');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;