<?php
// src/View/client/confirm_delete.php
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">تأكيد حذف العميل</h5>
                </div>
                <div class="card-body">
                    <?php if ($has_orders): ?>
                        <div class="alert alert-warning">
                            <strong>تحذير!</strong> لا يمكن حذف هذا العميل لأنه مرتبط بطلبات موجودة.
                        </div>
                        <h6>تفاصيل العميل:</h6>
                        <ul class="list-unstyled">
                            <li><strong>الرقم:</strong> <?= htmlspecialchars($client['client_id']) ?></li>
                            <li><strong>اسم المؤسسة:</strong> <?= htmlspecialchars($client['company_name']) ?></li>
                            <li><strong>الشخص المسؤول:</strong> <?= htmlspecialchars($client['contact_person']) ?></li>
                            <li><strong>الجوال:</strong> <?= htmlspecialchars($client['phone']) ?></li>
                            <li><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($client['email']) ?></li>
                        </ul>
                        <p class="text-muted">يجب حذف جميع الطلبات المرتبطة بهذا العميل أولاً قبل حذفه.</p>
                        <a href="/new_injaz/clients" class="btn btn-secondary">العودة للقائمة</a>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>تحذير!</strong> هذا الإجراء لا يمكن التراجع عنه.
                        </div>
                        <h6>هل أنت متأكد من حذف العميل التالي؟</h6>
                        <ul class="list-unstyled">
                            <li><strong>الرقم:</strong> <?= htmlspecialchars($client['client_id']) ?></li>
                            <li><strong>اسم المؤسسة:</strong> <?= htmlspecialchars($client['company_name']) ?></li>
                            <li><strong>الشخص المسؤول:</strong> <?= htmlspecialchars($client['contact_person']) ?></li>
                            <li><strong>الجوال:</strong> <?= htmlspecialchars($client['phone']) ?></li>
                            <li><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($client['email']) ?></li>
                        </ul>
                        <div class="mt-4">
                            <form method="POST" action="/new_injaz/clients/delete" style="display: inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($client['client_id']) ?>">
                                <button type="submit" class="btn btn-danger">نعم، احذف العميل</button>
                            </form>
                            <a href="/new_injaz/clients" class="btn btn-secondary">إلغاء</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
