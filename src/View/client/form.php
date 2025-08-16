<?php
// src/View/client/form.php
// Logic is now in ClientController

$is_edit = $is_edit ?? false;
$client = $client ?? [];
$page_title = $page_title ?? ($is_edit ? 'تعديل العميل' : 'إضافة عميل جديد');
$error = $error ?? null;

// Set form values
$company_name = $client['company_name'] ?? '';
$phone = $client['phone'] ?? '';
$email = $client['email'] ?? '';
?>
<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="<?= $is_edit ? $_ENV['BASE_PATH'] . '/clients/update' : $_ENV['BASE_PATH'] . '/clients' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($client['client_id']) ?>">
        <?php endif; ?>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">اسم الجهة والشخص المسؤول <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($company_name) ?>" required placeholder="اكتب اسم الجهة والشخص المسؤول">
            </div>
            <div class="col-md-6">
                <label class="form-label">الجوال <span class="text-danger">*</span></label>
                <input type="tel" name="phone" id="phone_input" class="form-control" 
                       pattern="^05[0-9]{8}$" 
                       placeholder="05xxxxxxxx" 
                       title="يجب أن يبدأ الرقم بـ 05 ويتكون من 10 أرقام"
                       maxlength="10" 
                       required 
                       value="<?= htmlspecialchars($phone) ?>">
                <div class="form-text text-muted">مثال: 0501234567</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">البريد الإلكتروني (اختياري)</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="email@example.com">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ</button>
        <a href="<?= $_ENV['BASE_PATH'] ?>/clients" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number validation
    const phoneInputEl = document.getElementById('phone_input');
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
});
</script>
