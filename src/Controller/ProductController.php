<?php

function list_products() {
    global $conn;
    $page_title = 'إدارة المنتجات';

    // --- Sorting Logic ---
    $sort_column_key = $_GET['sort'] ?? 'name';
    $sort_order = $_GET['order'] ?? 'ASC';

    $column_map = [
        'product_id' => 'product_id',
        'name' => 'name',
        'default_size' => 'default_size',
        'default_material' => 'default_material'
    ];
    $allowed_sort_columns = array_keys($column_map);
    if (!in_array($sort_column_key, $allowed_sort_columns)) {
        $sort_column_key = 'name';
    }
    if (strtoupper($sort_order) !== 'ASC' && strtoupper($sort_order) !== 'DESC') {
        $sort_order = 'ASC';
    }
    $sort_column_sql = $column_map[$sort_column_key];

    include_once __DIR__ . '/../View/product/list.php';
}

function add_product() {
    global $conn;
    $page_title = 'إضافة منتج جديد';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'];
        $size = $_POST['default_size'];
        $material = $_POST['default_material'];
        $details = $_POST['default_details'];

        $stmt = $conn->prepare("INSERT INTO products (name, default_size, default_material, default_details) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $size, $material, $details);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة المنتج بنجاح.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء إضافة المنتج.'];
        }
        header("Location: /?page=products");
        exit;
    }

    include_once __DIR__ . '/../View/product/form.php';
}

function edit_product() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);
    $page_title = "تعديل المنتج #" . $id;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'];
        $size = $_POST['default_size'];
        $material = $_POST['default_material'];
        $details = $_POST['default_details'];
        $stmt2 = $conn->prepare("UPDATE products SET name=?, default_size=?, default_material=?, default_details=? WHERE product_id=?");
        $stmt2->bind_param("ssssi", $name, $size, $material, $details, $id);
        if ($stmt2->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل المنتج بنجاح.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء تعديل المنتج.'];
        }
        header("Location: /?page=products&action=edit&id=" . $id);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo "<div class='alert alert-danger'>المنتج غير موجود</div>";
        return;
    }

    include_once __DIR__ . '/../View/product/form.php';
}

function delete_product() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);

    if ($id) {
        // Check for related records in the order_items table
        $stmt_check = $conn->prepare("SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن حذف هذا المنتج لأنه مستخدم في طلبات حالية.'];
            header("Location: /?page=products");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف المنتج بنجاح.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لم يتم العثور على المنتج أو حدث خطأ.'];
        }
    }
    header("Location: /?page=products");
    exit;
}

function ajax_add_product() {
    global $conn;
    header('Content-Type: application/json');

    if (!has_permission('product_add', $conn)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك الصلاحية لإضافة منتج.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['name']) || empty(trim($input['name']))) {
        echo json_encode(['success' => false, 'message' => 'اسم المنتج مطلوب']);
        exit;
    }

    $name = trim($input['name']);
    $default_size = trim($input['default_size'] ?? '');
    $default_material = trim($input['default_material'] ?? '');
    $default_details = trim($input['default_details'] ?? '');

    try {
        // التحقق من عدم وجود منتج بنفس الاسم
        $check_stmt = $conn->prepare("SELECT product_id FROM products WHERE name = ?");
        $check_stmt->bind_param("s", $name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'يوجد منتج بهذا الاسم مسبقاً']);
            exit;
        }

        // إضافة المنتج الجديد
        $stmt = $conn->prepare("INSERT INTO products (name, default_size, default_material, default_details) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $default_size, $default_material, $default_details);
        
        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'تم إضافة المنتج بنجاح',
                'product' => [
                    'product_id' => $product_id,
                    'name' => $name,
                    'default_size' => $default_size,
                    'default_material' => $default_material
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل في إضافة المنتج']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
    exit;
}
