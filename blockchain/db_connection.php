<?php

$config = include('../include/config.php'); // Load the config file
// Database connection
$secretKey = $config['SECRET_KEY'];  // Access the SECRET_KEY from config
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host,$db_username,$db_password,$db_name);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$conn->set_charset('utf8mb4');

$conn->query("SET time_zone = '+03:00'");