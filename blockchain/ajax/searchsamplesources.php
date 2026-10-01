<?php
header('Content-Type: application/json');
require '../db_connection.php'; 

$query = $_GET['q'] ?? '';
$query = trim($query);

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT DISTINCT source_name FROM sample_sources WHERE source_name LIKE ? ORDER BY source_name LIMIT 20");
$likeQuery = '%' . $query . '%';
$stmt->bind_param('s', $likeQuery);
$stmt->execute();
$result = $stmt->get_result();

$sources = [];
while ($row = $result->fetch_assoc()) {
    $sources[] = ['source_name' => $row['source_name']];
}

echo json_encode($sources);