<?php
declare(strict_types=1);

namespace App\Core;

/**
 * النظام الموحد لإدارة منطق الأدوار في التطبيق
 * 
 * هذا الملف يحتوي على جميع منطق الأدوار ويستخدم في:
 * - عرض البطاقات (المهام النشطة)
 * - الإحصائيات (المهام المكتملة) 
 * - التقارير (الفلترة)
 * - الأذونات (الصلاحيات)
 * 
 * @version 2.0
 * @author نظام إنجاز
 * @since تم إنشاؤه في أغسطس 2025
 */
class UnifiedRoleLogic
{
    // ==================== منطق المهام النشطة (للعرض) ====================
    
    /**
     * الحصول على شروط المهام النشطة للموظف (التي تظهر في البطاقات)
     * 
     * @param string $role دور الموظف
     * @param int $user_id معرف الموظف
     * @param mysqli|null $conn اتصال قاعدة البيانات (اختياري)
     * @return array مصفوفة تحتوي على where_conditions وparams وtypes وdescription
     */
    public static function getActiveTasksConditions(string $role, int $user_id, \mysqli $conn = null): array
    {
        $role = trim($role);
        
        // التحقق من صحة المعاملات
        if (empty($role)) {
            return [
                'where_conditions' => ["1=0"],
                'params' => [],
                'types' => "",
                'description' => 'دور غير صالح'
            ];
        }
        
        if ($user_id < 0) {
            return [
                'where_conditions' => ["1=0"],
                'params' => [],
                'types' => "",
                'description' => 'معرف موظف غير صالح'
            ];
        }
        
        switch ($role) {
            case 'مصمم':
                return [
                    'where_conditions' => ["o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'"],
                    'params' => [$user_id],
                    'types' => "i",
                    'description' => 'المهام التي قيد التصميم فقط'
                ];
                
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                return [
                    'where_conditions' => ["((o.workshop_id = ?) OR (o.workshop_id IS NULL AND TRIM(o.status) = 'قيد التنفيذ')) AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')"],
                    'params' => [$user_id],
                    'types' => "i",
                    'description' => 'المهام قيد التنفيذ والجاهزة للتسليم'
                ];
                
            case 'محاسب':
                return [
                    'where_conditions' => ["o.payment_settled_at IS NULL AND o.total_amount > 0"],
                    'params' => [],
                    'types' => "",
                    'description' => 'الطلبات غير المحاسبة'
                ];
                
            case 'مدير':
            case 'admin':
                return [
                    'where_conditions' => ["NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'"],
                    'params' => [],
                    'types' => "",
                    'description' => 'جميع المهام النشطة'
                ];
                
            default:
                return [
                    'where_conditions' => ["1=0"],
                    'params' => [],
                    'types' => "",
                    'description' => 'لا يوجد صلاحية'
                ];
        }
    }
    
    // ==================== منطق المهام المكتملة (للإحصائيات) ====================
    
    /**
     * الحصول على شروط المهام المكتملة للموظف (للإحصائيات)
     */
    public static function getCompletedTasksConditions(string $role): array
    {
        $role = trim($role);
        
        switch ($role) {
            case 'مصمم':
                return [
                    'sql_condition' => "o.status IN ('قيد التنفيذ', 'جاهز للتسليم', 'مكتمل')",
                    'description' => 'مكتمل عند إرسال للتنفيذ',
                    'relationship' => 'o.designer_id'
                ];
                
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                return [
                    'sql_condition' => "o.status = 'مكتمل'",
                    'description' => 'مكتمل عند تسليم العميل',
                    'relationship' => 'o.workshop_id'
                ];
                
            case 'محاسب':
                return [
                    'sql_condition' => "o.payment_status = 'مدفوع'",
                    'description' => 'مكتمل عند اكتمال الدفع',
                    'relationship' => 'ANY' // يرى جميع الطلبات
                ];
                
            case 'مدير':
                return [
                    'sql_condition' => "o.status = 'مكتمل' AND o.payment_status = 'مدفوع'",
                    'description' => 'مكتمل عند انتهاء كل شيء',
                    'relationship' => 'o.designer_id' // كمصمم أساساً
                ];
                
            default:
                return [
                    'sql_condition' => "o.status = 'مكتمل'",
                    'description' => 'مكتمل عند انتهاء الطلب',
                    'relationship' => 'o.designer_id'
                ];
        }
    }
    
    // ==================== منطق ربط الموظف بالطلبات ====================
    
    /**
     * تحديد كيفية ربط الموظف بالطلبات حسب دوره
     */
    public static function getEmployeeOrderRelationship(string $role): array
    {
        $role = trim($role);
        
        switch ($role) {
            case 'مصمم':
                return [
                    'joins' => ['o.designer_id = e.employee_id'],
                    'description' => 'الطلبات التي يصممها'
                ];
                
            case 'معمل':
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي':
                return [
                    'joins' => ['o.workshop_id = e.employee_id'],
                    'description' => 'الطلبات التي ينفذها'
                ];
                
            case 'محاسب':
                return [
                    'joins' => ['1=1'], // يرى جميع الطلبات
                    'description' => 'جميع الطلبات للمحاسبة'
                ];
                
            case 'مدير':
                return [
                    'joins' => [
                        'o.designer_id = e.employee_id',
                        'o.workshop_id = e.employee_id', 
                        'o.created_by = e.employee_id'
                    ],
                    'description' => 'الطلبات كمصمم أو معمل أو منشئ'
                ];
                
            default:
                return [
                    'joins' => [
                        'o.designer_id = e.employee_id',
                        'o.workshop_id = e.employee_id'
                    ],
                    'description' => 'الطلبات المرتبطة بالموظف'
                ];
        }
    }
    
    // ==================== بناء الاستعلامات الموحدة ====================
    
    /**
     * بناء استعلام المهام النشطة (للبطاقات)
     */
    public static function buildActiveTasksQuery(
        string $user_role, 
        int $user_id, 
        string $filter_status = '',
        string $filter_payment = '',
        string $search_query = '',
        string $filter_employee = '',
        \mysqli $conn = null
    ): array {
        // بدء بشروط فارغة
        $where_clauses = [];
        $params = [];
        $types = "";
        
        // إذا كان هناك فلتر موظف محدد (للمدير)
        if (!empty($filter_employee) && $conn) {
            return self::buildEmployeeFilterQuery($filter_employee, $filter_status, $filter_payment, $search_query, $conn);
        }
        
        // منطق الدور العادي
        $role_conditions = self::getActiveTasksConditions($user_role, $user_id, $conn);
        $where_clauses = array_merge($where_clauses, $role_conditions['where_conditions']);
        $params = array_merge($params, $role_conditions['params']);
        $types .= $role_conditions['types'];
        
        // إضافة فلاتر إضافية
        $additional_filters = self::buildAdditionalFilters($filter_status, $filter_payment, $search_query);
        $where_clauses = array_merge($where_clauses, $additional_filters['where_clauses']);
        $params = array_merge($params, $additional_filters['params']);
        $types .= $additional_filters['types'];
        
        return [
            'where_clauses' => $where_clauses,
            'params' => $params,
            'types' => $types
        ];
    }
    
    /**
     * بناء استعلام إحصائيات الموظفين (موحد)
     */
    public static function buildEmployeeStatsQuery(string $start_date, string $end_date): string
    {
        return "
        SELECT 
            e.employee_id, 
            e.name, 
            e.role,
            COUNT(DISTINCT o.order_id) as total_tasks,
            COUNT(DISTINCT CASE 
                WHEN e.role = 'مصمم' AND o.designer_id = e.employee_id AND o.status IN ('قيد التنفيذ', 'جاهز للتسليم', 'مكتمل') THEN o.order_id
                WHEN e.role = 'مدير' AND o.designer_id = e.employee_id AND o.status = 'مكتمل' AND o.payment_status = 'مدفوع' THEN o.order_id
                WHEN e.role IN ('معمل', 'معمل التنفيذ', 'المعمل التنفيذي') AND o.workshop_id = e.employee_id AND o.status = 'مكتمل' THEN o.order_id
                WHEN e.role = 'محاسب' AND o.payment_status = 'مدفوع' THEN o.order_id
            END) as completed_tasks,
            COUNT(DISTINCT CASE 
                WHEN e.role = 'مصمم' AND o.designer_id = e.employee_id AND o.status = 'قيد التصميم' THEN o.order_id
                WHEN e.role = 'مدير' AND o.designer_id = e.employee_id AND (o.status != 'مكتمل' OR o.payment_status != 'مدفوع') THEN o.order_id
                WHEN e.role IN ('معمل', 'معمل التنفيذ', 'المعمل التنفيذي') AND o.workshop_id = e.employee_id AND o.status IN ('قيد التنفيذ', 'جاهز للتسليم') THEN o.order_id
                WHEN e.role = 'محاسب' AND o.payment_status IN ('غير مدفوع', 'مدفوع جزئياً') THEN o.order_id
            END) as active_tasks,
            SUM(DISTINCT CASE 
                WHEN e.role IN ('مصمم', 'مدير') AND o.designer_id = e.employee_id THEN o.total_amount
                WHEN e.role IN ('معمل', 'معمل التنفيذ', 'المعمل التنفيذي') AND o.workshop_id = e.employee_id THEN o.total_amount
                WHEN e.role = 'محاسب' THEN o.total_amount
            END) as total_revenue
        FROM employees e
        LEFT JOIN orders o ON (
            (e.role IN ('مصمم', 'مدير') AND e.employee_id = o.designer_id) OR
            (e.role IN ('معمل', 'معمل التنفيذ', 'المعمل التنفيذي') AND e.employee_id = o.workshop_id) OR
            (e.role = 'محاسب')
        )
        WHERE (o.order_date BETWEEN ? AND ? OR o.order_date IS NULL)
        GROUP BY e.employee_id, e.name, e.role
        HAVING total_tasks > 0
        ORDER BY completed_tasks DESC, total_revenue DESC";
    }
    
    // ==================== دوال مساعدة ====================
    
    /**
     * بناء شروط فلترة موظف محدد
     */
    private static function buildEmployeeFilterQuery(
        string $filter_employee,
        string $filter_status,
        string $filter_payment, 
        string $search_query,
        \mysqli $conn
    ): array {
        $where_clauses = [];
        $params = [];
        $types = "";
        
        // فلتر الموظف المحدد
        if (!empty($filter_employee) && is_numeric($filter_employee)) {
            $employee_id = (int)$filter_employee;
            
            // جلب معلومات الموظف
            $emp_query = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
            $emp_query->bind_param("i", $employee_id);
            $emp_query->execute();
            $emp_result = $emp_query->get_result();
            
            if ($emp_row = $emp_result->fetch_assoc()) {
                $emp_role = $emp_row['role'];
                
                // استخدام المنطق الموحد للدور المحدد
                $role_conditions = self::getActiveTasksConditions($emp_role, $employee_id, $conn);
                $where_clauses = array_merge($where_clauses, $role_conditions['where_conditions']);
                $params = array_merge($params, $role_conditions['params']);
                $types .= $role_conditions['types'];
            }
        }
        
        // إضافة الفلاتر الإضافية
        $additional_filters = self::buildAdditionalFilters($filter_status, $filter_payment, $search_query);
        $where_clauses = array_merge($where_clauses, $additional_filters['where_clauses']);
        $params = array_merge($params, $additional_filters['params']);
        $types .= $additional_filters['types'];
        
        return [
            'where_clauses' => $where_clauses,
            'params' => $params,
            'types' => $types
        ];
    }
    
    /**
     * بناء الفلاتر الإضافية
     */
    private static function buildAdditionalFilters(
        string $filter_status, 
        string $filter_payment, 
        string $search_query
    ): array {
        $where_clauses = [];
        $params = [];
        $types = "";
        
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
    
    // ==================== معلومات الأدوار ====================
    
    /**
     * الحصول على معلومات شاملة عن دور معين
     */
    public static function getRoleInfo(string $role): array
    {
        $role = trim($role);
        
        $active_conditions = self::getActiveTasksConditions($role, 0, null);
        $completed_conditions = self::getCompletedTasksConditions($role);
        $relationship = self::getEmployeeOrderRelationship($role);
        
        return [
            'role' => $role,
            'active_tasks_description' => $active_conditions['description'],
            'completed_tasks_description' => $completed_conditions['description'],
            'relationship_description' => $relationship['description'],
            'can_view_all' => in_array($role, ['مدير', 'admin']),
            'css_class' => self::getRoleCssClass($role)
        ];
    }
    
    /**
     * الحصول على CSS class للدور
     */
    public static function getRoleCssClass(string $role): string
    {
        switch (trim($role)) {
            case 'مصمم': return 'bg-info';
            case 'معمل': 
            case 'معمل التنفيذ':
            case 'المعمل التنفيذي': return 'bg-warning';
            case 'محاسب': return 'bg-success';
            case 'مدير': return 'bg-primary';
            default: return 'bg-secondary';
        }
    }
    
    /**
     * الحصول على الحالات المرئية للدور
     */
    public static function getVisibleStatusesForRole(string $role): array
    {
        switch (trim($role)) {
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
