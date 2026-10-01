<?php
// Include database connection
require '../db_connection.php'; // Assuming this provides the mysqli connection $conn

// Set content type to JSON
header('Content-Type: application/json');

// Initialize a default response
$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from POST request and sanitize
    $ParameterID = (mb_strlen($_POST['ParameterID'])>0) ? intval($_POST['ParameterID']) : null;
    $name        = trim($_POST['ParameterNameForm']); // Use trim to remove whitespace
    $ParentID    = (mb_strlen($_POST['ParentID'])>0) ? intval($_POST['ParentID']) : null;

    // Validate required fields
    if (empty($name)) {
        $response['message'] = 'Parameter name is required.';
        // Echo the error response and exit
        echo json_encode($response);
        exit;
    }

    try {
        // --- Uniqueness Check (for both CREATE and UPDATE) ---
        // This query checks if the name exists on any record other than the one being updated.
        // For a CREATE operation, $ParameterID is null, so the WHERE clause effectively checks all records.
        $stmt = $conn->prepare("SELECT COUNT(*) FROM ParameterMatrix WHERE ParameterName = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->bind_result($totalRecords);
        $stmt->fetch();
        $stmt->close(); // Close the statement for the uniqueness check

        if ($totalRecords > 0 && $ParameterID===NULL) {
            $response['message'] = 'Duplicate Name detected. A parameter with this name already exists.';
        } else {
            // If the name is unique, proceed with the CREATE or UPDATE operation
            if ($ParameterID) {
                // --- UPDATE Operation ---
                if ($ParentID === null) {
                    // Use a query that explicitly sets ParentID to NULL
                    $query = "UPDATE ParameterMatrix SET ParameterName = ?, ParentID = NULL, UpdatedAt = NOW() WHERE ParameterID = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("si", $name, $ParameterID); // 's' for name, 'i' for ParameterID
                    }
                } else {
                    // Use a query with a placeholder for ParentID
                    $query = "UPDATE ParameterMatrix SET ParameterName = ?, ParentID = ?, UpdatedAt = NOW() WHERE ParameterID = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("sii", $name, $ParentID, $ParameterID);
                    }
                }

                if ($stmt) {
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Test Parameters updated successfully.';
                    } else {
                        $response['message'] = 'Update failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $response['message'] = 'Database error during update preparation: ' . $conn->error;
                }
            } else {
                // --- CREATE Operation ---
                if ($ParentID === null) {
                    // Use a query that explicitly sets ParentID to NULL
                    $query = "INSERT INTO ParameterMatrix (ParameterName, ParentID) VALUES (?, NULL)";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("s", $name);
                    }
                } else {
                    // Use a query with a placeholder for ParentID
                    $query = "INSERT INTO ParameterMatrix (ParameterName, ParentID) VALUES (?, ?)";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("si", $name, $ParentID);
                    }
                }
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Test Parameters created successfully.';
                        // You can get the new ID if needed
                        $response['newId'] = $conn->insert_id;
                    } else {
                        $response['message'] = 'Creation failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $response['message'] = 'Database error during create preparation: ' . $conn->error;
                }
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
    }
}

// Return JSON response at the end of the script
echo json_encode($response);
?>