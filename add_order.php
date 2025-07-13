<?php
include 'header.php';
include 'db_connection.php';

check_permission('order_add');

// جلب المنتجات
$products_res = $conn->query("SELECT product_id, name FROM products ORDER BY name");
$products_options = '';
while($p_row = $products_res->fetch_assoc()) {
    $products_options .= "<option value='{$p_row['product_id']}'>" . htmlspecialchars($p_row['name']) . "</option>";
}
// جلب المصممين فقط
$designers_res = $conn->query("SELECT employee_id, name FROM employees WHERE role='مصمم'");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        // 1. معالجة العميل (جديد أو حالي)
        $client_id = $_POST['client_id'];
        // إذا كان client_id فارغاً، فهذا عميل جديد
        if (empty($client_id)) {
            $company_name = $_POST['company_name'];
            $contact_person = $_POST['contact_person'];
            $phone = $_POST['phone'];

            if (empty($company_name)) {
                throw new Exception("اسم المؤسسة مطلوب للعميل الجديد.");
            }
            $stmt_new_client = $conn->prepare("INSERT INTO clients (company_name, contact_person, phone) VALUES (?, ?, ?)");
            $stmt_new_client->bind_param("sss", $company_name, $contact_person, $phone);
            $stmt_new_client->execute();
            $client_id = $conn->insert_id; // الحصول على ID العميل الجديد
        }

        // 2. إدراج الطلب الرئيسي
        $total_amount = $_POST['total_amount'];
        $deposit_amount = $_POST['deposit_amount'];
        $remaining_amount = $total_amount - $deposit_amount;
        $created_by = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session not set
        $designer_id = !empty($_POST['designer_id']) ? $_POST['designer_id'] : null;

        $stmt_order = $conn->prepare("INSERT INTO orders (client_id, designer_id, total_amount, deposit_amount, remaining_amount, payment_method, due_date, status, priority, notes, created_by, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'جديد', ?, ?, ?, NOW())");
        $stmt_order->bind_param("iidddssssi", $client_id, $designer_id, $total_amount, $deposit_amount, $remaining_amount, $_POST['payment_method'], $_POST['due_date'], $_POST['priority'], $_POST['notes'], $created_by);
        $stmt_order->execute();
        $order_id = $conn->insert_id;

        // 3. إدراج بنود الطلب
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
        if (!empty($_POST['products']) && is_array($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                if (empty($product['product_id'])) {
                    throw new Exception("الرجاء اختيار منتج صالح لجميع البنود المضافة.");
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
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ: ' . $e->getMessage()];
        header("Location: add_order.php");
        exit;
    }
}
?>
<div class="container">
    <h2 style="color:#D44759;" class="mb-4">إضافة طلب جديد</h2>
    <form method="POST" action="add_order.php" id="order-form">
        <div class="row g-3">
            <!-- Client Info Section -->
            <fieldset class="border p-3 rounded mb-3">
                <legend class="float-none w-auto px-2 h6">معلومات العميل</legend>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">اسم المؤسسة</label>
                        <div class="position-relative">
                            <input type="text" name="company_name" id="company_name_input" class="form-control" autocomplete="off" required>
                            <input type="hidden" name="client_id" id="client_id_hidden">
                            <div id="autocomplete-list" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الشخص المسؤول</label>
                        <input type="text" name="contact_person" id="contact_person_input" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجوال</label>
                        <input type="text" name="phone" id="phone_input" class="form-control">
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
                    <div class="col-md-3">
                        <label class="form-label">تاريخ التسليم</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select">
                            <option value="عاجل جداً">عاجل جداً</option>
                            <option value="عالي">عالي</option>
                            <option value="متوسط" selected>متوسط</option>
                            <option value="منخفض">منخفض</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المبلغ الإجمالي (شامل الضريبة)</label>
                        <input type="number" name="total_amount" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الدفعة المقدمة</label>
                        <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            <option value="نقدي">نقدي</option>
                            <option value="تحويل بنكي">تحويل بنكي</option>
                            <option value="فوري">فوري</option>
                            <option value="غيره">غيره</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسؤول عن التصميم</label>
                        <select name="designer_id" class="form-select">
                            <option value="">غير محدد</option>
                            <?php mysqli_data_seek($designers_res, 0); while($d_row = $designers_res->fetch_assoc()): ?>
                                <option value="<?= $d_row['employee_id'] ?>"><?= htmlspecialchars($d_row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات عامة على الطلب</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
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
    const productsOptions = `<?php
        mysqli_data_seek($products_res, 0); // Reset pointer
        $options = "<option value=\\\"\\\">اختر المنتج...</option>";
        while($p_row = $products_res->fetch_assoc()) {
            $options .= "<option value=\\\"{$p_row['product_id']}\\\">" . htmlspecialchars($p_row['name']) . "</option>";
        }
        echo addslashes($options);
    ?>`;

    function addOrderItem(item = null) {
        const itemQty = item ? item.quantity : 1;
        const itemNotes = item ? item.item_notes : '';
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
        productSelects.forEach(select => {
            if (select.value === '') {
                allProductsSelected = false;
            }
        });

        if (!allProductsSelected) {
            e.preventDefault(); // Stop form submission
            alert('الرجاء اختيار منتج لجميع البنود المضافة في الطلب.');
        }
    });

    // Add one item by default when the page loads
    addOrderItem();
});
</script>
<?php include 'footer.php'; ?>
