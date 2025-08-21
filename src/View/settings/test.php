
<?php
// صفحة اختبار شامل للنظام
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['مدير', 'admin'])) {
    http_response_code(403);
    echo '<h2 style="color:red;text-align:center;margin-top:50px">غير مصرح لك بالدخول لهذه الصفحة</h2>';
    exit;
}
$page_title = 'اختبار شامل للنظام';
require_once __DIR__ . '/test_helpers.php';

$pages = function_exists('test_pages') ? test_pages() : [];
$reports = function_exists('test_reports') ? test_reports() : [];
$functions = function_exists('test_functions') ? test_functions() : [];
$links = function_exists('test_links') ? test_links() : [];
?>
<div style="padding:20px;">
    <h3 style="color:#D44759;">اختبار شامل للنظام</h3>
    <h4>الصفحات</h4>
    <ul style="font-size:15px;line-height:2;">
        <?php foreach($pages as $p): ?>
            <li><?= htmlspecialchars($p['page']) ?> <?= !empty($p['exists']) ? '<span style="color:green">✔</span>' : '<span style="color:red">✖</span>' ?></li>
        <?php endforeach; ?>
    </ul>
    <h4>التقارير</h4>
    <ul style="font-size:15px;line-height:2;">
        <?php foreach($reports as $r): ?>
            <li><?= htmlspecialchars($r['report']) ?> <?= !empty($r['exists']) ? '<span style="color:green">✔</span>' : '<span style="color:red">✖</span>' ?></li>
        <?php endforeach; ?>
    </ul>
    <h4>الدوال البرمجية</h4>
    <ul style="font-size:15px;line-height:2;">
        <?php foreach($functions as $f): ?>
            <li><?= htmlspecialchars($f['file']) ?> :: <?= htmlspecialchars($f['function']) ?></li>
        <?php endforeach; ?>
    </ul>
    <h4>الروابط والربط</h4>
    <ul style="font-size:15px;line-height:2;">
        <?php foreach($links as $l): ?>
            <li><?= htmlspecialchars($l['link']) ?></li>
        <?php endforeach; ?>
    </ul>
    <p style="color:#888;font-size:13px;">هذه الصفحة مخصصة للمدير فقط وتستخدم لاختبار جميع أجزاء النظام فعلياً.</p>
</div>
