<?php
include 'db_connection_secure.php';
include 'auth_check.php';

check_permission('client_delete', $conn);

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // Check for related records in the orders table
    $stmt_check = $conn->prepare("SELECT 1 FROM orders WHERE client_id = ? LIMIT 1");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن حذف هذا العميل لأنه مرتبط بطلبات حالية.'];
        header("Location: clients.php");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM clients WHERE client_id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف العميل بنجاح.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لم يتم العثور على العميل أو حدث خطأ.'];
    }
}
header("Location: clients.php");
exit;
