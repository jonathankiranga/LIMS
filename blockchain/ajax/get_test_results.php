<?php
require '../db_connection.php';

$statusID = isset($_GET['statusID']) ? (int)$_GET['statusID'] : 0;
$department = strtolower(trim($_GET['department'] ?? ''));
$groupBySample = isset($_GET['groupBySample']) && (int)$_GET['groupBySample'] === 1;
$wholeSample = isset($_GET['wholeSample']) && (int)$_GET['wholeSample'] === 1;

$allowedDepartments = ['microbiological', 'chemical'];
if (!$wholeSample && !in_array($department, $allowedDepartments, true)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid department supplied by the calling program.']);
    exit;
}

$sql = "
    SELECT
        st.*,
        sp.*,
        tr.*,
        tp.*,
        ts.*,
        COALESCE(bp.ResultType, tp.ResultType, 'quantitativeField') AS ResultType,
        tp.Category AS ParamCategory
    FROM test_results tr
    JOIN Sample_Tests st ON tr.TestID = st.TestID
    JOIN Sample_Header sp ON tr.HeaderID = sp.HeaderID
    JOIN testparameters tp
      ON tp.ParameterID = tr.ParameterID
     AND tp.StandardID = tr.StandardID
    JOIN teststandards ts ON ts.StandardID = tr.StandardID
    LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
    WHERE tr.StatusID = ?
";

$params = [$statusID];
$types = 'i';

if (!$wholeSample) {
    $sql .= " AND tp.Category = ?";
    $params[] = $department;
    $types .= 's';
}

$sql .= " ORDER BY sp.SampleID, tr.HeaderID, tr.resultsID";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Query execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$groups = [];

while ($row = $result->fetch_assoc()) {
    if (!$groupBySample) {
        $groups[] = $row;
        continue;
    }

    /*
     * HeaderID is part of the grouping key. The UI still presents one
     * block for each SampleID, while preventing two headers with a reused
     * SampleID from being mixed.
     */
    $key = (string)$row['HeaderID'];

    if (!isset($groups[$key])) {
        $groups[$key] = [
            'HeaderID' => $row['HeaderID'],
            'SampleID' => $row['SampleID'],
            'DocumentNo' => $row['DocumentNo'] ?? '',
            'Date' => $row['Date'] ?? '',
            'tests' => []
        ];
    }

    $groups[$key]['tests'][] = $row;
}

if ($groupBySample) {
    $groups = array_values($groups);
}

header('Content-Type: application/json');
echo json_encode([
    'success' => count($groups) > 0,
    'results' => $groups
]);

$stmt->close();
$conn->close();
