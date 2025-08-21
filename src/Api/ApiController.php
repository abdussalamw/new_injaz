<?php
declare(strict_types=1);

namespace App\Api;

use App\Core\Permissions;
use App\Core\OrderUpdater; // Use the centralized updater

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
        $order_id = intval($input['order_id'] ?? 0);
        $new_status = $input['value'] ?? null;

        if (!$order_id || !$new_status) {
            $this->send_json_response(false, 'بيانات الطلب غير كافية.');
        }

        if (!Permissions::has_permission('order_edit', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتغيير حالة الطلب.');
        }

        try {
            // Use the centralized updater. This handles timers and other related logic.
            $result = OrderUpdater::updateStatus($this->conn, $order_id, $new_status);
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            // TODO: Notification logic should also be centralized inside the OrderUpdater if needed.

            $this->send_json_response(true, $result['message']);
        } catch (\Exception $e) {
            $this->send_json_response(false, $e->getMessage());
        }
    }

    public function updatePayment(): void
    {
        // استقبال البيانات دائماً عبر JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = intval($input['order_id'] ?? 0);
        $payment_amount = floatval($input['payment_amount'] ?? 0);
        $payment_method = trim($input['payment_method'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if (!$order_id) {
            $this->send_json_response(false, 'بيانات الدفعة غير كاملة.');
        }
        // يسمح بالدفع حتى لو كان المبلغ صفر أو طريقة الدفع فارغة (للتسوية اليدوية)
        if (!Permissions::has_permission('payment_edit', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتحديث الدفعات.');
        }

        try {
            $result = OrderUpdater::addPayment(
                $this->conn,
                $order_id,
                $payment_amount,
                $payment_method,
                $notes,
                $_SESSION['user_id'],
                $_SESSION['user_name']
            );

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $this->send_json_response(true, $result['message'], $result['data']);
        } catch (\Exception $e) {
            $this->send_json_response(false, $e->getMessage());
        }
    }

    public function getOrderDetails(): void
    {
        $order_id = $_GET['id'] ?? null;
        if (!$order_id) {
            $this->send_json_response(false, 'لم يتم تحديد رقم الطلب.');
        }

        if (!Permissions::has_permission('order_view_all', $this->conn) && !Permissions::has_permission('order_view_own', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لعرض تفاصيل الطلب.');
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
        }

        if (!Permissions::has_permission('order_edit_status', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتأكيد التسليم.');
        }

        try {
            // The updater now handles setting the status and delivered_at timestamp.
            $result = OrderUpdater::updateStatus($this->conn, $order_id, 'مكتمل');
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // You can add extra messages if needed, but the core logic is done.
            $this->send_json_response(true, 'تم تأكيد استلام العميل بنجاح.');

        } catch (\Exception $e) {
            $this->send_json_response(false, $e->getMessage());
        }
    }

    public function confirmPayment(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = intval($input['order_id'] ?? 0);

        if ($order_id <= 0) {
            $this->send_json_response(false, 'معرف الطلب غير صحيح');
        }

        if (!Permissions::has_permission('order_financial_settle', $this->conn)) {
            $this->send_json_response(false, 'ليس لديك الصلاحية لتسوية الطلبات مالياً.');
        }

        try {
            // Use the centralized payment settlement method.
            $result = OrderUpdater::settlePayment($this->conn, $order_id);
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $this->send_json_response(true, $result['message']);

        } catch (\Exception $e) {
            $this->send_json_response(false, $e->getMessage());
        }
    }
}