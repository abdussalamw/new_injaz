<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Permissions;
use App\Core\MessageSystem;

class ProductController
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        if (!Permissions::has_permission('product_view', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/');
            exit;
        }

        $page_title = 'المنتجات';

        $sort_column_key = $_GET['sort'] ?? 'product_id';
        $sort_order = $_GET['order'] ?? 'asc';
        $sort_column_sql = $sort_column_key; // For simplicity, assuming direct column names

        $res = $this->conn->query("SELECT * FROM products ORDER BY $sort_column_sql $sort_order");

        $conn = $this->conn; // Make $conn available for the view
        require_once __DIR__ . '/../View/product/list.php';
    }

    public function add(): void
    {
        if (!Permissions::has_permission('product_add', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/products');
            exit;
        }
        $page_title = 'إضافة منتج جديد';
        $is_edit = false;
        require_once __DIR__ . '/../View/product/form.php';
    }

    public function store(): void
    {
        if (!Permissions::has_permission('product_add', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/products');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $default_size = $_POST['default_size'] ?? '';
        $default_material = $_POST['default_material'] ?? '';
        $default_details = $_POST['default_details'] ?? '';

        $stmt = $this->conn->prepare("INSERT INTO products (name, default_size, default_material, default_details) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $default_size, $default_material, $default_details);

        if ($stmt->execute()) {
            header("Location: " . $_ENV['BASE_PATH'] . "/products");
            exit;
        } else {
            $error = "خطأ في إضافة المنتج";
            $page_title = 'إضافة منتج جديد';
            $is_edit = false;
            require_once __DIR__ . '/../View/product/form.php';
        }
    }

    public function edit(): void
    {
        if (!Permissions::has_permission('product_edit', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/products');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing product ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            http_response_code(404);
            echo "<h1>404 Not Found: Product not found</h1>";
            return;
        }

        $page_title = "تعديل المنتج #" . $product['product_id'];
        $is_edit = true;
        require_once __DIR__ . '/../View/product/form.php';
    }

    public function update(): void
    {
        if (!Permissions::has_permission('product_edit', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/products');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing product ID</h1>";
            return;
        }

        $name = $_POST['name'] ?? '';
        $default_size = $_POST['default_size'] ?? '';
        $default_material = $_POST['default_material'] ?? '';
        $default_details = $_POST['default_details'] ?? '';

        $stmt = $this->conn->prepare("UPDATE products SET name = ?, default_size = ?, default_material = ?, default_details = ? WHERE product_id = ?");
        $stmt->bind_param("ssssi", $name, $default_size, $default_material, $default_details, $id);

        if ($stmt->execute()) {
            header("Location: " . $_ENV['BASE_PATH'] . "/products");
            exit;
        } else {
            $error = "خطأ في تحديث البيانات";
            $page_title = "تعديل المنتج #" . $id;
            $is_edit = true;
            $stmt = $this->conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            require_once __DIR__ . '/../View/product/form.php';
        }
    }

    public function confirmDelete(): void
    {
        if (!Permissions::has_permission('product_delete', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/products');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing product ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            http_response_code(404);
            echo "<h1>404 Not Found: Product not found</h1>";
            return;
        }

        // Check if product is used in any orders
        $check_stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();

        $has_orders = $check_row['count'] > 0;
        $page_title = 'تأكيد حذف المنتج';
        require_once __DIR__ . '/../View/product/confirm_delete.php';
    }

    public function destroy(): void
    {
        if (!Permissions::has_permission('product_delete', $this->conn)) {
            header('Location: ' . $_ENV['BASE_PATH'] . '/products');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing product ID</h1>";
            return;
        }

        // Check if product is used in any orders
        $check_stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            $error = "لا يمكن حذف هذا المنتج لأنه مرتبط بطلبات موجودة. يجب حذف الطلبات المرتبطة أولاً.";
            $page_title = 'المنتجات';
            $sort_column_key = $_GET['sort'] ?? 'product_id';
            $sort_order = $_GET['order'] ?? 'asc';
            $sort_column_sql = $sort_column_key;
            $res = $this->conn->query("SELECT * FROM products ORDER BY $sort_column_sql $sort_order");
            $conn = $this->conn;
            require_once __DIR__ . '/../View/product/list.php';
            return;
        }

        $stmt = $this->conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: " . $_ENV['BASE_PATH'] . "/products");
            exit;
        } else {
            $error = "حدث خطأ أثناء حذف المنتج: " . $stmt->error;
            $page_title = 'المنتجات';
            $sort_column_key = $_GET['sort'] ?? 'product_id';
            $sort_order = $_GET['order'] ?? 'asc';
            $sort_column_sql = $sort_column_key;
            $res = $this->conn->query("SELECT * FROM products ORDER BY $sort_column_sql $sort_order");
            $conn = $this->conn;
            require_once __DIR__ . '/../View/product/list.php';
        }
    }
}
