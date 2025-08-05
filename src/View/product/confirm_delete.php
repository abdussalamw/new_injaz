<?php
// src/View/product/confirm_delete.php
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">تأكيد حذف المنتج</h5>
                </div>
                <div class="card-body">
                    <?php if ($has_orders): ?>
                        <div class="alert alert-warning">
                            <strong>تحذير!</strong> لا يمكن حذف هذا المنتج لأنه مرتبط بطلبات موجودة.
                        </div>
                        <h6>تفاصيل المنتج:</h6>
                        <ul class="list-unstyled">
                            <li><strong>الرقم:</strong> <?= htmlspecialchars($product['product_id']) ?></li>
                            <li><strong>الاسم:</strong> <?= htmlspecialchars($product['name']) ?></li>
                            <li><strong>المقاس الافتراضي:</strong> <?= htmlspecialchars($product['default_size']) ?></li>
                            <li><strong>المادة الافتراضية:</strong> <?= htmlspecialchars($product['default_material']) ?></li>
                        </ul>
                        <p class="text-muted">يجب حذف جميع الطلبات المرتبطة بهذا المنتج أولاً قبل حذفه.</p>
                        <a href="/new_injaz/products" class="btn btn-secondary">العودة للقائمة</a>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>تحذير!</strong> هذا الإجراء لا يمكن التراجع عنه.
                        </div>
                        <h6>هل أنت متأكد من حذف المنتج التالي؟</h6>
                        <ul class="list-unstyled">
                            <li><strong>الرقم:</strong> <?= htmlspecialchars($product['product_id']) ?></li>
                            <li><strong>الاسم:</strong> <?= htmlspecialchars($product['name']) ?></li>
                            <li><strong>المقاس الافتراضي:</strong> <?= htmlspecialchars($product['default_size']) ?></li>
                            <li><strong>المادة الافتراضية:</strong> <?= htmlspecialchars($product['default_material']) ?></li>
                        </ul>
                        <div class="mt-4">
                            <form method="POST" action="/new_injaz/products/delete" style="display: inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($product['product_id']) ?>">
                                <button type="submit" class="btn btn-danger">نعم، احذف المنتج</button>
                            </form>
                            <a href="/new_injaz/products" class="btn btn-secondary">إلغاء</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
