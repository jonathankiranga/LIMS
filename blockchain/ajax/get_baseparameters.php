<?php
require '../db_connection.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = $_GET['search'] ?? '';
$filterType = $_GET['resultType'] ?? '';
$filterCat = $_GET['category'] ?? '';
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT bp.*, COUNT(tp.ParameterID) AS linkedTests
        FROM baseparameters bp
        LEFT JOIN testparameters tp ON tp.BaseID = bp.ParameterID
        WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM baseparameters bp WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $sql .= " AND bp.ParameterName LIKE ?";
    $countSql .= " AND bp.ParameterName LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($filterType === 'none') {
    $sql .= " AND (bp.ResultType IS NULL OR bp.ResultType = '')";
    $countSql .= " AND (bp.ResultType IS NULL OR bp.ResultType = '')";
} elseif ($filterType) {
    $sql .= " AND bp.ResultType = ?";
    $countSql .= " AND bp.ResultType = ?";
    $params[] = $filterType;
    $types .= 's';
}

$sql .= " GROUP BY bp.ParameterID ORDER BY bp.ParameterName LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($countSql);
if ($params && count($params) === ($types === 's' ? 1 : ($types === 'si' ? 2 : 0))) {
    $stmt->bind_param(substr($types, 0, -2), ...array_slice($params, 0, -2));
}
$stmt->execute();
$stmt->bind_result($totalRows);
$stmt->fetch();
$stmt->close();

$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$parameters = [];
while ($row = $result->fetch_assoc()) {
    $parameters[] = $row;
}

echo json_encode([
    'success' => true,
    'parameters' => $parameters,
    'totalPages' => $totalPages,
    'currentPage' => $page
], JSON_INVALID_UTF8_SUBSTITUTE);