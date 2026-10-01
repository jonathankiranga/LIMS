<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$testCode = isset($_GET['test']) ? trim($_GET['test']) : '';
if (empty($testCode)) {
  echo json_encode(['success' => false, 'message' => 'Missing test parameter']);
  exit;
}

// Get test info
$stmt = mysqli_prepare($db, "SELECT itemcode, descrip, units FROM stockmaster WHERE itemcode = ?");
mysqli_stmt_bind_param($stmt, 's', $testCode);
mysqli_stmt_execute($stmt);
$testResult = mysqli_stmt_get_result($stmt);
$testInfo = mysqli_fetch_assoc($testResult);
mysqli_stmt_close($stmt);

if (!$testInfo) {
  echo json_encode(['success' => false, 'message' => 'Test not found']);
  exit;
}

// Get current PriceList price
$priceStmt = mysqli_prepare($db, "SELECT price FROM PriceList WHERE stockcode = ? AND (customerCode IS NULL OR customerCode = '') AND approved = 1 LIMIT 1");
mysqli_stmt_bind_param($priceStmt, 's', $testCode);
mysqli_stmt_execute($priceStmt);
$priceResult = mysqli_stmt_get_result($priceStmt);
$priceRow = mysqli_fetch_assoc($priceResult);
$currentPrice = $priceRow ? (float)$priceRow['price'] : null;
mysqli_stmt_close($priceStmt);

// Get mappings
$mapStmt = mysqli_prepare($db, "
  SELECT trm.id, trm.reagent_itemcode, sm.descrip AS reagent_descrip,
         COALESCE(sm.averagestock, 0) AS unit_cost, trm.quantity
  FROM test_reagent_mapping trm
  JOIN stockmaster sm ON sm.itemcode = trm.reagent_itemcode
  WHERE trm.test_itemcode = ?
  ORDER BY sm.descrip
");
mysqli_stmt_bind_param($mapStmt, 's', $testCode);
mysqli_stmt_execute($mapStmt);
$mapResult = mysqli_stmt_get_result($mapStmt);

$mappings = [];
$baseCost = 0;
while ($row = mysqli_fetch_assoc($mapResult)) {
  $subtotal = (float)$row['unit_cost'] * (float)$row['quantity'];
  $baseCost += $subtotal;
  $mappings[] = [
    'id'               => (int)$row['id'],
    'reagent_itemcode'  => trim($row['reagent_itemcode']),
    'reagent_descrip'   => trim($row['reagent_descrip']),
    'qty'               => (float)$row['quantity'],
    'unit_cost'         => (float)$row['unit_cost'],
    'subtotal'          => round($subtotal, 4)
  ];
}
mysqli_stmt_close($mapStmt);

echo json_encode([
  'success' => true,
  'data' => [
    'test' => [
      'itemcode' => trim($testInfo['itemcode']),
      'descrip'  => trim($testInfo['descrip']),
      'units'    => trim($testInfo['units'] ?? '')
    ],
    'current_pricelist_price' => $currentPrice,
    'base_cost' => round($baseCost, 4),
    'mappings' => $mappings
  ]
]);

mysqli_close($db);
