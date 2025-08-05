<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Permissions;

class EmployeeController
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        if (!Permissions::has_permission('employee_view', $this->conn)) {
            header('Location: /new_injaz/');
            exit;
        }

        $page_title = 'الموظفون';
        
        $sort_column_key = $_GET['sort'] ?? 'employee_id';
        $sort_order = $_GET['order'] ?? 'asc';
        $sort_column_sql = $sort_column_key; // For simplicity, assuming direct column names

        $res = $this->conn->query("SELECT * FROM employees ORDER BY $sort_column_sql $sort_order");

        // Pass variables to the view
        $data = [
            'conn' => $this->conn,
            'sort_column_key' => $sort_column_key,
            'sort_order' => $sort_order,
            'sort_column_sql' => $sort_column_sql,
            'res' => $res
        ];

        // Start output buffering
        ob_start();
        extract($data);
        require_once __DIR__ . '/../View/employee/list.php';
        $content = ob_get_clean();

        echo $content;
    }

    public function add(): void
    {
        if (!Permissions::has_permission('employee_add', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }
        $page_title = 'إضافة موظف جديد';
        $is_edit = false;
        require_once __DIR__ . '/../View/employee/form.php';
    }

    public function store(): void
    {
        if (!Permissions::has_permission('employee_add', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $role = $_POST['role'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            $error = "كلمة المرور مطلوبة للموظف الجديد.";
            $page_title = 'إضافة موظف جديد';
            $is_edit = false;
            require_once __DIR__ . '/../View/employee/form.php';
            return;
        }

        // التحقق من صحة رقم الجوال
        if (!empty($phone) && !preg_match('/^05[0-9]{8}$/', $phone)) {
            $error = "رقم الجوال غير صحيح. يجب أن يبدأ بـ 05 ويتكون من 10 أرقام";
            $page_title = 'إضافة موظف جديد';
            $is_edit = false;
            require_once __DIR__ . '/../View/employee/form.php';
            return;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO employees (name, role, phone, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $role, $phone, $email, $password_hash);

        if ($stmt->execute()) {
            header("Location: /new_injaz/employees");
            exit;
        } else {
            $error = "خطأ في إضافة الموظف";
            $page_title = 'إضافة موظف جديد';
            $is_edit = false;
            require_once __DIR__ . '/../View/employee/form.php';
        }
    }

    public function edit(): void
    {
        if (!Permissions::has_permission('employee_edit', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing employee ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();

        if (!$employee) {
            http_response_code(404);
            echo "<h1>404 Not Found: Employee not found</h1>";
            return;
        }

        $page_title = "تعديل الموظف #" . $employee['employee_id'];
        $is_edit = true;
        require_once __DIR__ . '/../View/employee/form.php';
    }

    public function update(): void
    {
        if (!Permissions::has_permission('employee_edit', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing employee ID</h1>";
            return;
        }

        $name = $_POST['name'] ?? '';
        $role = $_POST['role'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // التحقق من صحة رقم الجوال
        if (!empty($phone) && !preg_match('/^05[0-9]{8}$/', $phone)) {
            $error = "رقم الجوال غير صحيح. يجب أن يبدأ بـ 05 ويتكون من 10 أرقام";
            $page_title = "تعديل الموظف #" . $id;
            $is_edit = true;
            $stmt = $this->conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
            require_once __DIR__ . '/../View/employee/form.php';
            return;
        }

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE employees SET name = ?, role = ?, phone = ?, email = ?, password = ? WHERE employee_id = ?");
            $stmt->bind_param("sssssi", $name, $role, $phone, $email, $password_hash, $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE employees SET name = ?, role = ?, phone = ?, email = ? WHERE employee_id = ?");
            $stmt->bind_param("ssssi", $name, $role, $phone, $email, $id);
        }

        if ($stmt->execute()) {
            header("Location: /new_injaz/employees");
            exit;
        } else {
            $error = "خطأ في تحديث البيانات";
            $page_title = "تعديل الموظف #" . $id;
            $is_edit = true;
            $stmt = $this->conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
            require_once __DIR__ . '/../View/employee/form.php';
        }
    }

    public function permissions(): void
    {
        if (!Permissions::has_permission('employee_permissions_edit', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing employee ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();

        if (!$employee) {
            http_response_code(404);
            echo "<h1>404 Not Found: Employee not found</h1>";
            return;
        }

        $page_title = "صلاحيات الموظف: " . $employee['name'];
        $conn = $this->conn;
        require_once __DIR__ . '/../View/employee/permissions.php';
    }

    public function updatePermissions(): void
    {
        if (!Permissions::has_permission('employee_permissions_edit', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $id = $_POST['employee_id'] ?? $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing employee ID</h1>";
            return;
        }

        // Delete existing permissions
        $stmt = $this->conn->prepare("DELETE FROM employee_permissions WHERE employee_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Insert new permissions
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $stmt = $this->conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?)");
            foreach ($_POST['permissions'] as $permission) {
                $stmt->bind_param("is", $id, $permission);
                $stmt->execute();
            }
        }

        header("Location: /new_injaz/employees");
        exit;
    }

    public function destroy(): void
    {
        if (!Permissions::has_permission('employee_delete', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing employee ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: /new_injaz/employees");
            exit;
        } else {
            http_response_code(500);
            echo "Error deleting employee: " . $stmt->error;
        }
    }

    public function confirmDelete(): void
    {
        if (!Permissions::has_permission('employee_delete', $this->conn)) {
            header('Location: /new_injaz/employees');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing employee ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();

        if (!$employee) {
            http_response_code(404);
            echo "<h1>404 Not Found: Employee not found</h1>";
            return;
        }

        $page_title = "تأكيد حذف الموظف: " . $employee['name'];
        $conn = $this->conn;
        require_once __DIR__ . '/../View/employee/delete_confirm.php';
    }
}
