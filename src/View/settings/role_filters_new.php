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
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }

        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, #D44759 0%, #F37D47 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header-section h2 {
            margin: 0;
            font-weight: 700;
            font-size: 2.5rem;
        }

        .header-section p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content-section {
            padding: 3rem;
        }

        .setup-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .setup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #D44759;
        }

        .step-number {
            background: #D44759;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .radio-group {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .radio-option {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 200px;
            text-align: center;
        }

        .radio-option:hover {
            border-color: #D44759;
            background: #fff5f5;
        }

        .radio-option.selected {
            border-color: #D44759;
            background: #D44759;
            color: white;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .checkbox-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkbox-item:hover {
            border-color: #D44759;
            background: #fff5f5;
        }

        .checkbox-item.selected {
            border-color: #D44759;
            background: #D44759;
            color: white;
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

        .btn-custom {
            background: linear-gradient(135deg, #D44759 0%, #F37D47 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 71, 89, 0.4);
        }

        .alert-custom {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .preview-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .preview-section h4 {
            color: #D44759;
            margin-bottom: 1rem;
        }

        .preview-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h2>🎯 إعدادات الفلترة الجديدة</h2>
            <p>سهلة الاستخدام - خطوة بخطوة</p>
        </div>

        <div class="content-section">
            <?php if (!empty($message)): ?>
                <div class="alert-custom">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!$table_exists): ?>
                <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 10px; padding: 1rem; margin-bottom: 2rem;">
                    <h5>ℹ️ النظام الجديد يعمل بملفات JSON</h5>
                    <p>النظام الجديد يحفظ الإعدادات في ملف <code>employee_filter_settings_new.json</code> بدلاً من قاعدة البيانات.</p>
                    <p>هذا يجعل الرفع للاستضافة أسهل ولا يتطلب تعديل قاعدة البيانات.</p>
                </div>
            <?php endif; ?>

            <form method="post" id="filterForm">
                <!-- الخطوة 1: اختيار الموظف -->
                <div class="setup-card">
                    <div class="step-number">1</div>
                    <h4>👤 اختر الموظف</h4>
                    <p>اختر الموظف الذي تريد تخصيص إعدادات العرض له</p>

                    <div class="form-group">
                        <label for="employee_id">الموظف:</label>
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
                <div class="setup-card">
                    <div class="step-number">2</div>
                    <h4>📄 اختر الصفحة</h4>
                    <p>اختر الصفحة التي تريد تطبيق هذه الإعدادات عليها</p>

                    <div class="form-group">
                        <label for="context">الصفحة:</label>
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
                <div class="setup-card">
                    <div class="step-number">3</div>
                    <h4>🔍 نطاق العرض</h4>
                    <p>اختر المهام التي يمكن للموظف رؤيتها</p>

                    <div class="radio-group">
                        <label class="radio-option <?= ($current_settings['view_scope'] ?? '') === 'all' ? 'selected' : '' ?>">
                            <input type="radio" name="view_scope" value="all" <?= ($current_settings['view_scope'] ?? '') === 'all' ? 'checked' : '' ?>>
                            <div>
                                <strong>جميع المهام</strong><br>
                                <small>يرى جميع المهام في النظام</small>
                            </div>
                        </label>

                        <label class="radio-option <?= ($current_settings['view_scope'] ?? '') === 'role' ? 'selected' : '' ?>">
                            <input type="radio" name="view_scope" value="role" <?= ($current_settings['view_scope'] ?? '') === 'role' ? 'checked' : '' ?>>
                            <div>
                                <strong>جميع مهام الدور</strong><br>
                                <small>يرى مهام جميع موظفي نفس الدور</small>
                            </div>
                        </label>

                        <label class="radio-option <?= ($current_settings['view_scope'] ?? '') === 'self' ? 'selected' : '' ?>">
                            <input type="radio" name="view_scope" value="self" <?= ($current_settings['view_scope'] ?? '') === 'self' ? 'checked' : '' ?>>
                            <div>
                                <strong>مهامه فقط</strong><br>
                                <small>يرى مهامه الخاصة فقط</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- الخطوة 4: الفلاتر التخصصية -->
                <div class="setup-card">
                    <div class="step-number">4</div>
                    <h4>🎛️ الفلاتر التخصصية</h4>
                    <p>اختر فلاتر إضافية لتخصيص العرض أكثر</p>

                    <!-- عرض المهام في حالات معينة -->
                    <div class="form-group">
                        <label>✅ عرض المهام في الحالات التالية:</label>
                        <div class="checkbox-grid">
                            <?php foreach ($all_statuses as $status): ?>
                                <label class="checkbox-item <?= in_array($status, $current_settings['show_stages'] ?? []) ? 'selected' : '' ?>">
                                    <input type="checkbox" name="show_stages[]" value="<?= $status ?>"
                                           <?= in_array($status, $current_settings['show_stages'] ?? []) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- إخفاء المهام في حالات معينة -->
                    <div class="form-group">
                        <label>❌ إخفاء المهام في الحالات التالية:</label>
                        <div class="checkbox-grid">
                            <?php foreach ($all_statuses as $status): ?>
                                <label class="checkbox-item <?= in_array($status, $current_settings['hide_stages'] ?? []) ? 'selected' : '' ?>">
                                    <input type="checkbox" name="hide_stages[]" value="<?= $status ?>"
                                           <?= in_array($status, $current_settings['hide_stages'] ?? []) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- شروط مزدوجة -->
                    <div class="form-group">
                        <label>🔗 شروط مزدوجة (إخفاء المهام التي تطابق الشرطين):</label>
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

                                <span>و</span>

                                <select name="exclude_payment[]" class="form-select">
                                    <option value="">اختر حالة الدفع...</option>
                                    <?php foreach ($payment_statuses as $payment): ?>
                                        <option value="<?= $payment ?>" <?= $comb['payment_status'] === $payment ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($payment) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="button" class="btn btn-danger btn-sm" onclick="removeCombination(this)">حذف</button>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn-custom" onclick="addCombination()">
                            ➕ إضافة شرط مزدوج جديد
                        </button>
                    </div>
                </div>

                <!-- معاينة الإعدادات -->
                <div class="preview-section">
                    <h4>👁️ معاينة الإعدادات</h4>
                    <div id="preview-content">
                        <?php if ($current_employee && $current_context): ?>
                            <div class="preview-item">
                                <strong>الموظف:</strong> <?= htmlspecialchars($employees[array_search($current_employee, array_column($employees, 'employee_id'))]['name'] ?? 'غير معروف') ?>
                            </div>
                            <div class="preview-item">
                                <strong>الصفحة:</strong> <?= htmlspecialchars($contexts[$current_context] ?? $current_context) ?>
                            </div>
                            <div class="preview-item">
                                <strong>نطاق العرض:</strong>
                                <?php
                                $scope_labels = [
                                    'all' => 'جميع المهام',
                                    'role' => 'جميع مهام الدور',
                                    'self' => 'مهامه فقط'
                                ];
                                echo htmlspecialchars($scope_labels[$current_settings['view_scope'] ?? ''] ?? 'غير محدد');
                                ?>
                            </div>

                            <?php if (!empty($current_settings['show_stages'])): ?>
                            <div class="preview-item">
                                <strong>✅ سيتم عرض المهام في:</strong> <?= htmlspecialchars(implode(', ', $current_settings['show_stages'])) ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($current_settings['hide_stages'])): ?>
                            <div class="preview-item">
                                <strong>❌ سيتم إخفاء المهام في:</strong> <?= htmlspecialchars(implode(', ', $current_settings['hide_stages'])) ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($current_settings['exclude_combinations'])): ?>
                            <div class="preview-item">
                                <strong>🔗 سيتم إخفاء المهام التي تطابق:</strong>
                                <?php foreach ($current_settings['exclude_combinations'] as $comb): ?>
                                    <br>• <?= htmlspecialchars($comb['status']) ?> + <?= htmlspecialchars($comb['payment_status']) ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="preview-item">
                                <strong>📝 النتيجة المتوقعة:</strong><br>
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
                        <?php else: ?>
                            <p>اختر الموظف والصفحة لرؤية معاينة الإعدادات</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- حفظ الإعدادات -->
                <div class="text-center">
                    <button type="submit" name="save_filter" class="btn-custom">
                        💾 حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                <span>و</span>
                <select name="exclude_payment[]" class="form-select">
                    <option value="">اختر حالة الدفع...</option>
                    <?php foreach ($payment_statuses as $payment): ?>
                        <option value="<?= $payment ?>"><?= htmlspecialchars($payment) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeCombination(this)">حذف</button>
            `;
            container.appendChild(newRow);
        }

        function removeCombination(button) {
            button.parentElement.remove();
        }

        // تحسين تفاعل العناصر
        document.addEventListener('DOMContentLoaded', function() {
            // تحسين أزرار الراديو
            document.querySelectorAll('.radio-option').forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        // إزالة التحديد من الآخرين
                        document.querySelectorAll('.radio-option').forEach(opt => opt.classList.remove('selected'));
                        this.classList.add('selected');
                    }
                });
            });

            // تحسين خانات الاختيار
            document.querySelectorAll('.checkbox-item').forEach(item => {
                item.addEventListener('click', function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        this.classList.toggle('selected');
                    }
                });
            });
        });
    </script>
</body>
</html>
