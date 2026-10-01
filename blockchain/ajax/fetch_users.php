<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
header('Content-Type: application/json');
// Fetch users from the database
$query = "SELECT * FROM users";
$result = $conn->query($query);

$users = [];

if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    echo json_encode(['success' => true, 'data' => $users]);
} else {
    echo json_encode(['success' => false, 'message' => 'No users found.']);
}

$conn->close();
?>
