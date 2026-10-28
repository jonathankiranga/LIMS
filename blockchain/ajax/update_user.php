<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Get input data from the request
$data = json_decode(file_get_contents('php://input'), true);
// Validate required fields
if (!isset($data['email']) || !isset($data['role']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}
// Prepare the update query
$query = "UPDATE users SET full_name = ?, telephone = ?, email = ?, "
        . " role = ?, status = ?, department = ?, user_description = ? WHERE user_id = ?";
// Prepare the statement
$stmt = $conn->prepare($query);
$userDescription = trim((string)($data['user_description'] ?? ''));
$stmt->bind_param('sssisssi', $data['full_name'], 
                  $data['telephone'], 
                  $data['email'], 
                  $data['role'], 
                  $data['status'], 
                  $data['assignedDepartment'], 
                  $userDescription, 
                  $data['user_id']);  // Assuming you are passing the user_id
// Execute the statement
header('Content-Type: application/json');
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user.'.$stmt->error]);
}

$stmt->close();
$conn->close();
?>
