<?php
/**
 * ملف الإعدادات العامة للنظام
 * يحتوي على جميع الإعدادات القابلة للتخصيص
 */

namespace App\Config;

/**
 * فئة الإعدادات العامة
 */
class Config
{
    // ==========================================
    // إعدادات قاعدة البيانات
    // ==========================================

    /**
     * إعدادات الاتصال بقاعدة البيانات
     */
    const DB_CONFIG = [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'injaz',
        'charset' => 'utf8mb4'
    ];

    // ==========================================
    // إعدادات الملفات
    // ==========================================

    /**
     * مسارات ملفات الإعدادات
     */
    const SETTINGS_FILES = [
        'employee_filters' => __DIR__ . '/../View/settings/employee_filter_settings_new.json',
        'legacy_filters' => __DIR__ . '/../View/settings/role_filters.json'
    ];

    // ==========================================
    // إعدادات النظام
    // ==========================================

    /**
     * إعدادات النظام العامة
     */
    const SYSTEM_CONFIG = [
        'default_view_scope' => 'role',
        'enable_legacy_support' => false,
        'cache_settings' => true,
        'debug_mode' => false
    ];

    // ==========================================
    // إعدادات الفلترة الافتراضية
    // ==========================================

    /**
     * إعدادات الفلترة الافتراضية لكل دور
     */
    const DEFAULT_ROLE_FILTERS = [
        'مدير' => [
            'view_scope' => 'all',
            'show_stages' => [],
            'hide_stages' => [],
            'exclude_combinations' => [
                [
                    'status' => 'مكتمل',
                    'payment_status' => 'مدفوع'
                ]
            ]
        ],
        'مصمم' => [
            'view_scope' => 'role',
            'show_stages' => ['قيد التصميم'],
            'hide_stages' => [],
            'exclude_combinations' => []
        ],
        'معمل' => [
            'view_scope' => 'role',
            'show_stages' => ['قيد التنفيذ'],
            'hide_stages' => [],
            'exclude_combinations' => []
        ],
        'محاسب' => [
            'view_scope' => 'self',
            'show_stages' => ['تأكيد استلام العميل', 'مكتمل'],
            'hide_stages' => [],
            'exclude_combinations' => []
        ]
    ];

    // ==========================================
    // إعدادات الواجهة
    // ==========================================

    /**
     * إعدادات الواجهة والعرض
     */
    const UI_CONFIG = [
        'items_per_page' => 12,
        'max_file_size' => 5242880, // 5MB
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s'
    ];

    // ==========================================
    // إعدادات الأمان
    // ==========================================

    /**
     * إعدادات الأمان
     */
    const SECURITY_CONFIG = [
        'session_timeout' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'password_min_length' => 8,
        'require_https' => false
    ];

    // ==========================================
    // دوال مساعدة
    // ==========================================

    /**
     * الحصول على إعداد من قاعدة البيانات
     */
    public static function getDatabaseSetting(string $key, $default = null)
    {
        // يمكن تنفيذ هذا لاحقاً للحصول على إعدادات من قاعدة البيانات
        return $default;
    }

    /**
     * الحصول على إعداد من ملف JSON
     */
    public static function getJsonSetting(string $file_key, string $setting_path = null)
    {
        $file_path = self::SETTINGS_FILES[$file_key] ?? null;

        if (!$file_path || !file_exists($file_path)) {
            return null;
        }

        $data = json_decode(file_get_contents($file_path), true);

        if ($setting_path && $data) {
            $keys = explode('.', $setting_path);
            foreach ($keys as $key) {
                if (isset($data[$key])) {
                    $data = $data[$key];
                } else {
                    return null;
                }
            }
        }

        return $data;
    }

    /**
     * حفظ إعداد في ملف JSON
     */
    public static function saveJsonSetting(string $file_key, $data, string $setting_path = null): bool
    {
        $file_path = self::SETTINGS_FILES[$file_key] ?? null;

        if (!$file_path) {
            return false;
        }

        if ($setting_path) {
            $existing_data = self::getJsonSetting($file_key) ?: [];
            $keys = explode('.', $setting_path);
            $temp = &$existing_data;

            foreach ($keys as $key) {
                if (!isset($temp[$key])) {
                    $temp[$key] = [];
                }
                $temp = &$temp[$key];
            }
            $temp = $data;
            $data = $existing_data;
        }

        return file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * الحصول على إعدادات دور معين
     */
    public static function getRoleSettings(string $role): array
    {
        return self::DEFAULT_ROLE_FILTERS[$role] ?? [];
    }

    /**
     * الحصول على إعدادات قاعدة البيانات
     */
    public static function getDatabaseConfig(): array
    {
        return self::DB_CONFIG;
    }

    /**
     * الحصول على مسار النسخ الاحتياطية
     */
    public static function getBackupPath(): string
    {
        return __DIR__ . '/../../backups/';
    }
}
?>
