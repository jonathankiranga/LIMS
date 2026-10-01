<?php
 
require '../db_connection.php'; 
if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    
    
    // Fetch user details from the database
    $query = "SELECT user_id ,username ,telephone , password_hash, email,"
            . " role, status, full_name, created_at, department,signature_path FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No user ID provided.']);
}

$conn->close();
?>
