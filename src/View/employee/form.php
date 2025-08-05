<?php
// src/View/employee/form.php
// Logic is now in EmployeeController

$is_edit = $is_edit ?? false;
$employee = $employee ?? [];
$page_title = $page_title ?? ($is_edit ? 'تعديل الموظف' : 'إضافة موظف جديد');
$error = $error ?? null;

// Set form values
$name = $employee['name'] ?? '';
$role = $employee['role'] ?? '';
$phone = $employee['phone'] ?? '';
$email = $employee['email'] ?? '';
?>
<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $is_edit ? '/new_injaz/employees/update' : '/new_injaz/employees' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($employee['employee_id']) ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">الدور</label>
                <select class="form-select" name="role" required>
                    <option value="مدير" <?= ($role == 'مدير') ? 'selected' : '' ?>>مدير</option>
                    <option value="مصمم" <?= ($role == 'مصمم') ? 'selected' : '' ?>>مصمم</option>
                    <option value="معمل" <?= ($role == 'معمل') ? 'selected' : '' ?>>معمل</option>
                    <option value="محاسب" <?= ($role == 'محاسب') ? 'selected' : '' ?>>محاسب</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="tel" name="phone" id="phone_input" class="form-control" 
                       pattern="^05[0-9]{8}$" 
                       placeholder="05xxxxxxxx" 
                       title="يجب أن يبدأ الرقم بـ 05 ويتكون من 10 أرقام"
                       maxlength="10" 
                       value="<?= htmlspecialchars($phone) ?>">
                <div class="form-text text-muted">مثال: 0501234567</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label"><?= $is_edit ? 'كلمة المرور الجديدة (اختياري)' : 'كلمة المرور' ?></label>
                <input type="password" class="form-control" name="password" <?= $is_edit ? '' : 'required' ?>>
                <?php if ($is_edit): ?>
                    <small class="text-muted">اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور</small>
                <?php endif; ?>
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ</button>
        <a href="/new_injaz/employees" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number validation
    const phoneInputEl = document.getElementById('phone_input');
    if (phoneInputEl) {
        phoneInputEl.addEventListener('input', function() {
            const phone = this.value;
            const phonePattern = /^05[0-9]{8}$/;

            if (phone && !phonePattern.test(phone)) {
                this.setCustomValidity('يجب أن يبدأ الرقم بـ 05 ويتكون من 10 أرقام');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>
