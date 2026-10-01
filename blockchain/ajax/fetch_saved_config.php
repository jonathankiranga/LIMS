<?php
require '../db_connection.php'; // should define $conn as a mysqli connection
 
$matrix_id   = isset($_GET['matrix_id']) ? intval($_GET['matrix_id']) : null;
$descendantIds = [];
if ($matrix_id) {
    $stmt = $conn->prepare("
        WITH RECURSIVE Descendants AS (
            SELECT ParameterID FROM ParameterMatrix WHERE ParentID = ?
            UNION ALL
            SELECT pm.ParameterID
            FROM ParameterMatrix pm
            INNER JOIN Descendants d ON pm.ParentID = d.ParameterID
        )
        SELECT ParameterID FROM Descendants
    ");
    $stmt->bind_param("i",$matrix_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $descendantIds[] = $row['ParameterID'];
    }
    $descendantIds[] = $matrix_id; // Also exclude itself
}

$placeholders = implode(',', array_fill(0, count($descendantIds), '?'));

$query = "
   SELECT 
        p.ParameterID , 
        p.ParameterName,
        IF(pm.ParameterID IS NULL, 0, 1) AS is_selected
   FROM baseparameters p
   JOIN standard_parameter_matrix_config pm 
        ON pm.ParameterID = p.ParameterID 
       " . (
            count($descendantIds) > 0
            ? " AND pm.MatrixID IN ($placeholders)"
            : ""
        ) . "
   ORDER BY is_selected DESC, p.ParameterName
";

$stmt = $conn->prepare($query);
if (!$stmt) {
        echo "Prepare failed: " . $conn->error; // ✅ SQL error in prepare
    }
// always bind standard_id
// bind params dynamically
$types  = "";             // StandardID
$params = [];

if (count($descendantIds) > 0) {
    $types .= str_repeat("i", count($descendantIds));
    foreach ($descendantIds as $id) {
        $params[] = $id;
    }
}
// bind_param with spread
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $params[] = $row;
    }
}

$data=[];
$data["success"] = true;
$data["data"] = $params;

header('Content-Type: application/json');

echo json_encode($data);
