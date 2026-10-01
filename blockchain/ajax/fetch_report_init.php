<?php
$config = include('../include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);
// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
// Get search term
$searchTerm = isset($_POST['searchTerm']) ? $mysqli->real_escape_string($_POST['searchTerm']) : '';
$page = $_POST['page'] ?? 1;
$limit = $_POST['limit'] ?? 20;
$offset = ($page - 1) * $limit;
// Query to fetch the items
$sql = "SELECT `HeaderID`,
               `DocumentNo`,
               `CustomerName`  
        FROM `sample_header`
        WHERE (DocumentNo LIKE '%$searchTerm%' or CustomerName LIKE '%$searchTerm%')
        LIMIT $limit OFFSET $offset";
$result = $mysqli->query($sql);
$options = [];
while ($row = $result->fetch_assoc()) {
    $options[] = ['id' => $row['HeaderID'], 'text' =>  $row['DocumentNo']. ' - ' .$row['CustomerName']];
}
     
// Query to check if more pages exist
$totalResult = $mysqli->query("SELECT COUNT(*) as total FROM sample_header");
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'];
$hasMore = ($totalItems > ($page * $limit));

echo json_encode(['items' => $options, 'hasMore' => $hasMore]);
?>