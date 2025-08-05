<?php
// src/View/employee/delete_confirm.php

$page_title = "تأكيد حذف الموظف";
?>

<div class="container-fluid px-3">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        تأكيد حذف الموظف
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="alert alert-warning border-0" role="alert">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                            <h4 class="mt-3 mb-2">هل أنت متأكد من حذف هذا الموظف؟</h4>
                            <p class="mb-0 text-muted">هذا الإجراء لا يمكن التراجع عنه</p>
                        </div>
                    </div>

                    <div class="employee-info bg-light rounded p-3 mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>الاسم:</strong> <?= htmlspecialchars($employee['name']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>الدور:</strong> <?= htmlspecialchars($employee['role']) ?>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($employee['email']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>الهاتف:</strong> <?= htmlspecialchars($employee['phone'] ?? 'غير محدد') ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <form method="POST" action="/new_injaz/employees/delete" class="d-inline">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($employee['employee_id']) ?>">
                            <button type="submit" class="btn btn-danger px-4">
                                <i class="bi bi-trash"></i>
                                نعم، احذف الموظف
                            </button>
                        </form>
                        <a href="/new_injaz/employees" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i>
                            إلغاء
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.employee-info {
    border-right: 4px solid #dc3545;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card {
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
}
</style>