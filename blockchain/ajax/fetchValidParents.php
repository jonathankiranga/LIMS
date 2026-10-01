<?php
// Database connection
require '../db_connection.php'; 

function debugQuery($sql, $params) {
    foreach ($params as $p) {
        $val = is_numeric($p) ? $p : "'$p'";
        $sql = preg_replace('/\?/', $val, $sql, 1);
    }
    return $sql;
}

// Ensure this connects to your PDO instance and sets $pdo
// Retrieve input values
$q = $_GET['q'] ?? '';
$currentId = (int) ($_GET['id'] ?? 0);
// Get descendants of current node
$descendantIds = [];
if ($currentId) {
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
    $stmt->bind_param("i", $currentId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $descendantIds[] = $row['ParameterID'];
    }
    $descendantIds[] = $currentId; // Also exclude itself
}

$placeholders = implode(',', array_fill(0, count($descendantIds), '?'));
$params = array_merge(["%" . $q . "%"], $descendantIds);

// Build dynamic SQL
$sql = "WITH RECURSIVE ParameterHierarchy AS (
    SELECT 
        ParameterID,
        ParameterName,
        ParentID,
        CAST(ParameterName AS CHAR(255)) AS FullPath,
        0 AS Level
    FROM ParameterMatrix
    WHERE ParentID IS NULL

    UNION ALL

    SELECT 
        pm.ParameterID,
        pm.ParameterName,
        pm.ParentID,
        CONCAT(ph.FullPath, ' > ', pm.ParameterName),
        ph.Level + 1
    FROM ParameterMatrix pm
    INNER JOIN ParameterHierarchy ph ON pm.ParentID = ph.ParameterID
)
SELECT ParameterID, ParameterName, FullPath, Level
FROM ParameterHierarchy
WHERE ParameterName LIKE ? 
";

// Exclude descendants + itself
if (!empty($descendantIds)) {
    $sql .= " AND ParameterID NOT IN ($placeholders)";
}

$stmt = $conn->prepare($sql);
// Bind parameters dynamically
$types = str_repeat('s', 1) . str_repeat('i', count($descendantIds));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    'data' => $data,
    'current_page' => 1,
    'total_pages' => 1
]);

?>


