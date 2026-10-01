<?php
require '../db_connection.php';

$resultsID = $_GET['resultsID'];

$stmt = $conn->prepare("
    SELECT tr.*, tp.*, ts.*,
        COALESCE(bp.ResultType, tp.ResultType, 'quantitativeField') AS ResultType,
        tp.Category AS ParamCategory
    FROM test_results tr 
    JOIN testparameters tp ON tp.ParameterID = tr.ParameterID AND tp.StandardID = tr.StandardID
    JOIN teststandards ts ON ts.StandardID = tr.StandardID
    LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
    WHERE tr.resultsID = ?
");
$stmt->bind_param("i", $resultsID);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // ResultType already resolved in SQL via COALESCE
    echo json_encode(['success' => true, 'result' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'No result found.']);
}