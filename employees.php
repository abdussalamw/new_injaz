<?php
$page_title = 'الموظفون';
include 'db_connection.php';
include 'header.php';

check_permission('employee_view', $conn);

// --- Sorting Logic ---
$sort_column_key = $_GET['sort'] ?? 'name';
$sort_order = $_GET['order'] ?? 'ASC';

$column_map = [
    'employee_id' => 'employee_id',
    'name' => 'name',
    'role' => 'role',
    'email' => 'email'
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
    $url = 'employees.php?' . http_build_query($query_params);
    $icon = ($current_sort_key === $column_key) ? ((strtoupper($current_order) === 'ASC') ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>') : '';
    return '<a href="' . htmlspecialchars($url) . '" class="text-decoration-none text-dark">' . htmlspecialchars($display_text) . $icon . '</a>';
}
// --- End Sorting Logic ---

$res = $conn->query("SELECT * FROM employees ORDER BY $sort_column_sql $sort_order");
?>
<div class="container">
    <?php if (has_permission('employee_add', $conn)): ?>
        <a href="add_employee.php" class="btn btn-success mb-3">إضافة موظف جديد</a>
    <?php endif; ?>
    <table class="table table-bordered table-striped text-center">
        <thead class="table-light">
            <tr>
                <th><?= generate_sort_link('employee_id', '#', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('name', 'الاسم', $sort_column_key, $sort_order) ?></th>
                <th><?= generate_sort_link('role', 'الدور', $sort_column_key, $sort_order) ?></th>
                <th>الجوال</th>
                <th><?= generate_sort_link('email', 'البريد', $sort_column_key, $sort_order) ?></th>
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
