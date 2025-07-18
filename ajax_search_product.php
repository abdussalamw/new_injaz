<?php
include 'db_connection.php';
include 'auth_check.php';

header('Content-Type: application/json');

if (!isset($_GET['query'])) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['query']);
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$search_term = '%' . $query . '%';
$stmt = $conn->prepare("SELECT product_id, name, default_size, default_material FROM products WHERE name LIKE ? ORDER BY name LIMIT 10");
$stmt->bind_param("s", $search_term);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>
