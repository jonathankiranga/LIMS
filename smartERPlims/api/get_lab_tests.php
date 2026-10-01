<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$sql = "SELECT sm.itemcode, sm.descrip, sm.units,
    (SELECT price FROM PriceList 
     WHERE stockcode = sm.itemcode 
       AND (customerCode IS NULL OR customerCode = '') 
       AND approved = 1 
     LIMIT 1) AS pricelist_price
  FROM stockmaster sm
  WHERE (sm.inactive = 0 OR sm.inactive IS NULL)
    AND sm.isstock_1 = 1
  GROUP BY sm.itemcode
  ORDER BY sm.descrip";

$result = mysqli_query($db, $sql);
$tests = [];
while ($row = mysqli_fetch_assoc($result)) {
  $tests[] = [
    'itemcode' => trim($row['itemcode']),
    'descrip'  => trim($row['descrip']),
    'units'    => trim($row['units'] ?? ''),
    'pricelist_price' => $row['pricelist_price'] !== null ? (float)$row['pricelist_price'] : null
  ];
}

echo json_encode(['success' => true, 'data' => $tests]);
mysqli_close($db);
