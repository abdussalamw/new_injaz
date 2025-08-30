<?php
declare(strict_types=1);

namespace App\Core;

use DateTime;
use Exception;

class Helpers
{
    public static function get_priority_class(string $priority): string
    {
        switch ($priority) {
            case 'عاجل جداً': return 'border-danger';
            case 'عالي': return 'border-warning';
            case 'متوسط': return 'border-info';
            default: return 'border-light';
        }
    }


    public static function get_status_class(string $status): string
    {
        $classes = [
            'قيد التصميم' => 'status-design',
            'قيد التنفيذ' => 'status-execution',
            'جاهز للتسليم' => 'status-ready',
            'مكتمل' => 'status-completed',
            'ملغي' => 'status-cancelled',
        ];
        return $classes[trim($status)] ?? 'status-default';
    }

    public static function get_status_badge_class(string $status): string
    {
        $classes = [
            'قيد التصميم' => 'bg-warning text-dark',
            'قيد التنفيذ' => 'bg-info',
            'جاهز للتسليم' => 'bg-primary',
            'مكتمل' => 'bg-success',
            'ملغي' => 'bg-danger',
        ];
        return $classes[trim($status)] ?? 'bg-secondary';
    }

    public static function get_payment_badge_class(string $payment_status): string
    {
        $classes = [
            'مدفوع' => 'bg-success',
            'مدفوع جزئياً' => 'bg-warning text-dark',
            'غير مدفوع' => 'bg-danger',
        ];
        return $classes[trim($payment_status)] ?? 'bg-secondary';
    }

    public static function get_payment_status_display(string $payment_status_from_db, float $total_amount, float $deposit_amount): string
    {
        $recalculated_status = '';
        if ($total_amount <= 0) {
            $recalculated_status = 'غير مدفوع';
        } elseif ($deposit_amount >= $total_amount) {
            $recalculated_status = 'مدفوع';
        } elseif ($deposit_amount > 0) {
            $recalculated_status = 'مدفوع جزئياً';
        } else {
            $recalculated_status = 'غير مدفوع';
        }

        if ($recalculated_status === 'مدفوع') {
            return '<div class="progress" style="height: 20px;" title="مدفوع بالكامل"><div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">مدفوع: ' . number_format($total_amount, 2) . '</div></div>';
        }

        if ($recalculated_status === 'غير مدفوع') {
            $title = $total_amount <= 0 ? 'غير مدفوع (إجمالي صفر)' : 'غير مدفوع';
            return '<div class="progress" style="height: 20px;" title="' . $title . '"><div class="progress-bar bg-danger" role="progressbar" style="width: 100%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">المتبقي: ' . number_format($total_amount, 2) . '</div></div>';
        }

        if ($recalculated_status === 'مدفوع جزئياً') {
            $paid_percentage = ($deposit_amount / $total_amount) * 100;
            $remaining_percentage = 100 - $paid_percentage;
            $remaining_amount = $total_amount - $deposit_amount;
            return '<div class="progress" style="height: 20px;" title="مدفوع جزئياً: ' . number_format($paid_percentage, 0) . '%">'
                 . '<div class="progress-bar bg-success" role="progressbar" style="width: ' . $paid_percentage . '%;" aria-valuenow="' . $paid_percentage . '" aria-valuemin="0" aria-valuemax="100">' . number_format($deposit_amount, 2) . '</div>'
                 . '<div class="progress-bar bg-warning text-dark" role="progressbar" style="width: ' . $remaining_percentage . '%;" aria-valuenow="' . $remaining_percentage . '" aria-valuemin="0" aria-valuemax="100">' . number_format($remaining_amount, 2) . '</div>'
                 . '</div>';
        }

        return '<span class="badge bg-secondary">' . htmlspecialchars($payment_status_from_db) . '</span>';
    }

    public static function get_next_actions(array $order, string $user_role, int $user_id, \mysqli $conn, string $context = 'dashboard'): array
    {

        if ($context === 'orders_page') {
            return [];
        }

        $actions = [];
        $status = trim($order['status'] ?? '');
        $is_delivered = !empty($order['delivered_at']);

        // حساب حالة الدفع بنفس منطق get_payment_status_display
        $total_amount = (float)($order['total_amount'] ?? 0);
        $deposit_amount = (float)($order['deposit_amount'] ?? 0);
        $payment_settled_at = $order['payment_settled_at'] ?? null;

        $is_paid = false;
        // إذا كان الطلب مكتمل وله تاريخ تسوية الدفع، فهو مدفوع
        if (!empty($payment_settled_at) && trim($order['status'] ?? '') === 'مكتمل') {
            $is_paid = true;
        }
        // أو إذا كان له مبالغ صحيحة وتم دفع المبلغ الكامل
        elseif ($total_amount > 0 && $deposit_amount >= $total_amount) {
            $is_paid = true;
        }

        $is_creator = ($order['created_by'] == $user_id);
        $is_designer = ($order['designer_id'] == $user_id);

        if (!$is_paid) {
            if ($user_role === 'محاسب' && Permissions::has_permission('order_financial_settle', $conn)) {
                $actions['update_payment'] = ['label' => 'تحديث حالة الدفع', 'class' => 'btn-success', 'icon' => 'bi-cash-coin'];
            } elseif ($user_role === 'مدير' && Permissions::has_permission('order_financial_settle', $conn)) {
                $actions['confirm_payment'] = ['label' => 'تأكيد الدفع الكامل', 'class' => 'btn-success', 'icon' => 'bi-cash-coin'];
            }
        }

        if (!$is_delivered && $status === 'جاهز للتسليم' && (in_array($user_role, ['مدير', 'معمل']) || $is_creator)) {
            $actions['confirm_delivery'] = ['label' => 'تأكيد استلام العميل', 'class' => 'btn-primary', 'icon' => 'bi-box-arrow-in-down'];
        }

        if ($is_delivered && $is_paid && $status !== 'مكتمل' && $user_role === 'مدير') {
            $actions['close_order'] = ['label' => 'إغلاق الطلب نهائياً', 'class' => 'btn-dark', 'icon' => 'bi-archive-fill'];
        }

        $status_changes = [];
        if ($status !== 'مكتمل' && $status !== 'ملغي') {
            switch ($status) {
                case 'قيد التصميم':
                    if ($user_role === 'مدير' || ($user_role === 'مصمم' && $is_designer)) {
                        $status_changes['قيد التنفيذ'] = [
                            'label' => 'إرسال للتنفيذ',
                            'confirm_message' => 'تأكد بأنك قمت بمراجعة جميع التصاميم المطلوبة وإرسالها للمعمل للتنفيذ؟'
                        ];
                    }
                    break;
                case 'قيد التنفيذ':
                    if (in_array($user_role, ['مدير', 'معمل'])) {
                        $client_phone = trim($order['client_phone'] ?? '');
                        if (!empty($client_phone)) {
                            $status_changes['جاهز للتسليم'] = [
                                'label' => 'تحديد كـ "جاهز للتسليم"',
                                'confirm_message' => 'هل أنت متأكد من أن الطلب جاهز بالكامل للتسليم للعميل؟ سيتم إرسال إشعار للعميل عبر واتساب.',
                                'whatsapp_action' => true
                            ];
                        } else {
                            $status_changes['جاهز للتسليم'] = [
                                'label' => 'تحديد كـ "جاهز للتسليم"',
                                'confirm_message' => 'هل أنت متأكد؟ (لا يمكن إرسال واتساب لعدم وجود رقم جوال للعميل)'
                            ];
                        }
                    }
                    break;
            }
        }

        if (!empty($status_changes)) {
            $actions['change_status'] = [
                'label' => $status,
                'class' => self::get_status_class($status),
                'options' => $status_changes
            ];
        }

        return $actions;
    }

    public static function format_duration(int $seconds): string
    {
        if ($seconds < 0) {
            $seconds = 0;
        }
        if ($seconds < 60) {
            return "أقل من دقيقة";
        }

        $days = floor($seconds / 86400);
        $seconds %= 86400;
        $hours = floor($seconds / 3600);
        $seconds %= 3600;
        $minutes = floor($seconds / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . " يوم";
        }
        if ($hours > 0) {
            $parts[] = $hours . " ساعة";
        }
        if ($minutes > 0 && $days == 0) {
            $parts[] = $minutes . " دقيقة";
        }

        return empty($parts) ? "لحظات" : implode(' و ', array_slice($parts, 0, 2));
    }

    public static function calculate_stage_duration(?string $start_date_str, ?string $end_date_str): ?int
    {
        if (empty($start_date_str) || empty($end_date_str)) {
            return null;
        }
        try {
            $start_date = new DateTime($start_date_str);
            $end_date = new DateTime($end_date_str);
            return $end_date->getTimestamp() - $start_date->getTimestamp();
        } catch (Exception $e) {
            return null;
        }
    }

    public static function calculate_current_stage_duration(?string $start_date_str): ?int
    {
        if (empty($start_date_str)) {
            return null;
        }
        try {
            $start_date = new DateTime($start_date_str);
            $now = new DateTime();
            return $now->getTimestamp() - $start_date->getTimestamp();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * دالة موحدة لحساب توقيت الطلب بالكامل
     * تستخدم الحقول الصحيحة مع الرجوع للبدائل
     */
    public static function calculate_order_timeline(array $order): array
    {
        $timeline = [
            'design_duration' => null,
            'execution_duration' => null,
            'total_duration' => null,
            'design_start' => null,
            'design_end' => null,
            'execution_start' => null,
            'execution_end' => null,
            'is_current_design' => false,
            'is_current_execution' => false
        ];

        try {
            $now = new DateTime();
            $order_date = !empty($order['order_date']) ? new DateTime($order['order_date']) : $now;
            $status = $order['status'] ?? '';

            // تحديد بداية التصميم (موحدة على order_date)
            $design_start = null;
            if (!empty($order['order_date'])) {
                $design_start = new DateTime($order['order_date']);
            }

            // تحديد نهاية التصميم
            $design_end = !empty($order['design_completed_at']) ? new DateTime($order['design_completed_at']) : null;

            // تحديد بداية التنفيذ (مع الأولوية للحقل الفعلي)
            $execution_start = null;
            if (!empty($order['execution_started_at'])) {
                $execution_start = new DateTime($order['execution_started_at']);
            } elseif (!empty($order['design_completed_at'])) {
                $execution_start = new DateTime($order['design_completed_at']);
            }

            // تحديد نهاية التنفيذ
            $execution_end = !empty($order['execution_completed_at']) ? new DateTime($order['execution_completed_at']) : null;

            // حساب مدة التصميم
            if ($design_start && $design_end) {
                $timeline['design_duration'] = $design_end->getTimestamp() - $design_start->getTimestamp();
            } elseif ($design_start && $status === 'قيد التصميم') {
                $timeline['design_duration'] = $now->getTimestamp() - $design_start->getTimestamp();
                $timeline['is_current_design'] = true;
            }

            // حساب مدة التنفيذ
            if ($execution_start && $execution_end) {
                $timeline['execution_duration'] = $execution_end->getTimestamp() - $execution_start->getTimestamp();
            } elseif ($execution_start && $status === 'قيد التنفيذ') {
                $timeline['execution_duration'] = $now->getTimestamp() - $execution_start->getTimestamp();
                $timeline['is_current_execution'] = true;
            }

            // حساب المدة الإجمالية
            if ($execution_end) {
                $timeline['total_duration'] = $execution_end->getTimestamp() - $order_date->getTimestamp();
            } else {
                $timeline['total_duration'] = $now->getTimestamp() - $order_date->getTimestamp();
            }

            // حفظ التواريخ للاستخدام في العرض
            $timeline['design_start'] = $design_start;
            $timeline['design_end'] = $design_end;
            $timeline['execution_start'] = $execution_start;
            $timeline['execution_end'] = $execution_end;

        } catch (Exception $e) {
            // في حالة خطأ، نعيد القيم الافتراضية
        }

        return $timeline;
    }

    public static function generate_timeline_bar(array $order): string
    {
        try {
            // استخدام الدالة الموحدة لحساب التوقيت
            $timeline = self::calculate_order_timeline($order);
            $now = new DateTime();
            $stages = [];

            // التعامل مع الطلبات القديمة التي لا تحتوي على بيانات المراحل
            if ($order['status'] === 'قيد التنفيذ' && empty($order['design_completed_at']) && empty($order['design_started_at'])) {
                $duration = $timeline['total_duration'];
                $label = 'إجمالي الوقت: ' . self::format_duration($duration);
                $title = 'بيانات المراحل غير متوفرة لهذا الطلب القديم';
                return '<div class="progress" style="height: 18px; font-size: 0.7rem;">'
                     . '<div class="progress-bar bg-secondary" role="progressbar" style="width: 100%;" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($label) . '</div>'
                     . '</div>';
            }

            // مرحلة التصميم
            if ($timeline['design_duration'] !== null) {
                $label_suffix = $timeline['is_current_design'] ? ' (حالي)' : '';
                $stages[] = [
                    'label' => 'تصميم' . $label_suffix . ': ' . self::format_duration($timeline['design_duration']),
                    'duration' => $timeline['design_duration'],
                    'class' => 'bg-info',
                    'title' => 'مرحلة التصميم' . $label_suffix . ': ' . self::format_duration($timeline['design_duration'])
                ];
            }

            // مرحلة التنفيذ
            if ($timeline['execution_duration'] !== null) {
                $label_suffix = $timeline['is_current_execution'] ? ' (حالي)' : '';
                $stages[] = [
                    'label' => 'تنفيذ' . $label_suffix . ': ' . self::format_duration($timeline['execution_duration']),
                    'duration' => $timeline['execution_duration'],
                    'class' => 'bg-primary',
                    'title' => 'مرحلة التنفيذ' . $label_suffix . ': ' . self::format_duration($timeline['execution_duration'])
                ];
            }

            if (empty($stages)) {
                return '';
            }

            $total_visible_duration = array_sum(array_column($stages, 'duration'));
            if ($total_visible_duration <= 0) {
                return '';
            }

            $html = '<div class="progress" style="height: 18px; font-size: 0.7rem;">';
            $stage_count = count($stages);
            foreach ($stages as $stage) {
                $percentage = ($stage['duration'] / $total_visible_duration) * 100;
                $border_style = ($stage_count > 1 && $percentage < 99) ? 'border-left: 2px solid white;' : '';
                if ($percentage > 1) {
                    $html .= '<div class="progress-bar ' . $stage['class'] . '" role="progressbar" style="width: ' . $percentage . '%;' . $border_style . '" title="' . htmlspecialchars($stage['title']) . '">' . htmlspecialchars($stage['label']) . '</div>';
                }
            }
            $html .= '</div>';
            return $html;
        } catch (Exception $e) {
            return '';
        }
    }

    public static function format_whatsapp_link(string $phone_number, string $message = ''): string
    {
        if (empty($phone_number)) {
            return '#';
        }
        $cleaned_phone = preg_replace('/[^0-9]/', '', $phone_number);
        $saudi_number = substr($cleaned_phone, -9);
        $international_number = '966' . $saudi_number;
        
        $url = 'https://wa.me/' . $international_number;
        if (!empty($message)) {
            $url .= '?text=' . urlencode($message);
        }
        return $url;
    }

    public static function generate_sort_link(string $column, string $title, string $sort_column_key, string $sort_order): string
    {
        $order = ($sort_column_key === $column && $sort_order === 'asc') ? 'desc' : 'asc';
        $icon = '';
        if ($sort_column_key === $column) {
            $icon = $sort_order === 'asc' ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>';
        }
        
        // الحفاظ على الفلاتر الحالية
        $current_params = $_GET;
        $current_params['sort'] = $column;
        $current_params['order'] = $order;
        $query_string = http_build_query($current_params);
        
        return "<a href=\"?{$query_string}\">{$title}{$icon}</a>";
    }

    public static function generate_non_sort_column(string $title): string
    {
        return $title;
    }

    public static function display_products_summary(string $summary): string
    {
        $items = explode(', ', $summary);
        if (count($items) > 2) {
            return htmlspecialchars($items[0] . ', ' . $items[1] . '...') . ' <span class="badge bg-secondary">+' . (count($items) - 2) . '</span>';
        }
        return htmlspecialchars($summary);
    }

    // Helper functions for URL paths
    public static function url($path = '') {
        return $_ENV['BASE_PATH'] . $path;
    }

    public static function asset($path) {
        return self::url('/public/assets/' . $path);
    }

    public static function css($filename) {
        return self::url('/public/css/' . $filename);
    }

    public static function js($filename) {
        return self::url('/public/js/' . $filename);
    }
    /**
     * Display a clear error message with error log details
     * @param string $userMessage
     * @param string|null $errorLogPath
     */
    public static function showErrorWithLog($userMessage, $errorLogPath = null) {
        echo '<div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0;border:1px solid #f5c6cb;">';
        echo '<strong>خطأ فني!</strong> ' . htmlspecialchars($userMessage);
        if ($errorLogPath && file_exists($errorLogPath)) {
            echo '<br><details style="margin-top:10px;"><summary>عرض سجل الأخطاء</summary>';
            echo '<pre style="background:#fff;border:1px solid #ccc;padding:10px;max-height:300px;overflow:auto;">';
            echo htmlspecialchars(file_get_contents($errorLogPath));
            echo '</pre></details>';
        }
        echo '</div>';
    }

    /**
     * تنسيق المدة بالتفصيل (للمصممين والمعامل)
     * @param int|null $seconds
     * @return string
     */
    public static function format_duration_detailed(?int $seconds): string {
        if ($seconds === null) return '0د';
        if ($seconds < 60) return '1د'; // أقل من دقيقة نعرض دقيقة واحدة

        $d = floor($seconds / 86400);
        $h = floor(($seconds % 86400) / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        $parts = [];
        if ($d > 0) $parts[] = $d . 'ي';
        if ($h > 0) $parts[] = $h . 'س';
        if ($m > 0 || ($d == 0 && $h == 0)) $parts[] = $m . 'د';
        if ($s > 0 && $d == 0 && $h == 0) $parts[] = $s . 'ث';

        return implode(' ', array_slice($parts, 0, 4)); // عرض حتى 4 أجزاء كحد أقصى
    }

    /**
     * تنسيق المدة المختصر (للمديرين والآخرين)
     * @param int|null $seconds
     * @return string
     */
    public static function format_duration_compact(?int $seconds): string {
        if ($seconds === null) return '0د';
        if ($seconds < 60) return '1د'; // أقل من دقيقة نعرض دقيقة واحدة

        $d = floor($seconds / 86400);
        $h = floor(($seconds % 86400) / 3600);
        $m = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($d > 0) $parts[] = $d . 'ي';
        if ($h > 0) $parts[] = $h . 'س';
        if ($m > 0 && $d == 0) $parts[] = $m . 'د';

        return implode(' ', array_slice($parts, 0, 2)); // عرض جزئين كحد أقصى
    }

    /**
     * تنسيق المدة حسب الدور
     * @param int|null $seconds
     * @param bool $isDesignerOrWorkshop
     * @return string
     */
    public static function format_duration_by_role(?int $seconds, bool $isDesignerOrWorkshop = false): string {
        return $isDesignerOrWorkshop
            ? self::format_duration_detailed($seconds)
            : self::format_duration_compact($seconds);
    }
}
