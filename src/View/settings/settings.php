<?php
// صفحة إعدادات النظام الموحدة - تصميم احترافي من اليمين إلى اليسار
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['مدير', 'admin'])) {
    http_response_code(403);
    echo '<h2 style="color:red;text-align:center;margin-top:50px">غير مصرح لك بالدخول لهذه الصفحة</h2>';
    exit;
}

$page_title = 'إعدادات النظام - لوحة التحكم المتقدمة';

// التبويبات المرتبة والمنظمة
$tabs = [
    'role_filters_new' => [
        'title' => 'إعدادات الفلترة المتقدمة',
        'icon' => 'bi-funnel-fill',
        'description' => 'إدارة صلاحيات العرض والفلترة للموظفين',
        'color' => 'primary'
    ],
    'mabs' => [
        'title' => 'خريطة النظام',
        'icon' => 'bi-diagram-3-fill',
        'description' => 'دليل شامل لسير العمل وهيكل النظام',
        'color' => 'info'
    ],
    'data-guide' => [
        'title' => 'دليل البيانات',
        'icon' => 'bi-database-fill',
        'description' => 'إدارة البيانات والنسخ الاحتياطي',
        'color' => 'success'
    ],
    'test' => [
        'title' => 'أدوات الاختبار',
        'icon' => 'bi-tools',
        'description' => 'أدوات فحص واختبار النظام',
        'color' => 'warning'
    ]
];

$current_tab = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'role_filters_new';

// مسارات الملفات
$tab_files = [
    'role_filters_new' => 'role_filters_new.php',
    'mabs' => 'mabs.php',
    'data-guide' => 'data-guide.php',
    'test' => 'test.php'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Font Awesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --info-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            direction: rtl;
        }

        /* Header Section */
        .main-header {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 0 4rem;
            position: relative;
            overflow: hidden;
        }

        .main-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,0 1000,0 1000,60 0,100"/></svg>') no-repeat center bottom;
            background-size: cover;
        }

        .header-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .header-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Navigation Tabs */
        .tabs-container {
            background: white;
            border-radius: 20px 20px 0 0;
            margin-top: -2rem;
            position: relative;
            z-index: 3;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }

        .tabs-wrapper {
            padding: 0 2rem;
            padding-top: 2rem;
        }

        .tabs-list {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 1rem;
        }

        .tabs-list::-webkit-scrollbar {
            display: none;
        }

        .tab-item {
            flex: 1;
            min-width: 280px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .tab-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .tab-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .tab-item.active {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102,126,234,0.3);
        }

        .tab-item.active::before {
            transform: scaleX(1);
        }

        .tab-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .tab-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .tab-description {
            font-size: 0.9rem;
            color: #6c757d;
            line-height: 1.4;
        }

        /* Content Section */
        .content-container {
            background: white;
            min-height: calc(100vh - 300px);
            padding: 3rem 2rem;
        }

        .content-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
        }

        .content-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .content-subtitle {
            font-size: 1.1rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Breadcrumb */
        .breadcrumb-nav {
            background: #f8f9fa;
            padding: 1rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .breadcrumb {
            background: none;
            margin: 0;
            padding: 0;
        }

        .breadcrumb-item {
            font-weight: 500;
        }

        .breadcrumb-item.active {
            color: #667eea;
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-title {
                font-size: 2rem;
            }

            .tabs-list {
                gap: 0.5rem;
            }

            .tab-item {
                min-width: 250px;
            }

            .content-container {
                padding: 2rem 1rem;
            }
        }

        @media (max-width: 576px) {
            .tabs-list {
                flex-direction: column;
                gap: 0.5rem;
            }

            .tab-item {
                min-width: auto;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Tab Colors */
        .tab-primary { border-color: #667eea; }
        .tab-primary.active { border-color: #667eea; box-shadow: 0 5px 20px rgba(102,126,234,0.3); }
        .tab-primary.active::before { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        .tab-info { border-color: #17a2b8; }
        .tab-info.active { border-color: #17a2b8; box-shadow: 0 5px 20px rgba(23,162,184,0.3); }
        .tab-info.active::before { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }

        .tab-success { border-color: #28a745; }
        .tab-success.active { border-color: #28a745; box-shadow: 0 5px 20px rgba(40,167,69,0.3); }
        .tab-success.active::before { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }

        .tab-warning { border-color: #ffc107; }
        .tab-warning.active { border-color: #ffc107; box-shadow: 0 5px 20px rgba(255,193,7,0.3); }
        .tab-warning.active::before { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header Section -->
    <header class="main-header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="bi bi-gear-fill me-3"></i>
                إعدادات النظام
            </h1>
            <p class="header-subtitle">
                لوحة تحكم متقدمة لإدارة وتهيئة جميع إعدادات النظام بطريقة احترافية ومنظمة
            </p>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <section class="tabs-container">
        <div class="tabs-wrapper">
            <div class="tabs-list">
                <?php foreach ($tabs as $key => $tab): ?>
                    <div class="tab-item tab-<?= $tab['color'] ?> <?= $current_tab === $key ? 'active' : '' ?>"
                         onclick="switchTab('<?= $key ?>')">
                        <i class="bi <?= $tab['icon'] ?> tab-icon"></i>
                        <div class="tab-title"><?= htmlspecialchars($tab['title']) ?></div>
                        <div class="tab-description"><?= htmlspecialchars($tab['description']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <main class="content-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../dashboard.php" style="text-decoration: none; color: #667eea;">
                        <i class="bi bi-house-door me-1"></i>لوحة التحكم
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= htmlspecialchars($tabs[$current_tab]['title']) ?>
                </li>
            </ol>
        </nav>

        <!-- Content Header -->
        <div class="content-header">
            <h2 class="content-title">
                <i class="bi <?= $tabs[$current_tab]['icon'] ?> me-3"></i>
                <?= htmlspecialchars($tabs[$current_tab]['title']) ?>
            </h2>
            <p class="content-subtitle">
                <?= htmlspecialchars($tabs[$current_tab]['description']) ?>
            </p>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="tabContent">
            <?php
            $tab_file = isset($tab_files[$current_tab]) ? $tab_files[$current_tab] : $tab_files['role_filters_new'];
            $tab_path = __DIR__ . '/' . $tab_file;

            if (file_exists($tab_path)) {
                // عرض loading قبل تحميل المحتوى
                echo '<script>document.getElementById("loadingOverlay").style.display = "flex";</script>';

                include $tab_path;

                // إخفاء loading بعد تحميل المحتوى
                echo '<script>document.getElementById("loadingOverlay").style.display = "none";</script>';
            } else {
                echo '<div class="alert alert-warning text-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>تنبيه:</strong> محتوى هذا القسم غير متوفر حالياً
                      </div>';
            }
            ?>
        </div>
    </main>

    <!-- Footer -->
    <footer style="background: var(--dark-gradient); color: white; padding: 2rem 0; text-align: center; margin-top: 2rem;">
        <div class="container">
            <p class="mb-0">
                <i class="bi bi-copyright me-1"></i>
                2025 نظام إنجاز الإعلامية - إعدادات النظام المتقدمة
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Tab Switching Functionality
        function switchTab(tabKey) {
            // Show loading
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.pushState({}, '', url);

            // Reload page to load new tab content
            window.location.reload();
        }

        // Add fade-in animation to content
        document.addEventListener('DOMContentLoaded', function() {
            const content = document.getElementById('tabContent');
            if (content) {
                content.classList.add('fade-in-up');
            }

            // Hide loading on page load
            document.getElementById('loadingOverlay').style.display = 'none';
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            window.location.reload();
        });

        // Keyboard navigation for tabs
        document.addEventListener('keydown', function(e) {
            const tabs = document.querySelectorAll('.tab-item');
            const activeTab = document.querySelector('.tab-item.active');
            let activeIndex = Array.from(tabs).indexOf(activeTab);

            if (e.key === 'ArrowLeft' && activeIndex > 0) {
                tabs[activeIndex - 1].click();
            } else if (e.key === 'ArrowRight' && activeIndex < tabs.length - 1) {
                tabs[activeIndex + 1].click();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.classList.contains('alert-permanent')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>
