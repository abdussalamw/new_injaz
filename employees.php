<?php
$page_title = 'الموظفون';
include 'db_connection.php';
include 'header.php';

check_permission('employee_view', $conn);

$res = $conn->query("SELECT * FROM employees ORDER BY role, name");
?>
<div class="container">
    <?php if (has_permission('employee_add', $conn)): ?>
        <a href="add_employee.php" class="btn btn-success mb-3">إضافة موظف جديد</a>
    <?php endif; ?>
    <table class="table table-bordered table-striped text-center">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>الدور</th>
                <th>الجوال</th>
                <th>البريد</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['employee_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php if (has_permission('employee_edit', $conn)): ?>
                        <a href="edit_employee.php?id=<?= $row['employee_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('employee_delete', $conn)): ?>
                        <a href="delete_employee.php?id=<?= $row['employee_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
