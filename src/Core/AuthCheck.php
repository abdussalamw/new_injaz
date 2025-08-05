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
        // Add base path for proper redirection
        $base_path = '/new_injaz';
        if (!str_starts_with($path, $base_path) && !str_starts_with($path, 'http')) {
            $path = $base_path . $path;
        }
        header("Location: " . $path);
        exit;
    }
}
