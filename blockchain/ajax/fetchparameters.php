<?php
// Database connection
require '../db_connection.php'; // Adjust to your database connection file
$standardID = isset($_REQUEST['standardID']) ? intval($_REQUEST['standardID']) : NULL;
  
header('Content-Type: application/json');
if ($standardID != NULL) {
    $query = $conn->prepare("SELECT 
        ts.StandardName, 
        tp.ParameterID,
        tp.StandardID,
        tp.ParameterName,
        tp.MinLimit,
        tp.MaxLimit, 
        tp.Limits, 
        tp.Method, 
        tp.MRL,
        tp.MRLUnit, 
        tp.Category, 
        tp.BaseID,
        tp.UnitOfMeasure,
        tp.ResultType,
        tp.AccreditationStatus
 FROM testparameters tp
 INNER JOIN TestStandards ts ON tp.StandardID = ts.StandardID
 WHERE tp.StandardID = ? 
 ORDER BY ts.StandardName ASC, 
 tp.ParameterName ASC
");
    $query->bind_param('i', $standardID);
    $query->execute();
    $result = $query->get_result();

    $parameters['data'] = [];
    while ($row = $result->fetch_assoc()) {
        $parameters['data'][] = $row;
    }
   
    echo json_encode($parameters, JSON_INVALID_UTF8_SUBSTITUTE);
} else {
        
    echo json_encode(['data'=>'Nothing','error' => "Invalid StandardID $standardID"], JSON_INVALID_UTF8_SUBSTITUTE);
}



?>
