<?php
// ========================================
// الحل الثالث: تحسين AuthCheck.php للـ API
// ========================================

// إضافة هذه الدالة إلى AuthCheck.php
public static function requireAuthForAPI($conn): array
{
    if (!self::isLoggedIn($conn)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        return [
            'error' => true,
            'message' => 'Authentication required',
            'redirect' => $_ENV['BASE_PATH'] . '/login'
        ];
    }
    return ['authenticated' => true];
}

// ========================================
// تعديل SearchClient.php
// ========================================

// في بداية الملف، استبدال منطق المصادقة
$authCheck = \App\Core\AuthCheck::requireAuthForAPI($conn);
if (isset($authCheck['error'])) {
    echo json_encode($authCheck, JSON_UNESCAPED_UNICODE);
    exit;
}

// باقي الكود كما هو...
