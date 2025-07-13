<?php
include 'header.php';
include 'db_connection.php';

check_permission('employee_add');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    // تعيين كلمة مرور افتراضية وتشفيرها
    $password = password_hash('demo123', PASSWORD_DEFAULT); 
    $stmt = $conn->prepare("INSERT INTO employees (name, role, phone, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $role, $phone, $email, $password);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة الموظف بنجاح.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء إضافة الموظف.'];
    }
    header("Location: employees.php");
    exit;
}
?>
<div class="container">
    <h2 style="color:#D44759;" class="mb-4">إضافة موظف جديد</h2>
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">الدور</label>
                <select class="form-select" name="role" required>
                    <option value="مدير">مدير</option>
                    <option value="مصمم">مصمم</option>
                    <option value="معمل">معمل</option>
                    <option value="محاسب">محاسب</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="text" class="form-control" name="phone">
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email">
            </div>
            <!-- يمكنك إضافة حقل لكلمة المرور هنا إذا أردت -->
            <!-- <div class="col-md-4">
                <label class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" name="password">
            </div> -->
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ الموظف</button>
        <a href="employees.php" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
<?php include 'footer.php'; ?>
