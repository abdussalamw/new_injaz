<?php
// src/View/product/form.php
// Logic is now in ProductController

$is_edit = $is_edit ?? false;
$product = $product ?? [];
$page_title = $page_title ?? ($is_edit ? 'تعديل المنتج' : 'إضافة منتج جديد');
$error = $error ?? null;

// Set form values
$name = $product['name'] ?? '';
$default_size = $product['default_size'] ?? '';
$default_material = $product['default_material'] ?? '';
$default_details = $product['default_details'] ?? '';
?>
<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="<?= $is_edit ? '/new_injaz/products/update' : '/new_injaz/products' ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($product['product_id']) ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المنتج</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">المقاس الافتراضي</label>
                <input type="text" class="form-control" name="default_size" value="<?= htmlspecialchars($default_size) ?>" placeholder="مثال: 9x5 سم">
            </div>
            <div class="col-md-4">
                <label class="form-label">المادة الافتراضية</label>
                <input type="text" class="form-control" name="default_material" value="<?= htmlspecialchars($default_material) ?>" placeholder="مثال: ورق فاخر">
            </div>
            <div class="col-md-12">
                <label class="form-label">تفاصيل إضافية</label>
                <textarea class="form-control" name="default_details"><?= htmlspecialchars($default_details) ?></textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">حفظ</button>
        <a href="/new_injaz/products" class="btn btn-secondary mt-3">عودة للقائمة</a>
    </form>
</div>
