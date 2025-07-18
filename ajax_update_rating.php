<?php
include 'db_connection.php';
include 'auth_check.php';

// التحقق من أن المستخدم مدير
if (!has_permission('dashboard_reports_view', $conn)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مسموح لك بتقييم المراحل']);
    exit;
}

// التحقق من البيانات المرسلة
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
$stage = $input['stage']; // 'design' أو 'execution'
$rating = intval($input['rating']);

// التحقق من صحة التقييم
if ($rating < 1 || $rating > 10) {
    echo json_encode(['success' => false, 'message' => 'التقييم يجب أن يكون بين 1 و 10']);
    exit;
}

// التحقق من صحة المرحلة
if (!in_array($stage, ['design', 'execution'])) {
    echo json_encode(['success' => false, 'message' => 'مرحلة غير صحيحة']);
    exit;
}

// تحديد اسم العمود
$column = $stage === 'design' ? 'design_rating' : 'execution_rating';

// تحديث التقييم في قاعدة البيانات
$stmt = $conn->prepare("UPDATE orders SET {$column} = ? WHERE order_id = ?");
$stmt->bind_param("ii", $rating, $order_id);

if ($stmt->execute()) {
    // جلب معلومات الطلب للإشعار
    $order_query = $conn->prepare("SELECT o.order_id, c.company_name, o.designer_id, o.workshop_id 
                                   FROM orders o 
                                   JOIN clients c ON o.client_id = c.client_id 
                                   WHERE o.order_id = ?");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order_info = $order_query->get_result()->fetch_assoc();
    
    if ($order_info) {
        // إرسال إشعار للموظف المسؤول عن المرحلة
        $employee_id = $stage === 'design' ? $order_info['designer_id'] : $order_info['workshop_id'];
        $stage_name = $stage === 'design' ? 'التصميم' : 'التنفيذ';
        
        if ($employee_id) {
            $notification_message = "تم تقييم مرحلة {$stage_name} للطلب #{$order_id} ({$order_info['company_name']}) بدرجة {$rating}/10";
            $notification_link = "timeline_reports.php";
            
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
} else {
    echo json_encode(['success' => false, 'message' => 'فشل في حفظ التقييم: ' . $conn->error]);
}
?>
