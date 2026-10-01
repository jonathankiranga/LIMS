<?php
$config = include('include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
//require 'ajaxReports/sampletypes.php'; // Ensure this connects to your MySQLi instance
//require 'ajaxReports/laboratorystandards.php'; // Ensure this connects to your MySQLi instance
