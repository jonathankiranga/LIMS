<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$sql = "SELECT itemcode, descrip, COALESCE(averagestock, 0) AS unit_cost, units
  FROM stockmaster
  WHERE (inactive = 0 OR inactive IS NULL)
    AND isstock_2 = 1
  ORDER BY descrip";

$result = mysqli_query($db, $sql);
$reagents = [];
while ($row = mysqli_fetch_assoc($result)) {
  $reagents[] = [
    'itemcode' => trim($row['itemcode']),
    'descrip'  => trim($row['descrip']),
    'unit_cost' => (float)$row['unit_cost'],
    'units'    => trim($row['units'] ?? '')
  ];
}

echo json_encode(['success' => true, 'data' => $reagents]);
mysqli_close($db);
