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
            case 'منخفض': return 'border-secondary';
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
        $is_paid = !empty($order['payment_settled_at']);
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

    public static function generate_timeline_bar(array $order): string
    {
        try {
            $order_date = new DateTime($order['order_date']);
            $now = new DateTime();
            $stages = [];

            if ($order['status'] === 'قيد التنفيذ' && empty($order['design_completed_at'])) {
                $duration = $now->getTimestamp() - $order_date->getTimestamp();
                $label = 'إجمالي الوقت: ' . self::format_duration($duration);
                $title = 'بيانات المراحل غير متوفرة لهذا الطلب القديم';
                return '<div class="progress" style="height: 18px; font-size: 0.7rem;">'
                     . '<div class="progress-bar bg-secondary" role="progressbar" style="width: 100%;" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($label) . '</div>'
                     . '</div>';
            }

            if (!empty($order['design_completed_at'])) {
                $design_end = new DateTime($order['design_completed_at']);
                $duration = $design_end->getTimestamp() - $order_date->getTimestamp();
                if ($duration > 0) {
                    $stages[] = ['label' => 'تصميم: ' . self::format_duration($duration), 'duration' => $duration, 'class' => 'bg-info', 'title' => 'مرحلة التصميم: ' . self::format_duration($duration)];
                }
            } elseif ($order['status'] === 'قيد التصميم') {
                $duration = $now->getTimestamp() - $order_date->getTimestamp();
                if ($duration > 0) {
                    $stages[] = ['label' => 'تصميم (حالي): ' . self::format_duration($duration), 'duration' => $duration, 'class' => 'bg-info', 'title' => 'المرحلة الحالية (تصميم): ' . self::format_duration($duration)];
                }
            }

            if (!empty($order['design_completed_at'])) {
                $design_end = new DateTime($order['design_completed_at']);

                if (!empty($order['execution_completed_at'])) {
                    $exec_end = new DateTime($order['execution_completed_at']);
                    $duration = $exec_end->getTimestamp() - $design_end->getTimestamp();
                    if ($duration > 0) {
                        $stages[] = ['label' => 'تنفيذ: ' . self::format_duration($duration), 'duration' => $duration, 'class' => 'bg-primary', 'title' => 'مرحلة التنفيذ: ' . self::format_duration($duration)];
                    }
                } elseif ($order['status'] === 'قيد التنفيذ') {
                    $duration = $now->getTimestamp() - $design_end->getTimestamp();
                    if ($duration > 0) {
                        $stages[] = ['label' => 'تنفيذ (حالي): ' . self::format_duration($duration), 'duration' => $duration, 'class' => 'bg-primary', 'title' => 'المرحلة الحالية (تنفيذ): ' . self::format_duration($duration)];
                    }
                }
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
}
