<?php

require_once '../db_connection.php'; // Include your database connection file

$query = "SELECT user_id, full_name FROM users WHERE role=1 and status='active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$headers = [];
while ($row = $result->fetch_assoc()) {
    $headers[$row['user_id']] = $row['full_name'];
}

echo json_encode($headers);
?>