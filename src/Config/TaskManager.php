<?php
/**
 * فئة مركزية لإدارة المهام والفلترة
 * توحد منطق الاستعلامات والفلترة للمهام
 */

namespace App\Config;

use App\Config\Constants;
use App\Config\Config;
use App\Config\RoleManager;

/**
 * فئة إدارة المهام والفلترة
 */
class TaskManager
{
    /**
     * الحصول على استعلام المهام الأساسي
     */
    public static function getBaseTasksQuery(): string
    {
        return "
            SELECT
                o.*,
                c.name as client_name,
                c.phone as client_phone,
                e.name as employee_name,
                p.name as product_name,
                p.price as product_price,
                p.description as product_description,
                COALESCE(pay.status, 'غير محدد') as payment_status,
                COALESCE(pay.amount, 0) as payment_amount,
                COALESCE(pay.payment_date, NULL) as payment_date
            FROM orders o
            LEFT JOIN clients c ON o.client_id = c.id
            LEFT JOIN employees e ON o.employee_id = e.id
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN payments pay ON o.id = pay.order_id
        ";
    }

    /**
     * بناء شروط الفلترة حسب الدور والإعدادات
     */
    public static function buildFilterConditions(string $role, array $settings = []): array
    {
        $conditions = [];
        $params = [];

        // فلترة حسب الدور
        if (!RoleManager::canViewAllTasks($role)) {
            // الموظفين العاديين يرون مهامهم فقط
            $conditions[] = "o.employee_id = ?";
            $params[] = $_SESSION['employee_id'] ?? 0;
        }

        // فلترة حسب الحالة
        if (!empty($settings['status_filter'])) {
            $status_filter = $settings['status_filter'];

            if ($status_filter === 'active') {
                // المهام النشطة فقط
                $active_statuses = Constants::getActiveStatuses();
                $placeholders = str_repeat('?,', count($active_statuses) - 1) . '?';
                $conditions[] = "o.status IN ({$placeholders})";
                $params = array_merge($params, $active_statuses);
            } elseif ($status_filter === 'completed') {
                // المهام المكتملة فقط
                $completed_statuses = Constants::getCompletedStatuses();
                $placeholders = str_repeat('?,', count($completed_statuses) - 1) . '?';
                $conditions[] = "o.status IN ({$placeholders})";
                $params = array_merge($params, $completed_statuses);
            } elseif (is_array($status_filter)) {
                // حالات محددة
                $placeholders = str_repeat('?,', count($status_filter) - 1) . '?';
                $conditions[] = "o.status IN ({$placeholders})";
                $params = array_merge($params, $status_filter);
            }
        }

        // فلترة حسب حالة الدفع
        if (!empty($settings['payment_filter'])) {
            $payment_filter = $settings['payment_filter'];

            if ($payment_filter === 'paid') {
                $conditions[] = "pay.status = ?";
                $params[] = 'مدفوع';
            } elseif ($payment_filter === 'unpaid') {
                $conditions[] = "(pay.status IS NULL OR pay.status != ?)";
                $params[] = 'مدفوع';
            } elseif (is_array($payment_filter)) {
                $placeholders = str_repeat('?,', count($payment_filter) - 1) . '?';
                $conditions[] = "pay.status IN ({$placeholders})";
                $params = array_merge($params, $payment_filter);
            }
        }

        // فلترة حسب التاريخ
        if (!empty($settings['date_filter'])) {
            $date_filter = $settings['date_filter'];

            if ($date_filter === 'today') {
                $conditions[] = "DATE(o.created_at) = CURDATE()";
            } elseif ($date_filter === 'week') {
                $conditions[] = "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($date_filter === 'month') {
                $conditions[] = "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            } elseif (isset($date_filter['from']) && isset($date_filter['to'])) {
                $conditions[] = "DATE(o.created_at) BETWEEN ? AND ?";
                $params[] = $date_filter['from'];
                $params[] = $date_filter['to'];
            }
        }

        // فلترة حسب العميل
        if (!empty($settings['client_filter'])) {
            $conditions[] = "o.client_id = ?";
            $params[] = $settings['client_filter'];
        }

        // فلترة حسب المنتج
        if (!empty($settings['product_filter'])) {
            $conditions[] = "o.product_id = ?";
            $params[] = $settings['product_filter'];
        }

        return [
            'conditions' => $conditions,
            'params' => $params
        ];
    }

    /**
     * بناء استعلام كامل مع الفلاتر
     */
    public static function buildFilteredQuery(string $role, array $settings = [], string $additional_where = ""): array
    {
        $base_query = self::getBaseTasksQuery();
        $filter_data = self::buildFilterConditions($role, $settings);

        $where_conditions = $filter_data['conditions'];

        if (!empty($additional_where)) {
            $where_conditions[] = $additional_where;
        }

        $where_clause = "";
        if (!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }

        $query = $base_query . " " . $where_clause . " ORDER BY o.created_at DESC";

        return [
            'query' => $query,
            'params' => $filter_data['params']
        ];
    }

    /**
     * الحصول على إحصائيات المهام
     */
    public static function getTaskStatistics(string $role, array $settings = []): array
    {
        $query_data = self::buildFilteredQuery($role, $settings);

        $stats_query = "
            SELECT
                COUNT(*) as total_tasks,
                SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getCompletedStatuses()) . "') THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN pay.status = 'مدفوع' THEN 1 ELSE 0 END) as paid_tasks,
                SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getActiveStatuses()) . "') THEN 1 ELSE 0 END) as active_tasks,
                SUM(p.price) as total_revenue
            FROM orders o
            LEFT JOIN payments pay ON o.id = pay.order_id
            LEFT JOIN products p ON o.product_id = p.id
        ";

        // إضافة شروط الفلترة
        $filter_data = self::buildFilterConditions($role, $settings);
        if (!empty($filter_data['conditions'])) {
            $stats_query .= " WHERE " . implode(" AND ", $filter_data['conditions']);
        }

        return [
            'query' => $stats_query,
            'params' => $filter_data['params']
        ];
    }

    /**
     * التحقق من إمكانية عرض مهمة معينة
     */
    public static function canViewTask(string $role, int $task_id, int $task_employee_id): bool
    {
        if (RoleManager::canViewAllTasks($role)) {
            return true;
        }

        // الموظفين العاديين يرون مهامهم فقط
        return $task_employee_id === ($_SESSION['employee_id'] ?? 0);
    }

    /**
     * التحقق من إمكانية تعديل مهمة
     */
    public static function canEditTask(string $role, int $task_id, int $task_employee_id, string $task_status): bool
    {
        // المديرون يمكنهم تعديل جميع المهام
        if (RoleManager::isAdminRole($role)) {
            return true;
        }

        // الموظفين يمكنهم تعديل مهامهم النشطة فقط
        if ($task_employee_id === ($_SESSION['employee_id'] ?? 0)) {
            return in_array($task_status, Constants::getActiveStatuses());
        }

        return false;
    }

    /**
     * الحصول على المهام المخفية للمدير
     */
    public static function getHiddenTasksForManager(): array
    {
        return [
            'completed_paid' => [
                'status' => Constants::getCompletedStatuses(),
                'payment' => ['مدفوع']
            ]
        ];
    }

    /**
     * تطبيق قواعد الإخفاء للمدير
     */
    public static function applyManagerHidingRules(array &$conditions, array &$params): void
    {
        $hidden_rules = self::getHiddenTasksForManager();

        if (isset($hidden_rules['completed_paid'])) {
            $completed_statuses = $hidden_rules['completed_paid']['status'];
            $paid_statuses = $hidden_rules['completed_paid']['payment'];

            // إخفاء المهام المكتملة والمدفوعة
            $completed_placeholders = str_repeat('?,', count($completed_statuses) - 1) . '?';
            $paid_placeholders = str_repeat('?,', count($paid_statuses) - 1) . '?';

            $conditions[] = "NOT (o.status IN ({$completed_placeholders}) AND pay.status IN ({$paid_placeholders}))";

            $params = array_merge($params, $completed_statuses, $paid_statuses);
        }
    }
}
?>
