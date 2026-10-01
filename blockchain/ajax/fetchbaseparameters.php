<?php
// Database connection
require '../db_connection.php'; // Adjust to your database connection file
  

    $result = $conn->query("SELECT ParameterID, ParameterName FROM baseparameters ORDER BY ParameterName ASC");
    $params = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $params['data'][] = $row;
        }
    }
    
header('Content-Type: application/json');
    echo json_encode($params, JSON_INVALID_UTF8_SUBSTITUTE);




?>
