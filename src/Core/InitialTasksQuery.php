<?php
declare(strict_types=1);

namespace App\Core;

class InitialTasksQuery
{
    public static function fetch_tasks(\mysqli $conn, string $filter_status = '', string $filter_employee = '', string $filter_payment = '', string $search_query = '', string $sort_by = 'latest'): \mysqli_result|false
    {
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_role = $_SESSION['user_role'] ?? 'guest';

        $sql = "SELECT o.*, c.company_name AS client_name, c.phone as client_phone, e.name AS designer_name, 
                COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary,
                o.design_completed_at, o.execution_completed_at, c.client_id
                FROM orders o
                JOIN clients c ON o.client_id = c.client_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN employees e ON o.designer_id = e.employee_id";

        $where_clauses = [];
        $params = [];
        $types = "";

        if (Permissions::has_permission('order_view_all', $conn)) {
            // إخفاء المهام التي تحقق شرطين: مكتملة ومدفوعة، أو الملغية
            $where_clauses[] = "NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'";

            if (!empty($filter_employee)) {
                $employee_role_query = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
                $employee_role_query->bind_param("i", $filter_employee);
                $employee_role_query->execute();
                $employee_role_result = $employee_role_query->get_result();
                $employee_role = $employee_role_result->fetch_assoc()['role'] ?? '';

                switch ($employee_role) {
                    case 'مصمم':
                        $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                        $params[] = $filter_employee;
                        $types .= "i";
                        break;
                    case 'معمل':
                        $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                        $params[] = $filter_employee;
                        $types .= "i";
                        break;
                    case 'محاسب':
                        $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                        break;
                    default:
                        // للأدوار الأخرى، عرض جميع الطلبات النشطة
                        $where_clauses[] = "o.status IN ('جديد', 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم')";
                        break;
                }
            } else {
                if (!empty($filter_status)) {
                    $where_clauses[] = "o.status = ?";
                    $params[] = $filter_status;
                    $types .= "s";
                }
            }

            if (!empty($filter_payment)) {
                $where_clauses[] = "o.payment_status = ?";
                $params[] = $filter_payment;
                $types .= "s";
            }
            if (!empty($search_query)) {
                $where_clauses[] = "(o.order_id LIKE ? OR c.company_name LIKE ? OR p.name LIKE ?)";
                $search_param = "%$search_query%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
                $types .= "sss";
            }
        } elseif (Permissions::has_permission('order_view_own', $conn)) {
            // إخفاء المهام التي تحقق شرطين: مكتملة ومدفوعة، أو الملغية للموظفين
            $where_clauses = ["NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'"];
            
            switch ($user_role) {
                case 'مصمم':
                    $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                    $params[] = $user_id;
                    $types .= "i";
                    break;
                case 'معمل':
                    // يظهر له فقط قيد التنفيذ أو جاهز للتسليم (ولا يظهر مكتمل)
                    // ملاحظة: يجب ألا يتم تغيير الحالة إلى "مكتمل" إلا بعد تأكيد العميل فقط من الكنترولر
                    $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                    $params[] = $user_id;
                    $types .= "i";
                    break;
                case 'محاسب':
                    $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                    break;
                default:
                    $where_clauses[] = "1=0";
                    break;
            }
        } else {
            $where_clauses[] = "1=0";
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $order_by_clause = '';
        switch ($sort_by) {
            case 'latest':
                $order_by_clause = 'o.order_date DESC';
                break;
            case 'oldest':
                $order_by_clause = 'o.order_date ASC';
                break;
            case 'payment':
                $order_by_clause = 'o.payment_status ASC, o.order_date DESC';
                break;
            case 'employee':
                $order_by_clause = 'e.name ASC, o.order_date DESC';
                break;
            default:
                $order_by_clause = 'o.order_date DESC';
                break;
        }

        $sql .= " GROUP BY o.order_id ORDER BY " . $order_by_clause;

        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
}
