<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/Permissions.php';
require_once __DIR__ . '/vendor/autoload.php';

// بدء الجلسة والتحقق من الاتصال
session_start();
$database = new \App\Core\Database();
$conn = $database->getConnection();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

// التحقق من الصلاحيات
if (!\App\Core\Permissions::has_permission('order_financial_settle', $conn)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لتحديث حالة الدفع']);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

// التحقق من البيانات المطلوبة
$order_id = intval($_POST['order_id'] ?? 0);
$payment_amount = floatval($_POST['payment_amount'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$notes = trim($_POST['notes'] ?? '');

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
    // بدء المعاملة
    $conn->begin_transaction();

    // جلب بيانات الطلب الحالية
    $order_query = "SELECT total_amount, deposit_amount, payment_status FROM orders WHERE order_id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception('الطلب غير موجود');
    }

    $total_amount = floatval($order['total_amount']);
    $current_deposit = floatval($order['deposit_amount']);
    $new_deposit = $current_deposit + $payment_amount;

    // التحقق من عدم تجاوز المبلغ الإجمالي
    if ($new_deposit > $total_amount) {
        throw new Exception('مبلغ الدفعة يتجاوز المبلغ المتبقي. المتبقي: ' . number_format($total_amount - $current_deposit, 2) . ' ر.س');
    }

    // تحديد حالة الدفع الجديدة
    $new_payment_status = '';
    $payment_settled_at = null;

    if ($new_deposit >= $total_amount) {
        $new_payment_status = 'مدفوع';
        $payment_settled_at = date('Y-m-d H:i:s');
    } elseif ($new_deposit > 0) {
        $new_payment_status = 'مدفوع جزئياً';
    } else {
        $new_payment_status = 'غير مدفوع';
    }

    // تحديث بيانات الطلب
    $update_query = "UPDATE orders SET
                     deposit_amount = ?,
                     remaining_amount = ?,
                     payment_status = ?,
                     payment_method = ?,
                     payment_settled_at = ?,
                     last_update = NOW()
                     WHERE order_id = ?";

    $remaining_amount = $total_amount - $new_deposit;
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ddsssi", $new_deposit, $remaining_amount, $new_payment_status, $payment_method, $payment_settled_at, $order_id);

    if (!$stmt->execute()) {
        throw new Exception('فشل في تحديث بيانات الطلب');
    }

    // إضافة ملاحظة إذا تم توفيرها
    if (!empty($notes)) {
        $notes_text = "دفعة جديدة: " . number_format($payment_amount, 2) . " ر.س عبر " . $payment_method;
        if (!empty($notes)) {
            $notes_text .= " - " . $notes;
        }

        $current_notes_query = "SELECT notes FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($current_notes_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $current_notes = $stmt->get_result()->fetch_assoc()['notes'] ?? '';

        $updated_notes = empty($current_notes) ? $notes_text : $current_notes . "\n" . $notes_text;

        $update_notes_query = "UPDATE orders SET notes = ? WHERE order_id = ?";
        $stmt = $conn->prepare($update_notes_query);
        $stmt->bind_param("si", $updated_notes, $order_id);
        $stmt->execute();
    }

    // إرسال إشعار للمدراء
    $user_name = $_SESSION['user_name'] ?? 'مستخدم غير معروف';
    $notification_message = "💰 المحاسب {$user_name} حدث حالة الدفع للطلب #{$order_id} - الحالة الجديدة: {$new_payment_status}";
    $notification_link = "edit_order.php?id={$order_id}";

    $managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
    $stmt_notify = $conn->prepare("INSERT INTO notifications (employee_id, message, link) VALUES (?, ?, ?)");
    while ($manager = $managers_res->fetch_assoc()) {
        // لا نرسل إشعار للشخص الذي قام بالإجراء
        if ($manager['employee_id'] != $_SESSION['user_id']) {
            $stmt_notify->bind_param("iss", $manager['employee_id'], $notification_message, $notification_link);
            $stmt_notify->execute();
        }
    }

    // تأكيد المعاملة
    $conn->commit();

    // إرسال الاستجابة
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث حالة الدفع بنجاح',
        'new_payment_status' => $new_payment_status,
        'new_deposit' => $new_deposit,
        'remaining_amount' => $remaining_amount,
        'is_fully_paid' => ($new_payment_status === 'مدفوع')
    ]);

} catch (Exception $e) {
    // التراجع عن المعاملة في حالة الخطأ
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
