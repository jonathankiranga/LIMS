<?php
// Include DB connection
require_once '../db_connection.php'; 
// Set response header
// Get matrix ID from query string
$stdId = isset($_GET['stdId']) ? intval($_GET['stdId']) : NULL;
$matrixid = isset($_GET['matrixid']) ? intval($_GET['matrixid']) : NULL;


if ($matrixid === 0) {

    // Always return BaseID so quotation lines can be matched to testparameters.BaseID.
    // When a quotation supplies BaseIDs, include those exact quoted parameters even
    // when testparameters assigns them to a parent standard, while retaining the
    // selected standard's other parameters as unchecked options.
    $quotedBaseIds = [];
    if (isset($_GET['baseIds']) && trim((string)$_GET['baseIds']) !== '') {
        foreach (explode(',', (string)$_GET['baseIds']) as $id) {
            $id = (int)trim($id);
            if ($id > 0) $quotedBaseIds[] = $id;
        }
        $quotedBaseIds = array_values(array_unique($quotedBaseIds));
    }

    $sql = "
        SELECT DISTINCT
            s.StandardID,
            s.StandardName,
            s.StandardCode,
            p.ParameterID,
            p.BaseID,
            p.ParameterName,
            p.Method
        FROM testparameters p
        JOIN teststandards s ON s.StandardID = p.StandardID
        WHERE s.StandardID = ?";
    if (!empty($quotedBaseIds)) {
        $sql .= " OR p.BaseID IN (" . implode(',', $quotedBaseIds) . ")";
    }
    $sql .= " ORDER BY s.StandardName, p.ParameterName";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]));
    }
    $stmt->bind_param("i", $stdId);
    
}else{

// Recursive CTE to get all descendant ParameterIDs
$sql = "
WITH RECURSIVE matrix_tree AS (
    -- Anchor: starting node at level 0
    SELECT 
        ParameterID,
        ParentID,
        0 AS level
    FROM parametermatrix
    WHERE ParameterID = ?

    UNION ALL

    -- Recursive: get only children, increase level
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
    s.StandardID,
    s.StandardName,
    s.StandardCode,
    c.ParameterID,
    p.ParameterName,
    p.Method,
    c.MatrixID,
    m.ParameterName AS MatrixName,
    c.IsDefault,
    c.Notes,
    c.CreatedAt,
    c.UpdatedAt
FROM standard_parameter_matrix_config c
JOIN testparameters p ON p.BaseID = c.ParameterID
JOIN teststandards s ON p.StandardID = s.StandardID
JOIN parametermatrix m ON c.MatrixID = m.ParameterID
WHERE c.MatrixID IN 
(SELECT ParameterID FROM matrix_tree)
ORDER BY s.StandardName, MatrixName, p.ParameterName
";
// Prepare & bind
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$matrixid);
}

$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
 
$stmt->close();
$conn->close();
// Output JSON
header('Content-Type: application/json');
 echo json_encode($data);

?>
