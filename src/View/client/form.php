<?php
// src/View/client/form.php

// Determine if we are editing or adding
$is_edit = isset($client);
$page_title = $is_edit ? "تعديل العميل #{$client['client_id']}" : 'إضافة عميل جديد';

// Set form values
$company_name = $client['company_name'] ?? '';
$contact_person = $client['contact_person'] ?? '';
$phone = $client['phone'] ?? '';
$email = $client['email'] ?? '';

?>
<div class="container">
    <form method="post">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المؤسسة</label>
                <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($company_name) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">اسم الشخص المسؤول</label>
                <input type="text" class="form-control" name="contact_person" value="<?= htmlspecialchars($contact_person) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ</button>
        <a href="/?page=clients" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
