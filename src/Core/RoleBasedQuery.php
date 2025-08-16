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
     * @return array مصفوفة تحتوي على where_clauses و params و types
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
        $where_clauses = [];
        $params = [];
        $types = "";
        
        // إخفاء المهام المكتملة والمدفوعة، أو الملغية
        $where_clauses[] = "NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'";

        // إذا كان هناك فلتر لموظف محدد، استخدم منطق الفلترة الخاص به
        if (!empty($filter_employee) && $conn) {
            // تحديد ما إذا كان المستخدم الحالي مدير أم لا
            $is_manager = in_array(trim($user_role), ['مدير', 'admin']) || trim($user_role) === '';
            return self::buildEmployeeFilterConditions($filter_employee, $filter_status, $filter_payment, $search_query, $conn, $is_manager);
        }

        // منطق الفلترة حسب دور المستخدم الحالي
        $trimmed_role = trim($user_role);
        switch ($trimmed_role) {
            case 'مصمم':
                $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                $params[] = $user_id;
                $types .= "i";
                break;
                
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                // يظهر له المهام المعينة له أو المهام غير المعينة لأي معمل
                $where_clauses[] = "((o.workshop_id = ?) OR (o.workshop_id IS NULL AND TRIM(o.status) = 'قيد التنفيذ')) AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                $params[] = $user_id;
                $types .= "i";
                break;
                
            case 'محاسب':
                $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                break;
                
            case 'مدير':
            case 'admin':
                // المدير يرى جميع المهام النشطة (تم تطبيق الفلتر العام أعلاه)
                // لا نضيف شروط إضافية هنا لأن الفلتر العام كافي
                break;
                
            default:
                // للأدوار غير المعرفة، لا يرى أي مهام
                $where_clauses[] = "1=0";
                break;
        }

        // تطبيق الفلاتر الإضافية
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

    /**
     * بناء شروط الفلترة عند اختيار موظف محدد
     */
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
        
        // إخفاء المهام المكتملة والمدفوعة، أو الملغية
        $where_clauses[] = "NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'";

        // الحصول على دور الموظف المحدد
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
                    // المدير يرى جميع مهام هذا المعمل (بدون قيود حالة)
                    $where_clauses[] = "o.workshop_id = ?";
                } else {
                    // الموظف نفسه يرى مهامه المعينة أو غير المعينة في حالات محددة
                    $where_clauses[] = "((o.workshop_id = ?) OR (o.workshop_id IS NULL AND TRIM(o.status) = 'قيد التنفيذ')) AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                }
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

        // تطبيق الفلاتر الإضافية
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

    /**
     * التحقق من إمكانية رؤية المستخدم لجميع الطلبات أم طلباته فقط
     */
    public static function canViewAllOrders(string $user_role): bool
    {
        return in_array(trim($user_role), ['مدير', 'admin']);
    }

    /**
     * الحصول على الأدوار المتاحة في النظام
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

    /**
     * الحصول على الحالات التي يمكن للمستخدم رؤيتها حسب دوره
     */
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
