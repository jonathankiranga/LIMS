<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Query to fetch machine data
$sql = "SELECT machine_id, machine_name FROM Machines";
$result = $conn->query($sql);

$machines = array();
header('Content-Type: application/json');
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $machines[] = $row;
    }
} else {
    echo json_encode(['message' => 'No machines found']);
    exit();
}

$conn->close();

// Return the data as JSON
echo json_encode($machines);
?>
