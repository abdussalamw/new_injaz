<?php
$id = intval($_GET['id'] ?? 0);
$page_title = "تعديل بيانات الموظف";
include 'db_connection.php';
include 'header.php';

check_permission('employee_edit', $conn);

$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo "<div class='alert alert-danger'>الموظف غير موجود</div>";
    include 'footer.php'; exit;
}

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
    header("Location: edit_employee.php?id=" . $id);
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
    header("Location: edit_employee.php?id=" . $id);
    exit;
}
?>
<div class="container">
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">الدور</label>
                <select class="form-select" name="role" required <?= ($id === ($_SESSION['user_id'] ?? 0)) ? 'disabled' : '' ?>>
                    <option value="مدير" <?= $row['role']=='مدير'?'selected':'' ?>>مدير</option>
                    <option value="مصمم" <?= $row['role']=='مصمم'?'selected':'' ?>>مصمم</option>
                    <option value="معمل" <?= $row['role']=='معمل'?'selected':'' ?>>معمل</option>
                    <option value="محاسب" <?= $row['role']=='محاسب'?'selected':'' ?>>محاسب</option>
                </select>
                <?php
                // إذا كان المستخدم يعدل ملفه الخاص، أضف حقل مخفي لضمان إرسال الدور مع الفورم
                if ($id === ($_SESSION['user_id'] ?? 0)): ?>
                    <input type="hidden" name="role" value="<?= htmlspecialchars($row['role']) ?>">
                    <div class="form-text">لا يمكنك تغيير دور حسابك الخاص.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">كلمة المرور الجديدة</label>
                <input type="password" class="form-control" name="password" placeholder="اتركه فارغاً لعدم التغيير">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ التعديلات</button>
        <a href="employees.php" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>

    <?php if (has_permission('employee_password_reset', $conn)): ?>
    <hr class="my-4">
    <h4 style="color:#D44759;" class="mb-3">إدارة كلمة المرور</h4>
    <form method="post">
        <button type="submit" name="reset_password" class="btn btn-warning" onclick="return confirm('هل أنت متأكد من إعادة تعيين كلمة المرور لهذا الموظف؟ ستصبح كلمة المرور الافتراضية هي demo123.')">إعادة تعيين كلمة المرور</button>
    </form>
    <?php endif; ?>

    <?php 
    // عرض قسم الصلاحيات فقط إذا كان المستخدم الحالي هو "مدير"
    // ولا يقوم بتعديل ملفه الشخصي (لمنع قفل الحساب عن طريق الخطأ)
    if (($_SESSION['user_role'] ?? '') === 'مدير' && $id !== ($_SESSION['user_id'] ?? 0)): ?>
    <hr class="my-4">
    <h4 style="color:#D44759;" class="mb-3">صلاحيات الموظف</h4>
    <div id="permissions-container">
        <?php
        // جلب صلاحيات الموظف الحالية
        $current_permissions_stmt = $conn->prepare("SELECT permission_key FROM employee_permissions WHERE employee_id = ?");
        $current_permissions_stmt->bind_param("i", $id);
        $current_permissions_stmt->execute();
        $current_permissions_result = $current_permissions_stmt->get_result();
        $employee_permissions = [];
        while($perm_row = $current_permissions_result->fetch_assoc()) {
            $employee_permissions[] = $perm_row['permission_key'];
        }

        $all_permissions = get_all_permissions();
        foreach ($all_permissions as $group => $permissions):
        ?>
        <div class="card mb-3">
            <div class="card-header fw-bold"><?= $group ?></div>
            <div class="card-body">
                <div class="row">
                <?php foreach ($permissions as $key => $label): ?>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="<?= $key ?>" 
                                   data-permission-key="<?= $key ?>" 
                                   <?= in_array($key, $employee_permissions) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="<?= $key ?>"><?= $label ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div id="permission-feedback" class="mt-2"></div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const switches = document.querySelectorAll('#permissions-container .form-check-input');
    const feedbackDiv = document.getElementById('permission-feedback');

    switches.forEach(s => {
        s.addEventListener('change', function() {
            const permissionKey = this.dataset.permissionKey;
            const isChecked = this.checked;
            const employeeId = <?= $id ?>;

            feedbackDiv.innerHTML = `<div class="alert alert-info">جاري تحديث الصلاحية...</div>`;

            fetch('update_permissions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    permission_key: permissionKey,
                    has_permission: isChecked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const actionText = isChecked ? 'تم منح صلاحية' : 'تم سحب صلاحية';
                    feedbackDiv.innerHTML = `<div class="alert alert-success">${actionText} "${this.nextElementSibling.textContent}".</div>`;
                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    // إعادة المفتاح إلى حالته السابقة عند الفشل
                    this.checked = !isChecked;
                }
                setTimeout(() => { feedbackDiv.innerHTML = ''; }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackDiv.innerHTML = `<div class="alert alert-danger">حدث خطأ في الشبكة. يرجى التحقق من console المتصفح.</div>`;
                this.checked = !isChecked;
            });
        });
    });
});
</script>
<?php include 'footer.php'; ?>
