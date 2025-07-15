<?php
$page_title = 'إضافة طلب جديد';
include 'db_connection.php';
include 'header.php';

check_permission('order_add', $conn);

// جلب المنتجات
$products_res = $conn->query("SELECT product_id, name FROM products ORDER BY name");
$products_array = $products_res->fetch_all(MYSQLI_ASSOC);
// جلب المصممين فقط
$designers_res = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY role, name");

$error = '';
$post_data = []; // To hold submitted data on error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_data = $_POST; // Store submitted data
    $conn->begin_transaction();
    try {
        // 1. معالجة العميل (جديد أو حالي)
        $client_id = $_POST['client_id'];
        // إذا كان client_id فارغاً، فهذا عميل جديد
        if (empty($client_id)) {
            $company_name = $_POST['company_name'];
            $contact_person = $_POST['contact_person'];
            $phone = $_POST['phone'];

            if (empty($company_name) || empty($phone)) {
                throw new Exception("اسم المؤسسة ورقم الجوال حقلان إجباريان للعميل الجديد.");
            }
            // التحقق من صحة رقم الجوال السعودي
            if (!preg_match('/^05[0-9]{8}$/', $phone)) {
                throw new Exception("الرجاء إدخال رقم جوال سعودي صحيح للعميل الجديد (10 أرقام تبدأ بـ 05).");
            }
            $stmt_new_client = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone) VALUES (?, ?, ?)");
            $stmt_new_client->bind_param("sss", $company_name, $contact_person, $phone);
            $stmt_new_client->execute();
            $client_id = $conn->insert_id; // الحصول على ID العميل الجديد
        }

        // 2. إدراج الطلب الرئيسي
        $total_amount = floatval($_POST['total_amount']);
        // التحقق من أن المبلغ الإجمالي هو رقم صالح
        if (!isset($_POST['total_amount']) || !is_numeric($_POST['total_amount']) || $total_amount < 0) {
            throw new Exception("المبلغ الإجمالي حقل إجباري ويجب أن يكون رقماً موجباً.");
        }
        $deposit_amount = floatval($_POST['deposit_amount']);

        // **إصلاح منطقي:** إذا كان المبلغ الإجمالي صفراً، يجب أن تكون الدفعة المقدمة صفراً أيضاً
        if ($total_amount <= 0) {
            $deposit_amount = 0;
        }

        $remaining_amount = $total_amount - $deposit_amount;
        $created_by = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session not set
        
        // أتمتة حالة الدفع حسب المنطق الجديد
        if ($deposit_amount >= $total_amount && $total_amount > 0) {
            $payment_status = 'مدفوع';
        } elseif ($deposit_amount > 0 && $deposit_amount < $total_amount) {
            $payment_status = 'مدفوع جزئياً';
        } else { // يشمل حالة المبلغ الإجمالي صفر أو الدفعة صفر
            $payment_status = 'غير مدفوع';
        }

        $designer_id = $_POST['designer_id'];
        if (empty($designer_id) || !filter_var($designer_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new Exception("الرجاء اختيار المسؤول عن التصميم. الحقل إجباري.");
        }

        $stmt_order = $conn->prepare("INSERT INTO orders (client_id, designer_id, total_amount, deposit_amount, remaining_amount, payment_status, payment_method, due_date, status, priority, notes, created_by, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'قيد التصميم', ?, ?, ?, NOW())");
        $stmt_order->bind_param("iidddsssssi", $client_id, $designer_id, $total_amount, $deposit_amount, $remaining_amount, $payment_status, $_POST['payment_method'], $_POST['due_date'], $_POST['priority'], $_POST['notes'], $created_by);
        $stmt_order->execute();
        $order_id = $conn->insert_id;

        // 3. إدراج بنود الطلب
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
        if (!empty($_POST['products']) && is_array($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                // التحقق من أن معرّف المنتج هو رقم صحيح أكبر من صفر
                if (!isset($product['product_id']) || !filter_var($product['product_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                    throw new Exception("الرجاء اختيار منتج صالح لجميع البنود المضافة. قد يكون أحد المنتجات غير محدد.");
                }
                // التحقق من أن الكمية هي رقم صحيح أكبر من صفر
                if (!isset($product['quantity']) || !filter_var($product['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                    throw new Exception("الرجاء إدخال كمية صحيحة (رقم أكبر من صفر) لجميع البنود.");
                }
                $stmt_item->bind_param("iiss", $order_id, $product['product_id'], $product['quantity'], $product['item_notes']);
                $stmt_item->execute();
            }
        } else {
            throw new Exception("يجب إضافة منتج واحد على الأقل للطلب.");
        }

        $conn->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم حفظ الطلب بنجاح! رقم الطلب: ' . $order_id];
        header("Location: orders.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // Instead of redirecting, set an error message to display on the same page.
        $error = $e->getMessage();
        // The $post_data is already set, so the form will be repopulated.
    }
}
?>
<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="add_order.php" id="order-form">
        <div class="row g-3">
            <!-- Client Info Section -->
            <fieldset class="border p-3 rounded mb-3">
                <legend class="float-none w-auto px-2 h6">معلومات العميل</legend>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">اسم المؤسسة</label>
                        <div class="position-relative">
                            <input type="text" name="company_name" id="company_name_input" class="form-control" autocomplete="off" required value="<?= htmlspecialchars($post_data['company_name'] ?? '') ?>">
                            <input type="hidden" name="client_id" id="client_id_hidden" value="<?= htmlspecialchars($post_data['client_id'] ?? '') ?>">
                            <div id="autocomplete-list" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الشخص المسؤول</label>
                        <input type="text" name="contact_person" id="contact_person_input" class="form-control" value="<?= htmlspecialchars($post_data['contact_person'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجوال</label>
                        <input type="text" name="phone" id="phone_input" class="form-control" required value="<?= htmlspecialchars($post_data['phone'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>

            <!-- Order Items Section -->
            <fieldset class="border p-3 rounded mb-3">
                <legend class="float-none w-auto px-2 h6">بنود الطلب</legend>
                <div id="order-items-container">
                    <!-- Product rows will be inserted here by JavaScript -->
                </div>
                <button type="button" id="add-item-btn" class="btn btn-outline-success mt-2">إضافة منتج آخر +</button>
            </fieldset>

            <!-- Payment and Details Section -->
            <fieldset class="border p-3 rounded">
                <legend class="float-none w-auto px-2 h6">التفاصيل المالية والإدارية</legend>
                <div class="row g-3">
                    <!-- Row 1 -->
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التسليم</label>
                        <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($post_data['due_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select">
                            <option value="عاجل جداً" <?= ($post_data['priority'] ?? '') == 'عاجل جداً' ? 'selected' : '' ?>>عاجل جداً</option>
                            <option value="عالي" <?= ($post_data['priority'] ?? '') == 'عالي' ? 'selected' : '' ?>>عالي</option>
                            <option value="متوسط" <?= !isset($post_data['priority']) || ($post_data['priority'] == 'متوسط') ? 'selected' : '' ?>>متوسط</option>
                            <option value="منخفض" <?= ($post_data['priority'] ?? '') == 'منخفض' ? 'selected' : '' ?>>منخفض</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسؤول عن التصميم</label>
                        <?php if ($_SESSION['user_role'] === 'مدير'): ?>
                            <select name="designer_id" class="form-select" required>
                                <option value="">اختر المسؤول...</option>
                                <?php mysqli_data_seek($designers_res, 0); while($d_row = $designers_res->fetch_assoc()): ?>
                                    <option value="<?= $d_row['employee_id'] ?>" <?= ($post_data['designer_id'] ?? 0) == $d_row['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d_row['name']) ?> (<?= htmlspecialchars($d_row['role']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        <?php else: ?>
                            <p class="form-control-plaintext bg-light border rounded-pill px-3"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                            <input type="hidden" name="designer_id" value="<?= $_SESSION['user_id'] ?>">
                        <?php endif; ?>
                    </div>

                    <!-- Row 2 -->
                    <div class="col-md-4">
                        <label class="form-label">المبلغ الإجمالي (شامل الضريبة)</label>
                        <input type="number" name="total_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($post_data['total_amount'] ?? '0') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الدفعة المقدمة</label>
                        <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($post_data['deposit_amount'] ?? '0') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="نقدي" <?= ($post_data['payment_method'] ?? '') == 'نقدي' ? 'selected' : '' ?>>نقدي</option>
                            <option value="تحويل بنكي" <?= ($post_data['payment_method'] ?? '') == 'تحويل بنكي' ? 'selected' : '' ?>>تحويل بنكي</option>
                            <option value="فوري" <?= ($post_data['payment_method'] ?? '') == 'فوري' ? 'selected' : '' ?>>فوري</option>
                            <option value="غيره" <?= ($post_data['payment_method'] ?? '') == 'غيره' ? 'selected' : '' ?>>غيره</option>
                        </select>
                    </div>

                    <!-- Row 3 -->
                    <div class="col-12">
                        <label class="form-label">ملاحظات عامة على الطلب</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($post_data['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </fieldset>

            <div class="col-12 text-center mt-4">
                <button class="btn btn-lg px-5 text-white" type="submit" style="background-color:#D44759;">حفظ الطلب</button>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client Autocomplete Logic
    const companyInput = document.getElementById('company_name_input');
    const contactPersonInput = document.getElementById('contact_person_input');
    const phoneInput = document.getElementById('phone_input');
    const clientIdHidden = document.getElementById('client_id_hidden');
    const autocompleteList = document.getElementById('autocomplete-list');

    companyInput.addEventListener('keyup', function() {
        const query = this.value;
        clientIdHidden.value = ''; 
        if (query.length < 2) {
            autocompleteList.innerHTML = '';
            return;
        }
        fetch(`ajax_search_client.php?query=${query}`)
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
                            contactPersonInput.value = client.contact_person;
                            phoneInput.value = client.phone;
                            clientIdHidden.value = client.client_id;
                            autocompleteList.innerHTML = '';
                        });
                        autocompleteList.appendChild(item);
                    });
                } else {
                    autocompleteList.innerHTML = '<span class="list-group-item text-muted">لا توجد نتائج (سيتم إنشاء عميل جديد)</span>';
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

    // Repopulate items if form was submitted with errors
    const existingItems = <?= json_encode($post_data['products'] ?? []) ?> || [];
    const products = <?= json_encode($products_array) ?> || [];
    let productsOptions = '<option value="">اختر المنتج...</option>';

    if (products.length > 0) {
        products.forEach(p => {
            const tempOption = document.createElement('option');
            tempOption.value = p.product_id;
            tempOption.textContent = p.name;
            productsOptions += tempOption.outerHTML;
        });
    }

    function addOrderItem(item = null) {
        const itemQty = item ? item.quantity : 1;
        const itemNotes = item ? item.item_notes : '';
        const productId = item ? item.product_id : '';

        const itemHtml = `
            <div class="order-item-row row g-3 mb-3 border-bottom pb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">المنتج</label>
                    <select name="products[${itemCounter}][product_id]" class="form-select product-select" required>
                        ${productsOptions}
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">الكمية</label><input type="number" name="products[${itemCounter}][quantity]" class="form-control" min="1" value="${itemQty}" required></div>
                <div class="col-md-5"><label class="form-label">تفاصيل الطلب</label><textarea name="products[${itemCounter}][item_notes]" class="form-control" rows="1">${itemNotes}</textarea></div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger w-100 remove-item-btn" title="حذف المنتج">X</button>
                </div>
            </div>
        `;
        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);

        // Set the selected product for existing/repopulated items
        if (productId) {
            const newSelect = itemsContainer.querySelector(`select[name="products[${itemCounter}][product_id]"]`);
            if(newSelect) newSelect.value = productId;
        }

        itemCounter++;
    }

    addItemBtn.addEventListener('click', () => addOrderItem());

    itemsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-item-btn')) {
            e.target.closest('.order-item-row').remove();
        }
    });

    // Client-side validation before submitting the form
    const orderForm = document.getElementById('order-form');
    orderForm.addEventListener('submit', function(e) {
        const productSelects = itemsContainer.querySelectorAll('.product-select');
        let allProductsSelected = true;
        
        // Remove previous error states
        itemsContainer.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        productSelects.forEach(select => {
            if (select.value === '') {
                allProductsSelected = false;
                select.classList.add('is-invalid'); // Highlight the invalid select
            }
        });

        if (!allProductsSelected) {
            e.preventDefault(); // Stop form submission
            alert('الرجاء اختيار منتج لجميع البنود المضافة في الطلب.');
        }
    });

    // Load existing items from POST data on error, or add one default item
    if (existingItems.length > 0) {
        existingItems.forEach(item => addOrderItem(item));
    } else {
        addOrderItem();
    }
});
</script>
<?php include 'footer.php'; ?>
