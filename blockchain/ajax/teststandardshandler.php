<?php
// Include database connection
require '../db_connection.php'; // mysqli connection $conn

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'message' => 'Invalid request'
    ];

    // Get data from POST request
    $standardID = isset($_POST['StandardID']) ? intval($_POST['StandardID']) : null;
    $Code = $conn->real_escape_string($_POST['Code']);
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']); // Corrected field name
    $ApplicableRegulation = $conn->real_escape_string($_POST['applicableregulation']); // Corrected field name
    $standardmethod=$conn->real_escape_string($_POST['standardmethod']); // Corrected field name
    $updatedAt = date('Y-m-d H:i:s');

    // Validate required fields
    if (empty($name)) {
        $response['message'] = 'Name is required.';
        echo json_encode($response);
        exit;
    }

    try {
        // Determine whether to CREATE or UPDATE
        if ($standardID) {
            // UPDATE operation
            $query = "UPDATE TestStandards 
                      SET StandardCode= ?, StandardName = ?, Description = ?, ApplicableRegulation = ?, UpdatedAt = ?,sm=?
                      WHERE StandardID = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("sssssii", $Code, $name, $description, $ApplicableRegulation, $updatedAt,$standardmethod, $standardID);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Test standard updated successfully.';
                } else {
                    $response['message'] = 'Failed to update test standard.';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Database error: ' . $conn->error; // Provide error for debugging
            }
        } else {
            // CREATE operation
            $query = "INSERT INTO TestStandards (StandardCode, StandardName, Description, ApplicableRegulation, CreatedAt,sm)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $createdAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("sssssi", $Code, $name, $description, $ApplicableRegulation, $createdAt,$standardmethod);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Test standard created successfully.';
                } else {
                    $response['message'] = 'Failed to create test standard.';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Database error: ' . $conn->error; // Provide error for debugging
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }

    // Return JSON response
    echo json_encode($response);
}
?>
