<?php
require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$standardID = isset($_GET['standardID']) ? (int)$_GET['standardID'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$includeInactive = isset($_GET['includeInactive']) && (string)$_GET['includeInactive'] === '1';
$includeAll = isset($_GET['includeAll']) && (string)$_GET['includeAll'] === '1';
$perPage = 10;

if ($standardID <= 0) {
    echo json_encode([
        'data' => [],
        'current_page' => 1,
        'total_pages' => 0,
        'total_records' => 0,
        'error' => 'A valid StandardID is required.'
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($page < 1) {
    $page = 1;
}

$activeFilter = $includeInactive ? '' : ' AND tp.IsActive = 1 ';

$countSql = "SELECT COUNT(*) AS total
             FROM testparameters tp
             WHERE tp.StandardID = ?" . $activeFilter;
$countStmt = $conn->prepare($countSql);

if (!$countStmt) {
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'Failed to prepare parameter count query.'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$countStmt->bind_param('i', $standardID);
if (!$countStmt->execute()) {
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'Failed to count parameters.'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalRecords = (int)($countRow['total'] ?? 0);
$countStmt->close();

$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $perPage) : 0;
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = "SELECT
            ts.StandardName,
            tp.ParameterID,
            tp.StandardID,
            tp.ParameterName,
            tp.MinLimit,
            tp.MaxLimit,
            tp.Limits,
            tp.Method,
            tp.MRL,
            tp.MRLUnit,
            tp.Category,
            tp.BaseID,
            tp.UnitOfMeasure,
            tp.ResultType,
            tp.AccreditationStatus,
            tp.IsActive
        FROM testparameters tp
        INNER JOIN teststandards ts ON tp.StandardID = ts.StandardID
        WHERE tp.StandardID = ?" . $activeFilter . "
        ORDER BY ts.StandardName ASC, tp.ParameterName ASC";

if (!$includeAll) {
    $sql .= " LIMIT ? OFFSET ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'Failed to prepare parameters query.'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($includeAll) {
    $stmt->bind_param('i', $standardID);
} else {
    $stmt->bind_param('iii', $standardID, $perPage, $offset);
}
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'Failed to fetch parameters.'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'data' => $data,
    'current_page' => $page,
    'total_pages' => $totalPages,
    'total_records' => $totalRecords,
    'per_page' => $perPage
], JSON_INVALID_UTF8_SUBSTITUTE);

$stmt->close();
$conn->close();
?>