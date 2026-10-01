<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance


$query = "SELECT confname, confvalue FROM config";
$result = $conn->query($query);

$configs = [];
while ($row = $result->fetch_assoc()) {
    $configs[] = $row;
}

echo json_encode($configs);
?>