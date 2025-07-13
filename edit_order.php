<?php
include 'header.php';
include 'db_connection.php';

$id = intval($_GET['id'] ?? 0);

check_permission('order_edit');

$stmt = $conn->prepare("SELECT o.*, c.company_name, c.contact_person, c.phone 
                        FROM orders o 
                        JOIN clients c ON o.client_id = c.client_id 
                        WHERE o.order_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo "<div class='alert alert-danger'>الطلب غير موجود</div>";
    include 'footer.php'; exit;
}

// جلب بنود الطلب
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// جلب المنتجات والمصممين للقوائم المنسدلة
$products_res = $conn->query("SELECT product_id, name FROM products ORDER BY name");
$designers_res = $conn->query("SELECT employee_id, name FROM employees WHERE role='مصمم'");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        // 1. تحديث الطلب الرئيسي
        $total_amount = $_POST['total_amount'];
        $deposit_amount = $_POST['deposit_amount'];
        $remaining_amount = $total_amount - $deposit_amount;
        $designer_id = !empty($_POST['designer_id']) ? $_POST['designer_id'] : null;

        $stmt_order = $conn->prepare("UPDATE orders SET designer_id=?, total_amount=?, deposit_amount=?, remaining_amount=?, payment_method=?, due_date=?, priority=?, notes=? WHERE order_id=?");
        $stmt_order->bind_param("idddssssi", $designer_id, $total_amount, $deposit_amount, $remaining_amount, $_POST['payment_method'], $_POST['due_date'], $_POST['priority'], $_POST['notes'], $id);
        $stmt_order->execute();

        // 2. حذف البنود القديمة
        $stmt_delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();

        // 3. إدراج البنود الجديدة
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, item_notes) VALUES (?, ?, ?, ?)");
        if (!empty($_POST['products']) && is_array($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                if (empty($product['product_id'])) {
                    throw new Exception("الرجاء اختيار منتج صالح لجميع البنود المضافة.");
                }
                $stmt_item->bind_param("iiss", $id, $product['product_id'], $product['quantity'], $product['item_notes']);
                $stmt_item->execute();
            }
        } else {
            throw new Exception("يجب وجود منتج واحد على الأقل في الطلب.");
        }

        $conn->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم تعديل الطلب بنجاح!'];
        header("Location: edit_order.php?id=" . $id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'حدث خطأ: ' . $e->getMessage()];
        header("Location: edit_order.php?id=" . $id);
        exit;
    }
}
?>
<div class="container">
    <h2 style="color:#D44759;" class="mb-4">تعديل طلب</h2>
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
                    <div class="col-md-3">
                        <label class="form-label">تاريخ التسليم</label>
                        <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($row['due_date']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select">
                            <option value="عاجل جداً" <?= $row['priority'] == 'عاجل جداً' ? 'selected' : '' ?>>عاجل جداً</option>
                            <option value="عالي" <?= $row['priority'] == 'عالي' ? 'selected' : '' ?>>عالي</option>
                            <option value="متوسط" <?= $row['priority'] == 'متوسط' ? 'selected' : '' ?>>متوسط</option>
                            <option value="منخفض" <?= $row['priority'] == 'منخفض' ? 'selected' : '' ?>>منخفض</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المبلغ الإجمالي (شامل الضريبة)</label>
                        <input type="number" name="total_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($row['total_amount']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الدفعة المقدمة</label>
                        <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($row['deposit_amount']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            <option value="نقدي" <?= $row['payment_method'] == 'نقدي' ? 'selected' : '' ?>>نقدي</option>
                            <option value="تحويل بنكي" <?= $row['payment_method'] == 'تحويل بنكي' ? 'selected' : '' ?>>تحويل بنكي</option>
                            <option value="فوري" <?= $row['payment_method'] == 'فوري' ? 'selected' : '' ?>>فوري</option>
                            <option value="غيره" <?= $row['payment_method'] == 'غيره' ? 'selected' : '' ?>>غيره</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسؤول عن التصميم</label>
                        <select name="designer_id" class="form-select">
                            <option value="">غير محدد</option>
                            <?php while($d_row = $designers_res->fetch_assoc()): ?>
                                <option value="<?= $d_row['employee_id'] ?>" <?= $row['designer_id'] == $d_row['employee_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d_row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات عامة على الطلب</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($row['notes']) ?></textarea>
                    </div>
                </div>
            </fieldset>

            <div class="col-12 text-center mt-4">
                <button class="btn btn-primary px-5" type="submit">حفظ التعديلات</button>
                <a href="orders.php" class="btn btn-secondary ms-2">عودة للقائمة</a>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('order-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    let itemCounter = 0;
    const existingItems = <?= json_encode($order_items) ?>;
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

    // Load existing items
    if (existingItems.length > 0) {
        existingItems.forEach(item => addOrderItem(item));
    } else {
        // Add one empty item if there are no existing items
        addOrderItem();
    }
});
</script>
<?php include 'footer.php'; ?>
