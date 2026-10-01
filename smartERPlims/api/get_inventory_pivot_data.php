<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$periodNo = isset($_GET['period_no']) ? (int)$_GET['period_no'] : 0;

$r = mysqli_query($db, "SELECT MIN(start_date) AS cur_start, MAX(end_date) AS cur_end FROM financialperiods WHERE periodno = $periodNo");
$dates = mysqli_fetch_assoc($r);
if (!$dates || !$dates['cur_start']) {
  echo json_encode(['success' => false, 'message' => 'Invalid period']);
  mysqli_close($db);
  exit;
}
$cs = $dates['cur_start'];
$ce = $dates['cur_end'];

$today = date('Y-m-d');
$r = mysqli_query($db, "SELECT Name FROM financialperiods WHERE '$today' BETWEEN start_date AND end_date LIMIT 1");
$cmRow = mysqli_fetch_assoc($r);
$currentMonthName = $cmRow ? $cmRow['Name'] : date('F');

$monthNames = [];
$r = mysqli_query($db, "SELECT Name FROM financialperiods WHERE periodno = $periodNo ORDER BY start_date");
while ($row = mysqli_fetch_assoc($r)) {
  $monthNames[] = $row['Name'];
}
$months = array_merge(['Opening'], $monthNames);
$quarters = ['Opening', 'Q1', 'Q2', 'Q3', 'Q4'];

// Per-item+store list
$items = [];
$r = mysqli_query($db, "SELECT sm.itemcode, sm.descrip, IFNULL(sm.category,'') AS category, COALESCE(sc.categorydescription, sm.category, 'Uncategorized') AS category_name, IFNULL(u.code,'') AS unit_code, IFNULL(u.descrip,'') AS unit_name
  FROM stockmaster sm
  LEFT JOIN stockcategory sc ON sc.categoryid = sm.category
  LEFT JOIN unit u ON u.code = sm.units
  WHERE (sm.isstock_1 = 1 OR sm.isstock_2 = 1 OR sm.isstock_4 = 1 OR sm.isstock_5 = 1 OR sm.isstock_6 = 1)
    AND (sm.inactive IS NULL OR sm.inactive = 0)
  ORDER BY sm.descrip");
while ($row = mysqli_fetch_assoc($r)) {
  $items[] = $row;
}

// Per-item+store+month aggregation
$monthActivity = [];
$r = mysqli_query($db, "SELECT sl.itemcode, COALESCE(sl.store,'') AS store, fp.Name AS month_name,
  SUM(COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0)) AS qty,
  SUM(COALESCE(sl.stockvalue,0)) AS val
  FROM stockledger sl
  JOIN financialperiods fp ON sl.`date` BETWEEN fp.start_date AND fp.end_date
  WHERE fp.periodno = $periodNo
  GROUP BY sl.itemcode, sl.store, fp.Name");
while ($row = mysqli_fetch_assoc($r)) {
  $k = $row['itemcode'] . '|' . $row['store'];
  $monthActivity[$k][$row['month_name']] = ['qty' => (float)$row['qty'], 'val' => (float)$row['val']];
}

// Per-item+store opening balances
$openings = [];
$r = mysqli_query($db, "SELECT sl.itemcode, COALESCE(sl.store,'') AS store,
  SUM(COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0)) AS qty,
  SUM(COALESCE(sl.stockvalue,0)) AS val
  FROM stockledger sl
  WHERE sl.`date` < '$cs'
  GROUP BY sl.itemcode, sl.store");
while ($row = mysqli_fetch_assoc($r)) {
  $k = $row['itemcode'] . '|' . $row['store'];
  $openings[$k] = ['qty' => (float)$row['qty'], 'val' => (float)$row['val']];
}

// Get distinct stores for items that have ledger entries
$storeMap = [];
$r = mysqli_query($db, "SELECT DISTINCT sl.itemcode, COALESCE(sl.store,'') AS store, COALESCE(st.Storename,'') AS store_name
  FROM stockledger sl
  LEFT JOIN stores st ON st.code = sl.store
  WHERE sl.itemcode IN (SELECT itemcode FROM stockmaster WHERE (isstock_1=1 OR isstock_2=1 OR isstock_4=1 OR isstock_5=1 OR isstock_6=1) AND (inactive IS NULL OR inactive=0))");
while ($row = mysqli_fetch_assoc($r)) {
  $storeMap[$row['itemcode']][$row['store']] = $row['store_name'];
}

// Build 13 rows per item+store
$data = [];
foreach ($items as $item) {
  $ic = $item['itemcode'];
  $desc = trim($item['descrip'] ?? '');
  $cat = $item['category'];
  $catName = $item['category_name'];
  $uCode = $item['unit_code'];
  $uName = $item['unit_name'];

  $stores = isset($storeMap[$ic]) ? $storeMap[$ic] : ['' => ''];

  foreach ($stores as $sCode => $sName) {
    $key = $ic . '|' . $sCode;
    $op = $openings[$key] ?? ['qty' => 0, 'val' => 0];
    $act = $monthActivity[$key] ?? [];

    // Opening row
    $data[] = [
      'itemcode'      => $ic,
      'description'   => $desc,
      'category'      => $cat,
      'category_name' => $catName,
      'store_code'    => $sCode,
      'store_name'    => $sName,
      'unit_code'     => $uCode,
      'unit_name'     => $uName,
      'qty'           => (float)round($op['qty']),
      'value'         => $op['val'],
      'period_month'  => 'Opening',
      'period_quarter' => 'Opening'
    ];

    // Monthly rows
    foreach ($monthNames as $idx => $mn) {
      $q = 'Q' . (floor($idx / 3) + 1);
      $m = $act[$mn] ?? ['qty' => 0, 'val' => 0];
      $data[] = [
        'itemcode'      => $ic,
        'description'   => $desc,
        'category'      => $cat,
        'category_name' => $catName,
        'store_code'    => $sCode,
        'store_name'    => $sName,
        'unit_code'     => $uCode,
        'unit_name'     => $uName,
        'qty'           => (float)round($m['qty']),
          'value'         => $m['val'],
        'period_month'  => $mn,
        'period_quarter' => $q
      ];
    }
  }
}

echo json_encode([
  'success' => true,
  'data' => $data,
  'current_month' => $currentMonthName,
  'months' => $months,
  'quarters' => $quarters,
  'period' => ['periodno' => $periodNo, 'cur_start' => $cs, 'cur_end' => $ce]
]);

mysqli_close($db);
