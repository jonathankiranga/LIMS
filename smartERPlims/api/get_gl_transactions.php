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
$codes = isset($_GET['codes']) ? $_GET['codes'] : '';
if (!$codes || !$periodNo) {
  echo json_encode(['success' => false, 'message' => 'Missing codes or period_no']);
  mysqli_close($db);
  exit;
}

// Get period dates
$r = mysqli_query($db, "SELECT MIN(start_date) AS cur_start, MAX(end_date) AS cur_end FROM financialperiods WHERE periodno = $periodNo");
$dates = mysqli_fetch_assoc($r);
if (!$dates || !$dates['cur_start']) {
  echo json_encode(['success' => false, 'message' => 'Invalid period']);
  mysqli_close($db);
  exit;
}
$cs = $dates['cur_start'];
$ce = $dates['cur_end'];

// Escape and quote account codes
$codeArr = array_map('trim', explode(',', $codes));
$codeArr = array_filter($codeArr);
if (empty($codeArr)) {
  echo json_encode(['success' => false, 'message' => 'No valid codes']);
  mysqli_close($db);
  exit;
}
$quoted = array_map(function($c) use ($db) { return "'" . mysqli_real_escape_string($db, $c) . "'"; }, $codeArr);
$inClause = implode(',', $quoted);

// Opening balance transactions (before current period)
$openSql = "SELECT gl.Docdate, gl.DocumentNo, gl.narration, gl.accountcode, gl.balaccountcode,
  gl.amount * gl.ExchangeRate AS amount, gl.currencycode,
  CASE WHEN gl.accountcode IN ($inClause) THEN gl.amount * gl.ExchangeRate ELSE 0 END AS debit,
  CASE WHEN gl.balaccountcode IN ($inClause) THEN gl.amount * gl.ExchangeRate ELSE 0 END AS credit
  FROM Generalledger gl
  WHERE (gl.accountcode IN ($inClause) OR gl.balaccountcode IN ($inClause))
  AND gl.Docdate < '$cs'
  ORDER BY gl.Docdate, gl.DocumentNo";

// Current period transactions
$curSql = "SELECT gl.Docdate, gl.DocumentNo, gl.narration, gl.accountcode, gl.balaccountcode,
  gl.amount * gl.ExchangeRate AS amount, gl.currencycode,
  CASE WHEN gl.accountcode IN ($inClause) THEN gl.amount * gl.ExchangeRate ELSE 0 END AS debit,
  CASE WHEN gl.balaccountcode IN ($inClause) THEN gl.amount * gl.ExchangeRate ELSE 0 END AS credit
  FROM Generalledger gl
  WHERE (gl.accountcode IN ($inClause) OR gl.balaccountcode IN ($inClause))
  AND gl.Docdate BETWEEN '$cs' AND '$ce'
  ORDER BY gl.Docdate, gl.DocumentNo";

$openResult = mysqli_query($db, $openSql);
$curResult = mysqli_query($db, $curSql);

$openRows = [];
if ($openResult) {
  while ($row = mysqli_fetch_assoc($openResult)) {
    $openRows[] = [
      'date' => $row['Docdate'],
      'doc_no' => $row['DocumentNo'],
      'narration' => trim($row['narration'] ?? ''),
      'accountcode' => $row['accountcode'],
      'balaccountcode' => $row['balaccountcode'],
      'amount' => (float)$row['amount'],
      'debit' => (float)$row['debit'],
      'credit' => (float)$row['credit'],
      'currency' => $row['currencycode'] ?? 'USD'
    ];
  }
}

$curRows = [];
if ($curResult) {
  while ($row = mysqli_fetch_assoc($curResult)) {
    $curRows[] = [
      'date' => $row['Docdate'],
      'doc_no' => $row['DocumentNo'],
      'narration' => trim($row['narration'] ?? ''),
      'accountcode' => $row['accountcode'],
      'balaccountcode' => $row['balaccountcode'],
      'amount' => (float)$row['amount'],
      'debit' => (float)$row['debit'],
      'credit' => (float)$row['credit'],
      'currency' => $row['currencycode'] ?? 'USD'
    ];
  }
}

echo json_encode([
  'success' => true,
  'opening' => $openRows,
  'current' => $curRows,
  'period' => ['cur_start' => $cs, 'cur_end' => $ce]
]);

mysqli_close($db);
