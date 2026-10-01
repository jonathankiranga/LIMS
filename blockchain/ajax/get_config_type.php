<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance

if (isset($_GET['configName'])) {
    $configName = $_GET['configName'];
    
    $stmt = $conn->prepare("SELECT `type`,confvalue FROM config WHERE confname = ?");
    $stmt->bind_param("s", $configName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['type' => 'string','confvalue' => '']); // Default to string if not found
    }

    $stmt->close();
}
?>