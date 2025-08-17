<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\UnifiedRoleLogic;

/**
 * فئة موحدة لإدارة منطق الأدوار والفلترة
 * 
 * @deprecated سيتم الانتقال تدريجياً إلى UnifiedRoleLogic
 * هذا الملف يحافظ على التوافق مع الكود الموجود
 */
class RoleBasedQuery
{
    /**
     * بناء شروط الفلترة حسب دور المستخدم
     * 
     * @deprecated استخدم UnifiedRoleLogic::buildActiveTasksQuery()
     */
    public static function buildRoleBasedConditions(
        string $user_role, 
        int $user_id, 
        string $filter_employee = '', 
        string $filter_status = '', 
        string $filter_payment = '', 
        string $search_query = '',
        \mysqli $conn = null
    ): array {
        // استخدام النظام الموحد الجديد مع الحفاظ على التوافق
        return UnifiedRoleLogic::buildActiveTasksQuery(
            $user_role, 
            $user_id, 
            $filter_status, 
            $filter_payment, 
            $search_query, 
            $filter_employee, 
            $conn
        );
    }
    
    /**
     * @deprecated استخدم UnifiedRoleLogic::getVisibleStatusesForRole()
     */
    public static function getVisibleStatusesForRole(string $user_role): array
    {
        return UnifiedRoleLogic::getVisibleStatusesForRole($user_role);
    }
    
    /**
     * @deprecated استخدم UnifiedRoleLogic::getRoleInfo()
     */
    public static function canViewAllOrders(string $user_role): bool
    {
        $info = UnifiedRoleLogic::getRoleInfo($user_role);
        return $info['can_view_all'];
    }
    
    /**
     * @deprecated استخدم UnifiedRoleLogic::getRoleInfo()
     */
    public static function getAvailableRoles(): array
    {
        return [
            'مدير' => 'مدير',
            'مصمم' => 'مصمم', 
            'معمل' => 'معمل',
            'محاسب' => 'محاسب'
        ];
    }
}