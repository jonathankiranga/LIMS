<?php

// Database connection
require '../db_connection.php'; 

header('Content-Type: application/json');

try {
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT HeaderID,DocumentNo FROM sample_header ORDER BY HeaderID DESC"); // Query to fetch all rows
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if there are results
    if ($result->num_rows > 0) {
        // Fetch all rows as an associative array
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $rows]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data found']);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
 