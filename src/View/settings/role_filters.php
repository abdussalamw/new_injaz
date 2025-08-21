<?php
// src/View/settings/role_filters.php
// صفحة إعدادات الفلترة للأدوار والصلاحيات (مخفية، لا تظهر إلا لمن يدخل بالرابط)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['مدير', 'admin'])) {
    http_response_code(403);
    echo '<h2 style="color:red;text-align:center;margin-top:50px">غير مصرح لك بالدخول لهذه الصفحة</h2>';
    exit;
}

$page_title = 'إعدادات الفلترة للأدوار والصلاحيات';

// نموذج الإعدادات (بسيط، قابل للتوسعة)
$roles = ['مدير', 'مصمم', 'معمل', 'محاسب', 'موظف'];
$contexts = ['orders' => 'صفحة الطلبات', 'dashboard' => 'لوحة المهام', 'reports' => 'التقارير'];

// إعدادات افتراضية (قابلة للتعديل)

$settings_file = __DIR__ . '/role_filters.json';
// جلب الحالات الفعلية من قاعدة البيانات
$db_path = realpath(__DIR__ . '/../../Core/Database.php');
if ($db_path) {
    require_once $db_path;
} else {
    die('<div style="color:red;text-align:center;margin-top:50px">تعذر تحميل ملف قاعدة البيانات. تحقق من المسار.</div>');
}
// إعداد بيانات الاتصال من ملف البيئة أو مباشرة
$env_path = realpath(__DIR__ . '/../../../.env');
if ($env_path && file_exists($env_path)) {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname($env_path));
        $dotenv->load();
    }
}
$db_host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$db_user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$db_pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$db_name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'injaz');
$db = new \App\Core\Database($db_host, $db_user, $db_pass, $db_name);
$conn = $db->getConnection();
$all_statuses = [
    'قيد التصميم' => 'قيد التصميم',
    'قيد التنفيذ' => 'قيد التنفيذ',
    'جاهز للتسليم' => 'جاهز للتسليم',
    'تأكيد استلام العميل' => 'تأكيد استلام العميل',
    'مكتمل' => 'مكتمل',
    'ملغي' => 'ملغي'
];

// إعدادات افتراضية جديدة أكثر مرونة
$default_filters = [];
foreach ($contexts as $context => $label) {
    $default_filters[$context] = [];
    foreach ($roles as $role) {
        $default_filters[$context][$role] = [
            'enabled' => true,
            'filter_type' => 'show', // show: يعرض فقط المراحل المحددة، hide: يخفي فقط المراحل المحددة
            'stages' => [] // قائمة المراحل المختارة
        ];
    }
}

// تحميل الإعدادات من الملف إذا وجد
// لا حاجة لتصحيح القيم القديمة بعد الآن، النظام يعتمد فقط على البنية الجديدة

// تحميل الإعدادات من الملف إذا وجد
if (file_exists($settings_file)) {
    $json = file_get_contents($settings_file);
    $saved_filters = json_decode($json, true);
    if (is_array($saved_filters)) {
        $default_filters = array_replace_recursive($default_filters, $saved_filters);
    }
}

// معالجة زر التشغيل/التعطيل


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'], $_POST['context'])) {
    $role = $_POST['role'];
    $context = $_POST['context'];
    if (isset($default_filters[$context][$role])) {
        // زر التشغيل/التعطيل
        if (isset($_POST['toggle'])) {
            $default_filters[$context][$role]['enabled'] = !$default_filters[$context][$role]['enabled'];
            $message = "تم تحديث الفلتر لدور $role في $context بنجاح!";
        }
        // تغيير نوع الفلترة
        if (isset($_POST['filter_type'])) {
            $default_filters[$context][$role]['filter_type'] = $_POST['filter_type'];
            $message = "تم تحديث نوع الفلترة لدور $role في $context بنجاح!";
        }
        // تغيير المراحل
        if (isset($_POST['stages']) && is_array($_POST['stages'])) {
            $default_filters[$context][$role]['stages'] = $_POST['stages'];
            $message = "تم تحديث المراحل لدور $role في $context بنجاح!";
        }
        // حفظ التغيير في الملف
        file_put_contents($settings_file, json_encode($default_filters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background-color: #f8f9fa;
            direction: rtl;
        }
        .settings-container {
            max-width: 950px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 30px 40px;
        }
        h2 {
            color: #D44759;
            text-align: center;
            margin-bottom: 35px;
            font-weight: bold;
        }
        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
            padding: 1rem;
            border-radius: .5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 25px;
            border-bottom: 2px solid #dee2e6;
        }
        .tab-link {
            background: none;
            border: none;
            color: #495057;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 24px;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            bottom: -2px;
        }
        .tab-link:hover {
            color: #D44759;
        }
        .tab-link.active {
            background: #fff;
            color: #D44759;
            border: 2px solid #dee2e6;
            border-bottom-color: #fff;
        }
        .tab-pane {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }
        .tab-pane.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(1
    </style>
</head>
<body>
    <div class="settings-container">
        <h2><?= htmlspecialchars($page_title) ?></h2>
        <?php if (!empty($message)): ?>
            <div style="background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <table>
            <tr>
                <th>الدور</th>
                <?php foreach ($contexts as $key => $label): ?>
                    <th><?= htmlspecialchars($label) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($role) ?></strong></td>
                    <?php foreach ($contexts as $context => $label): ?>
                        <td style="vertical-align:top;">
                            <form method="post" style="display:inline; margin-bottom:5px;">
                                <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                                <input type="hidden" name="context" value="<?= htmlspecialchars($context) ?>">
                                <button type="submit" name="toggle" class="btn" style="margin-bottom:5px;">
                                    <?= $default_filters[$context][$role]['enabled'] ? 'مفعل' : 'معطل' ?>
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                                <input type="hidden" name="context" value="<?= htmlspecialchars($context) ?>">
                                <label style="font-size:13px;">نوع الفلترة:</label>
                                <select name="filter_type" onchange="this.form.submit()" style="margin-bottom:5px;">
                                    <option value="show" <?= $default_filters[$context][$role]['filter_type'] === 'show' ? 'selected' : '' ?>>يعرض فقط المراحل التالية</option>
                                    <option value="hide" <?= $default_filters[$context][$role]['filter_type'] === 'hide' ? 'selected' : '' ?>>يخفي فقط المراحل التالية</option>
                                </select>
                                <div style="margin-top:5px;">
                                    <?php
                                    // الحالات التي يجب إخفاؤها من واجهة الإعدادات فقط
                                    foreach ($all_statuses as $skey => $slabel) {
                                        ?>
                                        <label style="display:block;font-size:13px;">
                                            <input type="checkbox" name="stages[]" value="<?= $skey ?>" <?= in_array($skey, $default_filters[$context][$role]['stages']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                            <?= htmlspecialchars($slabel) ?>
                                        </label>
                                    <?php }
                                    ?>
                                </div>
                            </form>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <p style="text-align:center;color:#888;font-size:13px;">هذه الصفحة مخفية ولا يمكن الوصول إليها إلا من الرابط المباشر، وتظهر فقط للمدير أو الأدمن.</p>
    </div>
</body>
</html>
