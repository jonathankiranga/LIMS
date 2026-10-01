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

$itemcode = $_GET['itemcode'] ?? '';

if (empty($itemcode)) {
    echo json_encode([]);
    return;
}

$itemcode = mysqli_real_escape_string($db, $itemcode);

$sql = "SELECT 
            sr.rowid,
            sr.GRN,
            sr.expiry_date,
            COALESCE(sr.StockBalance, 0) as StockBalance,
            COALESCE(sr.StockIn, 0) as StockIn,
            COALESCE(sr.StockOut, 0) as StockOut,
            (
                SELECT COALESCE(SUM(fulqty * partperunit), 0) + COALESCE(SUM(loosqty), 0)
                FROM stockledger sl
                WHERE sl.itemcode = '$itemcode'
                  AND sl.invref = sr.GRN
            ) as ledger_balance
        FROM StockRegister sr
        WHERE sr.itemcode = '$itemcode'
          AND sr.StockIn > 0
        ORDER BY sr.GRN ASC";

$result = mysqli_query($db, $sql);
$items = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $balance = (float)$row['ledger_balance'];
        if ($balance > 0) {
            $items[] = [
                'rowid' => $row['rowid'],
                'GRN' => $row['GRN'],
                'expiry_date' => $row['expiry_date'],
                'StockBalance' => number_format($balance, 2, '.', '')
            ];
        }
    }
}

echo json_encode($items);