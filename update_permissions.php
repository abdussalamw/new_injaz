<?php
$page_title = 'تعديل صلاحيات الموظف';
include 'db_connection_secure.php';
include 'header.php';

// صلاحية الوصول لهذه الصفحة
check_permission('employee_permissions_edit', $conn);

// جلب ID الموظف من الرابط
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($employee_id === 0) {
    die('<div class="container alert alert-danger">معرف الموظف غير صحيح.</div>');
}

// جلب بيانات الموظف
$stmt_emp = $conn->prepare("SELECT name, role FROM employees WHERE employee_id = ?");
$stmt_emp->bind_param("i", $employee_id);
$stmt_emp->execute();
$employee = $stmt_emp->get_result()->fetch_assoc();
if (!$employee) {
    die('<div class="container alert alert-danger">لم يتم العثور على الموظف.</div>');
}

// جلب كل الصلاحيات المتاحة في النظام
$all_permissions = get_all_permissions();

// جلب الصلاحيات الحالية للموظف
$stmt_perms = $conn->prepare("SELECT permission_key FROM employee_permissions WHERE employee_id = ?");
$stmt_perms->bind_param("i", $employee_id);
$stmt_perms->execute();
$current_permissions_res = $stmt_perms->get_result();
$current_permissions = [];
while ($row = $current_permissions_res->fetch_assoc()) {
    $current_permissions[] = $row['permission_key'];
}

// --- معالجة تحديث البيانات عند إرسال النموذج ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_permissions = $_POST['permissions'] ?? [];

    // بدء transaction لضمان سلامة البيانات
    $conn->begin_transaction();

    try {
        // 1. حذف كل الصلاحيات القديمة للموظف
        $stmt_delete = $conn->prepare("DELETE FROM employee_permissions WHERE employee_id = ?");
        $stmt_delete->bind_param("i", $employee_id);
        $stmt_delete->execute();

        // 2. إضافة الصلاحيات الجديدة
        if (!empty($submitted_permissions)) {
            $stmt_insert = $conn->prepare("INSERT INTO employee_permissions (employee_id, permission_key) VALUES (?, ?)");
            foreach ($submitted_permissions as $perm_key) {
                // التأكد من أن الصلاحية المرسلة هي صلاحية حقيقية موجودة بالنظام
                $is_valid_perm = false;
                foreach ($all_permissions as $group) {
                    if (isset($group[$perm_key])) {
                        $is_valid_perm = true;
                        break;
                    }
                }
                if ($is_valid_perm) {
                    $stmt_insert->bind_param("is", $employee_id, $perm_key);
                    $stmt_insert->execute();
                }
            }
        }

        // إتمام العملية
        $conn->commit();

        // رسالة نجاح وإعادة توجيه
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تحديث صلاحيات الموظف بنجاح.'];
        header("Location: employees.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "حدث خطأ أثناء تحديث الصلاحيات: " . $e->getMessage();
    }
}
?>

<div class="container">
    <h3 class="mb-4">تعديل صلاحيات: <?= htmlspecialchars($employee['name']) ?> (<?= htmlspecialchars($employee['role']) ?>)</h3>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <form action="update_permissions.php?id=<?= $employee_id ?>" method="POST">
        <div class="card">
            <div class="card-body">
                <?php foreach ($all_permissions as $group_name => $permissions): ?>
                    <fieldset class="mb-4">
                        <legend class="fs-5 border-bottom pb-2 mb-3"><?= htmlspecialchars($group_name) ?></legend>
                        <div class="row">
                            <?php foreach ($permissions as $perm_key => $perm_description): ?>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm_key ?>" id="<?= $perm_key ?>"
                                            <?php if (in_array($perm_key, $current_permissions) || $employee['role'] === 'مدير') echo 'checked'; ?>
                                            <?php if ($employee['role'] === 'مدير') echo 'disabled'; ?>>
                                        <label class="form-check-label" for="<?= $perm_key ?>">
                                            <?= htmlspecialchars($perm_description) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <div class="card-footer text-end">
                <?php if ($employee['role'] !== 'مدير'): ?>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                <?php else: ?>
                    <p class="mb-0 text-muted">لا يمكن تعديل صلاحيات المدير.</p>
                <?php endif; ?>
                <a href="employees.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
