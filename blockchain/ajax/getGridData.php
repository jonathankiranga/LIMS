<?php
require '../db_connection.php';

// --- Input ---
$page         = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
$filterColumn = isset($_POST['filterColumn']) ? $_POST['filterColumn'] : '';
$filterValue  = isset($_POST['filterValue']) ? trim($_POST['filterValue']) : '';
$category     = isset($_POST['category']) ? $_POST['category'] : '';

$limit  = 10;
$offset = ($page - 1) * $limit;
$params = array();
$types  = '';

/*
 * Subcontracted results use the existing assignment relationship and
 * eligibility rules from getassignedTestParameters2.php. The chemical and
 * biological grid query below remains unchanged in behaviour.
 */
if ($category === 'subcontracted') {
    $sampleID = isset($_POST['sampleID']) ? trim($_POST['sampleID']) : '';
    $userid   = isset($_POST['userid']) ? trim($_POST['userid']) : '';

    if ($sampleID === '' || $userid === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'data' => array(),
            'pagination' => array('current' => $page, 'total' => 0),
            'message' => 'Select a sample assigned to a subcontractor.'
        ));
        exit;
    }

    $sql = "SELECT
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
            JOIN sample_tests ST
                ON ST.TestID = TR.TestID
               AND ST.SampleID = TR.SampleID
            JOIN test_assignments ta
                ON ta.resultsID = TR.resultsID
            JOIN TestParameters tp
                ON TR.ParameterID = tp.ParameterID
               AND TR.StandardID = tp.StandardID
            LEFT JOIN baseparameters bp
                ON tp.BaseID = bp.ParameterID
            WHERE ST.SampleID = ?
              AND ta.subcontractor = ?
              AND tp.AccreditationStatus = 'contracted'
              AND TR.MRL_Result IS NULL
              AND TR.ResultStatus IS NULL
              AND TR.RangeResult IS NULL";

    $params = array($sampleID, $userid);
    $types = 'ss';
} else {
    $sql = "SELECT
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
            LEFT JOIN baseparameters bp
                ON tp.BaseID = bp.ParameterID
            WHERE TR.StatusID = 1
              AND tp.Category = ?";

    $params = array($category);
    $types = 's';
}

// --- Filtering ---
if ($filterColumn && $filterValue !== '') {
    switch ($filterColumn) {
        case 'SampleID':
            $sql .= " AND TR.SampleID LIKE ?";
            $params[] = '%' . $filterValue . '%';
            $types .= 's';
            break;

        case 'ParameterName':
            $sql .= " AND tp.ParameterName LIKE ?";
            $params[] = '%' . $filterValue . '%';
            $types .= 's';
            break;
    }
}

// Count unique result rows, including protection against duplicate assignment joins.
$countSql = "SELECT COUNT(*) FROM (" . $sql . " GROUP BY TR.resultsID) AS total";
$stmt = $conn->prepare($countSql);
if (!$stmt) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(array('data' => array(), 'message' => 'Unable to prepare result count.'));
    exit;
}

$countTypes = $types;
$countParams = $params;
$stmt->bind_param($countTypes, ...$countParams);
$stmt->execute();
$stmt->bind_result($totalRows);
$stmt->fetch();
$stmt->close();

$totalPages = (int) ceil($totalRows / $limit);

// --- Final query with pagination ---
$sql .= " GROUP BY TR.resultsID ORDER BY TR.SampleID DESC, tp.ParameterName ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(array('data' => array(), 'message' => 'Unable to prepare result query.'));
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = array(
        'SampleID' => $row['SampleID'],
        'ParameterName' => $row['ParameterName'],
        'TestID' => $row['TestID'],
        'ParameterID' => $row['ParameterID'],
        'StandardID' => $row['StandardID'],
        'resultsID' => $row['resultsID'],
        'ResultType' => $row['ResultType']
    );
}
$stmt->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'data' => $data,
    'pagination' => array(
        'current' => $page,
        'total' => $totalPages
    )
));
?>