<?php
$page_title = 'استيراد عملاء';
include 'db_connection.php';
include 'header.php';

$success = false;
$error = '';

check_permission('client_import', $conn);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"]["tmp_name"];
    if (($handle = fopen($file, "r")) !== FALSE) {
        $conn->begin_transaction(); // بدء العملية
        $stmt = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
        try {
            $header = fgetcsv($handle); // تجاهل أول صف
            while (($data = fgetcsv($handle)) !== FALSE) {
                $company_name  = $data[0] ?? '';
                $contact_person = $data[1] ?? '';
                $phone = $data[2] ?? '';
                $email = $data[3] ?? '';
                if ($company_name) {
                    $stmt->bind_param("ssss", $company_name, $contact_person, $phone, $email);
                    $stmt->execute();
                }
            }
            $conn->commit(); // تأكيد العملية إذا نجحت
            $success = true;
        } catch (Exception $e) {
            $conn->rollback(); // التراجع عن العملية في حال حدوث خطأ
            $error = 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage();
        } finally {
            fclose($handle);
        }
    } else {
        $error = 'تعذر قراءة الملف.';
    }
}
?>
<div class="container">
    <?php if($success): ?><div class="alert alert-success">تم الاستيراد بنجاح!</div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">اختر ملف العملاء (CSV أو Excel محفوظ كـ CSV)</label>
            <input type="file" name="file" class="form-control" accept=".csv" required>
        </div>
        <button class="btn btn-primary" type="submit">استيراد</button>
        <a href="clients.php" class="btn btn-secondary">عودة للقائمة</a>
    </form>
    <div class="mt-4 alert alert-info">
        <b>صيغة الملف المطلوبة:</b><br>
        اسم المؤسسة | الشخص المسؤول | الجوال | البريد الإلكتروني<br>
        مثال: شركة إنجاز,أحمد,0551234567,ahmed@injaz.com
    </div>
</div>
<?php include 'footer.php'; ?>
