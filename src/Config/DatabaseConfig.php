<?php
declare(strict_types=1);

/**
 * ملف إعدادات قاعدة البيانات الموحد
 * Unified Database Configuration File
 *
 * هذا الملف يوفر طريقة موحدة وآمنة للاتصال بقاعدة البيانات
 * يستخدم فئة Database المخصصة مع معالجة أخطاء شاملة
 */

namespace App\Config;

use App\Core\Database;
use mysqli;

class DatabaseConfig
{
    private static ?Database $instance = null;
    private static ?mysqli $connection = null;

    /**
     * الحصول على اتصال قاعدة البيانات (Singleton Pattern)
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        try {
            // تحميل متغيرات البيئة إذا كانت متوفرة
            self::loadEnvironmentVariables();

            // إنشاء اتصال قاعدة البيانات
            $db = new Database(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                $_ENV['DB_NAME'] ?? 'injaz'
            );

            self::$instance = $db;
            self::$connection = $db->getConnection();

            return self::$connection;

        } catch (\Exception $e) {
            // معالجة الأخطاء حسب البيئة
            if (self::isProduction()) {
                error_log("Database connection failed: " . $e->getMessage());
                die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
            } else {
                die("خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()) .
                    "<br>يرجى التأكد من تشغيل XAMPP وإعداد قاعدة البيانات بشكل صحيح.");
            }
        }
    }

    /**
     * تحميل متغيرات البيئة
     */
    private static function loadEnvironmentVariables(): void
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
     * فحص إذا كان التطبيق في بيئة الإنتاج
     */
    private static function isProduction(): bool
    {
        return getenv('APP_ENV') === 'production' ||
               $_ENV['APP_ENV'] === 'production' ||
               (!isset($_ENV['APP_ENV']) && !isset($_SERVER['HTTP_HOST']));
    }

    /**
     * إغلاق الاتصال
     */
    public static function closeConnection(): void
    {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
            self::$instance = null;
        }
    }

    /**
     * اختبار الاتصال
     */
    public static function testConnection(): bool
    {
        try {
            $conn = self::getConnection();
            return $conn->ping();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * الحصول على معلومات الاتصال
     */
    public static function getConnectionInfo(): array
    {
        try {
            $conn = self::getConnection();
            return [
                'host' => $conn->host_info,
                'server_version' => $conn->server_version,
                'client_version' => $conn->client_version,
                'charset' => $conn->character_set_name()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
