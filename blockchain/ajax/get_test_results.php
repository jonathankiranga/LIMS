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
        sp.*,
        tr.*,
        tp.*,
        ts.*,
        COALESCE(bp.ResultType, tp.ResultType, 'quantitativeField') AS ResultType,
        tp.Category AS ParamCategory
    FROM test_results tr
    JOIN sample_header sp ON tr.HeaderID = sp.HeaderID
    LEFT JOIN testparameters tp
      ON tp.StandardID = tr.StandardID
     AND tp.ParameterID = COALESCE(
          (
              SELECT exact_tp.ParameterID
              FROM testparameters exact_tp
              WHERE exact_tp.ParameterID = tr.ParameterID
                AND exact_tp.StandardID = tr.StandardID
              LIMIT 1
          ),
          (
              SELECT MIN(base_tp.ParameterID)
              FROM testparameters base_tp
              WHERE base_tp.BaseID = tr.ParameterID
                AND base_tp.StandardID = tr.StandardID
                AND (
                    SELECT COUNT(DISTINCT category_check.Category)
                    FROM testparameters category_check
                    WHERE category_check.BaseID = tr.ParameterID
                      AND category_check.StandardID = tr.StandardID
                ) = 1
          )
     )
    LEFT JOIN teststandards ts ON ts.StandardID = tr.StandardID
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

$sql .= " ORDER BY tr.HeaderID, tr.resultsID";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error]);
    exit;
}

$bindParams = [$types];
foreach ($params as $paramKey => $paramValue) {
    $bindParams[] = &$params[$paramKey];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);

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
    'message' => count($groups) > 0 ? '' : 'No test results found for StatusID ' . $statusID . '. The query returned no matching rows.',
    'results' => $groups
]);

$stmt->close();
$conn->close();
