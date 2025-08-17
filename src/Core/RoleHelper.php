<?php
declare(strict_types=1);

namespace App\Core;

/**
 * مساعدات للتعامل مع الأدوار في ملفات العرض
 */
class RoleHelper
{
    /**
     * التحقق من دور المستخدم الحالي
     */
    public static function getCurrentUserRole(): string
    {
        return trim($_SESSION['role'] ?? 'guest');
    }

    /**
     * التحقق من معرف المستخدم الحالي
     */
    public static function getCurrentUserId(): int
    {
        return (int)($_SESSION['employee_id'] ?? 0);
    }

    /**
     * التحقق من كون المستخدم مدير
     */
    public static function isManager(): bool
    {
        $role = self::getCurrentUserRole();
        return in_array($role, ['مدير', 'admin']);
    }

    /**
     * التحقق من كون المستخدم مصمم
     */
    public static function isDesigner(): bool
    {
        return self::getCurrentUserRole() === 'مصمم';
    }

    /**
     * التحقق من كون المستخدم معمل
     */
    public static function isWorkshop(): bool
    {
        $role = self::getCurrentUserRole();
        return in_array($role, ['معمل', 'معمل التنفيذ', 'المعمل التنفيذي']);
    }

    /**
     * التحقق من كون المستخدم محاسب
     */
    public static function isAccountant(): bool
    {
        return self::getCurrentUserRole() === 'محاسب';
    }

    /**
     * التحقق من إمكانية المستخدم رؤية جميع الطلبات
     */
    public static function canViewAllOrders(\mysqli $conn): bool
    {
        return Permissions::has_permission('order_view_all', $conn);
    }

    /**
     * التحقق من إمكانية المستخدم رؤية طلباته فقط
     */
    public static function canViewOwnOrders(\mysqli $conn): bool
    {
        return Permissions::has_permission('order_view_own', $conn);
    }

    /**
     * الحصول على عنوان مناسب للداشبورد حسب صلاحيات المستخدم
     */
    public static function getDashboardTitle(\mysqli $conn): string
    {
        if (self::canViewOwnOrders($conn) && !self::canViewAllOrders($conn)) {
            return 'المهام الموكلة إليك';
        }
        return 'أحدث المهام النشطة';
    }

    /**
     * التحقق من إمكانية المستخدم تعديل طلب معين
     */
    public static function canEditOrder(array $order, \mysqli $conn): bool
    {
        $user_id = self::getCurrentUserId();
        $user_role = self::getCurrentUserRole();
        
        // المدير يمكنه تعديل كل شيء
        if (self::isManager()) {
            return Permissions::has_permission('order_edit', $conn);
        }
        
        // المصمم يمكنه تعديل طلباته فقط
        if (self::isDesigner() && $order['designer_id'] == $user_id) {
            return Permissions::has_permission('order_edit', $conn);
        }
        
        // المعمل يمكنه تعديل طلباته فقط
        if (self::isWorkshop() && $order['workshop_id'] == $user_id) {
            return Permissions::has_permission('order_edit_status', $conn);
        }
        
        return false;
    }

    /**
     * التحقق من إمكانية المستخدم حذف طلب معين
     */
    public static function canDeleteOrder(array $order, \mysqli $conn): bool
    {
        // فقط المدير يمكنه الحذف عادة
        return self::isManager() && Permissions::has_permission('order_delete', $conn);
    }

    /**
     * الحصول على CSS class مناسب للدور
     */
    public static function getRoleCssClass(string $role = null): string
    {
        $role = $role ?? self::getCurrentUserRole();
        
        switch ($role) {
            case 'مصمم':
                return 'bg-info';
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                return 'bg-warning';
            case 'محاسب':
                return 'bg-success';
            case 'مدير':
                return 'bg-primary';
            default:
                return 'bg-secondary';
        }
    }

    /**
     * الحصول على وصف مناسب لمعنى "مكتمل" حسب الدور
     */
    public static function getRoleCompletionDescription(string $role = null): string
    {
        $role = $role ?? self::getCurrentUserRole();
        
        switch ($role) {
            case 'مصمم':
                return 'مكتمل عند إرسال للتنفيذ';
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                return 'مكتمل عند تأكيد استلام العميل';
            case 'محاسب':
                return 'مكتمل عند استلام كامل المبلغ';
            case 'مدير':
                return 'مكتمل عند استلام العميل + استلام كامل المبلغ';
            default:
                return 'غير محدد';
        }
    }
}
