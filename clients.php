<?php
$page_title = 'العملاء';
include 'db_connection.php';
include 'header.php';

check_permission('client_view', $conn);

// --- Sorting Logic ---
$sort_column_key = $_GET['sort'] ?? 'company_name';
$sort_order = $_GET['order'] ?? 'ASC';

$column_map = [
    'client_id' => 'client_id',
    'company_name' => 'company_name',
    'contact_person' => 'contact_person',
    'email' => 'email'
];
$allowed_sort_columns = array_keys($column_map);
if (!in_array($sort_column_key, $allowed_sort_columns)) {
    $sort_column_key = 'company_name';
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
    $url = 'clients.php?' . http_build_query($query_params);
    
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

// تصدير العملاء (CSV)
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=clients_export.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'اسم المؤسسة', 'الشخص المسؤول', 'الجوال', 'البريد الإلكتروني']);
    $res = $conn->query("SELECT * FROM clients");
    while($row = $res->fetch_assoc()){
        fputcsv($output, [$row['client_id'], $row['company_name'], $row['contact_person'], $row['phone'], $row['email']]);
    }
    fclose($output);
    exit;
}
?>
<div class="container">
    <div class="mb-3">
        <?php if (has_permission('client_add', $conn)): ?>
            <a href="add_client.php" class="btn btn-success mb-2">إضافة عميل جديد</a>
        <?php endif; ?>
        <?php if (has_permission('client_export', $conn)): ?>
            <a href="clients.php?export=1" class="btn btn-outline-primary mb-2">تصدير (CSV)</a>
        <?php endif; ?>
        <?php if (has_permission('client_import', $conn)): ?>
            <a href="import_clients.php" class="btn btn-outline-secondary mb-2">استيراد (CSV)</a>
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
                        <a href="edit_client.php?id=<?= $row['client_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <?php endif; ?>
                    <?php if (has_permission('client_delete', $conn)): ?>
                        <a href="delete_client.php?id=<?= $row['client_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
