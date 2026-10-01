<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}




header('Content-Type: application/json');
// Get the POST data and decode the JSON
$data = json_decode(file_get_contents('php://input'), true);

$equipmentId = $data['equipment_id'];
$sampleName = $data['sample_name'];
$knownValue = $data['known_value'];
$measuredValue = $data['measured_value'];
$deviation = $measuredValue-$knownValue;
// Validate required fields
if (empty($equipmentId) || empty($sampleName) || !is_numeric($knownValue) || !is_numeric($measuredValue)) {
    echo json_encode(['error' => 'Invalid input data']);
    exit();
}

// Prepare and bind the SQL statement
$stmt = $conn->prepare("INSERT INTO ControlSampleResults (equipment_id, sample_name, known_value, measured_value,deviation) VALUES (?, ?, ?, ?,?)");
$stmt->bind_param("isddd", $equipmentId, $sampleName, $knownValue, $measuredValue,$deviation);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Test result saved successfully']);
} else {
    echo json_encode(['error' => 'Failed to save test result']);
}

$stmt->close();
$conn->close();
?>
