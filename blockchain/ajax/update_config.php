<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $configName = $_POST['configName'];
    $configValue = $_POST['configValue'];

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE config SET confvalue = ? WHERE confname = ?");
    $stmt->bind_param("ss", $configValue, $configName);

    if ($stmt->execute()) {
        echo "Configuration updated successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>