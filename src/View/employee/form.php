<?php
// src/View/employee/form.php

// Determine if we are editing or adding
$is_edit = isset($employee);
$page_title = $is_edit ? "تعديل الموظف #{$employee['employee_id']}" : 'إضافة موظف جديد';

// Set form values
$name = $employee['name'] ?? '';
$role = $employee['role'] ?? '';
$phone = $employee['phone'] ?? '';
$email = $employee['email'] ?? '';

?>
<div class="container">
    <form method="post">
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
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($phone) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ</button>
        <a href="/?page=employees" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
