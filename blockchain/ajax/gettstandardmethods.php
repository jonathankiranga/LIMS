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
 
// Query to fetch the items
$sql = "SELECT `MethodID`,
               `standard_method`  
        FROM `standard_methods`";
$result = $mysqli->query($sql);
$options = [];
while ($row = $result->fetch_assoc()) {
    $options[] = ['id' => $row['MethodID'], 'standard_method' =>  $row['standard_method']];
}
     


echo json_encode($options);
?>