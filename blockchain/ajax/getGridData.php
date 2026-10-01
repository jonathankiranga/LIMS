<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// --- Input ---
$page         = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$filterColumn = $_POST['filterColumn'] ?? '';
$filterValue  = trim($_POST['filterValue'] ?? '');
$category     = $_POST['category'] ?? '';

$limit  = 10; 
$offset = ($page - 1) * $limit;

// --- Base Query ---



$sql ="SELECT 
    TR.resultsID, 
    TR.TestID, 
    TR.HeaderID,   
    TR.SampleID,  
    tp.StandardID,  
    tp.ParameterID,
    tp.ParameterName,
    COALESCE(bp.ResultType, tp.ResultType, 'quantitativeField') AS ResultType,
    TR.StatusID
FROM test_results TR
JOIN TestParameters tp 
    ON TR.ParameterID = tp.ParameterID 
    AND TR.StandardID = tp.StandardID
JOIN TestStandards ts 
    ON TR.StandardID = ts.StandardID
LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
WHERE TR.StatusID = 1 
  AND tp.Category = ?";
$params = [$category];
$types  = 's';

// --- Filtering ---
if ($filterColumn && $filterValue !== '') {
    switch ($filterColumn) {
        case 'SampleID':
            $sql .= " AND TR.SampleID LIKE ? ";
            $params[] = "%{$filterValue}%";
            $types   .= 's';
            break;

        case 'ParameterName':
            $sql .= " AND tp.ParameterName LIKE ? ";
            $params[] = "%{$filterValue}%";
            $types   .= 's';
            break;
    }
}

// --- Count total ---
$countSql = "SELECT COUNT(*) FROM ($sql) as total";
$stmt = $conn->prepare($countSql);
if (!$stmt) {
    die("Count prepare failed: " . $conn->error . " — SQL: " . $countSql);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($totalRows);
$stmt->fetch();
$stmt->close();

$totalPages = ceil($totalRows / $limit);

// --- Final query with LIMIT ---
$sql .= " group by resultsID ORDER BY TR.SampleID DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- Collect raw data ---
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'SampleID'      => $row['SampleID'],
        'ParameterName' => $row['ParameterName'],
        'TestID'        => $row['TestID'],
        'ParameterID'   => $row['ParameterID'],
        'StandardID'    => $row['StandardID'],
        'resultsID'     => $row['resultsID'],
        'ResultType'    => $row['ResultType']
    ];
}
$stmt->close();

// --- Return JSON ---
echo json_encode([
    'data'       => $data,
    'pagination' => [
        'current' => $page,
        'total'   => $totalPages
    ]
]);
  
