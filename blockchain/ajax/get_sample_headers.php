<?php

require_once '../db_connection.php'; // Include your database connection file

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT HeaderID, DocumentNo FROM sample_header WHERE DocumentNo LIKE ? LIMIT 20";
$stmt = $conn->prepare($query);
$searchTerm = "%$searchTerm%";
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$headers = [];
while ($row = $result->fetch_assoc()) {
    $headers[] = [
        'HeaderID' => $row['HeaderID'],
        'DocumentNo' => $row['DocumentNo']
    ];
}

echo json_encode($headers);
?>
