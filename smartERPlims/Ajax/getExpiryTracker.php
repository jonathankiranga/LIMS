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

$sql = "SELECT 
            set.id,
            set.itemcode,
            set.GRN,
            set.batch_reference,
            set.expiry_date,
            set.quantity,
            set.remaining_qty,
            sm.description
        FROM stock_expiry_tracker set
        LEFT JOIN stockmaster sm ON set.itemcode = sm.itemcode";

if (!empty($itemcode)) {
    $sql .= " WHERE set.itemcode = '" . mysqli_real_escape_string($db, $itemcode) . "'";
}

$sql .= " ORDER BY set.expiry_date ASC";

$result = mysqli_query($db, $sql);
$items = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
}

$today = date('Y-m-d');
$thirtyDays = date('Y-m-d', strtotime('+30 days'));

$stats = ['total' => 0, 'expired' => 0, 'expiring_soon' => 0, 'ok' => 0];

foreach ($items as $item) {
    $stats['total']++;
    if (!empty($item['expiry_date'])) {
        if ($item['expiry_date'] < $today) {
            $stats['expired']++;
        } elseif ($item['expiry_date'] <= $thirtyDays) {
            $stats['expiring_soon']++;
        } else {
            $stats['ok']++;
        }
    }
}

echo json_encode(['items' => $items, 'stats' => $stats]);