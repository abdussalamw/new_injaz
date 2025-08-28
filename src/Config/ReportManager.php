<?php
/**
 * فئة مركزية لإدارة التقارير والإحصائيات
 * توحد منطق إنشاء التقارير والإحصائيات
 */

namespace App\Config;

use App\Config\Constants;
use App\Config\Config;
use App\Config\RoleManager;
use App\Config\TaskManager;

/**
 * فئة إدارة التقارير والإحصائيات
 */
class ReportManager
{
    /**
     * الحصول على تقرير المهام حسب الحالة
     */
    public static function getTasksByStatusReport(string $role, array $settings = []): array
    {
        $query_data = TaskManager::buildFilteredQuery($role, $settings);

        $report_query = "
            SELECT
                o.status,
                COUNT(*) as count,
                SUM(p.price) as total_value,
                AVG(p.price) as avg_value
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
        ";

        if (!empty($query_data['params'])) {
            $report_query .= " WHERE " . implode(" AND ", TaskManager::buildFilterConditions($role, $settings)['conditions']);
        }

        $report_query .= " GROUP BY o.status ORDER BY count DESC";

        return [
            'query' => $report_query,
            'params' => $query_data['params']
        ];
    }

    /**
     * الحصول على تقرير المدفوعات
     */
    public static function getPaymentsReport(string $role, array $settings = []): array
    {
        $query_data = TaskManager::buildFilteredQuery($role, $settings);

        $report_query = "
            SELECT
                COALESCE(pay.status, 'غير محدد') as payment_status,
                COUNT(*) as orders_count,
                SUM(COALESCE(pay.amount, 0)) as total_amount,
                AVG(COALESCE(pay.amount, 0)) as avg_amount,
                MIN(pay.payment_date) as first_payment,
                MAX(pay.payment_date) as last_payment
            FROM orders o
            LEFT JOIN payments pay ON o.id = pay.order_id
        ";

        if (!empty($query_data['params'])) {
            $report_query .= " WHERE " . implode(" AND ", TaskManager::buildFilterConditions($role, $settings)['conditions']);
        }

        $report_query .= " GROUP BY pay.status ORDER BY total_amount DESC";

        return [
            'query' => $report_query,
            'params' => $query_data['params']
        ];
    }

    /**
     * الحصول على تقرير الأداء حسب الموظف
     */
    public static function getEmployeePerformanceReport(string $role, array $settings = []): array
    {
        $query_data = TaskManager::buildFilteredQuery($role, $settings);

        $report_query = "
            SELECT
                e.name as employee_name,
                e.id as employee_id,
                COUNT(o.id) as total_tasks,
                SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getCompletedStatuses()) . "') THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN pay.status = 'مدفوع' THEN 1 ELSE 0 END) as paid_tasks,
                SUM(COALESCE(pay.amount, 0)) as total_revenue,
                ROUND(
                    (SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getCompletedStatuses()) . "') THEN 1 ELSE 0 END) / COUNT(o.id)) * 100,
                    2
                ) as completion_rate
            FROM employees e
            LEFT JOIN orders o ON e.id = o.employee_id
            LEFT JOIN payments pay ON o.id = pay.order_id
            LEFT JOIN products p ON o.product_id = p.id
        ";

        if (!empty($query_data['params'])) {
            $report_query .= " WHERE " . implode(" AND ", TaskManager::buildFilterConditions($role, $settings)['conditions']);
        }

        $report_query .= " GROUP BY e.id, e.name ORDER BY total_revenue DESC";

        return [
            'query' => $report_query,
            'params' => $query_data['params']
        ];
    }

    /**
     * الحصول على تقرير العملاء
     */
    public static function getClientsReport(string $role, array $settings = []): array
    {
        $query_data = TaskManager::buildFilteredQuery($role, $settings);

        $report_query = "
            SELECT
                c.name as client_name,
                c.phone as client_phone,
                c.id as client_id,
                COUNT(o.id) as total_orders,
                SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getCompletedStatuses()) . "') THEN 1 ELSE 0 END) as completed_orders,
                SUM(COALESCE(pay.amount, 0)) as total_paid,
                MAX(o.created_at) as last_order_date,
                AVG(p.price) as avg_order_value
            FROM clients c
            LEFT JOIN orders o ON c.id = o.client_id
            LEFT JOIN payments pay ON o.id = pay.order_id
            LEFT JOIN products p ON o.product_id = p.id
        ";

        if (!empty($query_data['params'])) {
            $report_query .= " WHERE " . implode(" AND ", TaskManager::buildFilterConditions($role, $settings)['conditions']);
        }

        $report_query .= " GROUP BY c.id, c.name, c.phone ORDER BY total_orders DESC";

        return [
            'query' => $report_query,
            'params' => $query_data['params']
        ];
    }

    /**
     * الحصول على تقرير المنتجات
     */
    public static function getProductsReport(string $role, array $settings = []): array
    {
        $query_data = TaskManager::buildFilteredQuery($role, $settings);

        $report_query = "
            SELECT
                p.name as product_name,
                p.id as product_id,
                p.price as product_price,
                COUNT(o.id) as total_orders,
                SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getCompletedStatuses()) . "') THEN 1 ELSE 0 END) as completed_orders,
                SUM(COALESCE(pay.amount, 0)) as total_revenue,
                ROUND(
                    (COUNT(o.id) / (SELECT COUNT(*) FROM orders)) * 100,
                    2
                ) as popularity_percentage
            FROM products p
            LEFT JOIN orders o ON p.id = o.product_id
            LEFT JOIN payments pay ON o.id = pay.order_id
        ";

        if (!empty($query_data['params'])) {
            $report_query .= " WHERE " . implode(" AND ", TaskManager::buildFilterConditions($role, $settings)['conditions']);
        }

        $report_query .= " GROUP BY p.id, p.name, p.price ORDER BY total_orders DESC";

        return [
            'query' => $report_query,
            'params' => $query_data['params']
        ];
    }

    /**
     * الحصول على تقرير زمني
     */
    public static function getTimelineReport(string $role, array $settings = [], string $period = 'month'): array
    {
        $query_data = TaskManager::buildFilteredQuery($role, $settings);

        $date_format = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        $report_query = "
            SELECT
                DATE_FORMAT(o.created_at, '{$date_format}') as period,
                COUNT(*) as total_orders,
                SUM(CASE WHEN o.status IN ('" . implode("','", Constants::getCompletedStatuses()) . "') THEN 1 ELSE 0 END) as completed_orders,
                SUM(COALESCE(pay.amount, 0)) as total_revenue,
                AVG(p.price) as avg_order_value
            FROM orders o
            LEFT JOIN payments pay ON o.id = pay.order_id
            LEFT JOIN products p ON o.product_id = p.id
        ";

        if (!empty($query_data['params'])) {
            $report_query .= " WHERE " . implode(" AND ", TaskManager::buildFilterConditions($role, $settings)['conditions']);
        }

        $report_query .= " GROUP BY period ORDER BY period DESC";

        return [
            'query' => $report_query,
            'params' => $query_data['params']
        ];
    }

    /**
     * الحصول على إحصائيات عامة
     */
    public static function getGeneralStatistics(string $role, array $settings = []): array
    {
        $stats = [];

        // إحصائيات المهام
        $task_stats = TaskManager::getTaskStatistics($role, $settings);
        $stats['tasks'] = $task_stats;

        // إحصائيات المالية
        $financial_query = "
            SELECT
                SUM(COALESCE(pay.amount, 0)) as total_revenue,
                AVG(COALESCE(pay.amount, 0)) as avg_payment,
                COUNT(CASE WHEN pay.status = 'مدفوع' THEN 1 END) as paid_orders,
                COUNT(CASE WHEN pay.status != 'مدفوع' OR pay.status IS NULL THEN 1 END) as unpaid_orders
            FROM orders o
            LEFT JOIN payments pay ON o.id = pay.order_id
        ";

        $filter_data = TaskManager::buildFilterConditions($role, $settings);
        if (!empty($filter_data['conditions'])) {
            $financial_query .= " WHERE " . implode(" AND ", $filter_data['conditions']);
        }

        $stats['financial'] = [
            'query' => $financial_query,
            'params' => $filter_data['params']
        ];

        return $stats;
    }

    /**
     * تصدير التقرير إلى CSV
     */
    public static function exportToCSV(array $data, string $filename): string
    {
        $temp_file = sys_get_temp_dir() . '/' . $filename . '.csv';

        $fp = fopen($temp_file, 'w');

        // كتابة رؤوس الأعمدة
        if (!empty($data)) {
            fputcsv($fp, array_keys($data[0]));
        }

        // كتابة البيانات
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $temp_file;
    }

    /**
     * إنشاء تقرير PDF (يتطلب مكتبة إضافية)
     */
    public static function generatePDFReport(array $data, string $title, string $template = 'default'): string
    {
        // يمكن إضافة منطق إنشاء PDF هنا
        // يتطلب مكتبة مثل TCPDF أو FPDF

        $temp_file = sys_get_temp_dir() . '/' . uniqid('report_') . '.pdf';

        // منطق إنشاء PDF
        // ...

        return $temp_file;
    }
}
?>
