<?php
// src/View/order/form.php

// All the logic is now in the OrderController.
// This file is now purely for presentation.

// The controller will set these variables for us.
$is_edit = $is_edit ?? false;
$order = $order ?? [];
$page_title = $page_title ?? ($is_edit ? 'تعديل الطلب' : 'إضافة طلب جديد');
$products_array = $products_array ?? [];
$employees_array = $employees_array ?? [];
$order_items = $order_items ?? [];
$error = $error ?? null;

// Set form values for both add and edit, falling back to defaults
$client_id = $order['client_id'] ?? '';
$company_name = $order['company_name'] ?? '';
$contact_person = $order['contact_person'] ?? '';
$phone = $order['phone'] ?? '';
$due_date = $order['due_date'] ?? date('Y-m-d');
$priority = $order['priority'] ?? 'متوسط';
$designer_id = $order['designer_id'] ?? '';
$total_amount = $order['total_amount'] ?? '0';
$deposit_amount = $order['deposit_amount'] ?? '0';
$payment_method = $order['payment_method'] ?? 'نقدي';
$notes = $order['notes'] ?? '';

?>
<div class="container" style="max-width: 900px;">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= $is_edit ? $_ENV['BASE_PATH'] . '/orders/update' : $_ENV['BASE_PATH'] . '/orders' ?>" id="order-form">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($order['order_id']) ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="row g-3">
                <fieldset class="border p-3 rounded mb-2">
                    <legend class="float-none w-auto px-2 h6">معلومات الجهة</legend>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">اسم الجهة واسم الشخص المسؤول</label>
                            <div class="position-relative">
                                <input type="text" name="company_name" id="company_name_input" class="form-control" autocomplete="off" required value="<?= htmlspecialchars($company_name) ?>">
                                <input type="hidden" name="client_id" id="client_id_hidden" value="<?= htmlspecialchars($client_id) ?>">
                                <div id="autocomplete-list" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
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
                    </div>
                </fieldset>

                <!-- Order Items Section -->
                <fieldset class="border p-3 rounded mb-2">
                    <legend class="float-none w-auto px-2 h6">بنود الطلب</legend>
                    <div id="order-items-container">
                        <!-- JS will populate this -->
                    </div>
                    <button type="button" id="add-item-btn" class="btn btn-outline-success mt-2">إضافة منتج آخر +</button>
                </fieldset>

                <!-- Payment and Details Section -->
                <fieldset class="border p-3 rounded mb-2">
                    <legend class="float-none w-auto px-2 h6">التفاصيل المالية والإدارية</legend>
                    <div class="row g-3">
                        <!-- Row 1 -->
                        <div class="col-md-4">
                            <label class="form-label">تاريخ التسليم</label>
                            <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($due_date) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الأولوية</label>
                            <select name="priority" class="form-select">
                                <option value="عاجل جداً" <?= ($priority == 'عاجل جداً') ? 'selected' : '' ?>>عاجل جداً</option>
                                <option value="عالي" <?= ($priority == 'عالي') ? 'selected' : '' ?>>عالي</option>
                                <option value="متوسط" <?= ($priority == 'متوسط') ? 'selected' : '' ?>>متوسط</option>
                                <option value="منخفض" <?= ($priority == 'منخفض') ? 'selected' : '' ?>>منخفض</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المسؤول عن التصميم</label>
                            <?php if ($_SESSION['user_role'] === 'مدير'): ?>
                                <select name="designer_id" class="form-select" required>
                                    <option value="">اختر المسؤول...</option>
                                    <?php foreach ($employees_array as $d_row): ?>
                                        <option value="<?= $d_row['employee_id'] ?>" <?= ($designer_id == $d_row['employee_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d_row['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <p class="form-control-plaintext bg-light border rounded-pill px-3"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                                <input type="hidden" name="designer_id" value="<?= $_SESSION['user_id'] ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Row 2 -->
                        <div class="col-md-4">
                            <label class="form-label">المبلغ الإجمالي (شامل الضريبة)</label>
                            <input type="number" name="total_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($total_amount) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الدفعة المقدمة</label>
                            <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($deposit_amount) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">طريقة الدفع</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="نقدي" <?= ($payment_method == 'نقدي') ? 'selected' : '' ?>>نقدي</option>
                                <option value="تحويل بنكي" <?= ($payment_method == 'تحويل بنكي') ? 'selected' : '' ?>>تحويل بنكي</option>
                                <option value="فوري" <?= ($payment_method == 'فوري') ? 'selected' : '' ?>>فوري</option>
                                <option value="غيره" <?= ($payment_method == 'غيره') ? 'selected' : '' ?>>غيره</option>
                            </select>
                        </div>

                        <!-- Row 3 -->
                        <div class="col-12">
                            <label class="form-label">ملاحظات عامة على الطلب</label>
                            <textarea name="notes" class="form-control" rows="2" style="max-width:400px;min-width:200px;display:inline-block;"><?= htmlspecialchars($notes) ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <div class="col-12 text-center mt-3">
                    <button class="btn btn-lg px-5 text-white" type="submit" style="background-color:#D44759;">حفظ</button>
                    <a href="<?= $_ENV['BASE_PATH'] ?>/orders" class="btn btn-secondary ms-2">عودة للقائمة</a>
                </div>
            </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client Autocomplete Logic
    const companyInput = document.getElementById('company_name_input');
    const phoneInput = document.getElementById('phone_input');
    const clientIdHidden = document.getElementById('client_id_hidden');
    const autocompleteList = document.getElementById('autocomplete-list');

    companyInput.addEventListener('keyup', function() {
        const query = this.value;
        clientIdHidden.value = '';
        if (query.length < 1) {
            autocompleteList.innerHTML = '';
            return;
        }
        fetch(`<?= $_ENV['BASE_PATH'] ?>/api/clients/search?query=${query}`)
            .then(response => response.json())
            .then(clients => {
                autocompleteList.innerHTML = '';
                if (clients.length > 0) {
                    clients.forEach(client => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.classList.add('list-group-item', 'list-group-item-action');
                        item.textContent = client.company_name;
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            companyInput.value = client.company_name;
                            phoneInput.value = client.phone;
                            clientIdHidden.value = client.client_id;
                            autocompleteList.innerHTML = '';
                        });
                        autocompleteList.appendChild(item);
                    });
                } else {
                    autocompleteList.innerHTML = '<span class="list-group-item text-muted">لا توجد نتائج (سيتم إنشاء مؤسسة جديدة)</span>';
                }
            });
    });

    document.addEventListener('click', function(e) {
        if (e.target !== companyInput) {
            autocompleteList.innerHTML = '';
        }
    });

    // Order Items Logic
    const itemsContainer = document.getElementById('order-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    let itemCounter = 0;

    const existingItems = <?= json_encode($order_items) ?>;
    const products_array = <?= json_encode($products_array) ?>;

    function addOrderItem(item = null) {
        itemCounter++;
        const newIndex = itemCounter;

        const itemQty = item ? item.quantity : 1;
        const itemNotes = item ? item.item_notes : '';
        const productId = item ? item.product_id : '';

        const productOptions = products_array.map(p => 
            `<option value="${p.product_id}" ${productId == p.product_id ? 'selected' : ''}>${p.name}</option>`
        ).join('') + `<option value="other" ${productId === null ? 'selected' : ''}>أخرى</option>`;

        const itemHtml = `
            <div class="order-item-row row g-3 mb-3 border-bottom pb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">المنتج</label>
                    <select name="products[${newIndex}][product_id]" class="form-select product-select" required>
                        <option value="">اختر منتج...</option>
                        ${productOptions}
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">الكمية</label><input type="number" name="products[${newIndex}][quantity]" class="form-control" min="1" value="${itemQty}" required></div>
                <div class="col-md-6"><label class="form-label">تفاصيل الطلب</label><textarea name="products[${newIndex}][item_notes]" class="form-control" rows="2">${itemNotes ? itemNotes : ''}</textarea></div>
            </div>
        `;
        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
    }

    itemsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-item-btn')) {
            e.target.closest('.order-item-row').remove();
        }
    });

    addItemBtn.addEventListener('click', () => addOrderItem());

    if (existingItems.length > 0) {
        itemsContainer.innerHTML = ''; // Clear placeholder
        existingItems.forEach(item => addOrderItem(item));
    } else {
        addOrderItem(); // Add one empty row for new orders
    }

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
