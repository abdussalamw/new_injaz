<?php
$page_title = 'المنتجات';
include 'db_connection.php';
include 'header.php';

check_permission('product_view', $conn);

$res = $conn->query("SELECT * FROM products ORDER BY name");
?>
<div class="container">
    <?php if (has_permission('product_add', $conn)): ?>
        <a href="add_product.php" class="btn btn-success mb-3">إضافة منتج جديد</a>
    <?php endif; ?>
    <table class="table table-bordered table-striped text-center">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>اسم المنتج</th>
                <th>المقاس الافتراضي</th>
                <th>المادة الافتراضية</th>
                <th>تفاصيل إضافية</th>
                <th>إجراءات</th>
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
