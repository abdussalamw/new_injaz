<?php
session_start();
include 'db_connection.php';
$error = '';
$success_reset = '';

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

            // --- منطق الترحيل التلقائي لصلاحيات المدراء القدامى ---
            // هذا يضمن أن المدراء الحاليين يحصلون على صلاحياتهم الكاملة في قاعدة البيانات بعد التحديث
            if ($row['role'] === 'مدير') {
                // التحقق مما إذا كان هذا المدير لديه أي صلاحيات مسجلة
                $perm_check_stmt = $conn->prepare("SELECT COUNT(*) FROM employee_permissions WHERE employee_id = ?");
                $perm_check_stmt->bind_param("i", $row['employee_id']);
                $perm_check_stmt->execute();
                $perm_count = $perm_check_stmt->get_result()->fetch_row()[0];
                $perm_check_stmt->close();

                // إذا لم يكن لديه صلاحيات، فهذا يعني أنه حساب مدير قديم ويجب منحه كل الصلاحيات
                if ($perm_count == 0) {
                    include_once 'permissions.php'; // لجلب دالة get_all_permissions
                    $all_permissions = get_all_permissions();
                    $stmt_grant = $conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?)");
                    foreach ($all_permissions as $group => $permissions) {
                        foreach (array_keys($permissions) as $perm_key) {
                            $stmt_grant->bind_param("is", $row['employee_id'], $perm_key);
                            $stmt_grant->execute();
                        }
                    }
                    $stmt_grant->close();
                }
            }

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
    </div>
</div>
</body>
</html>
