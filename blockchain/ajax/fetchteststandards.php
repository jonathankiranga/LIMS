<?php
require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$contractedOnly = isset($_GET['contractedOnly']) && (string)$_GET['contractedOnly'] === '1';
$limit = 10;
$offset = ($page - 1) * $limit;

$conditions = [];
if ($searchTerm !== '') {
    $conditions[] = "(ts.StandardID LIKE ? OR ts.StandardCode LIKE ? OR ts.StandardName LIKE ? OR ts.Description LIKE ?)";
}
if ($contractedOnly) {
    $conditions[] = "EXISTS (
        SELECT 1 FROM testparameters tp
        WHERE tp.StandardID = ts.StandardID
          AND tp.AccreditationStatus = 'contracted'
    )";
}
$where = count($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) AS total FROM TestStandards ts" . $where;
$countStmt = $conn->prepare($countSql);
if (!$countStmt) {
    http_response_code(500);
    echo json_encode(['data' => [], 'total_pages' => 0, 'message' => 'Failed to prepare standards count query.']);
    exit;
}
if ($searchTerm !== '') {
    $pattern = '%' . $searchTerm . '%';
    $countStmt->bind_param('ssss', $pattern, $pattern, $pattern, $pattern);
}
if (!$countStmt->execute()) {
    http_response_code(500);
    echo json_encode(['data' => [], 'total_pages' => 0, 'message' => 'Failed to count standards.']);
    exit;
}
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalRecords = (int)($countRow['total'] ?? 0);
$countStmt->close();
$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 0;

$sql = "SELECT ts.*, sm.*
        FROM TestStandards ts
        LEFT JOIN standard_methods sm ON ts.sm = sm.MethodID" . $where . "
        ORDER BY ts.StandardName ASC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['data' => [], 'total_pages' => 0, 'message' => 'Failed to prepare standards query.']);
    exit;
}
if ($searchTerm !== '') {
    $pattern = '%' . $searchTerm . '%';
    $stmt->bind_param('ssssii', $pattern, $pattern, $pattern, $pattern, $limit, $offset);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['data' => [], 'total_pages' => 0, 'message' => 'Failed to fetch standards.']);
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
    'total_records' => $totalRecords
], JSON_INVALID_UTF8_SUBSTITUTE);
$stmt->close();
$conn->close();
?>