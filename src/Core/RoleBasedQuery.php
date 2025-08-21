<?php
declare(strict_types=1);

namespace App\Core;

/**
 * فئة موحدة لإدارة منطق الأدوار والفلترة
 * تحتوي على جميع منطق الصلاحيات والفلترة الخاص بكل دور
 */
class RoleBasedQuery
{
    /**
     * بناء شروط الفلترة حسب دور المستخدم
     * 
     * @param string $user_role دور المستخدم الحالي
     * @param int $user_id معرف المستخدم الحالي
     * @param string $filter_employee معرف الموظف المطلوب فلترته (اختياري)
     * @param string $filter_status حالة الطلب المطلوب فلترتها (اختياري)
     * @param string $filter_payment حالة الدفع المطلوبة (اختياري)
     * @param string $search_query نص البحث (اختياري)
     * @param \mysqli $conn اتصال قاعدة البيانات
     * @param bool $ignore_default_status_filters تجاهل فلاتر الحالة الافتراضية
     * @return array مصفوفة تحتوي على where_clauses و params و types
     */
    public static function buildRoleBasedConditions(
        string $user_role, 
        int $user_id, 
        string $filter_employee = '', 
        string $filter_status = '', 
        string $filter_payment = '', 
        string $search_query = '',
        \mysqli $conn = null,
        bool $ignore_default_status_filters = false,
        string $context = 'orders'
    ): array {
        $where_clauses = [];
        $params = [];
        $types = "";

        // تحميل إعدادات الفلترة من ملف JSON
        $settings_file = __DIR__ . '/../View/settings/role_filters.json';
        $filters = [];
        if (file_exists($settings_file)) {
            $json = file_get_contents($settings_file);
            $filters = json_decode($json, true);
        }

        $trimmed_role = trim($user_role);
        $role_filter = $filters[$context][$trimmed_role] ?? null;

        // فلترة حسب الإعدادات الجديدة
        if ($role_filter && $role_filter['enabled']) {
            $stages = $role_filter['stages'] ?? [];
            $filter_type = $role_filter['filter_type'] ?? 'show';
            if (!empty($stages)) {
                $placeholders = implode(',', array_fill(0, count($stages), '?'));
                if ($filter_type === 'show') {
                    $where_clauses[] = "o.status IN ($placeholders)";
                } elseif ($filter_type === 'hide') {
                    $where_clauses[] = "o.status NOT IN ($placeholders)";
                }
                foreach ($stages as $st) {
                    $params[] = $st;
                    $types .= "s";
                }
            } else {
                // إذا كانت قائمة المراحل فارغة، لا فلترة إضافية (يعرض الكل)
            }
        } else {
            // إذا كان الدور غير مفعل، لا يرى أي شيء
            $where_clauses[] = "1=0";
        }

        // فلترة حسب الموظف إذا تم تحديده
        if (!empty($filter_employee) && $conn) {
            $is_manager = in_array(trim($user_role), ['مدير', 'admin']) || trim($user_role) === '';
            return self::buildEmployeeFilterConditions($filter_employee, $filter_status, $filter_payment, $search_query, $conn, $is_manager);
        }

        // فلترة إضافية حسب الفلاتر الأخرى
        if (!empty($filter_status)) {
            $where_clauses[] = "o.status = ?";
            $params[] = $filter_status;
            $types .= "s";
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

        return [
            'where_clauses' => $where_clauses,
            'params' => $params,
            'types' => $types
        ];
    }

    private static function buildEmployeeFilterConditions(
        string $filter_employee, 
        string $filter_status, 
        string $filter_payment, 
        string $search_query,
        \mysqli $conn,
        bool $is_manager = false
    ): array {
        $where_clauses = [];
        $params = [];
        $types = "";
        
    // منع ظهور أي مهمة مكتملة ومدفوعة بالكامل أو ملغية في الداش بورد بشكل قاطع
    $where_clauses[] = "NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'";

        $employee_role_query = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
        $employee_role_query->bind_param("i", $filter_employee);
        $employee_role_query->execute();
        $employee_role_result = $employee_role_query->get_result();
        $employee_role = trim($employee_role_result->fetch_assoc()['role'] ?? '');

        switch ($employee_role) {
            case 'مصمم':
                $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                $params[] = $filter_employee;
                $types .= "i";
                break;
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                if ($is_manager) {
                    $where_clauses[] = "o.workshop_id = ?";
                } else {
                    $where_clauses[] = "((o.workshop_id = ?) OR (o.workshop_id IS NULL AND TRIM(o.status) = 'قيد التنفيذ')) AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                }
                $params[] = $filter_employee;
                $types .= "i";
                break;
            case 'محاسب':
                $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                break;
            default:
                $where_clauses[] = "o.status IN ('جديد', 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم')";
                break;
        }

        if (!empty($filter_status)) {
            $where_clauses[] = "o.status = ?";
            $params[] = $filter_status;
            $types .= "s";
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

        return [
            'where_clauses' => $where_clauses,
            'params' => $params,
            'types' => $types
        ];
    }

    public static function canViewAllOrders(string $user_role): bool
    {
        return in_array(trim($user_role), ['مدير', 'admin']);
    }

    public static function getAvailableRoles(): array
    {
        return [
            'مدير' => 'مدير',
            'مصمم' => 'مصمم', 
            'معمل' => 'معمل',
            'محاسب' => 'محاسب'
        ];
    }

    public static function getVisibleStatusesForRole(string $user_role): array
    {
        switch (trim($user_role)) {
            case 'مصمم':
                return ['قيد التصميم'];
                
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                return ['قيد التنفيذ', 'جاهز للتسليم'];
                
            case 'محاسب':
                return ['جديد', 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم', 'مكتمل'];
                
            case 'مدير':
                return ['جديد', 'قيد التصميم', 'قيد التنفيذ', 'جاهز للتسليم', 'مكتمل', 'ملغي'];
                
            default:
                return [];
        }
    }
}