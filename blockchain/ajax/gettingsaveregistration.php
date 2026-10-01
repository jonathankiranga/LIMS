<?php
require '../db_connection.php'; // Adjust to your database connection file

$stdId = isset($_GET['stdId']) ? intval($_GET['stdId']) : NULL;
$matrixid = isset($_GET['matrixid']) ? intval($_GET['matrixid']) : NULL;
 
header('Content-Type: application/json');
if ($stdId === NULL || $matrixid === NULL) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

// Recursive CTE to get all descendant ParameterIDs
$sql = "
WITH RECURSIVE matrix_tree AS (
    SELECT 
        ParameterID,
        ParentID,
        0 AS level
    FROM parametermatrix
    WHERE ParameterID = ?

    UNION ALL

    SELECT 
        pm.ParameterID,
        pm.ParentID,
        mt.level + 1
    FROM parametermatrix pm
    INNER JOIN matrix_tree mt 
        ON pm.ParentID = mt.ParameterID
)
SELECT 
    c.ConfigID,
    c.StandardID,
    s.StandardName,
    c.ParameterID,
    p.ParameterName
FROM standard_parameter_matrix_config c
JOIN teststandards s ON c.StandardID = s.StandardID
JOIN testparameters p ON c.ParameterID = p.ParameterID
JOIN parametermatrix m ON c.MatrixID = m.ParameterID
WHERE c.StandardID = ?
  AND c.MatrixID IN (SELECT ParameterID FROM matrix_tree)
ORDER BY s.StandardName, MatrixName, p.ParameterName
";

// Prepare & bind
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii",$matrixid, $stdId);
$stmt->execute();

// Fetch result
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
 
$stmt->close();
$conn->close();

echo json_encode($data);