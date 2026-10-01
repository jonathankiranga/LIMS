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
$itemcode = isset($_GET['itemcode']) ? mysqli_real_escape_string($db, $_GET['itemcode']) : '';
$store = isset($_GET['store']) ? mysqli_real_escape_string($db, $_GET['store']) : '';
$month = isset($_GET['month']) ? mysqli_real_escape_string($db, $_GET['month']) : '';

if (!$periodNo || !$itemcode || !$month) {
  echo json_encode(['success' => false, 'message' => 'Missing parameters']);
  mysqli_close($db);
  exit;
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

// Resolve quarter to month names
$qMonths = ['Q1' => ['January','February','March'], 'Q2' => ['April','May','June'], 'Q3' => ['July','August','September'], 'Q4' => ['October','November','December']];

if ($month === 'Opening') {
  $sql = "SELECT sl.`date`, sl.invref AS document_no, st.typename AS document_type, sl.stname AS narration,
    COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0) AS qty,
    COALESCE(sl.price,0) AS unit_cost,
    COALESCE(sl.stockvalue,0) AS total_value
    FROM stockledger sl
    LEFT JOIN systypes_1 st ON st.typeid = sl.doctyp
    WHERE sl.itemcode = '$itemcode' " . ($store ? "AND sl.store = '$store' " : "") . "AND sl.`date` < '$cs'
    ORDER BY sl.`date`, sl.invref";
} elseif (isset($qMonths[$month])) {
  $monthNames = $qMonths[$month];
  $dateConditions = [];
  foreach ($monthNames as $mn) {
    $dateConditions[] = "(fp.Name = '$mn' AND sl.`date` BETWEEN fp.start_date AND fp.end_date)";
  }
  $dateWhere = '(' . implode(' OR ', $dateConditions) . ')';
  $sql = "SELECT sl.`date`, sl.invref AS document_no, st.typename AS document_type, sl.stname AS narration,
    COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0) AS qty,
    COALESCE(sl.price,0) AS unit_cost,
    COALESCE(sl.stockvalue,0) AS total_value
    FROM stockledger sl
    LEFT JOIN systypes_1 st ON st.typeid = sl.doctyp
    LEFT JOIN financialperiods fp ON sl.`date` BETWEEN fp.start_date AND fp.end_date
    WHERE sl.itemcode = '$itemcode' " . ($store ? "AND sl.store = '$store' " : "") . "AND $dateWhere
    ORDER BY sl.`date`, sl.invref";
} else {
  $sql = "SELECT sl.`date`, sl.invref AS document_no, st.typename AS document_type, sl.stname AS narration,
    COALESCE(sl.fulqty,0) * COALESCE(sl.PartPerUnit,1) + COALESCE(sl.loosqty,0) AS qty,
    COALESCE(sl.price,0) AS unit_cost,
    COALESCE(sl.stockvalue,0) AS total_value
    FROM stockledger sl
    LEFT JOIN systypes_1 st ON st.typeid = sl.doctyp
    LEFT JOIN financialperiods fp ON sl.`date` BETWEEN fp.start_date AND fp.end_date
    WHERE sl.itemcode = '$itemcode' " . ($store ? "AND sl.store = '$store' " : "") . "AND fp.Name = '$month' AND fp.periodno = $periodNo
    ORDER BY sl.`date`, sl.invref";
}

$result = mysqli_query($db, $sql);
if (!$result) {
  echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($db)]);
  mysqli_close($db);
  exit;
}

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
  $rows[] = [
    'date'        => $row['date'] ? substr($row['date'], 0, 10) : '',
    'document_no' => $row['document_no'] ?? '',
    'document_type' => $row['document_type'] ?? '',
    'narration'   => trim($row['narration'] ?? ''),
    'qty'         => (float)$row['qty'],
    'unit_cost'   => (float)$row['unit_cost'],
    'total_value' => (float)$row['total_value']
  ];
}

echo json_encode([
  'success' => true,
  'data' => $rows,
  'itemcode' => $itemcode,
  'store' => $store,
  'month' => $month
]);

mysqli_close($db);
