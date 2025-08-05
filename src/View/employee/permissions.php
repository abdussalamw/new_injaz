م<?php
// src/View/employee/permissions.php

// Load employee data
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    header("Location: /new_injaz/employees");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    header("Location: /new_injaz/employees");
    exit;
}

// Load current permissions
$stmt = $conn->prepare("SELECT permission_key FROM employee_permissions WHERE employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$current_permissions = [];
while ($row = $result->fetch_assoc()) {
    $current_permissions[] = $row['permission_key'];
}

// Get all available permissions
$all_permissions = \App\Core\Permissions::get_all_permissions();

$page_title = "صلاحيات الموظف: " . htmlspecialchars($employee['name']);
?>

<div class="container-fluid px-3" style="max-height: 100vh; overflow: hidden;">
    <div class="row h-100">
        <div class="col-12">
            <!-- Header مدمج -->
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom mb-3">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check text-primary"></i>
                        صلاحيات: <?= htmlspecialchars($employee['name']) ?>
                    </h5>
                    <small class="text-muted">الدور: <?= htmlspecialchars($employee['role']) ?></small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">الكل</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="selectNone()">لا شيء</button>
                    <button type="submit" class="btn btn-sm btn-success ms-2" form="permissions-form">
                        <i class="bi bi-check-lg"></i>
                        حفظ الصلاحيات
                    </button>
                    <a href="/new_injaz/employees" class="btn btn-sm btn-secondary ms-2">عودة</a>
                </div>
            </div>

            <!-- Form في الشاشة المتبقية -->
            <form method="POST" action="/new_injaz/employees/permissions" id="permissions-form" style="height: calc(100vh - 120px);">
                <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                <div class="row h-100" style="max-height: calc(100vh - 120px); overflow-y: auto;">
                    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
                    <?php foreach ($all_permissions as $category => $permissions): ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="mb-0 text-center">
                                        <?= htmlspecialchars($category) ?>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <?php foreach ($permissions as $key => $description): ?>
                                        <div class="mb-2">
                                            <input 
                                                type="checkbox" 
                                                name="permissions[]" 
                                                value="<?= htmlspecialchars($key) ?>"
                                                id="perm_<?= htmlspecialchars($key) ?>"
                                                <?= in_array($key, $current_permissions) ? 'checked' : '' ?>
                                                style="display: none;"
                                            >
                                            <button 
                                                type="button" 
                                                class="btn btn-sm permission-btn w-100 <?= in_array($key, $current_permissions) ? 'btn-success' : 'btn-outline-secondary' ?>"
                                                data-permission="<?= htmlspecialchars($key) ?>"
                                                onclick="togglePermission(this)"
                                                style="font-size: 11px; padding: 4px 8px; text-align: right;"
                                            >
                                                <i class="bi <?= in_array($key, $current_permissions) ? 'bi-check-circle-fill' : 'bi-circle' ?>" style="font-size: 12px;"></i>
                                                <?= htmlspecialchars($description) ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>


            </form>
        </div>
    </div>
</div>

<style>
body {
    overflow: hidden;
}

.permission-btn {
    text-align: right;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    font-size: 11px !important;
    padding: 4px 8px !important;
    border-radius: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.permission-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.permission-btn.btn-success {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

.permission-btn.btn-success:hover {
    background-color: #157347;
    border-color: #146c43;
}

.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

/* تحسين شريط التمرير */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
function togglePermission(button) {
    const permissionKey = button.getAttribute('data-permission');
    const checkbox = document.getElementById('perm_' + permissionKey);
    const icon = button.querySelector('i');

    // Toggle checkbox
    checkbox.checked = !checkbox.checked;

    // Toggle button appearance
    if (checkbox.checked) {
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        icon.classList.remove('bi-circle');
        icon.classList.add('bi-check-circle-fill');
    } else {
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
        icon.classList.remove('bi-check-circle-fill');
        icon.classList.add('bi-circle');
    }
}

function selectAll() {
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
        const button = document.querySelector(`[data-permission="${checkbox.value}"]`);
        const icon = button.querySelector('i');

        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        icon.classList.remove('bi-circle');
        icon.classList.add('bi-check-circle-fill');
    });
}

function selectNone() {
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
        const button = document.querySelector(`[data-permission="${checkbox.value}"]`);
        const icon = button.querySelector('i');

        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
        icon.classList.remove('bi-check-circle-fill');
        icon.classList.add('bi-circle');
    });
}
</script>
