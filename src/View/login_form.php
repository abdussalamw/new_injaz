<?php
// This file is included from Login.php, so it has access to $error and $success_reset.
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
            <img src="/assets/logoenjaz.jpg" alt="Logo" style="height:56px">
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
