<?php
// Connect to the database
require '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Sample input data (in a real scenario, these would come from an AJAX request or a form)
$equipmentId = $_POST['equipment_id'];  // The equipment ID from the form
$usageStartTime = $_POST['usage_start_time'];  // The start time from the form
$usageEndTime = $_POST['usage_end_time'];  // The end time from the form
$remarks = $_POST['remarks'];  // Remarks from the form
$user = $_POST['user'];  // User/Technician from the form

// Calculate duration in minutes
$startTime = new DateTime($usageStartTime);
$endTime = new DateTime($usageEndTime);
$duration = $startTime->diff($endTime);
$durationMinutes = ($duration->h * 60) + $duration->i;

// Insert data into equipmentusage table
$insertQuery = "INSERT INTO equipmentusage (equipment_id, usage_start_time, usage_end_time, duration_minutes, user, remarks)
                VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("isssis", $equipmentId, $usageStartTime, $usageEndTime, $durationMinutes, $user, $remarks);

if ($stmt->execute()) {
        echo json_encode(["message" => "Equipment usage recorded successfully."]);
    } else {
        echo json_encode(["error" => "Failed to record equipment usage."]);
    }

$stmt->close();
$conn->close();

}
?>

