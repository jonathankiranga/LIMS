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

$dates = null;
$r = mysqli_query($db, "SELECT
  (SELECT MIN(start_date) FROM financialperiods WHERE periodno = $periodNo) AS cur_start,
  (SELECT MAX(end_date)   FROM financialperiods WHERE periodno = $periodNo) AS cur_end,
  (SELECT MIN(start_date) FROM financialperiods WHERE periodno = ($periodNo - 12)) AS last_start,
  (SELECT MAX(end_date)   FROM financialperiods WHERE periodno = ($periodNo - 12)) AS last_end");
if ($r) $dates = mysqli_fetch_assoc($r);

if (!$dates || !$dates['cur_start']) {
  echo json_encode(['success' => false, 'message' => 'Invalid period or no dates found']);
  mysqli_close($db);
  exit;
}

$cs = $dates['cur_start'];
$ce = $dates['cur_end'];
$ls = $dates['last_start'];
$le = $dates['last_end'];

$sql = "SELECT a.accno, a.reportcode, a.accdesc, a.balance_income,
               COALESCE(a.accgrp, '') AS accgrp,
               COALESCE(a.currency, 'KES') AS currency,
               CASE a.balance_income
                   WHEN 0 THEN (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.accountcode = a.accno AND gl.Docdate <= '$ce')
                   WHEN 1 THEN (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.accountcode = a.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
               END AS debit,
               CASE a.balance_income
                   WHEN 0 THEN (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.balaccountcode = a.accno AND gl.Docdate <= '$ce')
                   WHEN 1 THEN (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.balaccountcode = a.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
               END AS credit,
               CASE a.balance_income
                   WHEN 0 THEN (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.accountcode = a.accno AND gl.Docdate < '$cs')
                   ELSE         (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.accountcode = a.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
               END AS debit_last,
               CASE a.balance_income
                   WHEN 0 THEN (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.balaccountcode = a.accno AND gl.Docdate < '$cs')
                   ELSE         (SELECT COALESCE(SUM(gl.amount * gl.ExchangeRate),0) FROM Generalledger gl
                                WHERE gl.balaccountcode = a.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
               END AS credit_last
        FROM acct a
        WHERE a.inactive = 0
        ORDER BY a.reportcode, a.balance_income, a.accgrp, a.accno";

$result = mysqli_query($db, $sql);
if (!$result) {
  echo json_encode(['success' => false, 'message' => 'Query failed: ' . mysqli_error($db)]);
  mysqli_close($db);
  exit;
}

$year = date('Y', strtotime($cs));
$quarter = 'Q' . ceil(date('n', strtotime($cs)) / 3);
$month = date('F', strtotime($cs));

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = [
    'accno'        => $row['accno'],
    'reportcode'   => $row['reportcode'],
    'accdesc'      => trim($row['accdesc']),
    'balance_income' => (int)$row['balance_income'],
    'accgrp'       => $row['accgrp'],
    'currency'     => $row['currency'],
    'debit'        => (float)$row['debit'],
    'credit'       => (float)$row['credit'],
    'debit_last'   => (float)$row['debit_last'],
    'credit_last'  => (float)$row['credit_last'],
    'period_year'  => $year,
    'period_quarter' => $quarter,
    'period_month' => $month
  ];
}

echo json_encode([
  'success' => true,
  'data' => $data,
  'period' => [
    'periodno' => $periodNo,
    'cur_start' => $cs,
    'cur_end'   => $ce
  ]
]);

mysqli_close($db);
