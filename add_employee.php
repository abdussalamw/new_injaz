<?php
$page_title = 'إضافة موظف جديد';
include 'db_connection_secure.php';
include 'header.php';

check_permission('employee_add', $conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    // تعيين كلمة مرور افتراضية وتشفيرها
    $password = password_hash('demo123', PASSWORD_DEFAULT);

    $conn->begin_transaction();
    try {
        // 1. إضافة الموظف
        $stmt = $conn->prepare("INSERT INTO employees (name, role, phone, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $role, $phone, $email, $password);
        $stmt->execute();
        $employee_id = $conn->insert_id;

        // 2. تعيين الصلاحيات الافتراضية بناءً على الدور
        $default_permissions = [];
        switch ($role) {
            case 'مدير':
                // المدير الجديد يحصل على كل الصلاحيات المتاحة في النظام
                // دالة get_all_permissions() متاحة من خلال ملف header.php
                $all_perms = get_all_permissions();
                foreach ($all_perms as $group => $permissions) {
                    $default_permissions = array_merge($default_permissions, array_keys($permissions));
                }
                break;
            case 'مصمم':
            case 'معمل':
                $default_permissions = ['dashboard_view', 'order_view_own'];
                break;
            case 'محاسب':
                // المحاسب يحتاج صلاحية عرض مهامه، وصلاحية التسوية المالية
                // وصلاحية عرض كل الطلبات للمتابعة
                $default_permissions = ['dashboard_view', 'order_view_own', 'order_financial_settle'];
                break;
        }

        if (!empty($default_permissions)) {
            $stmt_perm = $conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?)");
            foreach ($default_permissions as $perm_key) {
                $stmt_perm->bind_param("is", $employee_id, $perm_key);
                $stmt_perm->execute();
            }
        }

        $conn->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة الموظف وتعيين صلاحياته الافتراضية بنجاح.'];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ: ' . $e->getMessage()];
    }
    header("Location: employees.php");
    exit;
}
?>
<div class="container">
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
