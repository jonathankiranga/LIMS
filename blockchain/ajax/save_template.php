<?php

$config = include('../include/config.php'); // Load the config file
// Database connection
$secretKey = $config['SECRET_KEY'];  // Access the SECRET_KEY from config
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host,$db_username,$db_password,$db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get POST data from the AJAX request
$event_name = isset($_POST['event_name']) ? $_POST['event_name'] : '';
$subject = isset($_POST['subject']) ? $_POST['subject'] : '';
$body = isset($_POST['body']) ? $_POST['body'] : '';

// Check if all fields are provided
if ($event_name && $subject && $body) {
    // Prepare the query to check if the template for this event already exists
    $checkQuery = "SELECT * FROM email_templates WHERE event_name = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $event_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If a template already exists, update it
        $updateQuery = "UPDATE email_templates SET subject = ?, body = ? WHERE event_name = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sss", $subject, $body, $event_name);
        if ($updateStmt->execute()) {
            echo "Template updated successfully.";
        } else {
            echo "Error updating template: " . $conn->error;
        }
    } else {
        // If no template exists for this event, insert a new one
        $insertQuery = "INSERT INTO email_templates (event_name, subject, body) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("sss", $event_name, $subject, $body);
        if ($insertStmt->execute()) {
            echo "Template saved successfully.";
        } else {
            echo "Error saving template: " . $conn->error;
        }
    }
} else {
    echo "Please provide all required fields.";
}

$conn->close();
?>
