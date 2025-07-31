<?php
// src/View/employee/list.php
?>
<div class="container">
    <?php if (has_permission('employee_add', $conn)): ?>
        <a href="/?page=employees&action=add" class="btn btn-success mb-3">إضافة موظف جديد</a>
    <?php endif; ?>
    <table class="table table-bordered table-striped text-center" id="employeesMainTable">
        <thead class="table-dark">
            <tr>
                <th><?= generate_sort_link('employee_id', 'رقم الموظف', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('name', 'اسم الموظف', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('role', 'الدور الوظيفي', $sort_column_key, $sort_order) ?></th>
                <th>رقم الجوال</th>
                <th><?= generate_sort_link('email', 'البريد الإلكتروني', $sort_column_key, $sort_order) ?></th>
                <th>الإجراءات المتاحة</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $res = $conn->query("SELECT * FROM employees ORDER BY $sort_column_sql $sort_order");
            while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['employee_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php if (has_permission('employee_edit', $conn)): ?>
                        <a href="/?page=employees&action=edit&id=<?= $row['employee_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('employee_permissions_edit', $conn)): ?>
                        <a href="/?page=employees&action=permissions&id=<?= $row['employee_id'] ?>" class="btn btn-sm btn-info">الصلاحيات</a>
                    <?php endif; ?>
                    <?php if (has_permission('employee_delete', $conn)): ?>
                        <a href="/?page=employees&action=delete&id=<?= $row['employee_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
