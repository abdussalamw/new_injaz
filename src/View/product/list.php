<?php
// src/View/product/list.php
?>
<div class="container">
    <?php if (has_permission('product_add', $conn)): ?>
        <a href="/?page=products&action=add" class="btn btn-success mb-3">إضافة منتج جديد</a>
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
            <?php 
            $res = $conn->query("SELECT * FROM products ORDER BY $sort_column_sql $sort_order");
            while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['product_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['default_size']) ?></td>
                <td><?= htmlspecialchars($row['default_material']) ?></td>
                <td><?= htmlspecialchars($row['default_details']) ?></td>
                <td>
                    <?php if (has_permission('product_edit', $conn)): ?>
                        <a href="/?page=products&action=edit&id=<?= $row['product_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('product_delete', $conn)): ?>
                        <a href="/?page=products&action=delete&id=<?= $row['product_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
