<?php

function list_clients() {
    global $conn;
    $page_title = 'إدارة العملاء';

    // --- Sorting Logic ---
    $sort_column_key = $_GET['sort'] ?? 'company_name';
    $sort_order = $_GET['order'] ?? 'ASC';

    $column_map = [
        'client_id' => 'client_id',
        'company_name' => 'company_name',
        'contact_person' => 'contact_person',
        'email' => 'email'
    ];
    $allowed_sort_columns = array_keys($column_map);
    if (!in_array($sort_column_key, $allowed_sort_columns)) {
        $sort_column_key = 'company_name';
    }
    if (strtoupper($sort_order) !== 'ASC' && strtoupper($sort_order) !== 'DESC') {
        $sort_order = 'ASC';
    }
    $sort_column_sql = $column_map[$sort_column_key];

    include_once __DIR__ . '/../View/client/list.php';
}

function add_client() {
    global $conn;
    $page_title = 'إضافة عميل جديد';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $company_name = $_POST['company_name'];
        $phone = $_POST['phone'];
        $contact_person = $_POST['contact_person'];
        $email = $_POST['email'];

        // التحقق من صحة رقم الجوال السعودي
        if (empty($phone) || !preg_match('/^05[0-9]{8}$/', $phone)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'الرجاء إدخال رقم جوال سعودي صحيح (10 أرقام تبدأ بـ 05).'];
            header("Location: /?page=clients&action=add");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تمت إضافة العميل بنجاح!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء إضافة العميل.'];
        }
        header("Location: /?page=clients");
        exit;
    }

    include_once __DIR__ . '/../View/client/form.php';
}

function edit_client() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);
    $page_title = "تعديل بيانات العميل #" . $id;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $company_name = $_POST['company_name'];
        $contact_person = $_POST['contact_person'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];

        // التحقق من صحة رقم الجوال السعودي
        if (empty($phone) || !preg_match('/^05[0-9]{8}$/', $phone)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'الرجاء إدخال رقم جوال سعودي صحيح (10 أرقام تبدأ بـ 05).'];
            header("Location: /?page=clients&action=edit&id=" . $id);
            exit;
        }

        $stmt2 = $conn->prepare("UPDATE clients SET company_name=?, contact_person=?, phone=?, email=? WHERE client_id=?");
        $stmt2->bind_param("ssssi", $company_name, $contact_person, $phone, $email, $id);
        if ($stmt2->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل بيانات العميل بنجاح!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ أثناء التعديل.'];
        }
        header("Location: /?page=clients&action=edit&id=" . $id);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM clients WHERE client_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();

    if (!$client) {
        echo "<div class='alert alert-danger'>العميل غير موجود</div>";
        return;
    }

    include_once __DIR__ . '/../View/client/form.php';
}

function delete_client() {
    global $conn;
    $id = intval($_GET['id'] ?? 0);

    if ($id) {
        // Check for related records in the orders table
        $stmt_check = $conn->prepare("SELECT 1 FROM orders WHERE client_id = ? LIMIT 1");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن حذف هذا العميل لأنه مرتبط بطلبات حالية.'];
            header("Location: /?page=clients");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM clients WHERE client_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حذف العميل بنجاح.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لم يتم العثور على العميل أو حدث خطأ.'];
        }
    }
    header("Location: /?page=clients");
    exit;
}

function ajax_add_client() {
    global $conn;
    header('Content-Type: application/json');

    if (!has_permission('client_add', $conn)) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك الصلاحية لإضافة عميل.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $company_name = $data['company_name'] ?? '';
    $contact_person = $data['contact_person'] ?? '';
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';

    if (empty($company_name)) {
        echo json_encode(['success' => false, 'message' => 'اسم المؤسسة حقل إجباري.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);

    if ($stmt->execute()) {
        $new_client_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'تمت إضافة العميل بنجاح!',
            'client' => [
                'client_id' => $new_client_id,
                'company_name' => htmlspecialchars($company_name)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.']);
    }
    exit;
}

function ajax_get_client() {
    global $conn;
    header('Content-Type: application/json');

    $id = intval($_GET['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($client = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'client' => $client]);
        } else {
            echo json_encode(['success' => false, 'message' => 'لم يتم العثور على العميل.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'معرف العميل غير صحيح.']);
    }
    exit;
}

function generate_sort_link($column_key, $display_text, $current_sort_key, $current_order) {
    $next_order = ($current_sort_key === $column_key && strtoupper($current_order) === 'ASC') ? 'DESC' : 'ASC';
    $query_params = $_GET;
    $query_params['sort'] = $column_key;
    $query_params['order'] = $next_order;
    $url = '/?' . http_build_query($query_params);
    
    $icon = '';
    if ($current_sort_key === $column_key) {
        $icon = (strtoupper($current_order) === 'ASC') ? ' <i class="fas fa-sort-up text-primary"></i>' : ' <i class="fas fa-sort-down text-primary"></i>';
    } else {
        $icon = ' <i class="fas fa-sort text-muted"></i>';
    }
    
    return '<a href="' . htmlspecialchars($url) . '" class="text-decoration-none text-white d-flex align-items-center justify-content-center" style="cursor: pointer;">' . 
           '<span>' . htmlspecialchars($display_text) . '</span>' . $icon . '</a>';
}
