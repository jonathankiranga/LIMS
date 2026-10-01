<?php
session_write_close();
session_name('ErpWithCRM');
session_start();

include('../config.php');
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
mysqli_set_charset($db, 'utf8mb4');

header('Content-Type: application/json');

$itemcode = $_POST['itemcode'] ?? '';
$category = $_POST['category'] ?? '';

if (empty($itemcode) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Missing itemcode or category']);
    exit;
}

$itemcodeEsc = mysqli_real_escape_string($db, $itemcode);
$categoryEsc = mysqli_real_escape_string($db, $category);

$check = mysqli_query($db, "SELECT categoryid FROM stockcategory WHERE categoryid = '$categoryEsc'");
if (mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category']);
    exit;
}

$sql = "UPDATE stockmaster SET category = '$categoryEsc' WHERE itemcode = '$itemcodeEsc'";

if (mysqli_query($db, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Category updated']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($db)]);
}
