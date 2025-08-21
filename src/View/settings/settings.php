<?php
// صفحة إعدادات عامة مع تبويبات لكل ملف في مجلد settings
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['مدير', 'admin'])) {
    http_response_code(403);
    echo '<h2 style="color:red;text-align:center;margin-top:50px">غير مصرح لك بالدخول لهذه الصفحة</h2>';
    exit;
}
$page_title = 'إعدادات النظام';
$tabs = [
    'role_filters' => 'فلترة الأدوار',
    'mabs' => 'ماب',
    'data-guide' => 'جالاري',
    'test' => 'اختبار شامل'
];
$current_tab = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'role_filters';
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <style>
        body { font-family: Tahoma, Arial; background: #f8f9fa; }
        .settings-container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px #ddd; padding: 30px; }
        h2 { color: #D44759; text-align: center; margin-bottom: 30px; }
        .tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 25px; }
        .tab { padding: 12px 30px; cursor: pointer; color: #D44759; font-weight: bold; border: none; background: none; outline: none; transition: background 0.2s; }
        .tab.active { background: #F37D47; color: #fff; border-radius: 10px 10px 0 0; }
        .tab:not(.active):hover { background: #f8f9fa; }
        .tab-content { padding: 20px 0; }
    </style>
</head>
<body>
    <div class="settings-container">
        <h2><?= htmlspecialchars($page_title) ?></h2>
        <div class="tabs">
            <?php foreach ($tabs as $key => $label): ?>
                <a href="?tab=<?= $key ?>" class="tab<?= $current_tab === $key ? ' active' : '' ?>"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="tab-content">
            <?php
            // تضمين محتوى كل تبويب حسب الملف
            $tab_files = [
                'role_filters' => 'role_filters.php',
                'mabs' => 'mabs.php',
                'data-guide' => 'data-guide.php',
                'test' => 'test.php'
            ];
            $tab_file = isset($tab_files[$current_tab]) ? $tab_files[$current_tab] : $tab_files['role_filters'];
            $tab_path = __DIR__ . '/' . $tab_file;
            if (file_exists($tab_path)) {
                include $tab_path;
            } else {
                echo '<div style="color:red;text-align:center">لا يوجد محتوى لهذا التبويب</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
