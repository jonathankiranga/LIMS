<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

$sql ="SELECT e.id, m.machine_name, e.last_calibration, e.predicted_calibration, e.deviation_trend, e.usage_hours
FROM Equipment e
JOIN Machines m ON e.machine_id = m.machine_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $equipment = [];
    
    while ($row = $result->fetch_assoc()) {
        $equipment[] = [
            "id" => $row["id"],
            "name" => $row["machine_name"],
            "last_calibration" => $row["last_calibration"],
            "predicted_calibration" => $row["predicted_calibration"],
            "deviation_trend" => $row["deviation_trend"],
            "usage_hours" => $row["usage_hours"]
        ];
    }

    echo json_encode(["status" => "success", "equipment" => $equipment]);
} else {
    echo json_encode(["status" => "error", "message" => "No equipment data found"]);
}

// Close connection
$conn->close();
?>
