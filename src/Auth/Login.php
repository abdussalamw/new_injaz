<?php
declare(strict_types=1);

namespace App\Auth;

class Login
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function show(): void
    {
        $error = '';
        $success_reset = '';
        require_once __DIR__ . '/../View/login_form.php';
    }

    public function handle(): void
    {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $stmt = $this->conn->prepare("SELECT employee_id, name, role, password FROM employees WHERE email=? OR name=? LIMIT 1");
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user_id'] = $row['employee_id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_role'] = $row['role'];
                unset($_SESSION['user_permissions']);

                if ($row['role'] === 'مدير') {
                    $perm_check_stmt = $this->conn->prepare("SELECT COUNT(*) FROM employee_permissions WHERE employee_id = ?");
                    $perm_check_stmt->bind_param("i", $row['employee_id']);
                    $perm_check_stmt->execute();
                    $perm_count = $perm_check_stmt->get_result()->fetch_row()[0];
                    $perm_check_stmt->close();

                    if ($perm_count == 0) {
                        require_once __DIR__ . '/../Core/Permissions.php';
                        $all_permissions = get_all_permissions();
                        $stmt_grant = $this->conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?)");
                        foreach ($all_permissions as $group => $permissions) {
                            foreach (array_keys($permissions) as $perm_key) {
                                $stmt_grant->bind_param("is", $row['employee_id'], $perm_key);
                                $stmt_grant->execute();
                            }
                        }
                        $stmt_grant->close();
                    }
                }

                header("Location: /");
                exit;
            } else {
                $error = "كلمة المرور غير صحيحة.";
                require_once __DIR__ . '/../View/login_form.php';
            }
        } else {
            $error = "المستخدم غير موجود.";
            require_once __DIR__ . '/../View/login_form.php';
        }
    }
}

$login = new Login($conn);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login->handle();
} else {
    $login->show();
}