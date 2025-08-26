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

        // منطق الفلترة الجديد مع تخصيص view_scope لكل موظف
        // تحديد نطاق العرض (view_scope)
        $view_scope = null;
        if ($conn && $user_id > 0) {
            $stmt = $conn->prepare("SELECT view_scope FROM employees WHERE employee_id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $emp_scope = trim($row['view_scope'] ?? '');
                if (in_array($emp_scope, ['all','role','self'])) {
                    $view_scope = $emp_scope;
                }
            }
        }
        if (!$view_scope && $role_filter) {
            $view_scope = $role_filter['view_scope'] ?? 'self';
        }
        if (in_array($trimmed_role, ['مدير','admin'])) {
            $view_scope = 'all';
        }

        // إذا كان الدور غير مفعل، لا يرى أي شيء
        if (!($role_filter && $role_filter['enabled'])) {
            $where_clauses[] = "1=0";
        } else {
            $stages = $role_filter['stages'] ?? [];
            $filter_type = $role_filter['filter_type'] ?? 'show';
            $payment_stages = $role_filter['payment_stages'] ?? [];
            $exclude_combinations = $role_filter['exclude_combinations'] ?? [];
            $include_combinations = $role_filter['include_combinations'] ?? [];

            // منطق الفلترة حسب نطاق العرض
            // 'all' -> لا شرط
            // 'role' -> حسب دور الموظف العام (e.role)
            // 'self' -> حسب الحقول الخاصة بالأوامر: designer_id أو workshop_id أو employee_id
            if ($view_scope === 'role') {
                // عندما يكون النطاق حسب الدور، نطابق دور الموظف المرتبط بالحقلين المحتملين
                // (ed: designer employee, ew: workshop employee) — هذا يتوافق مع استعلامات العرض التي تستخدم هذه الأسماء المستعارة
                $where_clauses[] = "(ed.role = ? OR ew.role = ?)";
                $params[] = $trimmed_role;
                $params[] = $trimmed_role;
                $types .= "ss";
            } elseif ($view_scope === 'self') {
                // لن نضيف شرط على جدول الموظفين العام هنا لأن شروط الملكية الخاصة بكل دور
                // تُضاف لاحقاً (مثل o.designer_id = ? أو o.workshop_id = ?). نترك المساحة فارغة.
            }

            // إضافة قواعد مخصصة لكل دور ترتبط بحقول الأوامر (ليظهر للمستخدم مهامه الخاصة بدوره)
            // نفعل ذلك فقط عندما ليس لدى الدور صلاحية 'all' الكاملة أو إن كان المراد تقييد العرض بحسب الدور
            if (!in_array($view_scope, ['all'])) {
                switch ($trimmed_role) {
                    case 'مصمم':
                        // عرض المهام المخصصة للمصمم
                        $where_clauses[] = "o.designer_id = ?";
                        $params[] = $user_id;
                        $types .= "i";
                        break;
                    case 'معمل':
                    case 'معمل التنفيذ':
                    case 'المعمل التنفيذي':
                        $where_clauses[] = "o.workshop_id = ?";
                        $params[] = $user_id;
                        $types .= "i";
                        break;
                    case 'محاسب':
                        // المحاسب يرى الفواتير/الطلبات غير المسددة
                        $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                        break;
                    default:
                        // لا تغيير لباقي الأدوار
                        break;
                }
            }
            // 'all' لا يحتاج شرط إضافي

            // منطق الفلترة المركب
            if (!empty($include_combinations)) {
                $include_sql = [];
                foreach ($include_combinations as $comb) {
                    if (isset($comb['status']) && isset($comb['payment_status'])) {
                        $include_sql[] = "(o.status = ? AND o.payment_status = ?)";
                        $params[] = $comb['status'];
                        $types .= "s";
                        $params[] = $comb['payment_status'];
                        $types .= "s";
                    } elseif (isset($comb['status'])) {
                        $include_sql[] = "(o.status = ?)";
                        $params[] = $comb['status'];
                        $types .= "s";
                    } elseif (isset($comb['payment_status'])) {
                        $include_sql[] = "(o.payment_status = ?)";
                        $params[] = $comb['payment_status'];
                        $types .= "s";
                    }
                }
                if (!empty($include_sql)) {
                    $where_clauses[] = "(" . implode(" OR ", $include_sql) . ")";
                }
            } elseif (!empty($exclude_combinations)) {
                $exclude_sql = [];
                foreach ($exclude_combinations as $comb) {
                    if (isset($comb['status']) && isset($comb['payment_status'])) {
                        $exclude_sql[] = "(o.status = ? AND o.payment_status = ?)";
                        $params[] = $comb['status'];
                        $types .= "s";
                        $params[] = $comb['payment_status'];
                        $types .= "s";
                    } elseif (isset($comb['status'])) {
                        $exclude_sql[] = "(o.status = ?)";
                        $params[] = $comb['status'];
                        $types .= "s";
                    } elseif (isset($comb['payment_status'])) {
                        $exclude_sql[] = "(o.payment_status = ?)";
                        $params[] = $comb['payment_status'];
                        $types .= "s";
                    }
                }
                if (!empty($exclude_sql)) {
                    $where_clauses[] = "NOT (" . implode(" OR ", $exclude_sql) . ")";
                }
            } else {
                // منطق الفلترة العادي
                if (!empty($stages)) {
                    $placeholders = implode(',', array_fill(0, count($stages), '?'));
                    if ($filter_type === 'hide') {
                        $where_clauses[] = "o.status NOT IN ($placeholders)";
                    } else {
                        $where_clauses[] = "o.status IN ($placeholders)";
                    }
                    foreach ($stages as $st) {
                        $params[] = $st;
                        $types .= "s";
                    }
                }
                if (!empty($payment_stages)) {
                    $pay_placeholders = implode(',', array_fill(0, count($payment_stages), '?'));
                    if ($filter_type === 'hide') {
                        $where_clauses[] = "o.payment_status NOT IN ($pay_placeholders)";
                    } else {
                        $where_clauses[] = "o.payment_status IN ($pay_placeholders)";
                    }
                    foreach ($payment_stages as $pst) {
                        $params[] = $pst;
                        $types .= "s";
                    }
                }
            }
        }

        // فلترة حسب الموظف إذا تم تحديده (تجاهل للمدير بدون موظف محدد)
        $is_manager = in_array(trim($user_role), ['مدير', 'admin']) || trim($user_role) === '';
        if (!empty($filter_employee) && $conn && !$is_manager) {
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
        
    // للمدير، لا نمنع أي حالة من الحالات المحددة في ملف الفلترة
    if (!$is_manager) {
        // منع ظهور أي مهمة مكتملة ومدفوعة بالكامل أو ملغية في الداش بورد للموظفين فقط
        $where_clauses[] = "NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'";
    }

        $employee_role_query = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
        $employee_role_query->bind_param("i", $filter_employee);
        $employee_role_query->execute();
        $employee_role_result = $employee_role_query->get_result();
        $employee_role = trim($employee_role_result->fetch_assoc()['role'] ?? '');

        // تحديث منطق الفلترة لإضافة الشروط المفقودة
        switch ($employee_role) {
            case 'مصمم':
                $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                $params[] = $filter_employee;
                $types .= "i";
                break;
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                $where_clauses[] = "o.workshop_id = ?";
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
