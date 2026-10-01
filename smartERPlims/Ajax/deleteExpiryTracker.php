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

$id = (int)$_POST['id'] ?? 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    return;
}

$sql = "DELETE FROM stock_expiry_tracker WHERE id = $id";

if (mysqli_query($db, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($db)]);
}