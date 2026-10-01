<?php
// Session handling for Blockchain LIMS
ini_set('display_errors', 0);
ini_set('session.gc_maxlifetime', 3600);
session_name('BlockchainLIMS');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$PathPrefix = dirname(__FILE__) . '/';
if (!file_exists($PathPrefix . 'include/config.php')){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));
    if ($RootPath == '/' OR $RootPath == "\\") {
        $RootPath = '';
    }
    header('Location:' . $RootPath . '/installer/index.php');
    exit;
}

$config = include($PathPrefix . 'include/config.php');

$db_host = $config['DB_HOST'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];
$db_name = $config['DB_NAME'];

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
}

function is_logged_in() {
    return isset($_SESSION['UserID']) && !empty($_SESSION['UserID']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}