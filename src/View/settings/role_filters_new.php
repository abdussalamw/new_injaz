<?php
// src/View/settings/role_filters_new.php
// الواجهة الجديدة لإعدادات الفلترة - سهلة الاستخدام

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['مدير', 'admin'])) {
    http_response_code(403);
    echo '<h2 style="color:red;text-align:center;margin-top:50px">غير مصرح لك بالدخول لهذه الصفحة</h2>';
    exit;
}

$page_title = 'إعدادات الفلترة الجديدة - سهلة الاستخدام';

// الاتصال بقاعدة البيانات
$db_path = realpath(__DIR__ . '/../../Core/Database.php');
if ($db_path) {
    require_once $db_path;
} else {
    die('<div style="color:red;text-align:center;margin-top:50px">تعذر تحميل ملف قاعدة البيانات. تحقق من المسار.</div>');
}

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

// مسار ملف الإعدادات الجديد (بدون قاعدة بيانات)
$settings_file = __DIR__ . '/employee_filter_settings_new.json';

// دالة لقراءة الإعدادات من ملف JSON
function loadSettingsFromFile($file_path) {
    if (file_exists($file_path)) {
        $json = file_get_contents($file_path);
        return json_decode($json, true);
    }
    return [
        'employees' => [],
        'last_updated' => date('Y-m-d'),
        'version' => '1.0'
    ];
}

// دالة لحفظ الإعدادات في ملف JSON
function saveSettingsToFile($file_path, $settings) {
    $settings['last_updated'] = date('Y-m-d H:i:s');
    $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($file_path, $json);
}

// جلب البيانات الأساسية
$employees = $conn->query("SELECT employee_id, name, role FROM employees ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$contexts = [
    'dashboard' => 'لوحة المهام',
    'orders' => 'صفحة الطلبات',
    'reports' => 'التقارير'
];

// جلب الإعدادات من ملف JSON
$all_settings = loadSettingsFromFile($settings_file);

// جلب الإعدادات الحالية للموظف المحدد
$current_employee = $_GET['employee_id'] ?? null;
$current_context = $_GET['context'] ?? 'dashboard';
$current_settings = [];

// قراءة الإعدادات من ملف JSON
if ($current_employee && $current_context) {
    $employee_settings = $all_settings['employees'][$current_employee]['settings'][$current_context] ?? [];
    if (!empty($employee_settings)) {
        $current_settings = $employee_settings;
    }
}

// إعدادات افتراضية في حالة عدم وجود إعدادات
if (empty($current_settings)) {
    $current_settings = [
        'view_scope' => 'all',
        'show_stages' => [],
        'hide_stages' => [],
        'show_payment_stages' => [],
        'hide_payment_stages' => [],
        'exclude_combinations' => []
    ];
}

// التحقق من وجود الجدول (للتوافق مع النظام القديم)
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'employee_filter_settings'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}

// معالجة النماذج
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_filter'])) {
        // حفظ إعدادات الفلترة الجديدة
        $employee_id = (int)$_POST['employee_id'];
        $context = $_POST['context'];
        $view_scope = $_POST['view_scope'];

        // إعدادات الفلترة الأساسية
        $filter_settings = [
            'view_scope' => $view_scope,
            'show_stages' => $_POST['show_stages'] ?? [],
            'hide_stages' => $_POST['hide_stages'] ?? [],
            'show_payment_stages' => $_POST['show_payment_stages'] ?? [],
            'hide_payment_stages' => $_POST['hide_payment_stages'] ?? [],
            'exclude_combinations' => []
        ];

        // إعدادات التركيبات المستبعدة
        if (isset($_POST['exclude_status']) && isset($_POST['exclude_payment'])) {
            foreach ($_POST['exclude_status'] as $index => $status) {
                if (!empty($status) && !empty($_POST['exclude_payment'][$index])) {
                    $filter_settings['exclude_combinations'][] = [
                        'status' => $status,
                        'payment_status' => $_POST['exclude_payment'][$index]
                    ];
                }
            }
        }

        // حفظ في ملف JSON
        if (!isset($all_settings['employees'][$employee_id])) {
            // إنشاء إدخال جديد للموظف
            $employee_data = null;
            foreach ($employees as $emp) {
                if ($emp['employee_id'] == $employee_id) {
                    $employee_data = $emp;
                    break;
                }
            }

            if ($employee_data) {
                $all_settings['employees'][$employee_id] = [
                    'name' => $employee_data['name'],
                    'role' => $employee_data['role'],
                    'settings' => []
                ];
            }
        }

        // حفظ الإعدادات للصفحة المحددة
        $all_settings['employees'][$employee_id]['settings'][$context] = $filter_settings;

        if (saveSettingsToFile($settings_file, $all_settings)) {
            $message = "✅ تم حفظ إعدادات الفلترة بنجاح في ملف JSON!";
        } else {
            $message = "❌ حدث خطأ أثناء الحفظ في ملف JSON";
        }
    }
}

// تعريف الحالات المتاحة
$all_statuses = [
    'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم', 'تأكيد استلام العميل', 'مكتمل', 'ملغي'
];

$payment_statuses = [
    'غير مدفوع', 'مدفوع', 'مدفوع جزئياً'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الفلترة المتقدمة - نظام إنجاز الإعلامية</title>

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
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            direction: rtl;
            line-height: 1.6;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .page-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .setup-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .section-icon {
            background: var(--primary-gradient);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-left: 1rem;
        }

        .section-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
        }

        .section-description {
            color: #6c757d;
            margin: 0;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .radio-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .radio-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
        }

        .radio-card:hover {
            border-color: #667eea;
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .radio-card.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .radio-card input[type="radio"] {
            display: none;
        }

        .radio-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .radio-description {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .checkbox-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkbox-card:hover {
            border-color: #667eea;
            background: #fff;
        }

        .checkbox-card.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .checkbox-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .combination-builder {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .combination-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .combination-row:last-child {
            margin-bottom: 0;
        }

        .btn-add-combination {
            background: var(--success-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-add-combination:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(79, 172, 254, 0.3);
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .preview-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .preview-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .preview-icon {
            background: var(--warning-gradient);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
        }

        .preview-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.3rem;
            margin: 0;
        }

        .preview-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e9ecef;
        }

        .preview-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .preview-value {
            color: #6c757d;
        }

        .alert-custom {
            background: var(--success-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 500;
        }

        .btn-primary-custom {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary-custom:active {
            transform: translateY(0);
        }

        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 1px solid #90caf9;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-box-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .info-icon {
            background: #2196f3;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
        }

        .info-title {
            color: #1565c0;
            font-weight: 600;
            margin: 0;
        }

        .step-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 0.9rem;
            margin-left: 1rem;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .radio-group {
                grid-template-columns: 1fr;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .combination-row {
                flex-direction: column;
                gap: 0.5rem;
            }

            .section-header {
                flex-direction: column;
                text-align: center;
            }

            .section-icon {
                margin-left: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <?php if (!empty($message)): ?>
            <div class="alert-custom">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$table_exists): ?>
            <div class="info-box">
                <div class="info-box-header">
                    <div class="info-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <h5 class="info-title">النظام الجديد يعمل بملفات JSON</h5>
                </div>
                <p style="margin: 0; color: #1565c0;">
                    النظام الجديد يحفظ الإعدادات في ملف <code>employee_filter_settings_new.json</code> بدلاً من قاعدة البيانات.
                    هذا يجعل الرفع للاستضافة أسهل ولا يتطلب تعديل قاعدة البيانات.
                </p>
            </div>
        <?php endif; ?>

        <form method="post" id="filterForm">
            <!-- الخطوة 1: اختيار الموظف -->
            <div class="setup-section">
                <div class="section-header">
                    <div class="section-icon">
                        <span class="step-indicator">1</span>
                    </div>
                    <div>
                        <h3 class="section-title">
                            <i class="bi bi-person-fill me-2"></i>
                            اختيار الموظف
                        </h3>
                        <p class="section-description">
                            اختر الموظف الذي تريد تخصيص إعدادات العرض له
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="employee_id" class="form-label">
                        <i class="bi bi-person-badge me-2"></i>
                        الموظف:
                    </label>
                    <select name="employee_id" id="employee_id" class="form-select" required onchange="updateForm()">
                        <option value="">اختر موظف...</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['employee_id'] ?>" <?= $current_employee == $emp['employee_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- الخطوة 2: اختيار الصفحة -->
            <div class="setup-section">
                <div class="section-header">
                    <div class="section-icon">
                        <span class="step-indicator">2</span>
                    </div>
                    <div>
                        <h3 class="section-title">
                            <i class="bi bi-window me-2"></i>
                            اختيار الصفحة
                        </h3>
                        <p class="section-description">
                            اختر الصفحة التي تريد تطبيق هذه الإعدادات عليها
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="context" class="form-label">
                        <i class="bi bi-grid me-2"></i>
                        الصفحة:
                    </label>
                    <select name="context" id="context" class="form-select" required onchange="updateForm()">
                        <?php foreach ($contexts as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $current_context == $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- الخطوة 3: نطاق العرض -->
            <div class="setup-section">
                <div class="section-header">
                    <div class="section-icon">
                        <span class="step-indicator">3</span>
                    </div>
                    <div>
                        <h3 class="section-title">
                            <i class="bi bi-eye me-2"></i>
                            نطاق العرض
                        </h3>
                        <p class="section-description">
                            اختر المهام التي يمكن للموظف رؤيتها
                        </p>
                    </div>
                </div>

                <div class="radio-group">
                    <label class="radio-card <?= ($current_settings['view_scope'] ?? '') === 'all' ? 'selected' : '' ?>">
                        <input type="radio" name="view_scope" value="all" <?= ($current_settings['view_scope'] ?? '') === 'all' ? 'checked' : '' ?>>
                        <div class="radio-title">
                            <i class="bi bi-globe me-2"></i>
                            جميع المهام
                        </div>
                        <div class="radio-description">
                            يرى جميع المهام في النظام
                        </div>
                    </label>

                    <label class="radio-card <?= ($current_settings['view_scope'] ?? '') === 'role' ? 'selected' : '' ?>">
                        <input type="radio" name="view_scope" value="role" <?= ($current_settings['view_scope'] ?? '') === 'role' ? 'checked' : '' ?>>
                        <div class="radio-title">
                            <i class="bi bi-people me-2"></i>
                            جميع مهام الدور
                        </div>
                        <div class="radio-description">
                            يرى مهام جميع موظفي نفس الدور
                        </div>
                    </label>

                    <label class="radio-card <?= ($current_settings['view_scope'] ?? '') === 'self' ? 'selected' : '' ?>">
                        <input type="radio" name="view_scope" value="self" <?= ($current_settings['view_scope'] ?? '') === 'self' ? 'checked' : '' ?>>
                        <div class="radio-title">
                            <i class="bi bi-person me-2"></i>
                            مهامه فقط
                        </div>
                        <div class="radio-description">
                            يرى مهامه الخاصة فقط
                        </div>
                    </label>
                </div>
            </div>

            <!-- الخطوة 4: الفلاتر التخصصية -->
            <div class="setup-section">
                <div class="section-header">
                    <div class="section-icon">
                        <span class="step-indicator">4</span>
                    </div>
                    <div>
                        <h3 class="section-title">
                            <i class="bi bi-sliders me-2"></i>
                            الفلاتر التخصصية
                        </h3>
                        <p class="section-description">
                            اختر فلاتر إضافية لتخصيص العرض أكثر
                        </p>
                    </div>
                </div>

                <!-- عرض المهام في حالات معينة -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-check-circle me-2"></i>
                        عرض المهام في الحالات التالية:
                    </label>
                    <div class="checkbox-grid">
                        <?php foreach ($all_statuses as $status): ?>
                            <label class="checkbox-card <?= in_array($status, $current_settings['show_stages'] ?? []) ? 'selected' : '' ?>">
                                <input type="checkbox" name="show_stages[]" value="<?= $status ?>"
                                       <?= in_array($status, $current_settings['show_stages'] ?? []) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($status) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- إخفاء المهام في حالات معينة -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-x-circle me-2"></i>
                        إخفاء المهام في الحالات التالية:
                    </label>
                    <div class="checkbox-grid">
                        <?php foreach ($all_statuses as $status): ?>
                            <label class="checkbox-card <?= in_array($status, $current_settings['hide_stages'] ?? []) ? 'selected' : '' ?>">
                                <input type="checkbox" name="hide_stages[]" value="<?= $status ?>"
                                       <?= in_array($status, $current_settings['hide_stages'] ?? []) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($status) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- شروط مزدوجة -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-link me-2"></i>
                        شروط مزدوجة (إخفاء المهام التي تطابق الشرطين):
                    </label>
                    <div class="combination-builder">
                        <div id="combinations-container">
                            <?php
                            $combinations = $current_settings['exclude_combinations'] ?? [];
                            if (empty($combinations)) {
                                $combinations = [['status' => '', 'payment_status' => '']];
                            }
                            foreach ($combinations as $index => $comb):
                            ?>
                            <div class="combination-row">
                                <select name="exclude_status[]" class="form-select">
                                    <option value="">اختر حالة الطلب...</option>
                                    <?php foreach ($all_statuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $comb['status'] === $status ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <span style="color: #667eea; font-weight: 500;">
                                    <i class="bi bi-arrow-left me-1"></i>
                                    و
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </span>

                                <select name="exclude_payment[]" class="form-select">
                                    <option value="">اختر حالة الدفع...</option>
                                    <?php foreach ($payment_statuses as $payment): ?>
                                        <option value="<?= $payment ?>" <?= $comb['payment_status'] === $payment ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($payment) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="button" class="btn-remove" onclick="removeCombination(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn-add-combination" onclick="addCombination()">
                            <i class="bi bi-plus-circle me-1"></i>
                            إضافة شرط مزدوج جديد
                        </button>
                    </div>
                </div>
            </div>

            <!-- معاينة الإعدادات -->
            <div class="preview-section">
                <div class="preview-header">
                    <div class="preview-icon">
                        <i class="bi bi-eye"></i>
                    </div>
                    <h4 class="preview-title">
                        <i class="bi bi-search me-2"></i>
                        معاينة الإعدادات
                    </h4>
                </div>

                <div id="preview-content">
                    <?php if ($current_employee && $current_context): ?>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-person me-2"></i>
                                الموظف:
                            </div>
                            <div class="preview-value">
                                <?= htmlspecialchars($employees[array_search($current_employee, array_column($employees, 'employee_id'))]['name'] ?? 'غير معروف') ?>
                            </div>
                        </div>

                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-window me-2"></i>
                                الصفحة:
                            </div>
                            <div class="preview-value">
                                <?= htmlspecialchars($contexts[$current_context] ?? $current_context) ?>
                            </div>
                        </div>

                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-eye me-2"></i>
                                نطاق العرض:
                            </div>
                            <div class="preview-value">
                                <?php
                                $scope_labels = [
                                    'all' => 'جميع المهام',
                                    'role' => 'جميع مهام الدور',
                                    'self' => 'مهامه فقط'
                                ];
                                echo htmlspecialchars($scope_labels[$current_settings['view_scope'] ?? ''] ?? 'غير محدد');
                                ?>
                            </div>
                        </div>

                        <?php if (!empty($current_settings['show_stages'])): ?>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-check-circle me-2"></i>
                                سيتم عرض المهام في:
                            </div>
                            <div class="preview-value">
                                <?= htmlspecialchars(implode(' • ', $current_settings['show_stages'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($current_settings['hide_stages'])): ?>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-x-circle me-2"></i>
                                سيتم إخفاء المهام في:
                            </div>
                            <div class="preview-value">
                                <?= htmlspecialchars(implode(' • ', $current_settings['hide_stages'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($current_settings['exclude_combinations'])): ?>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-link me-2"></i>
                                سيتم إخفاء التركيبات:
                            </div>
                            <div class="preview-value">
                                <?php foreach ($current_settings['exclude_combinations'] as $comb): ?>
                                    <div style="margin-bottom: 0.25rem;">
                                        • <?= htmlspecialchars($comb['status']) ?> مع <?= htmlspecialchars($comb['payment_status']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="bi bi-lightbulb me-2"></i>
                                النتيجة المتوقعة:
                            </div>
                            <div class="preview-value">
                                <?php
                                $scope = $current_settings['view_scope'] ?? '';
                                $show_stages = $current_settings['show_stages'] ?? [];
                                $hide_stages = $current_settings['hide_stages'] ?? [];
                                $exclude_combinations = $current_settings['exclude_combinations'] ?? [];

                                if ($scope === 'all') {
                                    echo 'المدير سيستبعد المهام المكتملة والمدفوعة معاً فقط';
                                } elseif ($scope === 'role') {
                                    echo 'الموظف سيظهر مهام جميع موظفي نفس الدور';
                                    if (!empty($show_stages)) {
                                        echo '<br>• لكن سيظهر المهام في الحالات: ' . implode(', ', $show_stages) . ' فقط';
                                    }
                                    if (!empty($hide_stages)) {
                                        echo '<br>• وسيخفي المهام في الحالات: ' . implode(', ', $hide_stages);
                                    }
                                } elseif ($scope === 'self') {
                                    echo 'الموظف سيظهر مهامه الخاصة فقط';
                                    if (!empty($show_stages)) {
                                        echo '<br>• لكن سيظهر المهام في الحالات: ' . implode(', ', $show_stages) . ' فقط';
                                    }
                                    if (!empty($hide_stages)) {
                                        echo '<br>• وسيخفي المهام في الحالات: ' . implode(', ', $hide_stages);
                                    }
                                }

                                if (!empty($exclude_combinations)) {
                                    echo '<br>• بالإضافة إلى إخفاء التركيبات المحددة';
                                }
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: #6c757d; padding: 2rem;">
                            <i class="bi bi-info-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>اختر الموظف والصفحة لرؤية معاينة الإعدادات</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- حفظ الإعدادات -->
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" name="save_filter" class="btn-primary-custom">
                    <i class="bi bi-save me-2"></i>
                    حفظ الإعدادات
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function updateForm() {
            const employeeId = document.getElementById('employee_id').value;
            const context = document.getElementById('context').value;

            if (employeeId && context) {
                const url = new URL(window.location);
                url.searchParams.set('employee_id', employeeId);
                url.searchParams.set('context', context);
                window.location.href = url.toString();
            }
        }

        function addCombination() {
            const container = document.getElementById('combinations-container');
            const newRow = document.createElement('div');
            newRow.className = 'combination-row';
            newRow.innerHTML = `
                <select name="exclude_status[]" class="form-select">
                    <option value="">اختر حالة الطلب...</option>
                    <?php foreach ($all_statuses as $status): ?>
                        <option value="<?= $status ?>"><?= htmlspecialchars($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <span style="color: #667eea; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i>
                    و
                    <i class="bi bi-arrow-right ms-1"></i>
                </span>
                <select name="exclude_payment[]" class="form-select">
                    <option value="">اختر حالة الدفع...</option>
                    <?php foreach ($payment_statuses as $payment): ?>
                        <option value="<?= $payment ?>"><?= htmlspecialchars($payment) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-remove" onclick="removeCombination(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
        }

        function removeCombination(button) {
            button.parentElement.remove();
        }

        // تحسين تفاعل العناصر
        document.addEventListener('DOMContentLoaded', function() {
            // تحسين أزرار الراديو
            document.querySelectorAll('.radio-card').forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        // إزالة التحديد من الآخرين
                        document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                    }
                });
            });

            // تحسين خانات الاختيار
            document.querySelectorAll('.checkbox-card').forEach(card => {
                card.addEventListener('click', function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        this.classList.toggle('selected');
                    }
                });
            });

            // تحسين الرسائل
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-custom');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>
