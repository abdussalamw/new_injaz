<?php
include 'header.php';
include 'db_connection.php';

check_permission('client_add');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة العميل بنجاح!'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء إضافة العميل.'];
    }
    header("Location: clients.php");
    exit;
}
?>
<div class="container">
    <h2 style="color:#D44759;" class="mb-4">إضافة عميل جديد</h2>
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المؤسسة</label>
                <input type="text" class="form-control" name="company_name" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">اسم الشخص المسؤول</label>
                <input type="text" class="form-control" name="contact_person">
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="text" class="form-control" name="phone">
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ العميل</button>
        <a href="clients.php" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
<?php include 'footer.php'; ?>
