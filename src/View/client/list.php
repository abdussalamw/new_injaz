<?php
// src/View/client/list.php
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>
                        العملاء
                    </h5>
                    <!-- شريط البحث -->
                    <form method="get" class="d-flex align-items-center">
                        <div class="input-group input-group-sm me-3" style="width: 300px;">
                            <input type="text" name="search" id="searchInput" class="form-control" placeholder="البحث بالاسم أو الهاتف..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($_GET['search'])): ?>
                                <a href="/new_injaz/clients" class="btn btn-outline-light" title="مسح البحث">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <!-- الحفاظ على معاملات الترتيب -->
                        <?php if (!empty($_GET['sort'])): ?>
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort']) ?>">
                        <?php endif; ?>
                        <?php if (!empty($_GET['order'])): ?>
                            <input type="hidden" name="order" value="<?= htmlspecialchars($_GET['order']) ?>">
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-body">
                    <?= \App\Core\MessageSystem::displayMessages() ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <!-- أزرار الإجراءات -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <a href="/new_injaz/clients/add" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>
                            إضافة عميل جديد
                        </a>
                        <a href="/new_injaz/clients/export" class="btn btn-outline-primary">
                            <i class="bi bi-download me-1"></i>
                            تصدير (CSV)
                        </a>
                        <form method="POST" action="/new_injaz/clients/import" enctype="multipart/form-data" style="display: inline;">
                            <input type="file" name="csv_file" accept=".csv" required style="display: none;" id="csv_file" onchange="this.form.submit();">
                            <label for="csv_file" class="btn btn-outline-secondary" style="cursor: pointer;">
                                <i class="bi bi-upload me-1"></i>
                                استيراد (CSV)
                            </label>
                        </form>
                    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover text-center table-sm" id="clientsMainTable">
            <thead>
                <tr>
                    <th style="width: 8%;"><?= \App\Core\Helpers::generate_sort_link('client_id', 'رقم العميل', $sort_column_key, $sort_order) ?></th>
                    <th style="width: 25%;"><?= \App\Core\Helpers::generate_sort_link('company_name', 'اسم المؤسسة', $sort_column_key, $sort_order) ?></th>
                    <th style="width: 20%;"><?= \App\Core\Helpers::generate_sort_link('contact_person', 'الشخص المسؤول', $sort_column_key, $sort_order) ?></th>
                    <th style="width: 15%;">رقم الجوال</th>
                    <th style="width: 20%;"><?= \App\Core\Helpers::generate_sort_link('email', 'البريد الإلكتروني', $sort_column_key, $sort_order) ?></th>
                    <th style="width: 12%;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td class="fw-bold text-primary"><?= $row['client_id'] ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['contact_person']) ?></td>
                    <td>
                        <?php if (!empty($row['phone'])): ?>
                            <a href="<?= \App\Core\Helpers::format_whatsapp_link($row['phone']) ?>" target="_blank" class="btn btn-sm text-decoration-none" style="background-color: #25D366; color: white;" title="فتح واتساب">
                                <i class="bi bi-whatsapp me-1"></i>
                                <?= htmlspecialchars($row['phone']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-decoration-none text-truncate d-block" style="max-width: 150px;">
                            <?= htmlspecialchars($row['email']) ?>
                        </a>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="/new_injaz/clients/edit?id=<?= $row['client_id'] ?>" class="btn btn-outline-primary btn-sm" title="تعديل">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/new_injaz/clients/confirm-delete?id=<?= $row['client_id'] ?>" class="btn btn-outline-danger btn-sm" title="حذف">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-header {
    background: linear-gradient(135deg, #D44759, #F37D47) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(212, 71, 89, 0.1) !important;
}

.btn-whatsapp {
    background-color: #25D366 !important;
    border-color: #25D366 !important;
}

.btn-whatsapp:hover {
    background-color: #128C7E !important;
    border-color: #128C7E !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            const searchValue = searchInput.value.trim();
            const currentUrl = new URL(window.location);
            
            if (searchValue) {
                currentUrl.searchParams.set('search', searchValue);
            } else {
                currentUrl.searchParams.delete('search');
            }
            
            window.location.href = currentUrl.toString();
        }, 500); // انتظار 500ms بعد توقف الكتابة
    });
});
</script>
