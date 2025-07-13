<?php
include_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنجاز الإعلامية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Google Fonts Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: 'Tajawal', Arial, sans-serif; background: #faf6f4; }
        .main-navbar { background: #D44759; }
        .main-navbar .navbar-brand { color: #fff; font-weight: bold; }
        .main-navbar img { height: 40px; }
        .sidebar { background: #fff; min-height: 100vh; box-shadow: 1px 0 4px #eee; }
        .sidebar a { color: #333; display: block; padding: 10px 18px; border-radius: 10px; margin-bottom: 4px; transition: background 0.2s;}
        .sidebar a.active, .sidebar a:hover { background: #F37D47; color: #fff; }
    </style>
</head>
<body>
<nav class="navbar main-navbar navbar-expand-lg shadow-sm px-3">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/logoenjaz.jpg" class="me-2" alt="Logo">
        إنجاز الإعلامية
    </a>
    <div class="ms-auto">
        <?php if(isset($_SESSION['user_name'])): ?>
        <span class="text-white me-3">مرحبًا، <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn btn-light btn-sm">تسجيل الخروج</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <nav class="col-md-2 sidebar py-4 d-none d-md-block">
      <ul class="nav flex-column">
        <?php if (has_permission('dashboard_view')): // صلاحية افتراضية للجميع ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='index.php'?' active':'' ?>" href="index.php">لوحة التحكم</a></li>
        <?php endif; ?>
        <?php if (has_permission('order_view_all') || has_permission('order_view_own')): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='orders.php'?' active':'' ?>" href="orders.php">الطلبات</a></li>
        <?php endif; ?>
        <?php if (has_permission('client_view')): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='clients.php'?' active':'' ?>" href="clients.php">العملاء</a></li>
        <?php endif; ?>
        <?php if (has_permission('product_view')): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='products.php'?' active':'' ?>" href="products.php">المنتجات</a></li>
        <?php endif; ?>
        <?php if (has_permission('employee_view')): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='employees.php'?' active':'' ?>" href="employees.php">الموظفون</a></li>
        <?php endif; ?>
      </ul>
    </nav>
    <main class="col-md-10 ms-sm-auto px-md-4 pt-4">
    <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
