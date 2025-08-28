<?php
/**
 * ملف التحميل التلقائي للفئات المركزية
 * يوحد تحميل جميع الفئات في النظام
 */

// تحديد مسار المجلد الجذر للمشروع
define('PROJECT_ROOT', dirname(__DIR__, 2));

// تحديد مسارات الفئات
define('CONFIG_PATH', PROJECT_ROOT . '/src/Config/');
define('CONTROLLER_PATH', PROJECT_ROOT . '/src/Controller/');
define('CORE_PATH', PROJECT_ROOT . '/src/Core/');
define('API_PATH', PROJECT_ROOT . '/src/Api/');
define('PAYMENTS_PATH', PROJECT_ROOT . '/src/Payments/');
define('REPORTS_PATH', PROJECT_ROOT . '/src/Reports/');
define('VIEW_PATH', PROJECT_ROOT . '/src/View/');
define('AUTH_PATH', PROJECT_ROOT . '/src/Auth/');

/**
 * دالة التحميل التلقائي المخصصة
 */
spl_autoload_register(function ($class_name) {
    // تحويل اسم الفئة إلى مسار ملف
    $class_path = str_replace('\\', '/', $class_name);
    $class_path = str_replace('App/', '', $class_path);

    // قائمة بالمسارات المحتملة للفئات
    $possible_paths = [
        PROJECT_ROOT . '/' . $class_path . '.php',
        CONFIG_PATH . basename($class_path) . '.php',
        CONTROLLER_PATH . basename($class_path) . '.php',
        CORE_PATH . basename($class_path) . '.php',
        API_PATH . basename($class_path) . '.php',
        PAYMENTS_PATH . basename($class_path) . '.php',
        REPORTS_PATH . basename($class_path) . '.php',
        VIEW_PATH . basename($class_path) . '.php',
        AUTH_PATH . basename($class_path) . '.php',
    ];

    // البحث عن الملف في المسارات المحتملة
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }

    // إذا لم يتم العثور على الفئة، تسجيل خطأ
    error_log("فئة غير موجودة: {$class_name}");
});

/**
 * تحميل الفئات الأساسية تلقائياً
 */
require_once CONFIG_PATH . 'Constants.php';
require_once CONFIG_PATH . 'Config.php';
require_once CONFIG_PATH . 'RoleManager.php';
require_once CONFIG_PATH . 'TaskManager.php';
require_once CONFIG_PATH . 'ReportManager.php';
require_once CONFIG_PATH . 'DatabaseManager.php';
require_once CONFIG_PATH . 'NotificationManager.php';

/**
 * دالة للتحقق من تحميل الفئات الأساسية
 */
function verifyCoreClassesLoaded(): array
{
    $core_classes = [
        'App\Config\Constants',
        'App\Config\Config',
        'App\Config\RoleManager',
        'App\Config\TaskManager',
        'App\Config\ReportManager',
        'App\Config\DatabaseManager',
        'App\Config\NotificationManager'
    ];

    $loaded = [];
    $missing = [];

    foreach ($core_classes as $class) {
        if (class_exists($class)) {
            $loaded[] = $class;
        } else {
            $missing[] = $class;
        }
    }

    return [
        'loaded' => $loaded,
        'missing' => $missing,
        'all_loaded' => empty($missing)
    ];
}

/**
 * دالة لإعادة تهيئة النظام
 */
function reinitializeSystem(): void
{
    // تنظيف المتغيرات العامة إذا لزم الأمر
    if (isset($GLOBALS['system_initialized'])) {
        unset($GLOBALS['system_initialized']);
    }

    // إعادة تحميل الفئات الأساسية
    $verification = verifyCoreClassesLoaded();

    if (!$verification['all_loaded']) {
        error_log("فشل تحميل بعض الفئات الأساسية: " . implode(', ', $verification['missing']));
        throw new Exception("فشل تهيئة النظام - فئات مفقودة");
    }

    $GLOBALS['system_initialized'] = true;
}

/**
 * دالة للحصول على معلومات النظام
 */
function getSystemInfo(): array
{
    return [
        'project_root' => PROJECT_ROOT,
        'config_path' => CONFIG_PATH,
        'php_version' => PHP_VERSION,
        'core_classes_loaded' => verifyCoreClassesLoaded()['all_loaded'],
        'timezone' => date_default_timezone_get(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ];
}

// تهيئة النظام عند التحميل
reinitializeSystem();
?>
