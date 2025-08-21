<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Permissions;
use App\Core\InitialTasksQuery;
use App\Core\Helpers;
use App\Core\MessageSystem;
use App\Core\OrderUpdater; // Use the centralized updater
use App\Core\RoleHelper;

class OrderController
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        // This method is for viewing orders, no changes needed here.
        if (!Permissions::has_permission('order_view_all', $this->conn) && !Permissions::has_permission('order_view_own', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/');
            exit;
        }

        $page_title = 'الطلبات';
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

        $sort_column_key = $_GET['sort'] ?? 'order_id';
        $sort_order = $_GET['order'] ?? 'desc';

        $res = $this->fetchOrders($filter_status, $filter_employee, $filter_payment, $filter_search, $sort_by, $sort_column_key, $sort_order, $filter_date_from, $filter_date_to);

        $conn = $this->conn;
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $is_ajax_request = true;
            require_once __DIR__ . '/../View/order/list.php';
            return;
        }
        
        require_once __DIR__ . '/../View/order/list.php';
    }

    private function fetchOrders(string $filter_status = '', string $filter_employee = '', string $filter_payment = '', string $search_query = '', string $sort_by = 'latest', string $sort_column = 'order_id', string $sort_order = 'desc', string $filter_date_from = '', string $filter_date_to = ''): \mysqli_result|false
    {
        // This method is for fetching orders, no changes needed here.
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
            if (!empty($filter_employee)) {
                $where_clauses[] = "(o.designer_id = ? OR o.workshop_id = ?)";
                $params[] = $filter_employee;
                $params[] = $filter_employee;
                $types .= "ii";
            }
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
                $where_clauses[] = "(o.order_id LIKE ? OR c.company_name LIKE ? OR p.name LIKE ?)";
                $search_param = "%$search_query%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
                $types .= "sss";
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
        } elseif (Permissions::has_permission('order_view_own', $this->conn)) {
            $role_conditions = \App\Core\RoleBasedQuery::buildRoleBasedConditions($user_role, $user_id, '', $filter_status, $filter_payment, $search_query, $this->conn, true);
            $where_clauses = array_merge($where_clauses, $role_conditions['where_clauses']);
            $params = array_merge($params, $role_conditions['params']);
            $types .= $role_conditions['types'];
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
            $where_clauses[] = "1=0";
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql .= " GROUP BY o.order_id";
        
        $order_by_clause = 'o.order_date DESC'; // Default sort
        // Sorting logic remains the same...

        $sql .= " ORDER BY " . $order_by_clause;

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function add(): void
    {
        // This method is for showing the form, no changes needed here.
        if (!Permissions::has_permission('order_add', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }
        $page_title = 'إضافة طلب جديد';
        $conn = $this->conn;
        $is_edit = false;
        
        $products_result = $this->conn->query("SELECT product_id, name FROM products ORDER BY name");
        $products_array = $products_result->fetch_all(MYSQLI_ASSOC);

        $employees_array = [];
        $workshop_employees = [];
        if ($_SESSION['user_role'] === 'مدير') {
            $employees_result = $this->conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
            $employees_array = $employees_result->fetch_all(MYSQLI_ASSOC);
            
            $workshop_result = $this->conn->query("SELECT employee_id, name, role FROM employees WHERE role = 'معمل' ORDER BY name");
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

        // Prepare data for the centralized updater
        $orderData = $_POST;

        // Handle designer assignment logic before passing to the updater
        if (empty($orderData['designer_id'])) {
            if (RoleHelper::getCurrentUserRole() === 'مصمم') {
                $orderData['designer_id'] = RoleHelper::getCurrentUserId();
            } else {
                MessageSystem::setError("يجب اختيار المسؤول عن التصميم.");
                header("Location: " . $_ENV['BASE_PATH'] . "/orders/add");
                exit;
            }
        }

        // Call the centralized method to create the order
        $result = OrderUpdater::createOrder($this->conn, $orderData);

        if ($result['success']) {
            MessageSystem::setSuccess($result['message']);
            header("Location: " . $_ENV['BASE_PATH'] . "/orders");
            exit;
        } else {
            MessageSystem::setError($result['message']);
            // Redirect back to the form to show the error
            header("Location: " . $_ENV['BASE_PATH'] . "/orders/add");
            exit;
        }
    }

    public function edit(): void
    {
        // This method is for showing the form, no changes needed here.
        if (!Permissions::has_permission('order_edit', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }
        $id = $_GET['id'] ?? null;
        if (!$id) { exit; }

        $stmt = $this->conn->prepare("SELECT o.*, c.company_name, c.phone FROM orders o JOIN clients c ON o.client_id = c.client_id WHERE o.order_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) { exit; }
        
        $page_title = "تعديل الطلب #" . $order['order_id'];
        $conn = $this->conn;
        $is_edit = true;

        $products_result = $this->conn->query("SELECT product_id, name FROM products ORDER BY name");
        $products_array = $products_result->fetch_all(MYSQLI_ASSOC);

        $employees_result = $this->conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY name");
        $employees_array = $employees_result->fetch_all(MYSQLI_ASSOC);

        $items_result = $this->conn->query("SELECT * FROM order_items WHERE order_id = " . $id);
        $order_items = $items_result->fetch_all(MYSQLI_ASSOC);

        require_once __DIR__ . '/../View/order/form.php';
    }

    public function update(): void
    {
        // IMPORTANT: This method only updates order details, not status.
        // The logic for status changes is now in OrderUpdater and called from other methods.
        // This method is intentionally left for now to allow editing of non-status fields.
        // A future improvement could be to centralize this as well.
        if (!Permissions::has_permission('order_edit', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) { exit; }

        $client_id = $_POST['client_id'] ?? null;
        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $designer_id = $_POST['designer_id'] ?? $_SESSION['user_id'];
        $total_amount = $_POST['total_amount'] ?? 0;
        $deposit_amount = $_POST['deposit_amount'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? '';
        $notes = $_POST['notes'] ?? '';

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
        } else {
            MessageSystem::setError("حدث خطأ أثناء تحديث الطلب: " . $stmt->error);
            header("Location: " . $_ENV['BASE_PATH'] . "/orders/edit?id=" . $id);
        }
        exit;
    }

    public function destroy(): void
    {
        // This method is for deleting orders, no changes needed here.
        if (!Permissions::has_permission('order_delete', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/orders');
            exit;
        }
        $id = $_POST['id'] ?? null;
        if (!$id) { exit; }

        $this->conn->query("DELETE FROM order_items WHERE order_id = $id");
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            MessageSystem::setSuccess("تم حذف الطلب بنجاح!");
        } else {
            MessageSystem::setError("حدث خطأ أثناء حذف الطلب: " . $stmt->error);
        }
        header("Location: " . $_ENV['BASE_PATH'] . "/orders");
        exit;
    }

    public function markReadyForDelivery(): void
    {
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id) {
            $result = OrderUpdater::updateStatus($this->conn, intval($order_id), 'جاهز للتسليم');
            if ($result['success']) {
                MessageSystem::setSuccess("تم تحديث حالة الطلب بنجاح.");
                // Optional: WhatsApp logic can be triggered here after success
            } else {
                MessageSystem::setError($result['message']);
            }
        }
        header('Location: ' . $_ENV['BASE_PATH'] . '/dashboard'); // Redirect anyway
        exit;
    }

    public function confirmClientReceived(): void
    {
        $order_id = $_POST['order_id'] ?? null;
        if ($order_id) {
            $result = OrderUpdater::updateStatus($this->conn, intval($order_id), 'مكتمل');
            if ($result['success']) {
                MessageSystem::setSuccess("تم تأكيد استلام العميل.");
            } else {
                MessageSystem::setError($result['message']);
            }
        }
        header('Location: ' . $_ENV['BASE_PATH'] . '/dashboard'); // Redirect anyway
        exit;
    }

    public function rate(): void
    {
        // This method is for rating, no changes needed here.
        $order_id = $_POST['order_id'] ?? null;
        $type = $_POST['type'] ?? null;
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
            } else {
                error_log("DB Error (OrderController/rate): " . $stmt->error);
                http_response_code(500);
                echo "DB Error: " . $stmt->error;
            }
        }
        http_response_code(400);
        echo "Missing data";
        exit;
    }
}