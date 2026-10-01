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

$itemcode = $_POST['itemcode'] ?? '';
$rowid = (int)($_POST['rowid'] ?? 0);
$GRN = $_POST['GRN'] ?? '';
$batch_reference = $_POST['batch_reference'] ?? '';
$expiry_date = $_POST['expiry_date'] ?? '';
$quantity = (float)($_POST['quantity'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

if (empty($itemcode) || empty($expiry_date) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    return;
}

if ($rowid > 0) {
    $checkSql = "SELECT StockBalance FROM StockRegister WHERE rowid = $rowid AND itemcode = '" . mysqli_real_escape_string($db, $itemcode) . "'";
    $checkResult = mysqli_query($db, $checkSql);
    $stockRow = mysqli_fetch_assoc($checkResult);
    $availableBalance = (float)$stockRow['StockBalance'];
    
    if ($quantity > $availableBalance) {
        echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock balance of ' . $availableBalance]);
        return;
    }
}

$itemcode = mysqli_real_escape_string($db, $itemcode);
$GRN = mysqli_real_escape_string($db, $GRN);
$batch_reference = mysqli_real_escape_string($db, $batch_reference);
$expiry_date = mysqli_real_escape_string($db, $expiry_date);

if ($id > 0) {
    $sql = "UPDATE stock_expiry_tracker 
           SET itemcode = '$itemcode',
               GRN = '$GRN',
               batch_reference = '$batch_reference',
               expiry_date = '$expiry_date',
               quantity = $quantity,
               remaining_qty = $quantity
           WHERE id = $id";
} else {
    $sql = "INSERT INTO stock_expiry_tracker (itemcode, GRN, batch_reference, expiry_date, quantity, remaining_qty)
            VALUES ('$itemcode', '$GRN', '$batch_reference', '$expiry_date', $quantity, $quantity)";
}

if (mysqli_query($db, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($db)]);
}