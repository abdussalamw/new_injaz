<?php
/**
 * فئة مركزية لإدارة الإشعارات والرسائل
 * توحد منطق الإشعارات والرسائل في النظام
 */

namespace App\Config;

use App\Config\Config;
use App\Config\DatabaseManager;

/**
 * فئة إدارة الإشعارات والرسائل
 */
class NotificationManager
{
    /**
     * أنواع الإشعارات المتاحة
     */
    const NOTIFICATION_TYPES = [
        'task_assigned' => 'تم تعيين مهمة جديدة',
        'task_completed' => 'تم إكمال مهمة',
        'payment_received' => 'تم استلام دفعة',
        'payment_overdue' => 'دفعة متأخرة',
        'client_message' => 'رسالة من عميل',
        'system_alert' => 'تنبيه نظام',
        'deadline_approaching' => 'اقتراب موعد التسليم'
    ];

    /**
     * أولويات الإشعارات
     */
    const PRIORITIES = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'urgent' => 'عاجلة'
    ];

    /**
     * إنشاء إشعار جديد
     */
    public static function createNotification(
        int $user_id,
        string $type,
        string $title,
        string $message,
        string $priority = 'medium',
        array $metadata = []
    ): int {
        try {
            $data = [
                'user_id' => $user_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'priority' => $priority,
                'metadata' => json_encode($metadata),
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];

            return DatabaseManager::insert('notifications', $data);
        } catch (\Exception $e) {
            error_log("فشل إنشاء الإشعار: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * إرسال إشعار لجميع الموظفين في دور معين
     */
    public static function notifyRole(
        string $role,
        string $type,
        string $title,
        string $message,
        string $priority = 'medium',
        array $metadata = []
    ): array {
        $results = [];

        try {
            // الحصول على جميع الموظفين في الدور المحدد
            $employees = DatabaseManager::select(
                "SELECT id FROM employees WHERE role = ?",
                [$role]
            );

            foreach ($employees as $employee) {
                $notification_id = self::createNotification(
                    $employee['id'],
                    $type,
                    $title,
                    $message,
                    $priority,
                    $metadata
                );

                $results[] = [
                    'employee_id' => $employee['id'],
                    'notification_id' => $notification_id,
                    'success' => $notification_id > 0
                ];
            }
        } catch (\Exception $e) {
            error_log("فشل إرسال الإشعارات للدور: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * إرسال إشعار لجميع المديرين
     */
    public static function notifyAdmins(
        string $type,
        string $title,
        string $message,
        string $priority = 'high',
        array $metadata = []
    ): array {
        return self::notifyRole('مدير', $type, $title, $message, $priority, $metadata);
    }

    /**
     * تحديث حالة الإشعار كمقروء
     */
    public static function markAsRead(int $notification_id, int $user_id): bool
    {
        try {
            return DatabaseManager::update(
                'notifications',
                ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
                ['id' => $notification_id, 'user_id' => $user_id]
            );
        } catch (\Exception $e) {
            error_log("فشل تحديث حالة الإشعار: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تحديث حالة جميع إشعارات المستخدم كمقروءة
     */
    public static function markAllAsRead(int $user_id): bool
    {
        try {
            return DatabaseManager::update(
                'notifications',
                ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
                ['user_id' => $user_id, 'is_read' => 0]
            );
        } catch (\Exception $e) {
            error_log("فشل تحديث جميع الإشعارات: " . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على إشعارات المستخدم
     */
    public static function getUserNotifications(
        int $user_id,
        bool $unread_only = false,
        int $limit = 50,
        int $offset = 0
    ): array {
        try {
            $query = "SELECT * FROM notifications WHERE user_id = ?";
            $params = [$user_id];

            if ($unread_only) {
                $query .= " AND is_read = 0";
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $notifications = DatabaseManager::select($query, $params);

            // تحويل البيانات الوصفية من JSON
            foreach ($notifications as &$notification) {
                if (!empty($notification['metadata'])) {
                    $notification['metadata'] = json_decode($notification['metadata'], true);
                }
            }

            return $notifications;
        } catch (\Exception $e) {
            error_log("فشل الحصول على الإشعارات: " . $e->getMessage());
            return [];
        }
    }

    /**
     * الحصول على عدد الإشعارات غير المقروءة
     */
    public static function getUnreadCount(int $user_id): int
    {
        try {
            return DatabaseManager::getRowCount('notifications', [
                'user_id' => $user_id,
                'is_read' => 0
            ]);
        } catch (\Exception $e) {
            error_log("فشل الحصول على عدد الإشعارات: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * حذف إشعار
     */
    public static function deleteNotification(int $notification_id, int $user_id): bool
    {
        try {
            return DatabaseManager::delete('notifications', [
                'id' => $notification_id,
                'user_id' => $user_id
            ]);
        } catch (\Exception $e) {
            error_log("فشل حذف الإشعار: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تنظيف الإشعارات القديمة
     */
    public static function cleanupOldNotifications(int $days_old = 30): int
    {
        try {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));

            // حذف الإشعارات القديمة والمقروءة
            $deleted = DatabaseManager::execute(
                "DELETE FROM notifications WHERE is_read = 1 AND created_at < ?",
                [$cutoff_date]
            );

            return $deleted ? DatabaseManager::getConnection()->rowCount() : 0;
        } catch (\Exception $e) {
            error_log("فشل تنظيف الإشعارات القديمة: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * إرسال إشعار عند تعيين مهمة جديدة
     */
    public static function notifyTaskAssigned(int $employee_id, int $order_id, string $client_name): void
    {
        self::createNotification(
            $employee_id,
            'task_assigned',
            'مهمة جديدة',
            "تم تعيين مهمة جديدة للعميل: {$client_name}",
            'medium',
            ['order_id' => $order_id]
        );
    }

    /**
     * إرسال إشعار عند إكمال مهمة
     */
    public static function notifyTaskCompleted(int $order_id, string $client_name): void
    {
        // إشعار للمديرين
        self::notifyAdmins(
            'task_completed',
            'تم إكمال مهمة',
            "تم إكمال المهمة للعميل: {$client_name}",
            'medium',
            ['order_id' => $order_id]
        );
    }

    /**
     * إرسال إشعار عند استلام دفعة
     */
    public static function notifyPaymentReceived(int $order_id, string $client_name, float $amount): void
    {
        self::notifyAdmins(
            'payment_received',
            'تم استلام دفعة',
            "تم استلام دفعة بقيمة {$amount} ريال من العميل: {$client_name}",
            'high',
            ['order_id' => $order_id, 'amount' => $amount]
        );
    }

    /**
     * إرسال إشعار للمهام المتأخرة
     */
    public static function notifyOverdueTasks(): void
    {
        try {
            // البحث عن المهام المتأخرة
            $overdue_tasks = DatabaseManager::select("
                SELECT o.id, o.employee_id, c.name as client_name
                FROM orders o
                JOIN clients c ON o.client_id = c.id
                WHERE o.status NOT IN ('مكتملة', 'ملغية')
                AND o.deadline < CURDATE()
            ");

            foreach ($overdue_tasks as $task) {
                self::createNotification(
                    $task['employee_id'],
                    'deadline_approaching',
                    'مهمة متأخرة',
                    "المهمة للعميل {$task['client_name']} متأخرة عن الموعد المحدد",
                    'urgent',
                    ['order_id' => $task['id']]
                );
            }
        } catch (\Exception $e) {
            error_log("فشل إرسال إشعارات المهام المتأخرة: " . $e->getMessage());
        }
    }

    /**
     * إرسال إشعار للمدفوعات المتأخرة
     */
    public static function notifyOverduePayments(): void
    {
        try {
            // البحث عن المدفوعات المتأخرة
            $overdue_payments = DatabaseManager::select("
                SELECT o.id, o.employee_id, c.name as client_name, pay.amount
                FROM orders o
                JOIN clients c ON o.client_id = c.id
                JOIN payments pay ON o.id = pay.order_id
                WHERE pay.status != 'مدفوع'
                AND pay.due_date < CURDATE()
            ");

            foreach ($overdue_payments as $payment) {
                self::createNotification(
                    $payment['employee_id'],
                    'payment_overdue',
                    'دفعة متأخرة',
                    "الدفعة بقيمة {$payment['amount']} ريال للعميل {$payment['client_name']} متأخرة",
                    'urgent',
                    ['order_id' => $payment['id'], 'amount' => $payment['amount']]
                );
            }
        } catch (\Exception $e) {
            error_log("فشل إرسال إشعارات المدفوعات المتأخرة: " . $e->getMessage());
        }
    }

    /**
     * إرسال إشعارات دورية
     */
    public static function sendPeriodicNotifications(): void
    {
        // إشعارات المهام المتأخرة
        self::notifyOverdueTasks();

        // إشعارات المدفوعات المتأخرة
        self::notifyOverduePayments();

        // تنظيف الإشعارات القديمة
        self::cleanupOldNotifications();
    }
}
?>
