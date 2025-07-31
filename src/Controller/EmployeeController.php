<?php

function list_employees() {
    global $conn;
    $page_title = 'إدارة الموظفين';

    // --- Sorting Logic ---
    $sort_column_key = $_GET['sort'] ?? 'name';
    $sort_order = $_GET['order'] ?? 'ASC';

    $column_map = [
        'employee_id' => 'employee_id',
        'name' => 'name',
        'role' => 'role',
        'email' => 'email'
    ];
    $allowed_sort_columns = array_keys($column_map);
    if (!in_array($sort_column_key, $allowed_sort_columns)) {
        $sort_column_key = 'name';
    }
    if (strtoupper($sort_order) !== 'ASC' && strtoupper($sort_order) !== 'DESC') {
        $sort_order = 'ASC';
    }
    $sort_column_sql = $column_map[$sort_column_key];

    include_once __DIR__ . '/../View/employee/list.php';
}

function add_employee() {
    global $conn;
    $page_title = 'إضافة موظف جديد';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'];
        $role = $_POST['role'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        // تعيين كلمة مرور افتراضية وتشفيرها
        $password = password_hash('demo123', PASSWORD_DEFAULT);

        $conn->begin_transaction();
        try {
            // 1. إضافة الموظف
            $stmt = $conn->prepare("INSERT INTO employees (name, role, phone, email, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $role, $phone, $email, $password);
            $stmt->execute();
            $employee_id = $conn->insert_id;

            // 2. تعيين الصلاحيات الافتراضية بناءً على الدور
            $default_permissions = [];
            switch ($role) {
                case 'مدير':
                    // المدير الجديد يحصل على كل الصلاحيات المتاحة في النظام
                    // دالة get_all_permissions() متاحة من خلال ملف header.php
                    $all_perms = get_all_permissions();
                    foreach ($all_perms as $group => $permissions) {
                        $default_permissions = array_merge($default_permissions, array_keys($permissions));
                    }
                    break;
                case 'مصمم':
                case 'معمل':
                    $default_permissions = ['dashboard_view', 'order_view_own'];
                    break;
                case 'محاسب':
                    // المحاسب يحتاج صلاحية عرض مهامه، وصلاحية التسوية المالية
                    // وصلاحية عرض كل الطلبات للمتابعة
                    $default_permissions = ['dashboard_view', 'order_view_own', 'order_financial_settle'];
                    break;
            }

            if (!empty($default_permissions)) {
                $stmt_perm = $conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?)");
                foreach ($default_permissions as $perm_key) {
                    $stmt_perm->bind_param("is", $employee_id, $perm_key);
                    $stmt_perm->execute();
                }
            }

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة الموظف وتعيين صلاحياته الافتراضية بنجاح.'];
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
        header("Location: /?page=employees");
        exit;
    }

    include_once __DIR__ . '/../View/employee/form.php';
}

function edit_employee() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);
    $page_title = "تعديل بيانات الموظف";

    if (isset($_POST['reset_password'])) {
        check_permission('employee_password_reset', $conn);
        $password = password_hash('demo123', PASSWORD_DEFAULT);
        $stmt_reset = $conn->prepare("UPDATE employees SET password = ? WHERE employee_id = ?");
        $stmt_reset->bind_param("si", $password, $id);
        if ($stmt_reset->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم إعادة تعيين كلمة المرور بنجاح إلى "demo123".'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور.'];
        }
        header("Location: /?page=employees&action=edit&id=" . $id);
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['reset_password'])) {
        $name = $_POST['name'];
        $role = $_POST['role'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // بناء الاستعلام بشكل ديناميكي
        $sql = "UPDATE employees SET name=?, role=?, phone=?, email=? ";
        $types = "ssss";
        $params = [$name, $role, $phone, $email];

        if (!empty($password)) {
            $sql .= ", password=? ";
            $types .= "s";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE employee_id=?";
        $types .= "i";
        $params[] = $id;
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param($types, ...$params);
        if ($stmt2->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل بيانات الموظف بنجاح.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء التعديل.'];
        }
        header("Location: /?page=employees&action=edit&id=" . $id);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if (!$employee) {
        echo "<div class='alert alert-danger'>الموظف غير موجود</div>";
        return;
    }

    include_once __DIR__ . '/../View/employee/form.php';
}

function delete_employee() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);

    if ($id) {
        // منع المستخدم من حذف نفسه
        if ($id === ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'لا يمكنك حذف حسابك الخاص.'];
            header("Location: /?page=employees");
            exit;
        }

        // Check for related records in the orders table
        $stmt_check = $conn->prepare("SELECT 1 FROM orders WHERE created_by = ? OR designer_id = ? LIMIT 1");
        $stmt_check->bind_param("ii", $id, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن حذف هذا الموظف لأنه مرتبط بطلبات حالية (كمنشئ أو مصمم).'];
            header("Location: /?page=employees");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف الموظف بنجاح.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لم يتم العثور على الموظف أو حدث خطأ.'];
        }
    }
    header("Location: /?page=employees");
    exit;
}

function ajax_change_password() {
    global $conn;
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'جلسة غير صالحة. يرجى تسجيل الدخول مرة أخرى.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $employee_id = $data['employee_id'] ?? null;
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

    $current_user_id = $_SESSION['user_id'];
    $is_self_change = ($current_user_id == $employee_id);

    if (!($is_self_change && has_permission('profile_change_password', $conn)) && !has_permission('employee_password_reset', $conn)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك الصلاحية لتنفيذ هذا الإجراء.']);
        exit;
    }

    if (empty($employee_id) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة.']);
        exit;
    }

    if ($is_self_change && empty($current_password)) {
        echo json_encode(['success' => false, 'message' => 'يرجى إدخال كلمة المرور الحالية.']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'كلمة المرور الجديدة وتأكيدها غير متطابقين.']);
        exit;
    }

    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'يجب أن تتكون كلمة المرور الجديدة من 8 أحرف على الأقل.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM employees WHERE employee_id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'الموظف غير موجود.']);
        exit;
    }

    if ($is_self_change) {
        if (!password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة.']);
            exit;
        }
    }

    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE employees SET password = ? WHERE employee_id = ?");
    $update_stmt->bind_param("si", $hashed_new_password, $employee_id);

    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث كلمة المرور.']);
    }
    $update_stmt->close();
    exit;
}
