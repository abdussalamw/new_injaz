<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\RoleBasedQuery;

class InitialTasksQuery
{
    public static function fetch_tasks(\mysqli $conn, string $filter_status = '', string $filter_employee = '', string $filter_payment = '', string $search_query = '', string $sort_by = 'latest'): \mysqli_result|false
    {
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_role = $_SESSION['user_role'] ?? 'guest';

        $sql = "SELECT o.*, c.company_name AS client_name, c.phone as client_phone, e.name AS designer_name, 
                COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary,
                o.design_completed_at, o.execution_completed_at, o.design_started_at, o.execution_started_at, c.client_id
                FROM orders o
                JOIN clients c ON o.client_id = c.client_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN employees e ON o.designer_id = e.employee_id";

        // استخدام الفئة الموحدة لبناء شروط الفلترة
        if (Permissions::has_permission('order_view_all', $conn)) {
            // للمستخدمين الذين يمكنهم رؤية جميع الطلبات (مثل المدير)
            if (!empty($filter_employee)) {
                // إذا كان هناك فلتر لموظف محدد
                $conditions = RoleBasedQuery::buildRoleBasedConditions(
                    '', // دور فارغ لاستخدام منطق فلترة الموظف المحدد
                    $user_id,
                    $filter_employee,
                    $filter_status,
                    $filter_payment,
                    $search_query,
                    $conn
                );
            } else {
                // رؤية جميع الطلبات بدون فلتر موظف محدد
                $conditions = RoleBasedQuery::buildRoleBasedConditions(
                    'مدير',
                    $user_id,
                    '',
                    $filter_status,
                    $filter_payment,
                    $search_query,
                    $conn
                );
            }
        } elseif (Permissions::has_permission('order_view_own', $conn)) {
            // المستخدم يرى طلباته فقط حسب دوره
            $conditions = RoleBasedQuery::buildRoleBasedConditions(
                $user_role,
                $user_id,
                '', // لا نمرر filter_employee لأنه يرى طلباته فقط
                $filter_status,
                $filter_payment,
                $search_query,
                $conn
            );
        } else {
            // لا يملك أي صلاحية
            $conditions = [
                'where_clauses' => ['1=0'],
                'params' => [],
                'types' => ''
            ];
        }

        // بناء الاستعلام
        if (!empty($conditions['where_clauses'])) {
            $sql .= " WHERE " . implode(" AND ", $conditions['where_clauses']);
        }
        
        // ترتيب النتائج
        $order_by_clause = self::getOrderByClause($sort_by);
        $sql .= " GROUP BY o.order_id ORDER BY " . $order_by_clause;

        // تنفيذ الاستعلام
        $stmt = $conn->prepare($sql);
        if (!empty($conditions['params'])) {
            $stmt->bind_param($conditions['types'], ...$conditions['params']);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * الحصول على جملة الترتيب
     */
    private static function getOrderByClause(string $sort_by): string
    {
        switch ($sort_by) {
            case 'latest':
                return 'o.order_date DESC';
            case 'oldest':
                return 'o.order_date ASC';
            case 'payment':
                return 'o.payment_status ASC, o.order_date DESC';
            case 'employee':
                return 'e.name ASC, o.order_date DESC';
            default:
                return 'o.order_date DESC';
        }
    }
}
