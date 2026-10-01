<?php
session_write_close();
session_name('ErpWithCRM');
session_start();

include('../config.php');
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);
if (!$db) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
mysqli_set_charset($db, 'utf8mb4');

header('Content-Type: application/json');

$term = $_GET['q'] ?? $_GET['term'] ?? '';

$term = mysqli_real_escape_string($db, $term);

$sql = "SELECT *
       FROM stockmaster 
       WHERE isstock_2 = 1";

if (!empty($term)) {
    $sql .= " AND (itemcode LIKE '%$term%' OR descrip LIKE '%$term%')";
}

$sql .= " ORDER BY descrip ASC LIMIT 50";

$result = mysqli_query($db, $sql);
$items = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => $row['itemcode'],
            'text' => $row['itemcode'] . ' - ' . $row['descrip']
        ];
    }
}

echo json_encode($items);