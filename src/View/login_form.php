<?php
// This file is included from Login.php, so it has access to $error and $success_reset.
use App\Core\Helpers;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - إنجاز الإعلامية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            font-family: 'Tajawal', Arial, sans-serif; 
            background: white;
            min-height: 100vh;
        }
        .welcome-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #D44759;
            box-shadow: 0 0 0 0.2rem rgba(212, 71, 89, 0.25);
        }
        .btn-login {
            background: linear-gradient(45deg, #D44759, #F37D47);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 71, 89, 0.4);
        }
    </style>
</head>
<body>
<div class="container" style="min-height:100vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <!-- Welcome Header -->
    <div class="welcome-header" style="max-width: 500px;">
        <div class="mb-3">
            <img src="<?= \App\Core\Helpers::asset('logoenjaz.jpg') ?>" alt="Logo" style="height:70px">
        </div>
        <h2 style="color:#D44759; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-house-heart-fill me-2"></i>
            أهلاً وسهلاً بك
        </h2>
        <p style="color:#666; font-size: 16px; margin-bottom: 0;">
            مرحباً بك في نظام إدارة إنجاز الإعلامية<br>
            <small>يرجى تسجيل الدخول للوصول إلى لوحة التحكم</small>
        </p>
    </div>

    <!-- Login Form -->
    <div class="login-card" style="min-width:400px;max-width:450px;">
        <div class="text-center mb-4">
            <h3 style="color:#D44759; font-weight: bold;">
                <i class="bi bi-person-circle me-2"></i>
                تسجيل الدخول
            </h3>
            <p style="color:#666; font-size: 14px;">أدخل بياناتك للوصول إلى حسابك</p>
        </div>
        <?php if(isset($success_reset) && $success_reset): ?><div class="alert alert-success"><?= $success_reset ?></div><?php endif; ?>
        <?php if(isset($error) && $error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-bold" style="color:#333;">
                    <i class="bi bi-person-fill me-2"></i>
                    اسم المستخدم أو البريد الإلكتروني
                </label>
                <input type="text" class="form-control" name="username" required autofocus 
                       placeholder="أدخل اسم المستخدم أو البريد الإلكتروني">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold" style="color:#333;">
                    <i class="bi bi-lock-fill me-2"></i>
                    كلمة المرور
                </label>
                <input type="password" class="form-control" name="password" required 
                       placeholder="أدخل كلمة المرور">
            </div>
            <button class="btn btn-login btn-lg w-100 text-white">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                تسجيل الدخول
            </button>
        </form>

        <div class="text-center mt-4">
            <small style="color:#666;">
                <i class="bi bi-shield-check me-1"></i>
                جميع البيانات محمية ومشفرة
            </small>
        </div>
    </div>
</div>

</body>
</html>
