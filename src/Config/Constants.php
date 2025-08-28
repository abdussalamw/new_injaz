<?php
/**
 * ملف الثوابت المركزي للنظام
 * يحتوي على جميع التعريفات الأساسية للنظام
 */

namespace App\Config;

/**
 * فئة الثوابت الأساسية للنظام
 */
class Constants
{
    // ==========================================
    // تعريفات الحالات الأساسية
    // ==========================================

    /**
     * جميع حالات الطلبات المتاحة
     */
    const ORDER_STATUSES = [
        'قيد التصميم',
        'قيد التنفيذ',
        'جاهز للتسليم',
        'تأكيد استلام العميل',
        'مكتمل',
        'ملغي'
    ];

    /**
     * حالات الطلبات النشطة (غير مكتملة وغير ملغية)
     */
    const ACTIVE_ORDER_STATUSES = [
        'قيد التصميم',
        'قيد التنفيذ',
        'جاهز للتسليم',
        'تأكيد استلام العميل'
    ];

    /**
     * حالات الطلبات المكتملة
     */
    const COMPLETED_ORDER_STATUSES = [
        'مكتمل'
    ];

    /**
     * حالات الطلبات الملغية
     */
    const CANCELLED_ORDER_STATUSES = [
        'ملغي'
    ];

    // ==========================================
    // تعريفات حالات الدفع
    // ==========================================

    /**
     * جميع حالات الدفع المتاحة
     */
    const PAYMENT_STATUSES = [
        'غير مدفوع',
        'مدفوع جزئياً',
        'مدفوع'
    ];

    /**
     * حالات الدفع المكتملة
     */
    const COMPLETED_PAYMENT_STATUSES = [
        'مدفوع'
    ];

    /**
     * حالات الدفع غير المكتملة
     */
    const PENDING_PAYMENT_STATUSES = [
        'غير مدفوع',
        'مدفوع جزئياً'
    ];

    // ==========================================
    // تعريفات الأدوار والصلاحيات
    // ==========================================

    /**
     * جميع الأدوار المتاحة في النظام
     */
    const USER_ROLES = [
        'مدير',
        'مصمم',
        'معمل',
        'محاسب'
    ];

    /**
     * الأدوار التي لها صلاحيات إدارية
     */
    const ADMIN_ROLES = [
        'مدير'
    ];

    /**
     * الأدوار التي يمكنها الوصول للمهام
     */
    const TASK_ACCESS_ROLES = [
        'مدير',
        'مصمم',
        'معمل',
        'محاسب'
    ];

    // ==========================================
    // تعريفات نطاقات العرض
    // ==========================================

    /**
     * نطاقات عرض المهام المتاحة
     */
    const VIEW_SCOPES = [
        'all' => 'جميع المهام',
        'role' => 'مهام الدور فقط',
        'self' => 'مهامي فقط'
    ];

    // ==========================================
    // تعريفات السياقات
    // ==========================================

    /**
     * السياقات المتاحة للفلترة
     */
    const CONTEXTS = [
        'dashboard' => 'لوحة المهام',
        'orders' => 'صفحة الطلبات',
        'reports' => 'التقارير'
    ];

    // ==========================================
    // دوال مساعدة
    // ==========================================

    /**
     * التحقق من صحة حالة الطلب
     */
    public static function isValidOrderStatus(string $status): bool
    {
        return in_array($status, self::ORDER_STATUSES);
    }

    /**
     * التحقق من صحة حالة الدفع
     */
    public static function isValidPaymentStatus(string $status): bool
    {
        return in_array($status, self::PAYMENT_STATUSES);
    }

    /**
     * التحقق من صحة الدور
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::USER_ROLES);
    }

    /**
     * التحقق من كون الدور إداري
     */
    public static function isAdminRole(string $role): bool
    {
        return in_array($role, self::ADMIN_ROLES);
    }

    /**
     * الحصول على الحالات المتاحة لدور معين
     */
    public static function getAvailableStatusesForRole(string $role): array
    {
        switch (trim($role)) {
            case 'مصمم':
                return ['قيد التصميم'];

            case 'معمل':
                return ['قيد التنفيذ', 'جاهز للتسليم'];

            case 'محاسب':
                return array_merge(self::ACTIVE_ORDER_STATUSES, self::COMPLETED_ORDER_STATUSES);

            case 'مدير':
                return array_merge(self::ACTIVE_ORDER_STATUSES, self::COMPLETED_ORDER_STATUSES, self::CANCELLED_ORDER_STATUSES);

            default:
                return [];
        }
    }

    /**
     * الحصول على نطاق العرض الافتراضي لدور معين
     */
    public static function getDefaultViewScopeForRole(string $role): string
    {
        if (self::isAdminRole($role)) {
            return 'all';
        }

        return 'role';
    }

    /**
     * التحقق من إمكانية عرض جميع المهام
     */
    public static function canViewAllTasks(string $role): bool
    {
        return self::isAdminRole($role);
    }

    /**
     * الحصول على الحالات النشطة
     */
    public static function getActiveStatuses(): array
    {
        return [
            'جديد',
            'قيد التصميم',
            'قيد التنفيذ',
            'قيد المراجعة',
            'تأكيد استلام العميل'
        ];
    }

    /**
     * الحصول على الحالات المكتملة
     */
    public static function getCompletedStatuses(): array
    {
        return [
            'مكتملة',
            'ملغية'
        ];
    }

    /**
     * الحصول على الحالات المدفوعة
     */
    public static function getPaidStatuses(): array
    {
        return [
            'مدفوع',
            'مدفوع جزئياً'
        ];
    }

    /**
     * الحصول على الحالات غير المدفوعة
     */
    public static function getUnpaidStatuses(): array
    {
        return [
            'غير مدفوع',
            'مؤجل'
        ];
    }
}
?>
