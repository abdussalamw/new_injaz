<?php
include 'db_connection_secure.php';
include 'auth_check.php';
include_once 'push_notification_helper.php'; // The new helper file

/**
 * Sends notifications to a list of employee IDs, avoiding the current user.
 * @param mysqli $conn The database connection.
 * @param array $employee_ids Array of employee IDs to notify.
 * @param string $message The notification message.
 * @param string $link The link for the notification.
 */
function send_notifications(mysqli $conn, array $employee_ids, string $message, string $link) {
    if (empty($employee_ids)) {
        return;
    }
    // Ensure unique IDs
    $unique_ids = array_unique($employee_ids);

    $stmt_notify = $conn->prepare("INSERT INTO notifications (employee_id, message, link) VALUES (?, ?, ?)");
    foreach ($unique_ids as $id) {
        // Don't notify the person who triggered the action
        if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
            continue;
        }
        $stmt_notify->bind_param("iss", $id, $message, $link);
        $stmt_notify->execute();
    }
}

/**
 * Sends Web Push Notifications to a list of employee IDs.
 * @param mysqli $conn The database connection.
 * @param array $employee_ids Array of employee IDs to notify.
 * @param string $title The notification title.
 * @param string $body The notification body.
 * @param string $url The URL to open on click.
 */
function send_push_notifications(mysqli $conn, array $employee_ids, string $title, string $body, string $url) {
    if (empty($employee_ids) || !file_exists(__DIR__ . '/vendor/autoload.php')) {
        return; // Don't proceed if no one to notify or library not installed
    }
    $unique_ids = array_unique($employee_ids);
    $ids_placeholder = implode(',', array_fill(0, count($unique_ids), '?'));
    $types = str_repeat('i', count($unique_ids));

    $stmt = $conn->prepare("SELECT * FROM push_subscriptions WHERE employee_id IN ($ids_placeholder)");
    $stmt->bind_param($types, ...$unique_ids);
    $stmt->execute();
    $subscriptions = $stmt->get_result();

    $payload = ['title' => $title, 'body' => $body, 'url' => $url];
    while ($sub = $subscriptions->fetch_assoc()) {
        send_push_notification($sub, $payload);
    }
}

/**
 * يتحقق مما إذا كان يجب إغلاق الطلب تلقائيًا ويرسل إشعارات للمدراء.
 * @param int $order_id
 * @param mysqli $conn
 * @return string رسالة إضافية للنجاح ليتم عرضها للمستخدم.
 */
function checkAndCloseOrderAndNotify($order_id, $conn) {
    $stmt_check = $conn->prepare("SELECT delivered_at, payment_settled_at, status FROM orders WHERE order_id = ?");
    $stmt_check->bind_param("i", $order_id);
    $stmt_check->execute();
    $order = $stmt_check->get_result()->fetch_assoc();

    // التحقق من اكتمال الشرطين وأن الطلب ليس "مكتمل" بالفعل
    if ($order && !empty($order['delivered_at']) && !empty($order['payment_settled_at']) && $order['status'] !== 'مكتمل') {
        $stmt_close = $conn->prepare("UPDATE orders SET status = 'مكتمل' WHERE order_id = ?");
        $stmt_close->bind_param("i", $order_id);
        $stmt_close->execute();

        if ($stmt_close->affected_rows > 0) {
            $notification_message = "تم إغلاق الطلب #{$order_id} تلقائياً لاكتماله.";
            $notification_link = "edit_order.php?id={$order_id}";

            // إرسال إشعار لكل مدير
            $managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
            $stmt_notify = $conn->prepare("INSERT INTO notifications (employee_id, message, link) VALUES (?, ?, ?)");
            while ($manager = $managers_res->fetch_assoc()) {
                $stmt_notify->bind_param("iss", $manager['employee_id'], $notification_message, $notification_link);
                $stmt_notify->execute();
            }
            return " وتم إغلاق الطلب تلقائياً وإرسال إشعار للمدراء.";
        }
    }
    return ""; // لا يوجد رسالة إضافية
}

header('Content-Type: application/json');

// --- جلب أرقام معرفات المدراء لاستخدامها في الإشعارات ---
$managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
$manager_ids = [];
while ($manager = $managers_res->fetch_assoc()) {
    $manager_ids[] = $manager['employee_id'];
}

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
    $additional_message = '';

    switch ($action) {
        case 'change_status':
            $current_status = $order['status'];
            $new_status = $value;
            $sql_update = "UPDATE orders SET status = ?";
            $types_update = "s";
            $params_update = [$new_status];

            // تسجيل وقت انتهاء المرحلة عند الانتقال للمرحلة التالية
            if ($current_status === 'قيد التصميم' && $new_status === 'قيد التنفيذ') {
                $sql_update .= ", design_completed_at = NOW()";
            } elseif ($current_status === 'قيد التنفيذ' && $new_status === 'جاهز للتسليم') {
                $sql_update .= ", execution_completed_at = NOW()";
            }

            $sql_update .= " WHERE order_id = ?";
            $types_update .= "i";
            $params_update[] = $order_id;

            $update_stmt = $conn->prepare($sql_update);
            $update_stmt->bind_param($types_update, ...$params_update);
            $update_stmt->execute();
            $message = "تم تغيير حالة الطلب إلى '$new_status'.";

            // --- إرسال الإشعارات ---
            $notification_link = "edit_order.php?id={$order_id}";
            $notify_ids = $manager_ids; // المديرون يتم إعلامهم دائماً
            
            // جلب اسم المستخدم الذي قام بالإجراء
            $user_name = $_SESSION['user_name'] ?? 'مستخدم غير معروف';

            if ($current_status === 'قيد التصميم' && $new_status === 'قيد التنفيذ') {
                $notification_message = "🎨 المصمم {$user_name} أرسل الطلب #{$order_id} إلى مرحلة التنفيذ";
                $lab_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'معمل'");
                while($lab_user = $lab_res->fetch_assoc()) { $notify_ids[] = $lab_user['employee_id']; }
                send_notifications($conn, $notify_ids, $notification_message, $notification_link);
                send_push_notifications($conn, $notify_ids, '🎨 مهمة جديدة للتنفيذ', $notification_message, $notification_link);
            } elseif ($current_status === 'قيد التنفيذ' && $new_status === 'جاهز للتسليم') {
                $notification_message = "✅ {$user_name} أنهى تنفيذ الطلب #{$order_id} وأصبح جاهزاً للتسليم";
                $notify_ids[] = $order['created_by']; // إعلام منشئ الطلب
                send_notifications($conn, $notify_ids, $notification_message, $notification_link);
                send_push_notifications($conn, $notify_ids, '✅ طلب جاهز للتسليم', $notification_message, $notification_link);
            }
            break;

        case 'confirm_delivery':
            $is_creator = ($order['created_by'] == $user_id);
            if (!in_array($user_role, ['مدير', 'معمل']) && !$is_creator) {
                 throw new Exception('غير مصرح لك بتأكيد التسليم.');
            }
            $update_stmt = $conn->prepare("UPDATE orders SET delivered_at = NOW() WHERE order_id = ? AND delivered_at IS NULL");
            $update_stmt->bind_param("i", $order_id);
            $update_stmt->execute();
            $message = 'تم تأكيد استلام الطلب من قبل العميل بنجاح.';
            $additional_message = checkAndCloseOrderAndNotify($order_id, $conn);

            // --- إرسال الإشعارات ---
            $user_name = $_SESSION['user_name'] ?? 'مستخدم غير معروف';
            $notification_link = "edit_order.php?id={$order_id}";
            $notification_message = "📦 {$user_name} أكد استلام العميل للطلب #{$order_id}";
            $notify_ids = $manager_ids;
            $accountant_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'محاسب'");
            while($acc_user = $accountant_res->fetch_assoc()) { $notify_ids[] = $acc_user['employee_id']; }
            send_notifications($conn, $notify_ids, $notification_message, $notification_link);
            send_push_notifications($conn, $notify_ids, '📦 تم تسليم طلب', $notification_message, $notification_link);
            break;

        case 'update_payment':
            // للمحاسب: فتح نافذة تحديث الدفع (يتم التعامل معها في JavaScript)
            if ($user_role !== 'محاسب' || !has_permission('order_financial_settle', $conn)) {
                throw new Exception('غير مصرح لك بتحديث حالة الدفع.');
            }
            // هذا الإجراء يتم التعامل معه في الواجهة الأمامية
            $message = 'فتح نافذة تحديث الدفع';
            break;

        case 'confirm_payment':
            // للمدير: تأكيد الدفع الكامل مباشرة
            if ($user_role !== 'مدير' || !has_permission('order_financial_settle', $conn)) {
                 throw new Exception('غير مصرح لك بتأكيد الدفع.');
            }
            
            // تحديث حالة الدفع إلى مدفوع بالكامل
            $update_stmt = $conn->prepare("UPDATE orders SET 
                                         deposit_amount = total_amount, 
                                         remaining_amount = 0, 
                                         payment_status = 'مدفوع',
                                         payment_settled_at = NOW() 
                                         WHERE order_id = ? AND payment_settled_at IS NULL");
            $update_stmt->bind_param("i", $order_id);
            $update_stmt->execute();
            $message = 'تم تأكيد الدفع الكامل للطلب بنجاح.';
            $additional_message = checkAndCloseOrderAndNotify($order_id, $conn);

            // --- إرسال الإشعارات ---
            $notification_link = "edit_order.php?id={$order_id}";
            $notification_message = "تم تأكيد التسوية المالية للطلب #{$order_id}.";
            // فقط المديرون يتم إعلامهم بهذا الإجراء
            send_notifications($conn, $manager_ids, $notification_message, $notification_link);
            send_push_notifications($conn, $manager_ids, 'تسوية مالية', $notification_message, $notification_link);
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

    // التحقق من أن التحديث تم بنجاح
    if ($update_stmt->affected_rows === 0) {
        // إذا كان الإجراء هو تغيير الحالة، فمن المحتمل أن الحالة المطلوبة هي نفسها الحالية.
        // هذا ليس خطأ، لذا نسمح له بالمرور برسالة توضيحية.
        if ($action === 'change_status' && $order['status'] === $value) {
            $message = "الحالة لم تتغير لأنها بالفعل '$value'.";
        } else {
            // لأي إجراء آخر أو فشل حقيقي، أظهر خطأ
            throw new Exception('فشل تحديث قاعدة البيانات. لم تتأثر أي سجلات، قد يكون الإجراء قد تم تنفيذه مسبقاً أو هناك مشكلة في الصلاحيات.');
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message . $additional_message]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
