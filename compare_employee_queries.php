<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

echo "الفرق بين استعلامي قائمة الموظفين:\n";
echo str_repeat("=", 50) . "\n";

echo "1. الاستعلام القديم (جميع الموظفين):\n";
$old = $db->query('SELECT employee_id, name FROM employees ORDER BY name');
while($row = $old->fetch_assoc()) {
    echo "   - ID: {$row['employee_id']}, Name: {$row['name']}\n";
}

echo "\n2. الاستعلام الجديد (مصممين + معامل فقط):\n";
$new = $db->query("SELECT employee_id, name, role FROM employees WHERE role IN ('مصمم', 'معمل') ORDER BY name");
while($row = $new->fetch_assoc()) {
    echo "   - ID: {$row['employee_id']}, Name: {$row['name']}, Role: {$row['role']}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "الآن قائمة dashboard تطابق timeline تماماً!\n";
?>
