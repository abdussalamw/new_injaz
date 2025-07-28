<?php
include 'db_connection_secure.php';
include 'auth_check.php';

check_permission('order_delete', $conn);

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $conn->begin_transaction();
    try {
        // 1. Delete related order items first
        $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();

        // 2. Delete the main order
        $stmt_order = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt_order->bind_param("i", $id);
        $stmt_order->execute();

        $conn->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف الطلب وجميع بنوده بنجاح.'];

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()];
    }
}
header("Location: orders.php");
exit;
