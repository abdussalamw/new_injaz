<?php
include 'db_connection.php';
include 'auth_check.php';

check_permission('employee_delete', $conn);

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // منع المستخدم من حذف نفسه
    if ($id === ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'لا يمكنك حذف حسابك الخاص.'];
        header("Location: employees.php");
        exit;
    }

    // Check for related records in the orders table
    $stmt_check = $conn->prepare("SELECT 1 FROM orders WHERE created_by = ? OR designer_id = ? LIMIT 1");
    $stmt_check->bind_param("ii", $id, $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن حذف هذا الموظف لأنه مرتبط بطلبات حالية (كمنشئ أو مصمم).'];
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
