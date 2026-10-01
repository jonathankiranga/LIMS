<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = file_get_contents('php://input');
    $assignments = json_decode($input, true);

    if (!$assignments) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    // Insert assignments into the database
    $stmt = $conn->prepare("INSERT INTO test_assignments (user_id, resultsID, assigned_at)
        VALUES (?, ?, NOW())  ON DUPLICATE KEY UPDATE   assigned_at = NOW(); ");
    foreach ($assignments as $userID => $tests) {
        foreach ($tests as $resultsID) {
            $stmt->bind_param('ii', $userID, $resultsID);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Failed to save assignments.']);
                exit;
            }
        }
    }

    $stmt->close();
    $conn->close();
}
 echo json_encode(['success' => true, 'message' => 'Assignments saved successfully.']);