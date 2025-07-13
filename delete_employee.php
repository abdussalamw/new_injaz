<?php
include 'auth_check.php';
include 'db_connection.php';

check_permission('employee_delete');

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // منع المستخدم من حذف نفسه
    if ($id === ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'لا يمكنك حذف حسابك الخاص.'];
        header("Location: employees.php");
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف الموظف بنجاح.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لم يتم العثور على الموظف أو حدث خطأ.'];
    }
}
header("Location: employees.php");
exit;
