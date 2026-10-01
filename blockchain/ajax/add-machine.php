<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Check if data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['machine_name'])) {
    // Insert machine data into the Machines table
    $insertQuery = "INSERT INTO Machines (machine_name) VALUES (?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("s", $_POST['machine_name']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
    $conn->close();
    
} else {
    // Return error if POST request is not valid
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

