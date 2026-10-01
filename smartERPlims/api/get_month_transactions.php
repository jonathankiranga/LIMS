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
$accountCode = isset($_GET['account_code']) ? mysqli_real_escape_string($db, $_GET['account_code']) : '';
$month = isset($_GET['month']) ? mysqli_real_escape_string($db, $_GET['month']) : '';

if (!$periodNo || !$accountCode || !$month) {
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

// If month is a quarter (Q1-Q4), resolve to month names
$qMonths = ['Q1' => ['January','February','March'], 'Q2' => ['April','May','June'], 'Q3' => ['July','August','September'], 'Q4' => ['October','November','December']];
if (isset($qMonths[$month])) {
  $monthNames = $qMonths[$month];
  $whereMonth = "MONTHNAME(gl.Docdate) IN ('" . implode("','", $monthNames) . "')";
} else {
  $whereMonth = "MONTHNAME(gl.Docdate) = '$month'";
}

$sql = "SELECT gl.Docdate, gl.DocumentNo, gl.DocumentType, gl.narration, gl.currencycode,
  gl.amount * gl.ExchangeRate AS value,
  CASE WHEN gl.accountcode = '$accountCode' THEN 'Debit' ELSE 'Credit' END AS side
  FROM Generalledger gl
  WHERE (gl.accountcode = '$accountCode' OR gl.balaccountcode = '$accountCode')
    AND $whereMonth
    AND gl.Docdate BETWEEN '$cs' AND '$ce'
  ORDER BY gl.Docdate, gl.DocumentNo";

$result = mysqli_query($db, $sql);
if (!$result) {
  echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($db)]);
  mysqli_close($db);
  exit;
}

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
  $rows[] = [
    'date' => $row['Docdate'] ?? '',
    'document_no' => $row['DocumentNo'] ?? '',
    'document_type' => $row['DocumentType'] ?? '',
    'narration' => trim($row['narration'] ?? ''),
    'value' => (float)$row['value'],
    'side' => $row['side'] ?? '',
    'currency' => $row['currencycode'] ?? ''
  ];
}

echo json_encode([
  'success' => true,
  'data' => $rows,
  'account_code' => $accountCode,
  'month' => $month
]);

mysqli_close($db);
