<?php
// src/View/client/list.php
?>
<div class="container">
    <div class="mb-3">
        <?php if (has_permission('client_add', $conn)): ?>
            <a href="/?page=clients&action=add" class="btn btn-success mb-2">إضافة عميل جديد</a>
        <?php endif; ?>
        <?php if (has_permission('client_export', $conn)): ?>
            <a href="/?page=clients&action=export" class="btn btn-outline-primary mb-2">تصدير (CSV)</a>
        <?php endif; ?>
        <?php if (has_permission('client_import', $conn)): ?>
            <a href="/?page=clients&action=import" class="btn btn-outline-secondary mb-2">استيراد (CSV)</a>
        <?php endif; ?>
    </div>
    <table class="table table-bordered table-striped text-center" id="clientsMainTable">
        <thead class="table-dark">
            <tr>
                <th><?= generate_sort_link('client_id', 'رقم العميل', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('company_name', 'اسم المؤسسة', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('contact_person', 'الشخص المسؤول', $sort_column_key, $sort_order) ?></th>
                <th>رقم الجوال</th>
                <th><?= generate_sort_link('email', 'البريد الإلكتروني', $sort_column_key, $sort_order) ?></th>
                <th>الإجراءات المتاحة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM clients ORDER BY $sort_column_sql $sort_order");
            while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['client_id'] ?></td>
                <td><?= htmlspecialchars($row['company_name']) ?></td>
                <td><?= htmlspecialchars($row['contact_person']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php if (has_permission('client_edit', $conn)): ?>
                        <a href="/?page=clients&action=edit&id=<?= $row['client_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('client_delete', $conn)): ?>
                        <a href="/?page=clients&action=delete&id=<?= $row['client_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
