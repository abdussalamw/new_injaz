<?php
// src/View/product/list.php
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>
                        المنتجات
                    </h5>
                    <!-- شريط البحث -->
                    <form method="get" class="d-flex align-items-center">
                        <div class="input-group input-group-sm me-3" style="width: 300px;">
                            <input type="text" name="search" id="searchInput" class="form-control" placeholder="البحث بالاسم أو المادة..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($_GET['search'])): ?>
                                <a href="/new_injaz/products" class="btn btn-outline-light" title="مسح البحث">
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
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <!-- أزرار الإجراءات -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <a href="/new_injaz/products/add" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>
                            إضافة منتج جديد
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center table-sm" id="productsMainTable">
                            <thead>
                                <tr>
                                    <th style="width: 8%;"><?= \App\Core\Helpers::generate_sort_link('product_id', 'رقم المنتج', $sort_column_key, $sort_order) ?></th>
                                    <th style="width: 25%;"><?= \App\Core\Helpers::generate_sort_link('name', 'اسم المنتج', $sort_column_key, $sort_order) ?></th>
                                    <th style="width: 15%;"><?= \App\Core\Helpers::generate_sort_link('default_size', 'المقاس الافتراضي', $sort_column_key, $sort_order) ?></th>
                                    <th style="width: 15%;"><?= \App\Core\Helpers::generate_sort_link('default_material', 'المادة الافتراضية', $sort_column_key, $sort_order) ?></th>
                                    <th style="width: 25%;">التفاصيل الإضافية</th>
                                    <th style="width: 12%;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $res->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= $row['product_id'] ?></td>
                                    <td class="text-start">
                                        <i class="bi bi-box text-muted me-1"></i>
                                        <?= htmlspecialchars($row['name']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($row['default_size']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($row['default_material']) ?>
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        <small class="text-muted">
                                            <?= htmlspecialchars(substr($row['default_details'], 0, 50)) ?>
                                            <?= strlen($row['default_details']) > 50 ? '...' : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="/new_injaz/products/edit?id=<?= $row['product_id'] ?>" class="btn btn-outline-primary btn-sm" title="تعديل">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="/new_injaz/products/confirm-delete?id=<?= $row['product_id'] ?>" class="btn btn-outline-danger btn-sm" title="حذف">
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
</div>

<style>
.card-header {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(40, 167, 69, 0.1) !important;
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
