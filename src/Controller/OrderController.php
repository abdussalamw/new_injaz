<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Permissions;
use App\Core\InitialTasksQuery;
use App\Core\Helpers;
use App\Core\MessageSystem;

class OrderController
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        if (!Permissions::has_permission('order_view_all', $this->conn) && !Permissions::has_permission('order_view_own', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/');
            exit;
        }

        $page_title = 'الطلبات';

        // إضافة متغيرات المستخدم المطلوبة
        $user_role = $_SESSION['user_role'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 0;

        $filter_status = $_GET['status'] ?? '';
        $filter_employee = $_GET['employee'] ?? '';
        $filter_payment = $_GET['payment'] ?? '';
        $filter_search = $_GET['search'] ?? '';
        $filter_date_from = $_GET['date_from'] ?? '';
        $filter_date_to = $_GET['date_to'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'latest';

        $employees_res = $this->conn->query("SELECT employee_id, name, role FROM employees ORDER BY name");
        $employees_list = $employees_res->fetch_all(\MYSQLI_ASSOC);

        // Sorting logic for the table
        $sort_column_key = $_GET['sort'] ?? 'order_id';
        $sort_order = $_GET['order'] ?? 'desc';
        $sort_column_sql = $sort_column_key; // Assuming direct column names for now

        // استخدام query مخصص للطلبات بدلاً من InitialTasksQuery
        $res = $this->fetchOrders($filter_status, $filter_employee, $filter_payment, $filter_search, $sort_by, $sort_column_key, $sort_order, $filter_date_from, $filter_date_to);

        $conn = $this->conn; // Make $conn available for the view
        
        // إذا كان طلب AJAX، أرجع فقط محتوى الجدول
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // تحديد أنه طلب AJAX لتجنب عرض الهيدر والقائمة
            $is_ajax_request = true;
            require_once __DIR__ . '/../View/order/list.php';
            return;
        }
        
        require_once __DIR__ . '/../View/order/list.php';
    }

    private function fetchOrders(string $filter_status = '', string $filter_employee = '', string $filter_payment = '', string $search_query = '', string $sort_by = 'latest', string $sort_column = 'order_id', string $sort_order = 'desc', string $filter_date_from = '', string $filter_date_to = ''): \mysqli_result|false
    {
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

        if (Permissions::has_permission('order_view_all', $this->conn)) {
            // المدير يرى جميع الطلبات ما عدا المهام المكتملة والمدفوعة
            $where_clauses[] = "NOT (TRIM(o.status) = 'مكتمل' AND TRIM(o.payment_status) = 'مدفوع') AND TRIM(o.status) != 'ملغي'";

            if (!empty($filter_status)) {
                $where_clauses[] = "o.status = ?";
                $params[] = $filter_status;
                $types .= "s";
            }

            if (!empty($filter_employee)) {
                // البحث في المصمم أو المعمل
                $where_clauses[] = "(o.designer_id = ? OR o.workshop_id = ?)";
                $params[] = $filter_employee;
                $params[] = $filter_employee;
                $types .= "ii";
            }

            if (!empty($filter_payment)) {
                $where_clauses[] = "o.payment_status = ?";
                $params[] = $filter_payment;
                $types .= "s";
            }

            if (!empty($search_query)) {
                $where_clauses[] = "(o.order_id LIKE ? OR c.company_name LIKE ? OR p.name LIKE ?)";
                $search_param = "%$search_query%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
                $types .= "sss";
            }

            // فلتر التاريخ
            if (!empty($filter_date_from)) {
                $where_clauses[] = "DATE(o.order_date) >= ?";
                $params[] = $filter_date_from;
                $types .= "s";
            }

            if (!empty($filter_date_to)) {
                $where_clauses[] = "DATE(o.order_date) <= ?";
                $params[] = $filter_date_to;
                $types .= "s";
            }
        } elseif (Permissions::has_permission('order_view_own', $this->conn)) {
            switch ($user_role) {
                case 'مصمم':
                    $where_clauses[] = "o.designer_id = ? AND TRIM(o.status) = 'قيد التصميم'";
                    $params[] = $user_id;
                    $types .= "i";
                    // تطبيق الفلاتر الإضافية للمصمم فقط
                    if (!empty($filter_status)) {
                        $where_clauses[] = "o.status = ?";
                        $params[] = $filter_status;
                        $types .= "s";
                    }
                    if (!empty($filter_payment)) {
                        $where_clauses[] = "o.payment_status = ?";
                        $params[] = $filter_payment;
                        $types .= "s";
                    }
                    if (!empty($search_query)) {
                        $where_clauses[] = "(o.order_id LIKE ? OR c.company_name LIKE ?)";
                        $search_param = "%$search_query%";
                        $params[] = $search_param;
                        $params[] = $search_param;
                        $types .= "ss";
                    }
                    if (!empty($filter_date_from)) {
                        $where_clauses[] = "DATE(o.order_date) >= ?";
                        $params[] = $filter_date_from;
                        $types .= "s";
                    }
                    if (!empty($filter_date_to)) {
                        $where_clauses[] = "DATE(o.order_date) <= ?";
                        $params[] = $filter_date_to;
                        $types .= "s";
                    }
                    break;
                case 'معمل':
                    // يظهر له فقط قيد التنفيذ أو جاهز للتسليم (ولا يظهر مكتمل)
                    $where_clauses[] = "o.workshop_id = ? AND TRIM(o.status) IN ('قيد التنفيذ', 'جاهز للتسليم')";
                    $params[] = $user_id;
                    $types .= "i";
                    // لا تطبق أي فلترة إضافية هنا!
                    break;
                case 'محاسب':
                    $where_clauses[] = "o.payment_status != 'مدفوع'";
                    // يمكن تطبيق الفلاتر هنا إذا أردت
                    break;
                default:
                    $where_clauses[] = "1=0"; // لا يرى شيء
                    break;
            }

            // تطبيق الفلاتر للموظف
            if (!empty($filter_status)) {
                $where_clauses[] = "o.status = ?";
                $params[] = $filter_status;
                $types .= "s";
            }

            if (!empty($filter_payment)) {
                $where_clauses[] = "o.payment_status = ?";
                $params[] = $filter_payment;
                $types .= "s";
            }

            if (!empty($search_query)) {
                $where_clauses[] = "(o.order_id LIKE ? OR c.company_name LIKE ?)";
                $search_param = "%$search_query%";
                $params[] = $search_param;
                $params[] = $search_param;
                $types .= "ss";
            }

            // فلتر التاريخ للموظفين
            if (!empty($filter_date_from)) {
                $where_clauses[] = "DATE(o.order_date) >= ?";
                $params[] = $filter_date_from;
                $types .= "s";
            }

            if (!empty($filter_date_to)) {
                $where_clauses[] = "DATE(o.order_date) <= ?";
                $params[] = $filter_date_to;
                $types .= "s";
            }
        } else {
            $where_clauses[] = "1=0"; // لا صلاحية
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql .= " GROUP BY o.order_id";
        
        // إذا كان هناك ترتيب من رؤوس الأعمدة، استخدمه
        if (!empty($sort_column) && $sort_column !== 'order_id' || $sort_order !== 'desc') {
            $order_by_clause = '';
            switch ($sort_column) {
                case 'order_id':
                    $order_by_clause = 'o.order_id ' . strtoupper($sort_order);
                    break;
                case 'client_name':
                    $order_by_clause = 'c.company_name ' . strtoupper($sort_order);
                    break;
                case 'designer_name':
                    $order_by_clause = 'e.name ' . strtoupper($sort_order);
                    break;
                case 'status':
                    $order_by_clause = 'o.status ' . strtoupper($sort_order);
                    break;
                case 'payment_status':
                    $order_by_clause = 'o.payment_status ' . strtoupper($sort_order);
                    break;
                case 'total_amount':
                    $order_by_clause = 'o.total_amount ' . strtoupper($sort_order);
                    break;
                case 'order_date':
                    $order_by_clause = 'o.order_date ' . strtoupper($sort_order);
                    break;
                default:
                    $order_by_clause = 'o.order_date DESC';
                    break;
            }
            $sql .= " ORDER BY " . $order_by_clause;
        } else {
            // استخدام الترتيب من الفلتر
            $order_by_clause = '';
            switch ($sort_by) {
                case 'latest':
                    $order_by_clause = 'o.order_date DESC';
                    break;
                case 'oldest':
                    $order_by_clause = 'o.order_date ASC';
                    break;
                case 'payment':
                    $order_by_clause = 'o.payment_status ASC, o.order_date DESC';
                    break;
                case 'employee':
                    $order_by_clause = 'e.name ASC, o.order_date DESC';
                    break;
                default:
                    $order_by_clause = 'o.order_date DESC';
                    break;
            }
            $sql .= " ORDER BY " . $order_by_clause;
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function add(): void
    {
        if (!Permissions::has_permission('order_add', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }
        $page_title = 'إضافة طلب جديد';
        $conn = $this->conn;
        $is_edit = false;
        
        // Fetch products for dropdown
        $products_result = $this->conn->query("SELECT product_id, name FROM products ORDER BY name");
        $products_array = $products_result->fetch_all(MYSQLI_ASSOC);

        // Fetch employees (designers and workshop)
        $employees_array = [];
        $workshop_employees = [];
        if ($_SESSION['user_role'] === 'مدير') {
            $employees_result = $this->conn->query("SELECT employee_id, name FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
            $employees_array = $employees_result->fetch_all(MYSQLI_ASSOC);
            
            $workshop_result = $this->conn->query("SELECT employee_id, name FROM employees WHERE role = 'معمل' ORDER BY name");
            $workshop_employees = $workshop_result->fetch_all(MYSQLI_ASSOC);
        }

        require_once __DIR__ . '/../View/order/form.php';
    }

    public function store(): void
    {
        if (!Permissions::has_permission('order_add', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }

        $client_id = $_POST['client_id'] ?? null;
        // Logic to create a new client if one doesn't exist
        if (empty($client_id)) {
            $company_name = $_POST['company_name'] ?? '';
            $contact_person = $_POST['contact_person'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            if (!empty($company_name) && !empty($phone)) {
                // التحقق من صحة رقم الجوال
                if (!preg_match('/^05[0-9]{8}$/', $phone)) {
                    $error = "رقم الجوال غير صحيح. يجب أن يبدأ بـ 05 ويتكون من 10 أرقام";
                    $page_title = 'إضافة طلب جديد';
                    $conn = $this->conn;
                    $is_edit = false;
                    
                    // Fetch products for dropdown
                    $products_result = $this->conn->query("SELECT product_id, name FROM products ORDER BY name");
                    $products_array = $products_result->fetch_all(MYSQLI_ASSOC);

                    // Fetch employees (designers)
                    $employees_array = [];
                    if ($_SESSION['user_role'] === 'مدير') {
                        $employees_result = $this->conn->query("SELECT employee_id, name FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
                        $employees_array = $employees_result->fetch_all(MYSQLI_ASSOC);
                    }
                    
                    require_once __DIR__ . '/../View/order/form.php';
                    return;
                }
                
                $stmt = $this->conn->prepare("INSERT INTO clients (company_name, contact_person, phone) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $company_name, $contact_person, $phone);
                $stmt->execute();
                $client_id = $this->conn->insert_id;
            } else {
                // Handle error: client info is required
                // For now, we'll just redirect back
                header('Location: ' . $_ENV['BASE_PATH'] . '/orders/add');
                exit;
            }
        }

        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $designer_id = $_POST['designer_id'] ?? $_SESSION['user_id'];
        $total_amount = $_POST['total_amount'] ?? 0;
        $deposit_amount = $_POST['deposit_amount'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $status = 'قيد التصميم'; // Default status for new orders
        
        // تحديد حالة الدفع بناءً على المبلغ المدفوع
        $payment_status = 'غير مدفوع';
        if ($deposit_amount > 0) {
            if ($deposit_amount >= $total_amount) {
                $payment_status = 'مدفوع';
            } else {
                $payment_status = 'مدفوع جزئياً';
            }
        }

        $stmt = $this->conn->prepare("INSERT INTO orders (client_id, due_date, priority, designer_id, total_amount, deposit_amount, payment_method, notes, status, payment_status, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issisdssss", $client_id, $due_date, $priority, $designer_id, $total_amount, $deposit_amount, $payment_method, $notes, $status, $payment_status);

        if ($stmt->execute()) {
            $order_id = $this->conn->insert_id;

            if (!empty($_POST['products'])) {
                $item_stmt = $this->conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
                foreach ($_POST['products'] as $product) {
                    if (!empty($product['product_id'])) {
                        $product_id = $product['product_id'] === 'other' ? null : $product['product_id'];
                        $quantity = $product['quantity'] ?? 1;
                        $item_notes = $product['item_notes'] ?? '';
                        $item_stmt->bind_param("iiis", $order_id, $product_id, $quantity, $item_notes);
                        $item_stmt->execute();
                    }
                }
            }
            MessageSystem::setSuccess("تم إضافة الطلب بنجاح!");
            header("Location: " . $_ENV['BASE_PATH'] . "/orders");
            exit;
        } else {
            // Handle error
            MessageSystem::setError("حدث خطأ أثناء إضافة الطلب: " . $stmt->error);
            header("Location: " . $_ENV['BASE_PATH'] . "/orders/add");
            exit;
        }
    }

    public function edit(): void
    {
        if (!Permissions::has_permission('order_edit', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing order ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT o.*, c.company_name, c.contact_person, c.phone FROM orders o JOIN clients c ON o.client_id = c.client_id WHERE o.order_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            http_response_code(404);
            echo "<h1>404 Not Found: Order not found</h1>";
            return;
        }
        
        $page_title = "تعديل الطلب #" . $order['order_id'];
        $conn = $this->conn;
        $is_edit = true;

        // Fetch products for dropdown
        $products_result = $this->conn->query("SELECT product_id, name FROM products ORDER BY name");
        $products_array = $products_result->fetch_all(MYSQLI_ASSOC);

        // Fetch employees (designers)
        $employees_array = [];
        if ($_SESSION['user_role'] === 'مدير') {
            $employees_result = $this->conn->query("SELECT employee_id, name FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
            $employees_array = $employees_result->fetch_all(MYSQLI_ASSOC);
        }

        // Load existing order items
        $items_result = $this->conn->query("SELECT * FROM order_items WHERE order_id = " . $id);
        $order_items = $items_result->fetch_all(MYSQLI_ASSOC);

        require_once __DIR__ . '/../View/order/form.php';
    }

    public function update(): void
    {
        if (!Permissions::has_permission('order_edit', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing order ID</h1>";
            return;
        }

        $client_id = $_POST['client_id'] ?? null;
        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $designer_id = $_POST['designer_id'] ?? $_SESSION['user_id'];
        $total_amount = $_POST['total_amount'] ?? 0;
        $deposit_amount = $_POST['deposit_amount'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? '';
        $notes = $_POST['notes'] ?? '';

        // التحقق من وجود العميل قبل التحديث
        if ($client_id) {
            $check_client = $this->conn->prepare("SELECT client_id FROM clients WHERE client_id = ?");
            $check_client->bind_param("i", $client_id);
            $check_client->execute();
            $client_exists = $check_client->get_result()->num_rows > 0;
            
            if (!$client_exists) {
                // إذا لم يكن العميل موجود، استخدم العميل الحالي
                $current_order = $this->conn->prepare("SELECT client_id FROM orders WHERE order_id = ?");
                $current_order->bind_param("i", $id);
                $current_order->execute();
                $current_result = $current_order->get_result();
                if ($current_result->num_rows > 0) {
                    $current_data = $current_result->fetch_assoc();
                    $client_id = $current_data['client_id'];
                }
            }
        }

        // تحديث حالة الدفع بناءً على المبلغ المدفوع
        $payment_status = 'غير مدفوع';
        if ($deposit_amount > 0) {
            if ($deposit_amount >= $total_amount) {
                $payment_status = 'مدفوع';
            } else {
                $payment_status = 'مدفوع جزئياً';
            }
        }

        $stmt = $this->conn->prepare("UPDATE orders SET client_id = ?, due_date = ?, priority = ?, designer_id = ?, total_amount = ?, deposit_amount = ?, payment_method = ?, notes = ?, payment_status = ? WHERE order_id = ?");
        $stmt->bind_param("issisdsssi", $client_id, $due_date, $priority, $designer_id, $total_amount, $deposit_amount, $payment_method, $notes, $payment_status, $id);

        if ($stmt->execute()) {
            $this->conn->query("DELETE FROM order_items WHERE order_id = $id");

            if (!empty($_POST['products'])) {
                $item_stmt = $this->conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
                foreach ($_POST['products'] as $product) {
                    if (!empty($product['product_id'])) {
                        $product_id = $product['product_id'] === 'other' ? null : $product['product_id'];
                        $quantity = $product['quantity'] ?? 1;
                        $item_notes = $product['item_notes'] ?? '';
                        $item_stmt->bind_param("iiis", $id, $product_id, $quantity, $item_notes);
                        $item_stmt->execute();
                    }
                }
            }
            MessageSystem::setSuccess("تم تحديث الطلب بنجاح!");
            header("Location: " . $_ENV['BASE_PATH'] . "/orders");
            exit;
        } else {
            // Handle error
            MessageSystem::setError("حدث خطأ أثناء تحديث الطلب: " . $stmt->error);
            header("Location: " . $_ENV['BASE_PATH'] . "/orders/edit?id=" . $id);
            exit;
        }
    }

    public function destroy(): void
    {
        if (!Permissions::has_permission('order_delete', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing order ID</h1>";
            return;
        }

        // We might need to delete related items first if there are foreign key constraints
        $this->conn->query("DELETE FROM order_items WHERE order_id = $id");
        
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            MessageSystem::setSuccess("تم حذف الطلب بنجاح!");
            header("Location: " . $_ENV['BASE_PATH'] . "/orders");
            exit;
        } else {
            // Handle error
            MessageSystem::setError("حدث خطأ أثناء حذف الطلب: " . $stmt->error);
            header("Location: " . $_ENV['BASE_PATH'] . "/orders");
            exit;
        }
    }

    // عند ضغط زر "جاهز للتسليم" من المعمل
    public function markReadyForDelivery(): void
    {
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id) {
            $stmt = $this->conn->prepare("UPDATE orders SET status = 'جاهز للتسليم' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            // إرسال رسالة واتساب للعميل (مثال)
            $client_stmt = $this->conn->prepare("SELECT c.phone FROM orders o JOIN clients c ON o.client_id = c.client_id WHERE o.order_id = ?");
            $client_stmt->bind_param("i", $order_id);
            $client_stmt->execute();
            $client_result = $client_stmt->get_result();
            if ($client = $client_result->fetch_assoc()) {
                $phone = $client['phone'];
                // استدعاء دالة إرسال واتساب هنا
                // sendWhatsappMessage($phone, "طلبك جاهز للتسليم ...");
            }

            // ...redirect...
        }
    }

    // عند ضغط زر "تأكيد استلام العميل"
    public function confirmClientReceived(): void
    {
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id) {
            $stmt = $this->conn->prepare("UPDATE orders SET status = 'مكتمل' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            // ...redirect...
        }
    }

    public function rate(): void
    {
        $order_id = $_POST['order_id'] ?? null;
        $type = $_POST['type'] ?? null; // 'design' or 'execution'
        $rating = $_POST['rating'] ?? null;

        if ($order_id && $type && $rating !== null) {
            if ($type === 'design') {
                $stmt = $this->conn->prepare("UPDATE orders SET design_rating = ? WHERE order_id = ?");
            } elseif ($type === 'execution') {
                $stmt = $this->conn->prepare("UPDATE orders SET execution_rating = ? WHERE order_id = ?");
            } else {
                http_response_code(400);
                echo "Invalid rating type";
                exit;
            }
            $stmt->bind_param("ii", $rating, $order_id);
            if ($stmt->execute()) {
                echo "success";
                exit;
            } else {
                // سجل الخطأ في ملف أو اطبعه مباشرة للتشخيص
                error_log("DB Error (OrderController/rate): " . $stmt->error);
                http_response_code(500);
                echo "DB Error: " . $stmt->error;
                exit;
            }
        }
        http_response_code(400);
        echo "Missing data";
        exit;
    }
}
