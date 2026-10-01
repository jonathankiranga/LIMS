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

$currencycode = $_GET['currencycode'] ?? '';

if (empty($currencycode)) {
    echo json_encode([]);
    exit;
}

$SQL = "SELECT accountcode, bankName, BranchName, currency FROM BankAccounts WHERE currency='" . mysqli_real_escape_string($db, $currencycode) . "'";
$result = mysqli_query($db, $SQL);

$banks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $banks[] = $row;
}

echo json_encode($banks);