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
$query = "UPDATE users SET  full_name = ?, telephone = ?, email = ?, "
        . " role = ?, status = ?, department=?  WHERE user_id = ?";
// Prepare the statement
$stmt = $conn->prepare($query);
$stmt->bind_param('sssissi', $data['full_name'], 
                  $data['telephone'], 
                  $data['email'], 
                  $data['role'], 
                  $data['status'], 
                  $data['assignedDepartment'], 
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
