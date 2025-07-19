<?php
include 'db_connection_secure.php';
include 'auth_check.php';

header('Content-Type: application/json');

if (!has_permission('client_view', $conn)) {
    echo json_encode([]);
    exit;
}

$query = $_GET['query'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT client_id, company_name, contact_person, phone FROM clients WHERE company_name LIKE ? LIMIT 10");
$search_query = "%" . $query . "%";
$stmt->bind_param("s", $search_query);
$stmt->execute();
$result = $stmt->get_result();
$clients = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($clients);