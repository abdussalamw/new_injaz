<?php
// ...existing code...

public function confirmDelete(): void
{
    if (!Permissions::has_permission('employee_delete', $this->conn)) {
        header('Location: /new_injaz/employees');
        exit;
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        $_SESSION['error'] = 'رقم الموظف مطلوب';
        header('Location: /new_injaz/employees');
        exit;
    }

    // Render a confirmation view (replace with your view logic)
    include __DIR__ . '/../Views/employees/confirm_delete.php';
}

// ...existing code...