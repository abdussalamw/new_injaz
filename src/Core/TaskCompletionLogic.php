<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\UnifiedRoleLogic;

/**
 * منطق مركزي لتحديد إكمال المهام حسب دور كل موظف
 * 
 * @deprecated استخدم UnifiedRoleLogic بدلاً من هذا الملف
 * هذا الملف سيتم الاستغناء عنه تدريجياً
 */
class TaskCompletionLogic
{
    /**
     * استخدام النظام الموحد الجديد
     * 
     * @deprecated استخدم UnifiedRoleLogic::getCompletedTasksConditions()
     */
    public static function getCompletedTasksCondition(string $role): array
    {
        return UnifiedRoleLogic::getCompletedTasksConditions($role);
    }
    
    /**
     * استخدام النظام الموحد الجديد
     * 
     * @deprecated استخدم UnifiedRoleLogic::getActiveTasksConditions()
     */
    public static function getActiveTasksCondition(string $role): array
    {
        $conditions = UnifiedRoleLogic::getActiveTasksConditions($role, 0);
        return [
            'condition' => implode(' AND ', $conditions['where_conditions']),
            'description' => $conditions['description']
        ];
    }
    
    /**
     * استخدام النظام الموحد الجديد
     * 
     * @deprecated استخدم UnifiedRoleLogic::buildEmployeeStatsQuery()
     */
    public static function buildEmployeeStatsQuery(string $start_date, string $end_date): string
    {
        return UnifiedRoleLogic::buildEmployeeStatsQuery($start_date, $end_date);
    }
    
    /**
     * استخدام النظام الموحد الجديد
     * 
     * @deprecated استخدم UnifiedRoleLogic::getRoleInfo()
     */
    public static function getTaskTypeDescription(string $role): string
    {
        $info = UnifiedRoleLogic::getRoleInfo($role);
        return $info['relationship_description'];
    }
}
