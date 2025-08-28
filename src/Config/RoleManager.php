<?php
/**
 * فئة مركزية لإدارة الأدوار والصلاحيات
 * توحد جميع منطق إدارة الأدوار في مكان واحد
 */

namespace App\Config;

use App\Config\Constants;
use App\Config\Config;

/**
 * فئة إدارة الأدوار والصلاحيات
 */
class RoleManager
{
    /**
     * الحصول على جميع الأدوار المتاحة
     */
    public static function getAllRoles(): array
    {
        return Constants::USER_ROLES;
    }

    /**
     * التحقق من صحة الدور
     */
    public static function isValidRole(string $role): bool
    {
        return Constants::isValidRole($role);
    }

    /**
     * التحقق من كون الدور إداري
     */
    public static function isAdminRole(string $role): bool
    {
        return Constants::isAdminRole($role);
    }

    /**
     * الحصول على الأدوار التي يمكنها الوصول للمهام
     */
    public static function getTaskAccessRoles(): array
    {
        return Constants::TASK_ACCESS_ROLES;
    }

    /**
     * الحصول على الحالات المتاحة لدور معين
     */
    public static function getAvailableStatusesForRole(string $role): array
    {
        return Constants::getAvailableStatusesForRole($role);
    }

    /**
     * الحصول على نطاق العرض الافتراضي لدور معين
     */
    public static function getDefaultViewScopeForRole(string $role): string
    {
        return Constants::getDefaultViewScopeForRole($role);
    }

    /**
     * التحقق من إمكانية عرض جميع المهام
     */
    public static function canViewAllTasks(string $role): bool
    {
        return Constants::canViewAllTasks($role);
    }

    /**
     * الحصول على إعدادات الفلترة لدور معين
     */
    public static function getRoleFilterSettings(string $role, string $context = 'dashboard'): array
    {
        // محاولة الحصول من ملف JSON أولاً
        if (Config::settingsFileExists('employee_filters')) {
            $settings = Config::getJsonSetting('employee_filters', 'employees');

            if ($settings) {
                // البحث عن الموظف بالدور
                foreach ($settings as $employee_id => $employee_data) {
                    if (isset($employee_data['role']) && $employee_data['role'] === $role) {
                        return $employee_data['settings'][$context] ?? [];
                    }
                }
            }
        }

        // العودة للإعدادات الافتراضية
        return Config::getRoleSettings($role);
    }

    /**
     * الحصول على إعدادات موظف محدد
     */
    public static function getEmployeeFilterSettings(int $employee_id, string $context = 'dashboard'): array
    {
        if (Config::settingsFileExists('employee_filters')) {
            $settings = Config::getJsonSetting('employee_filters', "employees.{$employee_id}.settings.{$context}");

            if ($settings) {
                return $settings;
            }
        }

        // العودة للإعدادات الافتراضية حسب الدور
        $employee_role = self::getEmployeeRole($employee_id);
        return self::getRoleFilterSettings($employee_role, $context);
    }

    /**
     * الحصول على دور موظف محدد
     */
    public static function getEmployeeRole(int $employee_id): string
    {
        // يمكن تحسين هذا للحصول من قاعدة البيانات أو الملف
        if (Config::settingsFileExists('employee_filters')) {
            $settings = Config::getJsonSetting('employee_filters', "employees.{$employee_id}");

            if ($settings && isset($settings['role'])) {
                return $settings['role'];
            }
        }

        return 'موظف'; // دور افتراضي
    }

    /**
     * الحصول على قائمة الموظفين حسب الدور
     */
    public static function getEmployeesByRole(string $role): array
    {
        $employees = [];

        if (Config::settingsFileExists('employee_filters')) {
            $settings = Config::getJsonSetting('employee_filters', 'employees');

            if ($settings) {
                foreach ($settings as $employee_id => $employee_data) {
                    if (isset($employee_data['role']) && $employee_data['role'] === $role) {
                        $employees[$employee_id] = $employee_data;
                    }
                }
            }
        }

        return $employees;
    }

    /**
     * إنشاء إعدادات افتراضية لموظف جديد
     */
    public static function createDefaultEmployeeSettings(string $name, string $role): array
    {
        return [
            'name' => $name,
            'role' => $role,
            'settings' => [
                'dashboard' => self::getRoleFilterSettings($role, 'dashboard'),
                'orders' => self::getRoleFilterSettings($role, 'orders'),
                'reports' => self::getRoleFilterSettings($role, 'reports')
            ]
        ];
    }

    /**
     * حفظ إعدادات موظف
     */
    public static function saveEmployeeSettings(int $employee_id, array $settings): bool
    {
        return Config::saveJsonSetting('employee_filters', $settings, "employees.{$employee_id}");
    }

    /**
     * حذف إعدادات موظف
     */
    public static function deleteEmployeeSettings(int $employee_id): bool
    {
        $all_settings = Config::getJsonSetting('employee_filters');

        if ($all_settings && isset($all_settings['employees'][$employee_id])) {
            unset($all_settings['employees'][$employee_id]);
            return Config::saveJsonSetting('employee_filters', $all_settings);
        }

        return false;
    }

    /**
     * الحصول على إحصائيات الأدوار
     */
    public static function getRoleStatistics(): array
    {
        $stats = [];

        if (Config::settingsFileExists('employee_filters')) {
            $settings = Config::getJsonSetting('employee_filters', 'employees');

            if ($settings) {
                foreach ($settings as $employee_data) {
                    $role = $employee_data['role'] ?? 'غير محدد';
                    $stats[$role] = ($stats[$role] ?? 0) + 1;
                }
            }
        }

        return $stats;
    }
}
?>
