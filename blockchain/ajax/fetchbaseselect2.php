<?php
// Database connection
require '../db_connection.php'; 

// Support fetching by ID (for auto-fill)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM baseparameters WHERE ParameterID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$row, 'data' => $row], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$page       = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit      = 10;
$offset     = ($page - 1) * $limit;

$where = '';
$bindTypes = '';
$bindValues = [];

if ($searchTerm !== '') {
    $where = "WHERE ParameterName LIKE ? ";
    $searchPattern = '%' . $searchTerm . '%';
    $bindTypes = 's';
    $bindValues = [$searchPattern];
}

$countSql = "SELECT COUNT(*) FROM baseparameters $where";
$stmt = $conn->prepare($countSql);
if ($searchTerm !== '') {
    $stmt->bind_param($bindTypes, ...$bindValues);
}
$stmt->execute();
$stmt->bind_result($totalRecords);
$stmt->fetch();
$stmt->close();
$totalPages = ceil($totalRecords / $limit);

$where      = '';
$bindTypes  = '';
$bindValues = [];
if ($searchTerm !== '') {
    $where = "WHERE  ts.ParameterName LIKE ? ";
    $searchPattern = '%' . $searchTerm . '%';
    $bindTypes = 'sii';
    $bindValues = [$searchPattern, $offset,$limit];
}else{
    $bindTypes = 'ii';
    $bindValues = [$offset,$limit];
}

$query = $conn->prepare("SELECT  ts.*  FROM baseparameters ts  $where LIMIT ?, ?");
$query->bind_param($bindTypes, ...$bindValues);
$query->execute();
$result = $query->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
       $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    'data' => $data,
    'current_page' => $page,
    'total_pages' => $totalPages,
], JSON_INVALID_UTF8_SUBSTITUTE);
?>
