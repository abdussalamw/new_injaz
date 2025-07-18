<?php
$page_title = 'المنتجات';
include 'db_connection.php';
include 'header.php';

check_permission('product_view', $conn);

// --- Sorting Logic ---
$sort_column_key = $_GET['sort'] ?? 'name';
$sort_order = $_GET['order'] ?? 'ASC';

$column_map = [
    'product_id' => 'product_id',
    'name' => 'name',
    'default_size' => 'default_size',
    'default_material' => 'default_material'
];
$allowed_sort_columns = array_keys($column_map);
if (!in_array($sort_column_key, $allowed_sort_columns)) {
    $sort_column_key = 'name';
}
if (strtoupper($sort_order) !== 'ASC' && strtoupper($sort_order) !== 'DESC') {
    $sort_order = 'ASC';
}
$sort_column_sql = $column_map[$sort_column_key];

function generate_sort_link($column_key, $display_text, $current_sort_key, $current_order) {
    $next_order = ($current_sort_key === $column_key && strtoupper($current_order) === 'ASC') ? 'DESC' : 'ASC';
    $query_params = $_GET;
    $query_params['sort'] = $column_key;
    $query_params['order'] = $next_order;
    $url = 'products.php?' . http_build_query($query_params);
    
    $icon = '';
    if ($current_sort_key === $column_key) {
        $icon = (strtoupper($current_order) === 'ASC') ? ' <i class="fas fa-sort-up text-primary"></i>' : ' <i class="fas fa-sort-down text-primary"></i>';
    } else {
        $icon = ' <i class="fas fa-sort text-muted"></i>';
    }
    
    return '<a href="' . htmlspecialchars($url) . '" class="text-decoration-none text-white d-flex align-items-center justify-content-center" style="cursor: pointer;">' . 
           '<span>' . htmlspecialchars($display_text) . '</span>' . $icon . '</a>';
}
// --- End Sorting Logic ---

$res = $conn->query("SELECT * FROM products ORDER BY $sort_column_sql $sort_order");
?>
<div class="container">
    <?php if (has_permission('product_add', $conn)): ?>
        <a href="add_product.php" class="btn btn-success mb-3">إضافة منتج جديد</a>
    <?php endif; ?>
    <table class="table table-bordered table-striped text-center" id="productsMainTable">
        <thead class="table-dark">
            <tr>
                <th><?= generate_sort_link('product_id', 'رقم المنتج', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('name', 'اسم المنتج', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('default_size', 'المقاس الافتراضي', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('default_material', 'المادة الافتراضية', $sort_column_key, $sort_order) ?></th>
                <th>التفاصيل الإضافية</th>
                <th>الإجراءات المتاحة</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['product_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['default_size']) ?></td>
                <td><?= htmlspecialchars($row['default_material']) ?></td>
                <td><?= htmlspecialchars($row['default_details']) ?></td>
                <td>
                    <?php if (has_permission('product_edit', $conn)): ?>
                        <a href="edit_product.php?id=<?= $row['product_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('product_delete', $conn)): ?>
                        <a href="delete_product.php?id=<?= $row['product_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
