<?php
// Connect to the database
require '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Sample input data (in a real scenario, these would come from an AJAX request or a form)
$equipmentId = $_POST['equipmentId'];
$last_calibration = $_POST['last_calibration'];
$predicted_calibration = $_POST['predicted_calibration'];
$deviation_trend = $_POST['deviation_trend'];
$usage_hours = $_POST['usage_hours'];
 
// Insert data into equipmentusage table
$insertQuery = "INSERT INTO Equipment (machine_id, last_calibration, predicted_calibration, deviation_trend, usage_hours)
                VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("isssis", $equipmentId, $last_calibration, $predicted_calibration, $deviation_trend, $usage_hours);

if ($stmt->execute()) {
        echo json_encode(["message" => "Equipment usage recorded successfully."]);
    } else {
        echo json_encode(["error" => "Failed to record equipment usage."]);
    }

$stmt->close();
$conn->close();

}
?>

