<?php
declare(strict_types=1);

namespace App\Api;

use App\Core\Permissions;
use App\Core\Helpers;

class ApiController
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    private function send_json_response(bool $success, string $message, ?array $data = null): void
    {
        header('Content-Type: application/json');
        $response = ['success' => $success, 'message' => $message];
        if ($data) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }

    public function changeOrderStatus(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = $input['order_id'] ?? null;
        $new_status = $input['value'] ?? null;

        if (!$order_id || !$new_status) {
            $this->send_json_response(false, 'بيانات الطلب غير كافية.');
            return;
        }

        // Here you would add complex permission checks based on the new status
        // For now, we'll just check for a general edit permission
        if (!Permissions::has_permission('order_edit', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتغيير حالة الطلب.');
            return;
        }

        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);

        if ($stmt->execute()) {
            // Helpers::log_activity('status_change', $_SESSION['user_id'], $order_id, "Status changed to {$new_status}");
            
            // --- إرسال إشعار ---
            $user_name = $_SESSION['user_name'] ?? 'النظام';
            $notification_message = "قام {$user_name} بتغيير حالة الطلب #{$order_id} إلى '{$new_status}'";
            $notification_link = "/new_injaz/dashboard"; // رابط موحد للوحة التحكم

            // جلب معرفات المدراء
            $managers_res = $this->conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
            $managers = $managers_res->fetch_all(MYSQLI_ASSOC);

            if (!empty($managers)) {
                $stmt_notify = $this->conn->prepare("INSERT INTO notifications (employee_id, message, link) VALUES (?, ?, ?)");
                foreach ($managers as $manager) {
                    // لا نرسل إشعار للشخص الذي قام بالإجراء
                    if ($manager['employee_id'] != $_SESSION['user_id']) {
                        $stmt_notify->bind_param("iss", $manager['employee_id'], $notification_message, $notification_link);
                        $stmt_notify->execute();
                    }
                }
                $stmt_notify->close();
            }
            // --- نهاية إرسال الإشعار ---

            $this->send_json_response(true, 'تم تحديث حالة الطلب بنجاح.');
        } else {
            $this->send_json_response(false, 'فشل تحديث حالة الطلب.');
        }
    }

    public function updatePayment(): void
    {
        $order_id = $_POST['order_id'] ?? null;
        $payment_amount = $_POST['payment_amount'] ?? null;
        $payment_method = $_POST['payment_method'] ?? null;
        $notes = $_POST['notes'] ?? '';

        if (!$order_id || !$payment_amount || !$payment_method) {
            $this->send_json_response(false, 'بيانات الدفعة غير كاملة.');
            return;
        }
        
        if (!Permissions::has_permission('payment_edit', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتحديث الدفعات.');
            return;
        }

        // We need to get the current deposit amount and add the new payment to it
        $stmt = $this->conn->prepare("SELECT deposit_amount, total_amount FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            $this->send_json_response(false, 'لم يتم العثور على الطلب.');
            return;
        }

        $new_deposit = (float)$order['deposit_amount'] + (float)$payment_amount;
        $total_amount = (float)$order['total_amount'];
        
        $payment_status = 'مدفوع جزئياً';
        if ($new_deposit >= $total_amount) {
            $payment_status = 'مدفوع';
        }

        $update_stmt = $this->conn->prepare("UPDATE orders SET deposit_amount = ?, payment_status = ? WHERE order_id = ?");
        $update_stmt->bind_param("dsi", $new_deposit, $payment_status, $order_id);

        if ($update_stmt->execute()) {
            // Optionally, log the payment
            // Helpers::log_activity('payment_update', $_SESSION['user_id'], $order_id, "Payment of {$payment_amount} received.");
            $this->send_json_response(true, 'تم تسجيل الدفعة بنجاح.');
        } else {
            $this->send_json_response(false, 'فشل في تسجيل الدفعة.');
        }
    }

    public function getOrderDetails(): void
    {
        $order_id = $_GET['id'] ?? null;
        if (!$order_id) {
            $this->send_json_response(false, 'لم يتم تحديد رقم الطلب.');
            return;
        }

        if (!Permissions::has_permission('order_view_all', $this->conn) && !Permissions::has_permission('order_view_own', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لعرض تفاصيل الطلب.');
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if ($order) {
            $this->send_json_response(true, 'تم العثور على الطلب.', $order);
        } else {
            $this->send_json_response(false, 'لم يتم العثور على الطلب.');
        }
    }

    public function confirmDelivery(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = intval($input['order_id'] ?? 0);

        if ($order_id <= 0) {
            $this->send_json_response(false, 'معرف الطلب غير صحيح');
            return;
        }

        if (!Permissions::has_permission('order_edit_status', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتأكيد التسليم.');
            return;
        }

        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT payment_status FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new \Exception('الطلب غير موجود');
            }

            $stmt = $this->conn->prepare("UPDATE orders SET delivered_at = NOW(), status = 'مكتمل', updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            $is_paid = ($order['payment_status'] === 'مدفوع');
            if ($is_paid) {
                $message = 'تم تأكيد الاستلام. سيتم إخفاء الطلب من لوحة المهام لأنه مكتمل ومدفوع.';
            } else {
                $message = 'تم تأكيد استلام العميل. سيبقى الطلب ظاهراً في المهام لحين تسوية الدفع.';
            }

            $this->conn->commit();
            $this->send_json_response(true, $message);
        } catch (\Exception $e) {
            $this->conn->rollback();
            $this->send_json_response(false, $e->getMessage());
        }
    }

    public function confirmPayment(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = intval($input['order_id'] ?? 0);

        if ($order_id <= 0) {
            $this->send_json_response(false, 'معرف الطلب غير صحيح');
            return;
        }

        if (!Permissions::has_permission('order_financial_settle', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتسوية الطلبات مالياً.');
            return;
        }

        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT total_amount, status FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new \Exception('الطلب غير موجود');
            }

            $total_amount = $order['total_amount'];
            // السماح بتأكيد الدفع حتى لو كان المبلغ غير محدد أو صفر
            if (is_null($total_amount) || !is_numeric($total_amount)) {
                $total_amount = 0;
            }

            $stmt = $this->conn->prepare("
                UPDATE orders SET 
                    deposit_amount = total_amount, 
                    remaining_amount = 0,
                    payment_status = 'مدفوع', 
                    payment_settled_at = NOW(), 
                    updated_at = NOW() 
                WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            if ($order['status'] === 'مكتمل') {
                $message = 'تم تأكيد الدفع الكامل. سيتم إخفاء الطلب من لوحة المهام لأنه مكتمل ومدفوع.';
            } else {
                $message = 'تم تأكيد الدفع الكامل وتسوية المبلغ بنجاح. سيبقى الطلب ظاهراً لحين تأكيد استلام العميل.';
            }

            $this->conn->commit();
            $this->send_json_response(true, $message);
        } catch (\Exception $e) {
            $this->conn->rollback();
            $this->send_json_response(false, $e->getMessage());
        }
    }
}
