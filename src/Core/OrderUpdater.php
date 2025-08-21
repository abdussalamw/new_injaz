<?php
declare(strict_types=1);

namespace App\Core;

class OrderUpdater
{
    /**
     * Centralized function to create a new order.
     * @param \mysqli $conn
     * @param array $orderData
     * @return array
     */
    public static function createOrder(\mysqli $conn, array $orderData): array
    {
        try {
            $conn->begin_transaction();

            // تحقق من الحقول الأساسية
            $client_id = $orderData['client_id'] ?? null;
            $designer_id = $orderData['designer_id'] ?? null;
            $workshop_id = $orderData['workshop_id'] ?? null;
            $order_date = $orderData['order_date'] ?? date('Y-m-d');
            $status = $orderData['status'] ?? 'جديد';
            $total_amount = $orderData['total_amount'] ?? 0;
            $notes = $orderData['notes'] ?? '';

            if (!$client_id || !$designer_id) {
                throw new \Exception('يجب اختيار العميل والمصمم.');
            }

            $stmt = $conn->prepare("INSERT INTO orders (client_id, designer_id, workshop_id, order_date, status, total_amount, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("iiissds", $client_id, $designer_id, $workshop_id, $order_date, $status, $total_amount, $notes);
            if (!$stmt->execute()) {
                throw new \Exception('فشل في إنشاء الطلب: ' . $stmt->error);
            }
            $order_id = $stmt->insert_id;

            // إذا كان هناك منتجات مرتبطة بالطلب
            if (!empty($orderData['products']) && is_array($orderData['products'])) {
                foreach ($orderData['products'] as $product_id) {
                    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)");
                    $stmt_item->bind_param("ii", $order_id, $product_id);
                    $stmt_item->execute();
                }
            }

            $conn->commit();
            return [
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'order_id' => $order_id
            ];
        } catch (\Exception $e) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    /**
     * Centralized function to update order status and handle all related logic.
     *
     * @param \mysqli $conn The database connection.
     * @param int $order_id The ID of the order to update.
     * @param string $new_status The new status to set.
     * @return array An array containing the success status and a message.
     */
    public static function updateStatus(\mysqli $conn, int $order_id, string $new_status): array
    {
        $message = "";
        try {
            // Get current order details before making changes
            $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new \Exception("الطلب غير موجود.");
            }

            // Start transaction
            $conn->begin_transaction();

            // 1. Update status
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();

            // 2. Handle Timers and Timestamps (لا تمنع أي تحديث حالة لأي زر)
            if ($new_status === 'قيد التنفيذ') {
                $update_sql = "UPDATE orders SET design_completed_at = IFNULL(design_completed_at, NOW()), execution_started_at = IFNULL(execution_started_at, NOW()) WHERE order_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
            } elseif ($new_status === 'جاهز للتسليم') {
                $update_sql = "UPDATE orders SET execution_completed_at = IFNULL(execution_completed_at, NOW()) WHERE order_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
            } elseif ($new_status === 'مكتمل') {
                $update_sql = "UPDATE orders SET delivered_at = IFNULL(delivered_at, NOW()) WHERE order_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
            }

            // 3. Assign workshop if necessary
            if ($new_status === 'قيد التنفيذ' && empty($order['workshop_id'])) {
                $workshop_stmt = $conn->prepare("
                    SELECT employee_id, name FROM employees 
                    WHERE role IN ('معمل', 'مدير') 
                    ORDER BY CASE WHEN role = 'معمل' THEN 1 ELSE 2 END, employee_id LIMIT 1");
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

            // Commit transaction
            $conn->commit();

            return [
                'success' => true,
                'message' => "تم تحديث حالة الطلب إلى: $new_status" . $message
            ];

        } catch (\Exception $e) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Centralized function to settle an order's payment completely.
     *
     * @param \mysqli $conn The database connection.
     * @param int $order_id The ID of the order to update.
     * @return array An array containing the success status and a message.
     */
    public static function settlePayment(\mysqli $conn, int $order_id): array
    {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT total_amount, status FROM orders WHERE order_id = ? FOR UPDATE");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new \Exception("الطلب غير موجود.");
            }

            $total_amount = $order['total_amount'];
            // يسمح بتأكيد الدفع حتى لو كان المبلغ الإجمالي غير محدد أو يساوي صفر

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
            
            $conn->commit();

            if ($order['status'] === 'مكتمل') {
                $message = 'تم تأكيد الدفع الكامل. سيتم إخفاء الطلب من لوحة المهام لأنه مكتمل ومدفوع.';
            } else {
                $message = 'تم تأكيد الدفع الكامل وتسوية المبلغ بنجاح. سيبقى الطلب ظاهراً لحين تأكيد استلام العميل.';
            }

            return ['success' => true, 'message' => $message];

        } catch (\Exception $e) {
            $conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Centralized function to add a new payment to an order.
     */
    public static function addPayment(\mysqli $conn, int $order_id, float $payment_amount, string $payment_method, string $notes, int $user_id, string $user_name): array
    {
        try {
            $conn->begin_transaction();

            $order_query = "SELECT total_amount, deposit_amount, notes FROM orders WHERE order_id = ? FOR UPDATE";
            $stmt = $conn->prepare($order_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new \Exception('الطلب غير موجود');
            }

            // إذا لم يكن هناك مبلغ إجمالي، اعتبره صفر
            $total_amount = (is_null($order['total_amount']) || !is_numeric($order['total_amount'])) ? 0 : floatval($order['total_amount']);
            $current_deposit = floatval($order['deposit_amount']);
            $new_deposit = $current_deposit + $payment_amount;
            // لا تمنع أي دفعة حتى لو كان المبلغ الإجمالي أو الدفعة صفر

            $total_amount = floatval($order['total_amount']);
            $current_deposit = floatval($order['deposit_amount']);
            $new_deposit = $current_deposit + $payment_amount;

            // لا تمنع أي دفعة حتى لو تجاوزت المبلغ (تسوية يدوية)

            $new_payment_status = '';
            $payment_settled_at = null;

            if ($total_amount == 0 || $new_deposit >= $total_amount) {
                $new_payment_status = 'مدفوع';
                $payment_settled_at = date('Y-m-d H:i:s');
                $new_deposit = $total_amount;
            } elseif ($new_deposit > 0) {
                $new_payment_status = 'مدفوع جزئياً';
            } else {
                $new_payment_status = 'غير مدفوع';
            }

            $notes_text = "دفعة جديدة: " . number_format($payment_amount, 2) . " ر.س عبر " . $payment_method;
            if (!empty($notes)) {
                $notes_text .= " - " . $notes;
            }

            $update_query = "UPDATE orders SET
                             deposit_amount = ?,
                             remaining_amount = ?,
                             payment_status = ?,
                             payment_method = ?,
                             payment_settled_at = ?,
                             notes = CONCAT_WS('\
', notes, ?),
                             updated_at = NOW()
                             WHERE order_id = ?";

            $remaining_amount = $total_amount - $new_deposit;
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ddssssi", $new_deposit, $remaining_amount, $new_payment_status, $payment_method, $payment_settled_at, $notes_text, $order_id);

            if (!$stmt->execute()) {
                throw new \Exception('فشل في تحديث بيانات الطلب');
            }

            $notification_message = "💰 المحاسب {$user_name} حدث حالة الدفع للطلب #{$order_id} - الحالة الجديدة: {$new_payment_status}";
            $notification_link = "/new_injaz/dashboard";

            $managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
            $stmt_notify = $conn->prepare("INSERT INTO notifications (employee_id, message, link) VALUES (?, ?, ?)");
            while ($manager = $managers_res->fetch_assoc()) {
                if ($manager['employee_id'] != $user_id) {
                    $stmt_notify->bind_param("iss", $manager['employee_id'], $notification_message, $notification_link);
                    $stmt_notify->execute();
                }
            }
            $stmt_notify->close();

            $conn->commit();

            return [
                'success' => true,
                'message' => 'تم تحديث حالة الدفع بنجاح',
                'data' => [
                    'new_payment_status' => $new_payment_status,
                    'new_deposit' => $new_deposit,
                    'remaining_amount' => $remaining_amount,
                    'is_fully_paid' => ($new_payment_status === 'مدفوع')
                ]
            ];

        } catch (\Exception $e) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
