<?php
declare(strict_types=1);

namespace App\Core;

class AuthCheck
{
    public static function isLoggedIn(\mysqli $conn): bool
    {
        // التحقق مما إذا كان user_id موجودًا في الجلسة
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // يمكنك إضافة منطق إضافي هنا للتحقق من صلاحية الجلسة
        // مثلاً، التحقق من صلاحية المستخدم في قاعدة البيانات إذا لزم الأمر
        // For now, we assume if user_id is set, they are logged in.
        return true;
    }

    public static function redirect(string $path): void
    {
        // Add base path for proper redirection using environment setting
        $base_path = $_ENV['BASE_PATH'];
        if (!str_starts_with($path, $base_path) && !str_starts_with($path, 'http')) {
            $path = $base_path . $path;
        }
        header("Location: " . $path);
        exit;
    }

    /**
     * التحقق من المصادقة للـ APIs - يعيد JSON بدلاً من إعادة التوجيه
     */
    public static function requireApiAuth(\mysqli $conn): bool
    {
        if (!self::isLoggedIn($conn)) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => true,
                'message' => 'Authentication required',
                'redirect' => $_ENV['BASE_PATH'] . '/login'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return true;
    }
}
