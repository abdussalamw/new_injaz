<?php
// --- AJAX Handler ---
// يجب أن يكون هذا الجزء في بداية الملف لضمان عدم إرسال أي مخرجات أخرى
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // تعيين رأس المحتوى إلى JSON
    header('Content-Type: application/json');

    // تضمين الملفات الضرورية فقط
    require_once 'db_connection_secure.php';
    session_start();
    require_once 'permissions.php';

    $response = [];
    $id = intval($_GET['id'] ?? 0);

    // التحقق من الصلاحيات
    if (
        !isset($_SESSION['user_id']) || 
        (!has_permission('order_view_all', $conn) && 
         !has_permission('order_view_own', $conn) && 
         !has_permission('order_financial_settle', $conn))
    ) {
        http_response_code(403);
        $response['error'] = 'غير مصرح لك بالوصول لهذه البيانات.';
        echo json_encode($response);
        exit;
    }

    if ($id > 0) {
        $stmt = $conn->prepare("SELECT total_amount, deposit_amount, payment_status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $response = $row;
        } else {
            http_response_code(404);
            $response['error'] = 'الطلب غير موجود.';
        }
    } else {
        http_response_code(400);
        $response['error'] = 'معرف الطلب غير صحيح.';
    }

    echo json_encode($response);
    exit;
}

// --- Regular Page Load ---
$id = intval($_GET['id'] ?? 0);
$page_title = "تعديل الطلب #" . $id;
include 'db_connection_secure.php';
include 'header.php';

check_permission('order_edit', $conn);

$stmt = $conn->prepare("SELECT o.*, c.company_name, c.contact_person, c.phone, e.name as designer_name
                        FROM orders o 
                        JOIN clients c ON o.client_id = c.client_id 
                        LEFT JOIN employees e ON o.designer_id = e.employee_id
                        WHERE o.order_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo "<div class='alert alert-danger'>الطلب غير موجود</div>";
    include 'footer.php'; exit;
}

// --- تحديد قابلية التعديل ---
$status = trim($row['status']);
$user_role = $_SESSION['user_role'] ?? 'guest';
$is_order_editable = false;

// أي شخص يمكنه التعديل إذا كان الطلب "قيد التصميم"
if ($status === 'قيد التصميم') {
    $is_order_editable = true;
} 
// المدير يمكنه التعديل إلا إذا كان الطلب "مكتمل" أو "ملغي"
elseif ($user_role === 'مدير' && !in_array($status, ['مكتمل', 'ملغي'])) {
    $is_order_editable = true;
}

// جلب بنود الطلب
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// جلب المنتجات والمصممين للقوائم المنسدلة
$products_res = $conn->query("SELECT product_id, name FROM products ORDER BY name");
$designers_res = $conn->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'مدير') ORDER BY role, name");
$products_array = $products_res->fetch_all(MYSQLI_ASSOC);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_data = $_POST; // Store submitted data to repopulate form on error

    // *** التحقق من جهة الخادم: لا يمكن تعديل الطلب إلا إذا كان في مرحلة تسمح بذلك ***
    if (!$is_order_editable) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'لا يمكن تعديل هذا الطلب في حالته الحالية.'];
        header("Location: edit_order.php?id=" . $id);
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. تحديث الطلب الرئيسي
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

        // بناء الاستعلام بشكل ديناميكي
        $sql = "UPDATE orders SET total_amount=?, deposit_amount=?, remaining_amount=?, payment_status=?, payment_method=?, due_date=?, priority=?, notes=?";
        $types = "dddsssss";
        $params = [$total_amount, $deposit_amount, $remaining_amount, $payment_status, $_POST['payment_method'], $_POST['due_date'], $_POST['priority'], $_POST['notes']];

        // فقط المدير يستطيع تغيير المصمم المسؤول
        if ($_SESSION['user_role'] === 'مدير') {
            $sql .= ", designer_id=?";
            $types .= "i";
            $params[] = $designer_id;
        }
        $sql .= " WHERE order_id=?";
        $types .= "i";
        $params[] = $id;
        $stmt_order = $conn->prepare($sql);
        $stmt_order->bind_param($types, ...$params);
        $stmt_order->execute();

        // 2. حذف البنود القديمة
        $stmt_delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();

        // 3. إدراج البنود الجديدة
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
                $stmt_item->bind_param("iiss", $id, $product['product_id'], $product['quantity'], $product['item_notes']);
                $stmt_item->execute();
            }
        } else {
            throw new Exception("يجب وجود منتج واحد على الأقل في الطلب.");
        }

        $conn->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل الطلب بنجاح!'];
        header("Location: orders.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // On error, set the error message and let the page render again with the submitted data.
        $error = $e->getMessage();
        // Repopulate main order data and items from the submitted POST data
        $row = array_merge($row, $post_data);
        $order_items = $post_data['products'] ?? [];
    }
}
?>
<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" id="order-form">
        <div class="row g-3">
            <!-- Client Info Section (Read-only) -->
            <fieldset class="border p-3 rounded mb-3">
                <legend class="float-none w-auto px-2 h6">معلومات العميل</legend>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">اسم المؤسسة</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($row['company_name']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الشخص المسؤول</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($row['contact_person']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجوال</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($row['phone']) ?></p>
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
                        <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($row['due_date'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select">
                            <option value="عاجل جداً" <?= ($row['priority'] ?? '') == 'عاجل جداً' ? 'selected' : '' ?>>عاجل جداً</option>
                            <option value="عالي" <?= ($row['priority'] ?? '') == 'عالي' ? 'selected' : '' ?>>عالي</option>
                            <option value="متوسط" <?= ($row['priority'] ?? '') == 'متوسط' ? 'selected' : '' ?>>متوسط</option>
                            <option value="منخفض" <?= ($row['priority'] ?? '') == 'منخفض' ? 'selected' : '' ?>>منخفض</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسؤول عن التصميم</label>
                        <?php if ($_SESSION['user_role'] === 'مدير'): ?>
                            <select name="designer_id" class="form-select" required>
                                <option value="">اختر المسؤول...</option>
                                <?php mysqli_data_seek($designers_res, 0); while($d_row = $designers_res->fetch_assoc()): ?>
                                    <option value="<?= $d_row['employee_id'] ?>" <?= ($row['designer_id'] ?? 0) == $d_row['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d_row['name']) ?> (<?= htmlspecialchars($d_row['role']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        <?php else: ?>
                            <p class="form-control-plaintext bg-light border rounded-pill px-3"><?= htmlspecialchars($row['designer_name'] ?? 'غير محدد') ?></p>
                            <input type="hidden" name="designer_id" value="<?= htmlspecialchars($row['designer_id']) ?>">
                        <?php endif; ?>
                    </div>

                    <!-- Row 2 -->
                    <div class="col-md-4">
                        <label class="form-label">المبلغ الإجمالي (شامل الضريبة)</label>
                        <input type="number" name="total_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($row['total_amount'] ?? '0') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الدفعة المقدمة</label>
                        <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($row['deposit_amount'] ?? '0') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="نقدي" <?= ($row['payment_method'] ?? '') == 'نقدي' ? 'selected' : '' ?>>نقدي</option>
                            <option value="تحويل بنكي" <?= ($row['payment_method'] ?? '') == 'تحويل بنكي' ? 'selected' : '' ?>>تحويل بنكي</option>
                            <option value="فوري" <?= ($row['payment_method'] ?? '') == 'فوري' ? 'selected' : '' ?>>فوري</option>
                            <option value="غيره" <?= ($row['payment_method'] ?? '') == 'غيره' ? 'selected' : '' ?>>غيره</option>
                        </select>
                    </div>

                    <!-- Row 3 -->
                    <div class="col-12">
                        <label class="form-label">ملاحظات عامة على الطلب</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($row['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </fieldset>

            <div class="col-12 text-center mt-4">
                <button class="btn px-5 text-white" type="submit" style="background-color:#D44759;">حفظ التعديلات</button>
                <a href="orders.php" class="btn btn-secondary ms-2">عودة للقائمة</a>
            </div>
        </div>
    </form>
</div>
<script>
// تعطيل الفورم إذا لم يعد الطلب قابلاً للتعديل بناءً على حالته ودور المستخدم
const isEditable = <?= $is_order_editable ? 'true' : 'false' ?>;
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('order-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    let itemCounter = 0;
    <?php
    // Sanitize item notes for safe injection into JavaScript/HTML
    $safe_order_items = array_map(function($item) {
        $item['item_notes'] = htmlspecialchars($item['item_notes'] ?? '', ENT_QUOTES, 'UTF-8');
        return $item;
    }, $order_items);
    ?>
    const existingItems = <?= json_encode($safe_order_items) ?> || [];
    const products = <?= json_encode($products_array) ?> || [];
    let productsOptions = '<option value="">اختر المنتج...</option>';

    if (products.length > 0) {
        products.forEach(p => {
            // ننشئ عنصر option لضمان التعامل مع الأسماء التي تحتوي على رموز خاصة بشكل آمن
            const tempOption = document.createElement('option');
            tempOption.value = p.product_id;
            tempOption.textContent = p.name; // يضبط النص بشكل آمن
            productsOptions += tempOption.outerHTML;
        });
    }

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
                    <button type="button" class="btn btn-danger w-100 remove-item-btn">X</button>
                </div>
            </div>
        `;
        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
        
        // Set the selected product for existing items
        if (item && item.product_id) {
            const newSelect = itemsContainer.querySelector(`select[name="products[${itemCounter}][product_id]"]`);
            newSelect.value = item.product_id;
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
        // Remove previous error states
        itemsContainer.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        const productSelects = itemsContainer.querySelectorAll('.product-select');
        let allProductsSelected = true;
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

    // Load existing items
    if (existingItems.length > 0) {
        existingItems.forEach(item => addOrderItem(item));
    } else {
        // Add one empty item if there are no existing items
        addOrderItem();
    }

    // بعد تحميل كل العناصر، يتم تطبيق حالة التعديل
    if (!isEditable) {
        // إضافة رسالة تحذير للمستخدم
        const warningDiv = document.createElement('div');
        warningDiv.className = 'alert alert-warning';
        warningDiv.textContent = 'هذا الطلب في مرحلة متقدمة ولا يمكن تعديله من هذه الشاشة.';
        orderForm.parentNode.insertBefore(warningDiv, orderForm);

        // تعطيل جميع حقول الإدخال
        orderForm.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.type !== 'hidden') el.disabled = true;
        });

        // إخفاء أزرار الإجراءات وتعطيل زر الحفظ
        document.getElementById('add-item-btn').style.display = 'none';
        orderForm.querySelectorAll('.remove-item-btn').forEach(btn => btn.style.display = 'none');
        const submitButton = orderForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;
    }
});
</script>
<?php include 'footer.php'; ?>
