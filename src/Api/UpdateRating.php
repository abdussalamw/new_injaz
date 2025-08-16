<?php
declare(strict_types=1);

namespace App\Api;

use App\Core\Permissions;

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Note: The router handles session_start, db_connection, and auth checks.

// Check if $conn is available and is a valid mysqli connection
if (!isset($conn) || !($conn instanceof \mysqli) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات.']);
    exit;
}

if (!Permissions::has_permission('dashboard_reports_view', $conn)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مسموح لك بتقييم المراحل']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id']) || !isset($input['stage']) || !isset($input['rating'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة']);
    exit;
}

$order_id = intval($input['order_id']);
$stage = $input['stage'];
$rating = intval($input['rating']);

if ($rating < 1 || $rating > 10) {
    echo json_encode(['success' => false, 'message' => 'التقييم يجب أن يكون بين 1 و 10']);
    exit;
}

if (!in_array($stage, ['design', 'execution'])) {
    echo json_encode(['success' => false, 'message' => 'مرحلة غير صحيحة']);
    exit;
}

$column = $stage === 'design' ? 'design_rating' : 'execution_rating';

$stmt = $conn->prepare("UPDATE orders SET {$column} = ? WHERE order_id = ?");
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'خطأ في تحضير استعلام التحديث: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $rating, $order_id);

if ($stmt->execute()) {
    $order_query = $conn->prepare("SELECT o.order_id, c.company_name, o.designer_id, o.workshop_id 
                                   FROM orders o 
                                   JOIN clients c ON o.client_id = c.client_id 
                                   WHERE o.order_id = ?");
    if ($order_query === false) {
        echo json_encode(['success' => false, 'message' => 'خطأ في تحضير استعلام جلب الطلب: ' . $conn->error]);
        exit;
    }
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order_info = $order_query->get_result()->fetch_assoc();
    
    if ($order_info) {
        $employee_id = $stage === 'design' ? $order_info['designer_id'] : $order_info['workshop_id'];
        $stage_name = $stage === 'design' ? 'التصميم' : 'التنفيذ';
        
        if ($employee_id) {
            $notification_message = "تم تقييم مرحلة {$stage_name} للطلب #{$order_id} ({$order_info['company_name']}) بدرجة {$rating}/10";
            $notification_link = "/new_injaz/dashboard?tab=reports";
            
            $notif_stmt = $conn->prepare("INSERT INTO notifications (employee_id, message, link, created_at) VALUES (?, ?, ?, NOW())");
            $notif_stmt->bind_param("iss", $employee_id, $notification_message, $notification_link);
            $notif_stmt->execute();
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'تم حفظ التقييم بنجاح',
        'rating' => $rating
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'فشل في حفظ التقييم: ' . $conn->error]);
}
exit;