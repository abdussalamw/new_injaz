<?php
session_start();
include 'db_connection.php';
$error = '';
$success_reset = '';

// --- أداة مؤقتة لتغيير كلمة المرور ---
if (isset($_POST['reset_password'])) {
    $reset_user = $_POST['reset_username'];
    $new_pass = $_POST['new_password'];

    if (!empty($reset_user) && !empty($new_pass)) {
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt_reset = $conn->prepare("UPDATE employees SET password = ? WHERE name = ?");
        $stmt_reset->bind_param("ss", $hashed_password, $reset_user);
        if ($stmt_reset->execute() && $stmt_reset->affected_rows > 0) {
            $success_reset = "تم تحديث كلمة مرور المستخدم '" . htmlspecialchars($reset_user) . "' بنجاح.";
        } else {
            $error = "لم يتم العثور على المستخدم '" . htmlspecialchars($reset_user) . "' أو حدث خطأ.";
        }
    } else {
        $error = "الرجاء إدخال اسم المستخدم وكلمة المرور الجديدة.";
    }
}
// --- نهاية الأداة المؤقتة ---

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['reset_password'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $stmt = $conn->prepare("SELECT employee_id, name, role, password FROM employees WHERE email=? OR name=? LIMIT 1");
    $stmt->bind_param("ss", $user, $user);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user_id'] = $row['employee_id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'];
            // مسح أي صلاحيات قديمة مخزنة لضمان تحميل الصلاحيات الجديدة
            unset($_SESSION['user_permissions']); 
            header("Location: index.php"); exit;
        } else {
            $error = "كلمة المرور غير صحيحة.";
        }
    } else {
        $error = "المستخدم غير موجود.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - إنجاز الإعلامية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', Arial, sans-serif; background: #faf6f4; }
    </style>
</head>
<body>
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card shadow-lg rounded-4 p-4" style="min-width:340px;max-width:370px;">
        <div class="text-center mb-3">
            <img src="assets/logoenjaz.jpg" alt="Logo" style="height:56px">
        </div>
        <h3 class="text-center mb-3" style="color:#D44759;">تسجيل الدخول</h3>
        <?php if($success_reset): ?><div class="alert alert-success"><?= $success_reset ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                <input type="text" class="form-control" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button class="btn btn-lg w-100 text-white" style="background:#D44759;">دخول</button>
        </form>

        <!-- أداة مؤقتة لتغيير كلمة المرور -->
        <div class="card shadow-sm rounded-4 p-3 mt-4 bg-light border-warning">
            <h6 class="text-center text-muted mb-3">أداة مؤقتة لتغيير كلمة المرور</h6>
            <form method="post">
                <div class="mb-2">
                    <label class="form-label small">اسم المستخدم</label>
                    <input type="text" class="form-control form-control-sm" name="reset_username" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">كلمة المرور الجديدة</label>
                    <input type="password" class="form-control form-control-sm" name="new_password" required>
                </div>
                <button class="btn btn-sm btn-warning w-100" name="reset_password" type="submit">تحديث كلمة المرور</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
