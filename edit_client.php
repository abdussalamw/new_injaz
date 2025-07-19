<?php
$id = intval($_GET['id'] ?? 0);
$page_title = "تعديل بيانات العميل #" . $id;
include 'db_connection_secure.php';
include 'header.php';

check_permission('client_edit', $conn);

$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo "<div class='alert alert-danger'>العميل غير موجود</div>";
    include 'footer.php'; exit;
}

$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // التحقق من صحة رقم الجوال السعودي
    if (empty($phone) || !preg_match('/^05[0-9]{8}$/', $phone)) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'الرجاء إدخال رقم جوال سعودي صحيح (10 أرقام تبدأ بـ 05).'];
        header("Location: edit_client.php?id=" . $id);
        exit;
    }

    $stmt2 = $conn->prepare("UPDATE clients SET company_name=?, contact_person=?, phone=?, email=? WHERE client_id=?");
    $stmt2->bind_param("ssssi", $company_name, $contact_person, $phone, $email, $id);
    if ($stmt2->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل بيانات العميل بنجاح!'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء التعديل.'];
    }
    header("Location: edit_client.php?id=" . $id);
    exit;
}
?>
<div class="container">
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المؤسسة</label>
                <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($row['company_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">اسم الشخص المسؤول</label>
                <input type="text" class="form-control" name="contact_person" value="<?= htmlspecialchars($row['contact_person']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ التعديلات</button>
        <a href="clients.php" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
<?php include 'footer.php'; ?>
