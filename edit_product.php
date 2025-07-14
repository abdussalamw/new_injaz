<?php
$id = intval($_GET['id'] ?? 0);
$page_title = "تعديل المنتج #" . $id;
include 'db_connection.php';
include 'header.php';

check_permission('product_edit', $conn);

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo "<div class='alert alert-danger'>المنتج غير موجود</div>";
    include 'footer.php'; exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $size = $_POST['default_size'];
    $material = $_POST['default_material'];
    $details = $_POST['default_details'];
    $stmt2 = $conn->prepare("UPDATE products SET name=?, default_size=?, default_material=?, default_details=? WHERE product_id=?");
    $stmt2->bind_param("ssssi", $name, $size, $material, $details, $id);
    if ($stmt2->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل المنتج بنجاح.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء تعديل المنتج.'];
    }
    header("Location: edit_product.php?id=" . $id);
    exit;
}
?>
<div class="container">
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المنتج</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">المقاس الافتراضي</label>
                <input type="text" class="form-control" name="default_size" value="<?= htmlspecialchars($row['default_size']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">المادة الافتراضية</label>
                <input type="text" class="form-control" name="default_material" value="<?= htmlspecialchars($row['default_material']) ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">تفاصيل إضافية</label>
                <textarea class="form-control" name="default_details"><?= htmlspecialchars($row['default_details']) ?></textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ التعديلات</button>
        <a href="products.php" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
<?php include 'footer.php'; ?>
