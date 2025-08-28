<?php
declare(strict_types=1);

/**
 * ملف التحميل الأولي (Bootstrap)
 * Bootstrap File
 *
 * هذا الملف يقوم بتحميل جميع الإعدادات والملفات المطلوبة بطريقة موحدة
 * يضمن أن جميع الملفات تستخدم نفس الطريقة للاتصال بقاعدة البيانات
 */

namespace App\Bootstrap;

use App\Config\DatabaseConfig;
use mysqli;

class Bootstrap
{
    private static bool $initialized = false;

    /**
     * تهيئة التطبيق
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::loadEnvironment();
        self::loadCoreFiles();
        self::initializeSession();
        self::testDatabaseConnection();

        self::$initialized = true;
    }

    /**
     * تحميل متغيرات البيئة
     */
    private static function loadEnvironment(): void
    {
        // تحميل ملف autoload إذا كان موجوداً
        $autoload_path = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
        }

        // تحميل متغيرات البيئة إذا كان ملف .env موجوداً
        $env_path = __DIR__ . '/../.env';
        if (file_exists($env_path) && class_exists('Dotenv\Dotenv')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }
    }

    /**
     * تحميل الملفات الأساسية
     */
    private static function loadCoreFiles(): void
    {
        $core_files = [
            __DIR__ . '/Core/Database.php',
            __DIR__ . '/Core/AuthCheck.php',
            __DIR__ . '/Core/Permissions.php',
            __DIR__ . '/Core/Helpers.php',
            __DIR__ . '/Core/InitialTasksQuery.php',
            __DIR__ . '/Core/RoleHelper.php',
            __DIR__ . '/Config/DatabaseConfig.php'
        ];

        foreach ($core_files as $file) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                throw new \Exception("الملف المطلوب غير موجود: " . basename($file) . " (المسار: $file)");
            }
        }
    }

    /**
     * تهيئة الجلسة
     */
    private static function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * اختبار اتصال قاعدة البيانات
     */
    private static function testDatabaseConnection(): void
    {
        try {
            $conn = DatabaseConfig::getConnection();
            if (!$conn->ping()) {
                throw new \Exception("فشل في اختبار الاتصال بقاعدة البيانات");
            }
        } catch (\Exception $e) {
            if (self::isProduction()) {
                error_log("Database connection test failed: " . $e->getMessage());
                die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
            } else {
                die("خطأ في اختبار الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()) .
                    "<br>يرجى التأكد من تشغيل XAMPP وإعداد قاعدة البيانات بشكل صحيح.");
            }
        }
    }

    /**
     * الحصول على اتصال قاعدة البيانات
     */
    public static function getDatabaseConnection(): mysqli
    {
        self::initialize();
        return DatabaseConfig::getConnection();
    }

    /**
     * فحص التوثيق
     */
    public static function checkAuthentication(): bool
    {
        $conn = self::getDatabaseConnection();
        return \App\Core\AuthCheck::isLoggedIn($conn);
    }

    /**
     * إعادة توجيه إلى صفحة تسجيل الدخول
     */
    public static function redirectToLogin(): void
    {
        $base_path = $_ENV['BASE_PATH'] ?? '/';
        header('Location: ' . $base_path . '/login');
        exit;
    }

    /**
     * فحص إذا كان التطبيق في بيئة الإنتاج
     */
    private static function isProduction(): bool
    {
        return getenv('APP_ENV') === 'production' ||
               $_ENV['APP_ENV'] === 'production' ||
               (!isset($_ENV['APP_ENV']) && !isset($_SERVER['HTTP_HOST']));
    }

    /**
     * تنظيف الموارد
     */
    public static function cleanup(): void
    {
        DatabaseConfig::closeConnection();
    }
}
