<?php ob_start(); ?>
<?php
include_once 'auth_check.php';
include_once 'helpers.php';

// --- منطق الإشعارات ---
// 1. وضع علامة "مقروء" على الإشعار عند زيارة الرابط
if (isset($_GET['notif_id']) && isset($_SESSION['user_id'])) {
    $notif_id_to_mark = intval($_GET['notif_id']);
    $user_id_to_mark = $_SESSION['user_id'];
    $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND employee_id = ?");
    $stmt_mark_read->bind_param("ii", $notif_id_to_mark, $user_id_to_mark);
    $stmt_mark_read->execute();
    
    // إعادة توجيه لإزالة الباراميتر من الرابط لتجنب إعادة التنفيذ عند تحديث الصفحة
    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
    $query_params = $_GET;
    unset($query_params['notif_id']);
    $new_query_string = http_build_query($query_params);
    $redirect_url = $current_url . ($new_query_string ? '?' . $new_query_string : '');
    header("Location: " . $redirect_url);
    exit;
}
?>
<?php $page_title = $page_title ?? 'إنجاز الإعلامية'; // تعيين عنوان افتراضي في حال لم يتم تحديده ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?> - إنجاز الإعلامية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.4/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Google Fonts Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        /* --- Unified Order Status Styles --- */
        .status-design {
            background-color: #0dcaf0 !important; /* Bootstrap Info */
            color: #000 !important;
        }
        .status-execution {
            background-color: #0d6efd !important; /* Bootstrap Primary */
            color: #fff !important;
        }
        .status-ready {
            background-color: #ffc107 !important; /* Bootstrap Warning */
            color: #000 !important;
        }
        .status-completed {
            background-color: #198754 !important; /* Bootstrap Success */
            color: #fff !important;
        }
        .status-cancelled {
            background-color: #dc3545 !important; /* Bootstrap Danger */
            color: #fff !important;
        }
        .status-default {
            background-color: #f8f9fa !important; /* Bootstrap Light */
            color: #000 !important;
            border: 1px solid #dee2e6;
        }
        /* Ensure buttons also get the style correctly and remove their default border */
        .btn.status-design, .btn.status-execution, .btn.status-ready,
        .btn.status-completed, .btn.status-cancelled, .btn.status-default {
            border-color: transparent;
        }

        /* --- Enhanced Notifications Styles --- */
        .notification-item {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .notification-item:hover {
            background-color: #f8f9fa !important;
            border-left-color: #D44759 !important;
            transform: translateX(-2px);
        }
        .notification-item .bg-primary {
            background: linear-gradient(135deg, #D44759, #F37D47) !important;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(212, 71, 89, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(212, 71, 89, 0); }
            100% { box-shadow: 0 0 0 0 rgba(212, 71, 89, 0); }
        }
        #notificationDropdown {
            transition: all 0.3s ease;
        }
        #notificationDropdown:hover {
            transform: scale(1.1);
        }
        .dropdown-menu {
            border: none;
            border-radius: 15px;
        }
        .dropdown-header {
            background: linear-gradient(135deg, #D44759, #F37D47) !important;
            color: white !important;
            border-radius: 15px 15px 0 0 !important;
        }
        .dropdown-header h6 {
            color: white !important;
        }
    </style>
</head>
<body>
<nav class="navbar main-navbar navbar-expand-lg shadow-sm px-3">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/logoenjaz.jpg" class="me-2" alt="Logo">
        إنجاز الإعلامية
    </a>
    <div class="ms-auto d-flex align-items-center">
        <?php if(isset($_SESSION['user_name'])): ?>
        <?php
            // 2. جلب الإشعارات غير المقروءة للمستخدم الحالي
            $unread_notifications = [];
            $unread_count = 0;
            $user_id_notif = $_SESSION['user_id'];
            
            $stmt_notif = $conn->prepare("SELECT * FROM notifications WHERE employee_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
            $stmt_notif->bind_param("i", $user_id_notif);
            $stmt_notif->execute();
            $unread_notifications = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE employee_id = ? AND is_read = 0");
            $stmt_count->bind_param("i", $user_id_notif);
            $stmt_count->execute();
            $unread_count = $stmt_count->get_result()->fetch_row()[0];
        ?>
        <!-- قائمة الإشعارات المنسدلة -->
        <div class="dropdown me-3">
            <a href="#" class="text-white position-relative" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell-fill fs-4"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light fs-6"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="notificationDropdown" style="min-width: 400px; max-height: 500px; overflow-y: auto;">
                <li class="dropdown-header bg-light py-3 border-bottom">
                    <h6 class="mb-0 text-dark">🔔 الإشعارات الجديدة</h6>
                </li>
                <?php if (empty($unread_notifications)): ?>
                    <li class="p-4 text-center">
                        <div class="text-muted">
                            <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                            <p class="mb-0">لا توجد إشعارات جديدة</p>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($unread_notifications as $notif): ?>
                        <li>
                            <a class="dropdown-item py-3 px-4 border-bottom notification-item" 
                               href="<?= htmlspecialchars($notif['link']) ?>&notif_id=<?= $notif['notification_id'] ?>"
                               style="white-space: normal; line-height: 1.4;">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-info-circle text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">
                                            <?= htmlspecialchars($notif['message']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i - d/m/Y', strtotime($notif['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($unread_count > 5): ?>
                        <li class="dropdown-divider"></li>
                        <li class="text-center py-2">
                            <small class="text-muted">وهناك <?= $unread_count - 5 ?> إشعارات أخرى...</small>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
        <span class="text-white me-3 d-none d-sm-inline">مرحبًا، <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn btn-light btn-sm">تسجيل الخروج <i class="bi bi-box-arrow-right"></i></a>
        <?php endif; ?>
    </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <nav class="col-md-2 sidebar py-4 d-none d-md-block">
      <ul class="nav flex-column">
        <?php if (has_permission('dashboard_view', $conn)): // صلاحية افتراضية للجميع ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='index.php'?' active':'' ?>" href="index.php">لوحة التحكم</a></li>
        <?php endif; ?>
        <?php if (has_permission('order_view_all', $conn) || has_permission('order_view_own', $conn)): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='orders.php'?' active':'' ?>" href="orders.php">الطلبات</a></li>
        <?php endif; ?>
        <?php if (has_permission('client_view', $conn)): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='clients.php'?' active':'' ?>" href="clients.php">العملاء</a></li>
        <?php endif; ?>
        <?php if (has_permission('product_view', $conn)): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='products.php'?' active':'' ?>" href="products.php">المنتجات</a></li>
        <?php endif; ?>
        <?php if (has_permission('employee_view', $conn)): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='employees.php'?' active':'' ?>" href="employees.php">الموظفون</a></li>
        <?php endif; ?>
        <?php if (has_permission('dashboard_reports_view', $conn) || has_permission('order_view_own', $conn)): ?>
            <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])=='timeline_reports.php'?' active':'' ?>" href="timeline_reports.php">الجدول الزمني للمراحل</a></li>
        <?php endif; ?>
      </ul>
    </nav>
    <main class="col-md-10 ms-sm-auto px-md-4 pt-4">
    <h1 class="mb-4" style="color:#D44759;"><?= htmlspecialchars($page_title) ?></h1>
    <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
