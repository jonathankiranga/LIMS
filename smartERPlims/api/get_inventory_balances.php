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
if ($periodNo <= 0) {
  $r = mysqli_query($db, "SELECT MAX(periodno) AS maxp FROM financialperiods WHERE start_date <= CURDATE()");
  $row = mysqli_fetch_assoc($r);
  $periodNo = (int)($row['maxp'] ?? 0);
  if ($periodNo <= 0) {
    $r = mysqli_query($db, "SELECT MAX(periodno) AS maxp FROM financialperiods");
    $row = mysqli_fetch_assoc($r);
    $periodNo = (int)($row['maxp'] ?? 0);
  }
}

$r = mysqli_query($db, "SELECT MIN(start_date) AS cur_start, MAX(end_date) AS cur_end FROM financialperiods WHERE periodno = $periodNo");
$dates = mysqli_fetch_assoc($r);
if (!$dates || !$dates['cur_start']) {
  echo json_encode(['success' => false, 'message' => 'Invalid period']);
  mysqli_close($db);
  exit;
}
$cs = $dates['cur_start'];
$ce = $dates['cur_end'];

$sql = "SELECT
  sm.itemcode,
  sm.descrip,
  IFNULL(sm.category,'') AS category,
  COALESCE(sc.categorydescription, sm.category, 'Uncategorized') AS category_name,
  COALESCE(sl.store,'') AS store_code,
  IFNULL(st.Storename,'') AS store_name,
  IFNULL(u.code,'') AS unit_code,
  IFNULL(u.descrip,'') AS unit_name,
  COALESCE(sm.partperunit,1) AS partperunit,
  COALESCE(sm.averagestock,0) AS averagestock,
  COALESCE(SUM(CASE WHEN sl.`date` < '$cs' THEN COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0) ELSE 0 END),0) AS opening_qty,
  COALESCE(SUM(CASE WHEN sl.`date` < '$cs' THEN COALESCE(sl.stockvalue,0) ELSE 0 END),0) AS opening_value,
  COALESCE(SUM(CASE WHEN sl.`date` BETWEEN '$cs' AND '$ce' AND (COALESCE(sl.fulqty,0) > 0 OR COALESCE(sl.loosqty,0) > 0) THEN COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0) ELSE 0 END),0) AS in_qty,
  COALESCE(SUM(CASE WHEN sl.`date` BETWEEN '$cs' AND '$ce' AND (COALESCE(sl.fulqty,0) > 0 OR COALESCE(sl.loosqty,0) > 0) THEN COALESCE(sl.stockvalue,0) ELSE 0 END),0) AS in_value,
  COALESCE(SUM(CASE WHEN sl.`date` BETWEEN '$cs' AND '$ce' AND (COALESCE(sl.fulqty,0) < 0 OR COALESCE(sl.loosqty,0) < 0) THEN ABS(COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0)) ELSE 0 END),0) AS out_qty,
  COALESCE(SUM(CASE WHEN sl.`date` BETWEEN '$cs' AND '$ce' AND (COALESCE(sl.fulqty,0) < 0 OR COALESCE(sl.loosqty,0) < 0) THEN ABS(COALESCE(sl.stockvalue,0)) ELSE 0 END),0) AS out_value,
  COALESCE(SUM(CASE WHEN sl.`date` <= '$ce' THEN COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0) ELSE 0 END),0) AS closing_qty,
  COALESCE(SUM(CASE WHEN sl.`date` <= '$ce' THEN COALESCE(sl.stockvalue,0) ELSE 0 END),0) AS closing_value
FROM stockmaster sm
LEFT JOIN stockledger sl ON sl.itemcode = sm.itemcode
LEFT JOIN stockcategory sc ON sc.categoryid = sm.category
LEFT JOIN stores st ON st.code = sl.store
LEFT JOIN unit u ON u.code = sm.units
WHERE (sm.isstock_1 = 1 OR sm.isstock_2 = 1 OR sm.isstock_4 = 1 OR sm.isstock_5 = 1 OR sm.isstock_6 = 1)
  AND (sm.inactive IS NULL OR sm.inactive = 0)
GROUP BY sm.itemcode, sm.descrip, sm.category, category_name, sl.store, store_name, unit_code, unit_name, sm.partperunit, sm.averagestock
ORDER BY sm.descrip ASC, store_name ASC";

$result = mysqli_query($db, $sql);
if (!$result) {
  echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($db)]);
  mysqli_close($db);
  exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = [
    'itemcode'      => $row['itemcode'],
    'description'   => trim($row['descrip'] ?? ''),
    'category'      => $row['category'],
    'category_name' => $row['category_name'],
    'store_code'    => $row['store_code'],
    'store_name'    => $row['store_name'],
    'unit_code'     => $row['unit_code'],
    'unit_name'     => $row['unit_name'],
    'partperunit'   => (float)$row['partperunit'],
    'averagestock'  => (float)$row['averagestock'],
    'opening_qty'   => (float)round($row['opening_qty']),
    'opening_value' => (float)$row['opening_value'],
    'in_qty'        => (float)round($row['in_qty']),
    'in_value'      => (float)$row['in_value'],
    'out_qty'       => (float)round($row['out_qty']),
    'out_value'     => (float)$row['out_value'],
    'closing_qty'   => (float)round($row['closing_qty']),
    'closing_value' => (float)$row['closing_value']
  ];
}

echo json_encode([
  'success' => true,
  'data' => $data,
  'period' => ['periodno' => $periodNo, 'cur_start' => $cs, 'cur_end' => $ce]
]);

mysqli_close($db);
