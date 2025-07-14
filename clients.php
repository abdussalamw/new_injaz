<?php
$page_title = 'العملاء';
include 'db_connection.php';
include 'header.php';

check_permission('client_view', $conn);

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
    <table class="table table-bordered table-striped text-center">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>اسم المؤسسة</th>
                <th>الشخص المسؤول</th>
                <th>الجوال</th>
                <th>البريد الإلكتروني</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM clients ORDER BY company_name");
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
