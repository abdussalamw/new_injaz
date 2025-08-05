<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Permissions;
use App\Core\MessageSystem;

class ClientController
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        if (!Permissions::has_permission('client_view', $this->conn)) {
            header('Location: /new_injaz/');
            exit;
        }

        $page_title = 'العملاء';

        $sort_column_key = $_GET['sort'] ?? 'client_id';
        $sort_order = $_GET['order'] ?? 'asc';
        $sort_column_sql = $sort_column_key; // For simplicity, assuming direct column names

        // البحث
        $search = $_GET['search'] ?? '';
        $where_clause = '';
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where_clause = "WHERE company_name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?";
            $search_param = "%$search%";
            $params = [$search_param, $search_param, $search_param, $search_param];
            $types = 'ssss';
        }

        $sql = "SELECT * FROM clients $where_clause ORDER BY $sort_column_sql $sort_order";
        
        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->conn->query($sql);
        }

        $conn = $this->conn; // Make $conn available for the view
        require_once __DIR__ . '/../View/client/list.php';
    }

    public function add(): void
    {
        if (!Permissions::has_permission('client_add', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }
        $page_title = 'إضافة عميل جديد';
        $is_edit = false;
        require_once __DIR__ . '/../View/client/form.php';
    }

    public function store(): void
    {
        if (!Permissions::has_permission('client_add', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        $company_name = $_POST['company_name'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';

        // التحقق من صحة رقم الجوال
        if (!empty($phone) && !preg_match('/^05[0-9]{8}$/', $phone)) {
            $error = "رقم الجوال غير صحيح. يجب أن يبدأ بـ 05 ويتكون من 10 أرقام";
            $page_title = 'إضافة عميل جديد';
            $is_edit = false;
            require_once __DIR__ . '/../View/client/form.php';
            return;
        }

        $stmt = $this->conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);

        if ($stmt->execute()) {
            MessageSystem::setSuccess("تم إضافة العميل بنجاح!");
            header("Location: /new_injaz/clients");
            exit;
        } else {
            // Handle error
            MessageSystem::setError("حدث خطأ أثناء إضافة العميل: " . $stmt->error);
            $page_title = 'إضافة عميل جديد';
            $is_edit = false;
            require_once __DIR__ . '/../View/client/form.php';
        }
    }

    public function edit(): void
    {
        if (!Permissions::has_permission('client_edit', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing client ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM clients WHERE client_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $client = $result->fetch_assoc();

        if (!$client) {
            http_response_code(404);
            echo "<h1>404 Not Found: Client not found</h1>";
            return;
        }

        $page_title = "تعديل العميل #" . $client['client_id'];
        $is_edit = true;
        require_once __DIR__ . '/../View/client/form.php';
    }

    public function update(): void
    {
        if (!Permissions::has_permission('client_edit', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing client ID</h1>";
            return;
        }

        $company_name = $_POST['company_name'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';

        // التحقق من صحة رقم الجوال
        if (!empty($phone) && !preg_match('/^05[0-9]{8}$/', $phone)) {
            $error = "رقم الجوال غير صحيح. يجب أن يبدأ بـ 05 ويتكون من 10 أرقام";
            $page_title = "تعديل العميل #" . $id;
            $is_edit = true;
            // We need to fetch the client data again to show the form with the error
            $stmt = $this->conn->prepare("SELECT * FROM clients WHERE client_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $client = $result->fetch_assoc();
            require_once __DIR__ . '/../View/client/form.php';
            return;
        }

        $stmt = $this->conn->prepare("UPDATE clients SET company_name = ?, contact_person = ?, phone = ?, email = ? WHERE client_id = ?");
        $stmt->bind_param("ssssi", $company_name, $contact_person, $phone, $email, $id);

        if ($stmt->execute()) {
            MessageSystem::setSuccess("تم تحديث بيانات العميل بنجاح!");
            header("Location: /new_injaz/clients");
            exit;
        } else {
            // Handle error
            MessageSystem::setError("حدث خطأ أثناء تحديث بيانات العميل: " . $stmt->error);
            $page_title = "تعديل العميل #" . $id;
            $is_edit = true;
            // We need to fetch the client data again to show the form with the error
            $stmt = $this->conn->prepare("SELECT * FROM clients WHERE client_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $client = $result->fetch_assoc();
            require_once __DIR__ . '/../View/client/form.php';
        }
    }

    public function confirmDelete(): void
    {
        if (!Permissions::has_permission('client_delete', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing client ID</h1>";
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM clients WHERE client_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $client = $result->fetch_assoc();

        if (!$client) {
            http_response_code(404);
            echo "<h1>404 Not Found: Client not found</h1>";
            return;
        }

        // Check if client has any orders
        $check_stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM orders WHERE client_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();

        $has_orders = $check_row['count'] > 0;
        $page_title = 'تأكيد حذف العميل';
        require_once __DIR__ . '/../View/client/confirm_delete.php';
    }

    public function export(): void
    {
        if (!Permissions::has_permission('client_export', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clients_export.csv');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add CSV headers
        fputcsv($output, ['رقم العميل', 'اسم المؤسسة', 'الشخص المسؤول', 'رقم الجوال', 'البريد الإلكتروني']);

        // Get all clients
        $result = $this->conn->query("SELECT * FROM clients ORDER BY client_id");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['client_id'],
                $row['company_name'],
                $row['contact_person'],
                $row['phone'],
                $row['email']
            ]);
        }

        fclose($output);
        exit;
    }

    public function import(): void
    {
        if (!Permissions::has_permission('client_import', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error = "يرجى اختيار ملف CSV صحيح.";
            $page_title = 'العملاء';
            $sort_column_key = $_GET['sort'] ?? 'client_id';
            $sort_order = $_GET['order'] ?? 'asc';
            $sort_column_sql = $sort_column_key;
            $res = $this->conn->query("SELECT * FROM clients ORDER BY $sort_column_sql $sort_order");
            $conn = $this->conn;
            require_once __DIR__ . '/../View/client/list.php';
            return;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            $error = "لا يمكن قراءة الملف.";
            $page_title = 'العملاء';
            $sort_column_key = $_GET['sort'] ?? 'client_id';
            $sort_order = $_GET['order'] ?? 'asc';
            $sort_column_sql = $sort_column_key;
            $res = $this->conn->query("SELECT * FROM clients ORDER BY $sort_column_sql $sort_order");
            $conn = $this->conn;
            require_once __DIR__ . '/../View/client/list.php';
            return;
        }

        // Skip header row
        fgetcsv($handle);
        
        $imported = 0;
        $errors = 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 4) {
                $company_name = $data[1] ?? '';
                $contact_person = $data[2] ?? '';
                $phone = $data[3] ?? '';
                $email = $data[4] ?? '';

                $stmt = $this->conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors++;
                }
            }
        }

        fclose($handle);

        $message = "تم استيراد $imported عميل بنجاح.";
        if ($errors > 0) {
            $message .= " فشل في استيراد $errors عميل.";
            MessageSystem::setWarning($message);
        } else {
            MessageSystem::setSuccess($message);
        }

        header("Location: /new_injaz/clients");
        exit;
    }

    public function destroy(): void
    {
        if (!Permissions::has_permission('client_delete', $this->conn)) {
            header('Location: /new_injaz/clients');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo "<h1>400 Bad Request: Missing client ID</h1>";
            return;
        }

        // Check if client has any orders
        $check_stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM orders WHERE client_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            MessageSystem::setError("لا يمكن حذف هذا العميل لأنه مرتبط بطلبات موجودة. يجب حذف الطلبات المرتبطة أولاً.");
            header("Location: /new_injaz/clients");
            exit;
            $page_title = 'العملاء';
            $sort_column_key = $_GET['sort'] ?? 'client_id';
            $sort_order = $_GET['order'] ?? 'asc';
            $sort_column_sql = $sort_column_key;
            $res = $this->conn->query("SELECT * FROM clients ORDER BY $sort_column_sql $sort_order");
            $conn = $this->conn;
            require_once __DIR__ . '/../View/client/list.php';
            return;
        }

        $stmt = $this->conn->prepare("DELETE FROM clients WHERE client_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            MessageSystem::setSuccess("تم حذف العميل بنجاح!");
            header("Location: /new_injaz/clients");
            exit;
        } else {
            MessageSystem::setError("حدث خطأ أثناء حذف العميل: " . $stmt->error);
            header("Location: /new_injaz/clients");
            exit;
            $page_title = 'العملاء';
            $sort_column_key = $_GET['sort'] ?? 'client_id';
            $sort_order = $_GET['order'] ?? 'asc';
            $sort_column_sql = $sort_column_key;
            $res = $this->conn->query("SELECT * FROM clients ORDER BY $sort_column_sql $sort_order");
            $conn = $this->conn;
            require_once __DIR__ . '/../View/client/list.php';
        }
    }
}
