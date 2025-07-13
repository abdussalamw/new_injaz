<?php
include 'header.php';
include 'db_connection.php';

check_permission('product_add');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $size = $_POST['default_size'];
    $material = $_POST['default_material'];
    $details = $_POST['default_details'];

    $stmt = $conn->prepare("INSERT INTO products (name, default_size, default_material, default_details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $size, $material, $details);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة المنتج بنجاح.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء إضافة المنتج.'];
    }
    header("Location: products.php");
    exit;
}
?>
<div class="container">
    <h2 style="color:#D44759;" class="mb-4">إضافة منتج جديد</h2>
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المنتج</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">المقاس الافتراضي</label>
                <input type="text" class="form-control" name="default_size" placeholder="مثال: 9x5 سم">
            </div>
            <div class="col-md-4">
                <label class="form-label">المادة الافتراضية</label>
                <input type="text" class="form-control" name="default_material" placeholder="مثال: ورق فاخر">
            </div>
            <div class="col-md-12">
                <label class="form-label">تفاصيل إضافية</label>
                <textarea class="form-control" name="default_details"></textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ المنتج</button>
        <a href="products.php" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
<?php include 'footer.php'; ?>
