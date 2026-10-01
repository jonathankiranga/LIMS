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

// Get month names for this financial year
$monthNames = [];
$r = mysqli_query($db, "SELECT Name FROM financialperiods WHERE periodno = $periodNo ORDER BY start_date");
while ($row = mysqli_fetch_assoc($r)) {
  $monthNames[] = $row['Name'];
}
$months = array_merge(['Opening'], $monthNames);
$quarters = ['Opening','Q1','Q2','Q3','Q4'];

// Get all active accounts
$accounts = [];
$r = mysqli_query($db, "SELECT accno, accdesc, reportcode, balance_income, COALESCE(accgrp,'') AS accgrp, COALESCE(currency,'KES') AS currency FROM acct WHERE inactive=0 ORDER BY reportcode");
while ($row = mysqli_fetch_assoc($r)) {
  $accounts[$row['accno']] = $row;
}

// Get per-month net values for the year (debit - credit per account per month)
$monthNets = [];
$r = mysqli_query($db, "
  SELECT t.accno, t.month_name, SUM(t.val) AS net FROM (
    SELECT gl.accountcode AS accno, MONTHNAME(gl.Docdate) AS month_name, gl.amount * gl.ExchangeRate AS val
    FROM Generalledger gl WHERE gl.Docdate BETWEEN '$cs' AND '$ce'
    UNION ALL
    SELECT gl.balaccountcode AS accno, MONTHNAME(gl.Docdate) AS month_name, -gl.amount * gl.ExchangeRate AS val
    FROM Generalledger gl WHERE gl.Docdate BETWEEN '$cs' AND '$ce'
  ) t GROUP BY t.accno, t.month_name
");
while ($row = mysqli_fetch_assoc($r)) {
  $monthNets[$row['accno']][$row['month_name']] = (float)$row['net'];
}

// Get opening balances (before year start) for Balance Sheet accounts
$openings = [];
$r = mysqli_query($db, "
  SELECT t.accno, SUM(t.val) AS opening FROM (
    SELECT gl.accountcode AS accno, gl.amount * gl.ExchangeRate AS val
    FROM Generalledger gl WHERE gl.Docdate < '$cs'
    UNION ALL
    SELECT gl.balaccountcode AS accno, -gl.amount * gl.ExchangeRate AS val
    FROM Generalledger gl WHERE gl.Docdate < '$cs'
  ) t GROUP BY t.accno
");
while ($row = mysqli_fetch_assoc($r)) {
  $openings[$row['accno']] = (float)$row['opening'];
}

// Build 13 rows per account (Opening + 12 months)
$data = [];
foreach ($accounts as $accno => $acc) {
  $bi = (int)$acc['balance_income'];
  $opening = ($bi === 0) ? (float)($openings[$accno] ?? 0) : 0;
  $accName = trim($acc['accdesc'] ?? '');
  $rcode = $acc['reportcode'] ?? '';

  // Opening row
  $data[] = [
    'account_code' => $accno,
    'account_name' => $accName,
    'reportcode' => $rcode,
    'balance_income' => $bi,
    'account_group' => $acc['accgrp'],
    'currency' => $acc['currency'],
    'value' => $opening,
    'period_month' => 'Opening',
    'period_quarter' => 'Opening'
  ];

  // Monthly rows
  foreach ($monthNames as $idx => $mn) {
    $q = 'Q' . (floor($idx / 3) + 1);
    $net = (float)($monthNets[$accno][$mn] ?? 0);
    $data[] = [
      'account_code' => $accno,
      'account_name' => $accName,
      'reportcode' => $rcode,
      'balance_income' => $bi,
      'account_group' => $acc['accgrp'],
      'currency' => $acc['currency'],
      'value' => $net,
      'period_month' => $mn,
      'period_quarter' => $q
    ];
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
