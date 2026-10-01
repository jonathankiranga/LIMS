<?php
// MySQLi database connection
require '../db_connection.php'; // should define $conn as a mysqli connection

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$offset = ($page - 1) * $limit;
// Prepare SQL query
// 🔎 Optional search filter
$searchSql = "";
if (!empty($search)) {
    $searchSql = "WHERE 
        s.StandardName LIKE '%$search%' OR 
        p.ParameterName LIKE '%$search%' OR 
        m.ParameterName LIKE '%$search%' OR 
        c.Notes LIKE '%$search%'";
}

// 🧮 Total count for pagination
$totalSql = "
    SELECT COUNT(*) as total
    FROM standard_parameter_matrix_config c
    JOIN teststandards s ON c.StandardID = s.StandardID
    JOIN testparameters p ON c.ParameterID = p.ParameterID
    JOIN parametermatrix m ON c.MatrixID = m.ParameterID
    $searchSql
";

$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['total'] ?? 0;

// 📄 Paginated query
$dataSql = "
    SELECT 
        c.ConfigID,
        c.StandardID,
        s.StandardName,
        c.ParameterID,
        p.ParameterName,
        c.MatrixID,
        m.ParameterName AS MatrixName,
        c.IsDefault,
        c.Notes,
        c.CreatedAt,
        c.UpdatedAt
    FROM standard_parameter_matrix_config c
    JOIN teststandards s ON c.StandardID = s.StandardID
    JOIN testparameters p ON c.ParameterID = p.ParameterID
    JOIN parametermatrix m ON c.MatrixID = m.ParameterID
    $searchSql
    ORDER BY s.StandardName, MatrixName, p.ParameterName
    LIMIT $limit OFFSET $offset
";


$result = $conn->query($dataSql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Send JSON response
header('Content-Type: application/json');

echo json_encode([
    "rows" => $rows,
    "total" => $total,
    "page" => $page
]);
?>
