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
                    <legend class="float-none w-auto px-2 h6"><i class="fas fa-building me-2"></i>معلومات الجهة</legend>
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label"><i class="fas fa-building me-2"></i>اسم الجهة والشخص المسؤول <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" name="company_name" id="company_name_input" class="form-control" autocomplete="off" required value="<?= htmlspecialchars($company_name) ?>" placeholder="اكتب اسم الجهة والشخص المسؤول...">
                                <input type="hidden" name="client_id" id="client_id_hidden" value="<?= htmlspecialchars($client_id) ?>">
                                <div id="autocomplete-list" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ابدأ بالكتابة للبحث في الجهات الموجودة أو إنشاء جهة جديدة
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-phone me-2"></i>الجوال <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="tel" name="phone" id="phone_input" class="form-control" 
                                       pattern="^05[0-9]{8}$" 
                                       placeholder="05xxxxxxxx" 
                                       title="يجب أن يبدأ الرقم بـ 05 ويتكون من 10 أرقام"
                                       maxlength="10" 
                                       required 
                                       value="<?= htmlspecialchars($phone) ?>">
                                <i class="fas fa-lock position-absolute" id="phone_lock" style="left: 10px; top: 50%; transform: translateY(-50%); display: none; color: #6c757d;"></i>
                                <div class="form-text text-muted">مثال: 0501234567</div>
                            </div>
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
                            <?php if (\App\Core\RoleHelper::isManager()): ?>
                                <select name="designer_id" class="form-select" required>
                                    <option value="">اختر المسؤول...</option>
                                    <?php foreach ($employees_array as $d_row): ?>
                                        <?php if ($d_row['role'] === 'مصمم' || $d_row['role'] === 'مدير'): ?>
                                            <option value="<?= $d_row['employee_id'] ?>" <?= ($designer_id == $d_row['employee_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d_row['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif (\App\Core\RoleHelper::getCurrentUserRole() === 'مصمم'): ?>
                                <p class="form-control-plaintext bg-light border rounded-pill px-3"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                                <input type="hidden" name="designer_id" value="<?= $_SESSION['user_id'] ?>">
                            <?php else: ?>
                                <select name="designer_id" class="form-select" required>
                                    <option value="">اختر المسؤول...</option>
                                    <?php foreach ($employees_array as $d_row): ?>
                                        <?php if ($d_row['role'] === 'مصمم' || $d_row['role'] === 'مدير'): ?>
                                            <option value="<?= $d_row['employee_id'] ?>" <?= ($designer_id == $d_row['employee_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d_row['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
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
    // Client Autocomplete Logic - محسن للبحث الديناميكي
    const companyInput = document.getElementById('company_name_input');
    const phoneInput = document.getElementById('phone_input');
    const clientIdHidden = document.getElementById('client_id_hidden');
    const autocompleteList = document.getElementById('autocomplete-list');
    const phoneLock = document.getElementById('phone_lock');
    
    let searchTimeout;

    function performSearch() {
        const query = companyInput.value.trim();
        
        // إعادة تعيين القيم عند تغيير البحث
        if (!clientIdHidden.value || companyInput.value !== companyInput.dataset.selectedValue) {
            clientIdHidden.value = '';
            phoneInput.readOnly = false;
            companyInput.dataset.selectedValue = '';
        }

        if (query.length < 1) {
            autocompleteList.innerHTML = '';
            return;
        }

        // إظهار مؤشر التحميل
        autocompleteList.innerHTML = '<span class="list-group-item text-muted"><i class="fas fa-spinner fa-spin me-2"></i>جاري البحث...</span>';

        fetch(`/new_injaz/direct_search.php?query=${encodeURIComponent(query)}`)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Expected JSON, got:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                
                return response.json();
            })
            .then(clients => {
                console.log('Received clients:', clients);
                autocompleteList.innerHTML = '';
                
                if (clients.error) {
                    autocompleteList.innerHTML = `
                        <div class="list-group-item text-danger text-center py-3">
                            <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">خطأ: ${clients.message}</p>
                        </div>
                    `;
                    return;
                }
                
                if (clients.length > 0) {
                    clients.forEach((client, index) => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.classList.add('list-group-item', 'list-group-item-action', 'd-flex', 'justify-content-between', 'align-items-start');
                        item.style.transition = 'all 0.3s ease';
                        
                        // تحسين عرض النتائج
                        const mainDiv = document.createElement('div');
                        mainDiv.classList.add('flex-grow-1');
                        
                        const companyName = document.createElement('h6');
                        companyName.classList.add('mb-1', 'fw-bold', 'text-dark');
                        companyName.textContent = client.company_name;
                        mainDiv.appendChild(companyName);
                        
                        const phoneDiv = document.createElement('small');
                        phoneDiv.classList.add('text-success', 'd-flex', 'align-items-center');
                        phoneDiv.innerHTML = `<i class="fas fa-phone me-1"></i>${client.phone}`;
                        
                        // إضافة مؤشر بصري للاختيار
                        const selectIcon = document.createElement('i');
                        selectIcon.classList.add('fas', 'fa-chevron-left', 'text-muted', 'ms-2');
                        
                        item.appendChild(mainDiv);
                        item.appendChild(phoneDiv);
                        item.appendChild(selectIcon);
                        
                        // تأثير hover
                        item.addEventListener('mouseenter', function() {
                            this.style.backgroundColor = '#f8f9fa';
                            selectIcon.style.color = '#007bff';
                        });
                        
                        item.addEventListener('mouseleave', function() {
                            this.style.backgroundColor = '';
                            selectIcon.style.color = '';
                        });
                        
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // تأثير الاختيار
                            this.style.backgroundColor = '#d4edda';
                            
                            // ملء البيانات تلقائياً
                            companyInput.value = client.company_name;
                            companyInput.dataset.selectedValue = client.company_name;
                            phoneInput.value = client.phone;
                            clientIdHidden.value = client.client_id;
                            
                            // منع التعديل على البيانات المحددة مسبقاً
                            phoneInput.readOnly = true;
                            
                            // إضافة مؤشر بصري للحقول المقفلة
                            phoneInput.classList.add('bg-light');
                            phoneLock.style.display = 'block';
                            
                            autocompleteList.innerHTML = '';
                            
                            // عرض رسالة تأكيد
                            showClientSelectedMessage(client.company_name);
                        });
                        
                        autocompleteList.appendChild(item);
                    });
                    
                    // إضافة خيار إنشاء عميل جديد
                    const newClientItem = document.createElement('a');
                    newClientItem.href = '#';
                    newClientItem.classList.add('list-group-item', 'list-group-item-action', 'text-primary', 'fw-bold', 'border-top');
                    newClientItem.style.backgroundColor = '#f8f9fa';
                    newClientItem.innerHTML = '<i class="fas fa-plus me-2"></i>إنشاء جهة جديدة بهذا الاسم';
                    newClientItem.addEventListener('click', function(e) {
                        e.preventDefault();
                        clearClientSelection();
                        autocompleteList.innerHTML = '';
                    });
                    autocompleteList.appendChild(newClientItem);
                    
                } else {
                    autocompleteList.innerHTML = `
                        <div class="list-group-item text-center py-3">
                            <i class="fas fa-info-circle text-primary mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-1 text-muted">لا توجد جهات مطابقة</p>
                            <small class="text-muted">سيتم إنشاء جهة جديدة باسم: <strong>${query}</strong></small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('خطأ في البحث:', error);
                autocompleteList.innerHTML = `
                    <div class="list-group-item text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-0">حدث خطأ في البحث</p>
                        <small>الرجاء المحاولة مرة أخرى</small>
                    </div>
                `;
            });
    }

    function clearClientSelection() {
        clientIdHidden.value = '';
        phoneInput.readOnly = false;
        phoneInput.classList.remove('bg-light');
        phoneLock.style.display = 'none';
        companyInput.dataset.selectedValue = '';
        hideClientSelectedMessage();
        
        // إضافة تأثير بصري لإعادة التفعيل
        phoneInput.style.transition = 'all 0.3s ease';
        phoneInput.style.borderColor = '#007bff';
        setTimeout(() => {
            phoneInput.style.borderColor = '';
        }, 1000);
    }

    function showClientSelectedMessage(companyName) {
        // إزالة الرسالة السابقة إن وجدت
        hideClientSelectedMessage();
        
        const messageDiv = document.createElement('div');
        messageDiv.id = 'client-selected-message';
        messageDiv.classList.add('alert', 'alert-success', 'alert-dismissible', 'fade', 'show', 'mt-2', 'border-0');
        messageDiv.style.borderLeft = '4px solid #28a745';
        messageDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2 text-success"></i>
                <div class="flex-grow-1">
                    <strong>تم اختيار الجهة:</strong> ${companyName}
                    <br><small class="text-muted">البيانات محفوظة ومحمية من التعديل</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearClientSelection()">
                    <i class="fas fa-edit me-1"></i>تعديل البيانات
                </button>
            </div>
        `;
        
        companyInput.parentNode.appendChild(messageDiv);
        
        // تأثير ظهور مع تأخير
        setTimeout(() => {
            messageDiv.classList.add('show');
        }, 100);
    }

    function hideClientSelectedMessage() {
        const messageDiv = document.getElementById('client-selected-message');
        if (messageDiv) {
            messageDiv.remove();
        }
    }

    // جعل clearClientSelection متاحة عالمياً
    window.clearClientSelection = clearClientSelection;

    // البحث عند الكتابة مع تأخير
    companyInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300); // تأخير 300ms
    });

    // البحث الفوري عند التركيز إذا كان هناك نص
    companyInput.addEventListener('focus', function() {
        if (this.value.length > 0 && !clientIdHidden.value) {
            performSearch();
        }
    });

    // إخفاء القائمة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!companyInput.contains(e.target) && !autocompleteList.contains(e.target)) {
            autocompleteList.innerHTML = '';
        }
    });

    // تنظيف البيانات عند تعديل اسم الجهة يدوياً
    companyInput.addEventListener('input', function() {
        if (this.value !== this.dataset.selectedValue && clientIdHidden.value) {
            clearClientSelection();
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
