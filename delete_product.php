<?php
include 'db_connection.php';
include 'auth_check.php';

check_permission('product_delete', $conn);

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // Check for related records in the order_items table
    $stmt_check = $conn->prepare("SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن حذف هذا المنتج لأنه مستخدم في طلبات حالية.'];
        header("Location: products.php");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف المنتج بنجاح.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لم يتم العثور على المنتج أو حدث خطأ.'];
    }
}
header("Location: products.php");
exit;
