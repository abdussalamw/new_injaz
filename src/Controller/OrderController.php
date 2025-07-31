<?php

function list_orders() {
    global $conn;
    $page_title = 'إدارة الطلبات';

    // --- Sorting Logic ---
    $sort_by = $_GET['sort_by'] ?? 'latest';
    $sort_column_sql = '';
    $sort_order = '';

    switch ($sort_by) {
        case 'latest':
            $sort_column_sql = 'o.order_date';
            $sort_order = 'DESC';
            break;
        case 'oldest':
            $sort_column_sql = 'o.order_date';
            $sort_order = 'ASC';
            break;
        case 'payment':
            $sort_column_sql = 'o.payment_status';
            $sort_order = 'ASC';
            break;
        case 'employee':
            $sort_column_sql = 'e.name';
            $sort_order = 'ASC';
            break;
        default:
            $sort_column_sql = 'o.order_date';
            $sort_order = 'DESC';
            break;
    }

    // --- Fetch employees for filtering ---
    $employees_res = $conn->query("SELECT employee_id, name, role FROM employees ORDER BY name");
    $employees_list = $employees_res->fetch_all(MYSQLI_ASSOC);

    // --- Filter values ---
    $filter_status = $_GET['status'] ?? '';
    $filter_employee = $_GET['employee'] ?? '';
    $filter_payment = $_GET['payment'] ?? '';
    $filter_search = $_GET['search'] ?? '';

    // --- Base Query ---
    $sql = "SELECT o.*, c.company_name AS client_name, e.name AS designer_name,
            COALESCE(GROUP_CONCAT(DISTINCT p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary, c.phone AS client_phone
            FROM orders o
            JOIN clients c ON o.client_id = c.client_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN employees e ON o.designer_id = e.employee_id";

    // --- WHERE Clauses ---
    $where_clauses = [];
    $params = [];
    $types = "";

    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['user_role'] ?? 'guest';

    if (has_permission('order_view_all', $conn)) {
        $where_clauses[] = "TRIM(o.status) NOT IN ('مكتمل', 'ملغي')";

        if (!empty($filter_employee)) {
            $employee_role_query = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
            $employee_role_query->bind_param("i", $filter_employee);
            $employee_role_query->execute();
            $employee_role_result = $employee_role_query->get_result();
            $employee_role = $employee_role_result->fetch_assoc()['role'] ?? '';

            switch ($employee_role) {
                case 'مصمم':
                    $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                    $params[] = $filter_employee;
                    $types .= "i";
                    break;
                case 'معمل':
                    $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                    $params[] = $filter_employee;
                    $types .= "i";
                    break;
                case 'محاسب':
                    $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                    break;
                default:
                    $where_clauses[] = "1=0";
                    break;
            }
        } else {
            if (!empty($filter_status)) {
                $where_clauses[] = "o.status = ?";
                $params[] = $filter_status;
                $types .= "s";
            }
        }
    } elseif (has_permission('order_view_own', $conn)) {
        $where_clauses = ["TRIM(o.status) NOT IN ('مكتمل', 'ملغي')"];

        switch ($user_role) {
            case 'مصمم':
                $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                $params[] = $user_id;
                $types .= "i";
                break;
            case 'معمل':
                $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                $params[] = $user_id;
                $types .= "i";
                break;
            case 'محاسب':
                $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                break;
            default:
                $where_clauses[] = "1=0";
                break;
        }
    } else {
        $where_clauses[] = "1=0";
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " GROUP BY o.order_id ORDER BY o.due_date ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    include_once __DIR__ . '/../View/order/list.php';
}

function add_order() {
    global $conn;
    $page_title = 'إضافة طلب جديد';

    // جلب المنتجات
    $products_res = $conn->query("SELECT product_id, name FROM products ORDER BY CASE WHEN name = 'منتجات أخرى' THEN 0 ELSE 1 END, name");
    $products_array = $products_res->fetch_all(MYSQLI_ASSOC);
    // جلب المصممين فقط
    $designers_res = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY role, name");
    $designers_list = $designers_res->fetch_all(MYSQLI_ASSOC);

    $error = '';
    $post_data = []; // To hold submitted data on error

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $post_data = $_POST; // Store submitted data
        $conn->begin_transaction();
        try {
            // 1. معالجة العميل (جديد أو حالي)
            $client_id = $_POST['client_id'];
            // إذا كان client_id فارغاً، فهذا عميل جديد
            if (empty($client_id)) {
                $company_name = $_POST['company_name'];
                $contact_person = $_POST['contact_person'];
                $phone = $_POST['phone'];

                if (empty($company_name) || empty($phone)) {
                    throw new Exception("اسم المؤسسة ورقم الجوال حقلان إجباريان للعميل الجديد.");
                }
                // التحقق من صحة رقم الجوال السعودي
                if (!preg_match('/^05[0-9]{8}$/', $phone)) {
                    throw new Exception("الرجاء إدخال رقم جوال سعودي صحيح للعميل الجديد (10 أرقام تبدأ بـ 05).");
                }
                $stmt_new_client = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone) VALUES (?, ?, ?)");
                $stmt_new_client->bind_param("sss", $company_name, $contact_person, $phone);
                $stmt_new_client->execute();
                $client_id = $conn->insert_id; // الحصول على ID العميل الجديد
            }

            // 2. إدراج الطلب الرئيسي
            $total_amount = floatval($_POST['total_amount']);
            // التحقق من أن المبلغ الإجمالي هو رقم صالح
            if (!isset($_POST['total_amount']) || !is_numeric($_POST['total_amount']) || $total_amount < 0) {
                throw new Exception("المبلغ الإجمالي حقل إجباري ويجب أن يكون رقماً موجباً.");
            }
            $deposit_amount = floatval($_POST['deposit_amount']);

            // **إصلاح منطقي:** إذا كان المبلغ الإجمالي صفراً، يجب أن تكون الدفعة المقدمة صفراً أيضاً
            if ($total_amount <= 0) {
                $deposit_amount = 0;
            }

            $remaining_amount = $total_amount - $deposit_amount;
            $created_by = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session not set
            
            // أتمتة حالة الدفع حسب المنطق الجديد
            if ($deposit_amount >= $total_amount && $total_amount > 0) {
                $payment_status = 'مدفوع';
            } elseif ($deposit_amount > 0 && $deposit_amount < $total_amount) {
                $payment_status = 'مدفوع جزئياً';
            } else { // يشمل حالة المبلغ الإجمالي صفر أو الدفعة صفر
                $payment_status = 'غير مدفوع';
            }

            $designer_id = $_POST['designer_id'];
            if (empty($designer_id) || !filter_var($designer_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                throw new Exception("الرجاء اختيار المسؤول عن التصميم. الحقل إجباري.");
            }

            $stmt_order = $conn->prepare("INSERT INTO orders (client_id, designer_id, total_amount, deposit_amount, remaining_amount, payment_status, payment_method, due_date, status, priority, notes, created_by, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'قيد التصميم', ?, ?, ?, NOW())");
            $stmt_order->bind_param("iidddsssssi", $client_id, $designer_id, $total_amount, $deposit_amount, $remaining_amount, $payment_status, $_POST['payment_method'], $_POST['due_date'], $_POST['priority'], $_POST['notes'], $created_by);
            $stmt_order->execute();
            $order_id = $conn->insert_id;

            // 3. إدراج بنود الطلب
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
            if (!empty($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $product) {
                    // التحقق من أن معرّف المنتج هو رقم صحيح أكبر من صفر
                    if (!isset($product['product_id']) || !filter_var($product['product_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                        throw new Exception("الرجاء اختيار منتج صالح لجميع البنود المضافة. قد يكون أحد المنتجات غير محدد.");
                    }
                    // التحقق من أن الكمية هي رقم صحيح أكبر من صفر
                    if (!isset($product['quantity']) || !filter_var($product['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                        throw new Exception("الرجاء إدخال كمية صحيحة (رقم أكبر من صفر) لجميع البنود.");
                    }
                    $stmt_item->bind_param("iiss", $order_id, $product['product_id'], $product['quantity'], $product['item_notes']);
                    $stmt_item->execute();
                }
            } else {
                throw new Exception("يجب إضافة منتج واحد على الأقل للطلب.");
            }

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حفظ الطلب بنجاح! رقم الطلب: ' . $order_id];
            header("Location: /?page=orders");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }

    include_once __DIR__ . '/../View/order/form.php';
}

function edit_order() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);
    $page_title = "تعديل الطلب #" . $id;

    // جلب المنتجات
    $products_res = $conn->query("SELECT product_id, name FROM products ORDER BY CASE WHEN name = 'منتجات أخرى' THEN 0 ELSE 1 END, name");
    $products_array = $products_res->fetch_all(MYSQLI_ASSOC);
    // جلب المصممين فقط
    $designers_res = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY role, name");
    $designers_list = $designers_res->fetch_all(MYSQLI_ASSOC);

    $error = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $post_data = $_POST;
        $conn->begin_transaction();
        try {
            // 1. تحديث الطلب الرئيسي
            $total_amount = floatval($_POST['total_amount']);
            if (!isset($_POST['total_amount']) || !is_numeric($_POST['total_amount']) || $total_amount < 0) {
                throw new Exception("المبلغ الإجمالي حقل إجباري ويجب أن يكون رقماً موجباً.");
            }
            $deposit_amount = floatval($_POST['deposit_amount']);

            if ($total_amount <= 0) {
                $deposit_amount = 0;
            }

            $remaining_amount = $total_amount - $deposit_amount;
            
            if ($deposit_amount >= $total_amount && $total_amount > 0) {
                $payment_status = 'مدفوع';
            } elseif ($deposit_amount > 0 && $deposit_amount < $total_amount) {
                $payment_status = 'مدفوع جزئياً';
            } else {
                $payment_status = 'غير مدفوع';
            }

            $designer_id = $_POST['designer_id'];
            if (empty($designer_id) || !filter_var($designer_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                throw new Exception("الرجاء اختيار المسؤول عن التصميم. الحقل إجباري.");
            }

            $sql = "UPDATE orders SET total_amount=?, deposit_amount=?, remaining_amount=?, payment_status=?, payment_method=?, due_date=?, priority=?, notes=?";
            $types = "dddsssss";
            $params = [$total_amount, $deposit_amount, $remaining_amount, $payment_status, $_POST['payment_method'], $_POST['due_date'], $_POST['priority'], $_POST['notes']];

            if ($_SESSION['user_role'] === 'مدير') {
                $sql .= ", designer_id=?";
                $types .= "i";
                $params[] = $designer_id;
            }
            $sql .= " WHERE order_id=?";
            $types .= "i";
            $params[] = $id;
            $stmt_order = $conn->prepare($sql);
            $stmt_order->bind_param($types, ...$params);
            $stmt_order->execute();

            // 2. حذف البنود القديمة
            $stmt_delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt_delete_items->bind_param("i", $id);
            $stmt_delete_items->execute();

            // 3. إدراج البنود الجديدة
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
            if (!empty($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $product) {
                    if (!isset($product['product_id']) || !filter_var($product['product_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                        throw new Exception("الرجاء اختيار منتج صالح لجميع البنود المضافة. قد يكون أحد المنتجات غير محدد.");
                    }
                    if (!isset($product['quantity']) || !filter_var($product['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                        throw new Exception("الرجاء إدخال كمية صحيحة (رقم أكبر من صفر) لجميع البنود.");
                    }
                    $stmt_item->bind_param("iiss", $id, $product['product_id'], $product['quantity'], $product['item_notes']);
                    $stmt_item->execute();
                }
            } else {
                throw new Exception("يجب وجود منتج واحد على الأقل في الطلب.");
            }

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل الطلب بنجاح!'];
            header("Location: /?page=orders");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }

    $stmt = $conn->prepare("SELECT o.*, c.company_name, c.contact_person, c.phone, e.name as designer_name
                        FROM orders o 
                        JOIN clients c ON o.client_id = c.client_id 
                        LEFT JOIN employees e ON o.designer_id = e.employee_id
                        WHERE o.order_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        echo "<div class='alert alert-danger'>الطلب غير موجود</div>";
        return;
    }

    $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items_stmt->bind_param("i", $id);
    $items_stmt->execute();
    $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    include_once __DIR__ . '/../View/order/form.php';
}

function delete_order() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);

    if ($id) {
        $conn->begin_transaction();
        try {
            // 1. Delete related order items first
            $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt_items->bind_param("i", $id);
            $stmt_items->execute();

            // 2. Delete the main order
            $stmt_order = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $stmt_order->bind_param("i", $id);
            $stmt_order->execute();

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف الطلب وجميع بنوده بنجاح.'];

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()];
        }
    }
    header("Location: /?page=orders");
    exit;
}

function ajax_filter_tasks() {
    global $conn;
    header('Content-Type: application/json');

    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        die('وصول غير مسموح به');
    }

    $filter_status = $_GET['status'] ?? '';
    $filter_employee = $_GET['employee'] ?? '';
    $filter_payment = $_GET['payment'] ?? '';
    $search_query = $_GET['search'] ?? '';

    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['user_role'] ?? 'guest';

    $sql = "SELECT o.*, c.company_name AS client_name, c.phone as client_phone, e.name AS designer_name, 
            COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary,
            o.design_completed_at, o.execution_completed_at, c.client_id
            FROM orders o
            JOIN clients c ON o.client_id = c.client_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN employees e ON o.designer_id = e.employee_id";

    $where_clauses = [];
    $params = [];
    $types = "";

    if (has_permission('order_view_all', $conn)) {
        $where_clauses[] = "TRIM(o.status) NOT IN ('مكتمل', 'ملغي')";

        if (!empty($filter_employee)) {
            $employee_role_query = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
            $employee_role_query->bind_param("i", $filter_employee);
            $employee_role_query->execute();
            $employee_role_result = $employee_role_query->get_result();
            $employee_role = $employee_role_result->fetch_assoc()['role'] ?? '';

            switch ($employee_role) {
                case 'مصمم':
                    $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                    $params[] = $filter_employee;
                    $types .= "i";
                    break;
                case 'معمل':
                    $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                    $params[] = $filter_employee;
                    $types .= "i";
                    break;
                case 'محاسب':
                    $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                    break;
                default:
                    $where_clauses[] = "1=0";
                    break;
            }
        } else {
            if (!empty($filter_status)) {
                $where_clauses[] = "o.status = ?";
                $params[] = $filter_status;
                $types .= "s";
            }
        }
    } elseif (has_permission('order_view_own', $conn)) {
        $where_clauses = ["TRIM(o.status) NOT IN ('مكتمل', 'ملغي')"];

        switch ($user_role) {
            case 'مصمم':
                $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                $params[] = $user_id;
                $types .= "i";
                break;
            case 'معمل':
                $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                $params[] = $user_id;
                $types .= "i";
                break;
            case 'محاسب':
                $where_clauses[] = "o.payment_settled_at IS NULL AND o.total_amount > 0";
                break;
            default:
                $where_clauses[] = "1=0";
                break;
        }
    } else {
        $where_clauses[] = "1=0";
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " GROUP BY o.order_id ORDER BY o.due_date ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    ob_start();
    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            echo '<div class="col-md-6 col-lg-4">';
            $task_details = $row;
            $actions = get_next_actions($row, $user_role, $user_id, $conn, 'dashboard'); 
            include __DIR__ . '/../task_card.php';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12"><div class="alert alert-info text-center">لا توجد مهام تطابق معايير البحث.</div></div>';
    }
    $output = ob_get_clean();
    echo $output;

    exit;
}

function ajax_order_actions() {
    global $conn;
    header('Content-Type: application/json');

    // --- جلب أرقام معرفات المدراء لاستخدامها في الإشعارات ---
    $managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
    $manager_ids = [];
    while ($manager = $managers_res->fetch_assoc()) {
        $manager_ids[] = $manager['employee_id'];
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = intval($data['order_id'] ?? 0);
    $action = $data['action'] ?? '';
    $value = $data['value'] ?? null; // يُستخدم لتمرير قيم إضافية مثل الحالة الجديدة

    if (!$order_id || !$action) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    // جلب تفاصيل الطلب للتحقق من الصلاحيات والحالة الحالية
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'الطلب غير موجود.']);
        exit;
    }

    // --- معالج الإجراءات المركزي ---
    try {
        $conn->begin_transaction();
        $message = '';
        $additional_message = '';

        switch ($action) {
            case 'change_status':
                $current_status = $order['status'];
                $new_status = $value;
                $sql_update = "UPDATE orders SET status = ?";
                $types_update = "s";
                $params_update = [$new_status];

                // تسجيل وقت انتهاء المرحلة عند الانتقال للمرحلة التالية
                if ($current_status === 'قيد التصميم' && $new_status === 'قيد التنفيذ') {
                    $sql_update .= ", design_completed_at = NOW()";
                } elseif ($current_status === 'قيد التنفيذ' && $new_status === 'جاهز للتسليم') {
                    $sql_update .= ", execution_completed_at = NOW()";
                }

                $sql_update .= " WHERE order_id = ?";
                $types_update .= "i";
                $params_update[] = $order_id;

                $update_stmt = $conn->prepare($sql_update);
                $update_stmt->bind_param($types_update, ...$params_update);
                $update_stmt->execute();
                $message = "تم تغيير حالة الطلب إلى '$new_status'.";

                // --- إرسال الإشعارات ---
                $notification_link = "/?page=orders&action=edit&id={$order_id}";
                $notify_ids = $manager_ids; // المديرون يتم إعلامهم دائماً
                
                // جلب اسم المستخدم الذي قام بالإجراء
                $user_name = $_SESSION['user_name'] ?? 'مستخدم غير معروف';

                if ($current_status === 'قيد التصميم' && $new_status === 'قيد التنفيذ') {
                    $notification_message = "🎨 المصمم {$user_name} أرسل الطلب #{$order_id} إلى مرحلة التنفيذ";
                    $lab_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'معمل'");
                    while($lab_user = $lab_res->fetch_assoc()) { $notify_ids[] = $lab_user['employee_id']; }
                    send_notifications($conn, $notify_ids, $notification_message, $notification_link);
                    send_push_notifications($conn, $notify_ids, '🎨 مهمة جديدة للتنفيذ', $notification_message, $notification_link);
                } elseif ($current_status === 'قيد التنفيذ' && $new_status === 'جاهز للتسليم') {
                    $notification_message = "✅ {$user_name} أنهى تنفيذ الطلب #{$order_id} وأصبح جاهزاً للتسليم";
                    $notify_ids[] = $order['created_by']; // إعلام منشئ الطلب
                    send_notifications($conn, $notify_ids, $notification_message, $notification_link);
                    send_push_notifications($conn, $notify_ids, '✅ طلب جاهز للتسليم', $notification_message, $notification_link);
                }
                break;

            case 'confirm_delivery':
                $is_creator = ($order['created_by'] == $user_id);
                if (!in_array($user_role, ['مدير', 'معمل']) && !$is_creator) {
                     throw new Exception('غير مصرح لك بتأكيد التسليم.');
                }
                $update_stmt = $conn->prepare("UPDATE orders SET delivered_at = NOW() WHERE order_id = ? AND delivered_at IS NULL");
                $update_stmt->bind_param("i", $order_id);
                $update_stmt->execute();
                $message = 'تم تأكيد استلام الطلب من قبل العميل بنجاح.';
                $additional_message = checkAndCloseOrderAndNotify($order_id, $conn);

                // --- إرسال الإشعارات ---
                $user_name = $_SESSION['user_name'] ?? 'مستخدم غير معروف';
                $notification_link = "/?page=orders&action=edit&id={$order_id}";
                $notification_message = "📦 {$user_name} أكد استلام العميل للطلب #{$order_id}";
                $notify_ids = $manager_ids;
                $accountant_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'محاسب'");
                while($acc_user = $accountant_res->fetch_assoc()) { $notify_ids[] = $acc_user['employee_id']; }
                send_notifications($conn, $notify_ids, $notification_message, $notification_link);
                send_push_notifications($conn, $notify_ids, '📦 تم تسليم طلب', $notification_message, $notification_link);
                break;

            case 'update_payment':
                // للمحاسب: فتح نافذة تحديث الدفع (يتم التعامل معها في JavaScript)
                if ($user_role !== 'محاسب' || !has_permission('order_financial_settle', $conn)) {
                    throw new Exception('غير مصرح لك بتحديث حالة الدفع.');
                }
                // هذا الإجراء يتم التعامل معه في الواجهة الأمامية
                $message = 'فتح نافذة تحديث الدفع';
                break;

            case 'confirm_payment':
                // للمدير: تأكيد الدفع الكامل مباشرة
                if ($user_role !== 'مدير' || !has_permission('order_financial_settle', $conn)) {
                     throw new Exception('غير مصرح لك بتأكيد الدفع.');
                }
                
                // تحديث حالة الدفع إلى مدفوع بالكامل
                $update_stmt = $conn->prepare("UPDATE orders SET 
                                             deposit_amount = total_amount, 
                                             remaining_amount = 0, 
                                             payment_status = 'مدفوع',
                                             payment_settled_at = NOW() 
                                             WHERE order_id = ? AND payment_settled_at IS NULL");
                $update_stmt->bind_param("i", $order_id);
                $update_stmt->execute();
                $message = 'تم تأكيد الدفع الكامل للطلب بنجاح.';
                $additional_message = checkAndCloseOrderAndNotify($order_id, $conn);

                // --- إرسال الإشعارات ---
                $notification_link = "/?page=orders&action=edit&id={$order_id}";
                $notification_message = "تم تأكيد التسوية المالية للطلب #{$order_id}.";
                // فقط المديرون يتم إعلامهم بهذا الإجراء
                send_notifications($conn, $manager_ids, $notification_message, $notification_link);
                send_push_notifications($conn, $manager_ids, 'تسوية مالية', $notification_message, $notification_link);
                break;
            
            case 'close_order':
                if ($user_role !== 'مدير') {
                    throw new Exception('فقط المدير يمكنه إغلاق الطلب.');
                }
                if (empty($order['delivered_at']) || empty($order['payment_settled_at'])) {
                    throw new Exception('لا يمكن إغلاق الطلب قبل تسليمه وتسوية مدفوعاته.');
                }
                $update_stmt = $conn->prepare("UPDATE orders SET status = 'مكتمل' WHERE order_id = ?");
                $update_stmt->bind_param("i", $order_id);
                $update_stmt->execute();
                $message = 'تم إغلاق الطلب بنجاح.';
                break;

            default:
                throw new Exception('إجراء غير معروف.');
        }

        // التحقق من أن التحديث تم بنجاح
        if ($update_stmt->affected_rows === 0) {
            // إذا كان الإجراء هو تغيير الحالة، فمن المحتمل أن الحالة المطلوبة هي نفسها الحالية.
            // هذا ليس خطأ، لذا نسمح له بالمرور برسالة توضيحية.
            if ($action === 'change_status' && $order['status'] === $value) {
                $message = "الحالة لم تتغير لأنها بالفعل '$value'.";
            } else {
                // لأي إجراء آخر أو فشل حقيقي، أظهر خطأ
                throw new Exception('فشل تحديث قاعدة البيانات. لم تتأثر أي سجلات، قد يكون الإجراء قد تم تنفيذه مسبقاً أو هناك مشكلة في الصلاحيات.');
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => $message . $additional_message]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function ajax_update_payment() {
    global $conn;
    header('Content-Type: application/json');

    if (!has_permission('order_financial_settle', $conn)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لتحديث حالة الدفع']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
        exit;
    }

    $order_id = intval($_POST['order_id'] ?? 0);
    $payment_amount = floatval($_POST['payment_amount'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صحيح']);
        exit;
    }

    if ($payment_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'مبلغ الدفعة يجب أن يكون أكبر من صفر']);
        exit;
    }

    if (empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'يجب تحديد طريقة الدفع']);
        exit;
    }

    try {
        $conn->begin_transaction();
        
        $order_query = "SELECT total_amount, deposit_amount, payment_status FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            throw new Exception('الطلب غير موجود');
        }
        
        $total_amount = floatval($order['total_amount']);
        $current_deposit = floatval($order['deposit_amount']);
        $new_deposit = $current_deposit + $payment_amount;
        
        if ($new_deposit > $total_amount) {
            throw new Exception('مبلغ الدفعة يتجاوز المبلغ المتبقي. المتبقي: ' . number_format($total_amount - $current_deposit, 2) . ' ر.س');
        }
        
        $new_payment_status = '';
        $payment_settled_at = null;
        
        if ($new_deposit >= $total_amount) {
            $new_payment_status = 'مدفوع';
            $payment_settled_at = date('Y-m-d H:i:s');
        } elseif ($new_deposit > 0) {
            $new_payment_status = 'مدفوع جزئياً';
        } else {
            $new_payment_status = 'غير مدفوع';
        }
        
        $update_query = "UPDATE orders SET 
                         deposit_amount = ?, 
                         remaining_amount = ?, 
                         payment_status = ?, 
                         payment_method = ?,
                         payment_settled_at = ?,
                         last_update = NOW() 
                         WHERE order_id = ?";
        
        $remaining_amount = $total_amount - $new_deposit;
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ddsssi", $new_deposit, $remaining_amount, $new_payment_status, $payment_method, $payment_settled_at, $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception('فشل في تحديث بيانات الطلب');
        }
        
        if (!empty($notes)) {
            $notes_text = "دفعة جديدة: " . number_format($payment_amount, 2) . " ر.س عبر " . $payment_method;
            if (!empty($notes)) {
                $notes_text .= " - " . $notes;
            }
            
            $current_notes_query = "SELECT notes FROM orders WHERE order_id = ?";
            $stmt = $conn->prepare($current_notes_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $current_notes = $stmt->get_result()->fetch_assoc()['notes'] ?? '';
            
            $updated_notes = empty($current_notes) ? $notes_text : $current_notes . "\n" . $notes_text;
            
            $update_notes_query = "UPDATE orders SET notes = ? WHERE order_id = ?";
            $stmt = $conn->prepare($update_notes_query);
            $stmt->bind_param("si", $updated_notes, $order_id);
            $stmt->execute();
        }
        
        $user_name = $_SESSION['user_name'] ?? 'مستخدم غير معروف';
        $notification_message = "💰 المحاسب {$user_name} حدث حالة الدفع للطلب #{$order_id} - الحالة الجديدة: {$new_payment_status}";
        $notification_link = "/?page=orders&action=edit&id={$order_id}";
        
        $managers_res = $conn->query("SELECT employee_id FROM employees WHERE role = 'مدير'");
        $manager_ids = [];
        while ($manager = $managers_res->fetch_assoc()) {
            $manager_ids[] = $manager['employee_id'];
        }
        send_notifications($conn, $manager_ids, $notification_message, $notification_link);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'تم تحديث حالة الدفع بنجاح',
            'new_payment_status' => $new_payment_status,
            'new_deposit' => $new_deposit,
            'remaining_amount' => $remaining_amount,
            'is_fully_paid' => ($new_payment_status === 'مدفوع')
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

function generate_sort_link($column_key, $display_text, $current_sort_key, $current_order) {
    $next_order = ($current_sort_key === $column_key && strtoupper($current_order) === 'ASC') ? 'DESC' : 'ASC';
    $query_params = $_GET;
    $query_params['sort'] = $column_key;
    $query_params['order'] = $next_order;
    $url = '/?page=orders&' . http_build_query($query_params);
    
    $icon = '';
    if ($current_sort_key === $column_key) {
        $icon = (strtoupper($current_order) === 'ASC') ? ' <i class="fas fa-sort-up text-dark"></i>' : ' <i class="fas fa-sort-down text-dark"></i>';
    } else {
        $icon = ' <i class="fas fa-sort text-dark" style="opacity: 0.7;"></i>';
    }
    
    return '<a href="' . htmlspecialchars($url) . '" class="text-decoration-none text-dark d-flex align-items-center justify-content-center" style="cursor: pointer;">' . 
           '<span>' . htmlspecialchars($display_text) . '</span>' . $icon . '</a>';
}

function generate_non_sort_column($display_text) {
    return '<div class="d-flex align-items-center justify-content-center text-dark">' . 
           '<span>' . htmlspecialchars($display_text) . '</span>' . 
           ' <i class="fas fa-sort text-dark" style="opacity: 0.3;"></i></div>';
}

function display_products_summary($summary) { return empty($summary) ? 'لا يوجد منتجات' : htmlspecialchars($summary); }