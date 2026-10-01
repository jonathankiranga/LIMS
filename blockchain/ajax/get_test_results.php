<?php
require '../db_connection.php';

// Get parameters
$statusID = $_GET['statusID'] ?? 0;
$department = $_GET['department'] ?? ''; // chemical, microbiological, admin, guest

// Build query with department filtering
// ResultType resolution priority:
// 1. testparameters.ResultType (explicit override)
// 2. baseparameters.ResultType (via tp.BaseID = bp.ParameterID)
// 3. Default 'quantitativeField'

$sql = "
    SELECT DISTINCT 
        st.*, sp.*, tr.*, tp.*, ts.*,
        COALESCE(bp.ResultType, tp.ResultType, 'quantitativeField') AS ResultType,
        tp.Category AS ParamCategory
    FROM test_results tr 
    JOIN Sample_Tests st ON tr.TestID = st.TestID
    JOIN Sample_Header sp ON tr.HeaderID = sp.HeaderID
    JOIN testparameters tp ON tp.ParameterID = tr.ParameterID AND tp.StandardID = tr.StandardID
    JOIN teststandards ts ON ts.StandardID = tr.StandardID
    LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
    WHERE tr.StatusID = ?
";

// Add department filter
$params = [$statusID];
$types = 'i';

if ($department && $department !== 'admin' && $department !== 'guest') {
    $sql .= " AND tp.Category = ?";
    $params[] = $department;
    $types .= 's';
}
// For 'admin' - no filter (see all)
// For 'guest' - could add filter to show none, or handle at frontend

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$response = ['success' => false, 'results' => []];
if ($result->num_rows > 0) {
    $response['success'] = true;
    while ($row = $result->fetch_assoc()) {
        // ResultType already resolved in SQL via COALESCE
        $response['results'][] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($response);