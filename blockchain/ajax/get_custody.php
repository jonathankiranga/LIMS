<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

$sample_id = $_GET['sample_id'] ?? null;

header('Content-Type: application/json');
if ($sample_id) {
    $sql = "SELECT 
                `CustodyID`,
                `SampleID`,
                `HandlerName`,
                `Action`,
                `DateTime`,
                `Location`,
                `Notes`
            FROM `sample_custody`
            WHERE `SampleID` = ? 
            ORDER BY `CustodyID` DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sample_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

   
    echo json_encode($records);
} else {
    echo json_encode(["error" => "Missing sample_id"]);
}
