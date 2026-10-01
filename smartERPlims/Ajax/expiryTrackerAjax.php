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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    getExpiryTracker();
} elseif ($action === 'save') {
    saveExpiryTracker();
} elseif ($action === 'delete') {
    deleteExpiryTracker();
}

function getExpiryTracker() {
    global $db;
    
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
    
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
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
}

function saveExpiryTracker() {
    global $db;
    
    $itemcode = $_POST['itemcode'] ?? '';
    $GRN = $_POST['GRN'] ?? '';
    $batch_reference = $_POST['batch_reference'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $quantity = (float)$_POST['quantity'] ?? 0;
    $id = (int)$_POST['id'] ?? 0;
    
    if (empty($itemcode) || empty($expiry_date) || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
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
}

function deleteExpiryTracker() {
    global $db;
    
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
}