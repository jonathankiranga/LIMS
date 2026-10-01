<?php
// Database connection
require '../db_connection.php'; 
// Ensure this connects to your PDO instance and sets $pdo
// Retrieve input values
$page       = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit      = 10; // records per page
$offset     = ($page - 1) * $limit;
$hasSearch  = !empty($searchTerm);


$where = '';
$bindTypes = '';
$bindValues = [];

if ($searchTerm !== '') {
    $where = "WHERE ParameterName LIKE ? ";
    $searchPattern = '%' . $searchTerm . '%';
    $bindTypes = 's';
    $bindValues = [$searchPattern];
}

// Build the SQL query for counting total records
$countSql = "SELECT COUNT(*) FROM ParameterMatrix $where";
$stmt = $conn->prepare($countSql);

// Bind parameters if a search term was provided
if ($searchTerm !== '') {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

// Execute the statement
$stmt->execute();
$stmt->bind_result($totalRecords);
$stmt->fetch();
$stmt->close();
$totalPages = ceil($totalRecords / $limit);


$sqlquery='';

if($hasSearch){

$sqlquery = "
    WITH RECURSIVE ParameterHierarchy AS (
        SELECT 
            ParameterID,
            ParameterName,
            ParentID,
            CAST(ParameterName AS CHAR(255)) AS FullPath,
            0 AS Level
        FROM 
            ParameterMatrix
        WHERE 
            ParentID IS NULL 

        UNION ALL

        SELECT 
            pm.ParameterID,
            pm.ParameterName,
            pm.ParentID,
            CONCAT(ph.FullPath, ' -> ', pm.ParameterName) AS FullPath,
            ph.Level + 1
        FROM 
            ParameterMatrix pm
        INNER JOIN 
            ParameterHierarchy ph ON pm.ParentID = ph.ParameterID
    )
    SELECT * FROM ParameterHierarchy
    WHERE ParameterName LIKE ? OR FullPath LIKE ?
    ORDER BY FullPath
    LIMIT ?, ?
";

$query = $conn->prepare($sqlquery);
$bindTypes = "ssii"; // 2 strings, 2 integers
$query->bind_param($bindTypes,$searchPattern,$searchPattern, $offset, $limit);
}else{
    
$sqlquery = "
    WITH RECURSIVE ParameterHierarchy AS (
        SELECT 
            ParameterID,
            ParameterName,
            ParentID,
            CAST(ParameterName AS CHAR(255)) AS FullPath,
            0 AS Level
        FROM 
            ParameterMatrix
        WHERE 
            ParentID IS NULL

        UNION ALL

        SELECT 
            pm.ParameterID,
            pm.ParameterName,
            pm.ParentID,
            CONCAT(ph.FullPath, ' -> ', pm.ParameterName) AS FullPath,
            ph.Level + 1
        FROM 
            ParameterMatrix pm
        INNER JOIN 
            ParameterHierarchy ph ON pm.ParentID = ph.ParameterID
    )
    SELECT * FROM ParameterHierarchy
    ORDER BY FullPath
    LIMIT ?, ?
";

$query = $conn->prepare($sqlquery);
$bindTypes = "ii"; // 2 strings, 2 integers
$query->bind_param($bindTypes, $offset, $limit);
}

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
    'sql'=> $searchTerm
]);

?>


