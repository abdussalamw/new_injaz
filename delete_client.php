<?php
include 'auth_check.php';
include 'db_connection.php';

check_permission('client_delete');

$id = intval($_GET['id'] ?? 0);
if ($id) {
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
