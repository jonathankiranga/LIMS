<?php
require '../db_connection.php';

$departmentID = $_GET['department'];
 
$stmt = $conn->prepare("SELECT 
    DISTINCT st.*, sp.*, tr.*, tp.*, ts.*, bp.ResultType AS BaseResultType
    FROM test_results tr 
    JOIN Sample_Tests st ON tr.TestID = st.TestID
    JOIN Sample_Header sp ON tr.HeaderID = sp.HeaderID
    JOIN testparameters tp ON tp.ParameterID = tr.ParameterID AND tp.StandardID = tr.StandardID
    JOIN teststandards ts ON ts.StandardID = tr.StandardID
    LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
    WHERE tr.StatusID = 1 AND tp.Category = ?");
$stmt->bind_param("s", $departmentID);
$stmt->execute();
$result = $stmt->get_result();

$response = ['success' => false, 'results' => []];
if ($result->num_rows > 0) {
    $response['success'] = true;
    while ($row = $result->fetch_assoc()) {
        $row['ResultType'] = $row['ResultType'] ?? $row['BaseResultType'] ?? null;
        $response['results'][] = $row;
    }
}

 header('Content-Type: application/json');
echo json_encode($response);
