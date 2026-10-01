<?php
require '../db_connection.php';
header('Content-Type: application/json');

$query = "SELECT ts.*, sm.standard_method  
          FROM TestStandards ts  
          LEFT JOIN standard_methods sm ON ts.sm = sm.MethodID 
          ORDER BY ts.StandardID ASC";
$result = $conn->query($query);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode(['data' => $data], JSON_INVALID_UTF8_SUBSTITUTE);
