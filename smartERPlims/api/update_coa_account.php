<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

function esc($db, $v) {
  return mysqli_real_escape_string($db, (string)($v ?? ''));
}

function validateFormula($db, $formula) {
  $formula = trim($formula);
  if ($formula === '') return null;
  if (strpos($formula, '+') !== false) {
    foreach (explode('+', $formula) as $ref) {
      $ref = trim($ref);
      if ($ref === '') continue;
      $r = mysqli_query($db, "SELECT accno FROM acct WHERE ReportCode='" . esc($db, $ref) . "'");
      if (mysqli_num_rows($r) === 0) return "Invalid account reference: '$ref'";
    }
  }
  if (strpos($formula, '-') !== false) {
    foreach (explode('-', $formula) as $ref) {
      $ref = trim($ref);
      if ($ref === '') continue;
      $r = mysqli_query($db, "SELECT accno FROM acct WHERE ReportCode='" . esc($db, $ref) . "'");
      if (mysqli_num_rows($r) === 0) return "Invalid account reference: '$ref'";
    }
  }
  return null;
}

function genAccno($db, $accdesc) {
  $r = mysqli_query($db, "SELECT COUNT(*) FROM acct");
  $row = mysqli_fetch_row($r);
  $count = (int)$row[0];
  return substr($accdesc, 1, 2) . str_pad((string)$count, 6, '0', STR_PAD_LEFT);
}

switch ($action) {

  case 'update_field':
    $allowed = ['ReportCode','accdesc','balance_income','ReportStyle','Calculation','postinggroup'];
    $accno   = trim($_POST['accno'] ?? '');
    $field   = $_POST['field'] ?? '';
    $value   = $_POST['value'] ?? '';

    if (!$accno) {
      $response['message'] = 'Missing accno'; break;
    }
    if (!in_array($field, $allowed)) {
      $response['message'] = 'Invalid field'; break;
    }

    if (in_array($field, ['balance_income', 'ReportStyle', 'direct', 'inactive'])) {
      $value = (int)$value;
    }

    $sql = "UPDATE acct SET `$field`='" . esc($db, $value) . "' WHERE accno='" . esc($db, $accno) . "'";
    if (mysqli_query($db, $sql)) {
      $response['success'] = true;
    } else {
      $response['message'] = mysqli_error($db);
    }
    break;

  case 'update_all':
    $accno       = trim($_POST['accno'] ?? '');
    $reportCode  = trim($_POST['ReportCode'] ?? '');
    $accdesc     = trim($_POST['accdesc'] ?? '');
    $balIncome   = (int)($_POST['balance_income'] ?? 0);
    $repStyle    = (int)($_POST['ReportStyle'] ?? 0);
    $direct      = (int)($_POST['direct'] ?? 0);
    $inactive    = (int)($_POST['inactive'] ?? 0);
    $salePurch   = (int)($_POST['Sale_Purchase_Neither'] ?? 0);
    $calc        = $_POST['Calculation'] ?? '';
    $postGroup   = $_POST['postinggroup'] ?? '';

    if (!$accno) { $response['message'] = 'Missing accno'; break; }

    $fe = validateFormula($db, $calc);
    if ($fe) { $response['message'] = $fe; break; }

    $chk = mysqli_query($db, "SELECT accno FROM acct WHERE ReportCode='" . esc($db, $reportCode) . "' AND accno != '" . esc($db, $accno) . "'");
    if (mysqli_num_rows($chk) > 0) { $response['message'] = 'Account code already exists: ' . $reportCode; break; }

    if ($balIncome === 0) $direct = 0;

    $sql = "UPDATE acct SET ReportCode='" . esc($db, $reportCode) . "', accdesc='" . esc($db, $accdesc) .
           "', balance_income=$balIncome, ReportStyle=$repStyle, direct=$direct, inactive=$inactive" .
           ", Sale_Purchase_Neither=$salePurch, Calculation='" . esc($db, $calc) .
           "', postinggroup='" . esc($db, $postGroup) . "' WHERE accno='" . esc($db, $accno) . "'";

    if (mysqli_query($db, $sql)) {
      $response['success'] = true;
    } else {
      $response['message'] = mysqli_error($db);
    }
    break;

  case 'create':
    $reportCode  = trim($_POST['ReportCode'] ?? '');
    $accdesc     = trim($_POST['accdesc'] ?? '');
    $balIncome   = (int)($_POST['balance_income'] ?? 0);
    $repStyle    = (int)($_POST['ReportStyle'] ?? 0);
    $direct      = (int)($_POST['direct'] ?? 0);
    $inactive    = (int)($_POST['inactive'] ?? 0);
    $salePurch   = (int)($_POST['Sale_Purchase_Neither'] ?? 0);
    $calc        = $_POST['Calculation'] ?? '';
    $postGroup   = $_POST['postinggroup'] ?? '';

    if ($reportCode === '' || $accdesc === '') {
      $response['message'] = 'Account Code and Name are required'; break;
    }

    $chk = mysqli_query($db, "SELECT accno FROM acct WHERE ReportCode='" . esc($db, $reportCode) . "'");
    if (mysqli_num_rows($chk) > 0) { $response['message'] = 'Account code already exists: ' . $reportCode; break; }

    $fe = validateFormula($db, $calc);
    if ($fe) { $response['message'] = $fe; break; }

    if ($balIncome === 0) $direct = 0;

    $accno = genAccno($db, $accdesc);

    $sql = "INSERT INTO acct (ReportCode, accdesc, balance_income, ReportStyle, direct, inactive, Sale_Purchase_Neither, Calculation, postinggroup, accno) VALUES ('" .
           esc($db, $reportCode) . "','" . esc($db, $accdesc) . "',$balIncome,$repStyle,$direct,$inactive,$salePurch,'" .
           esc($db, $calc) . "','" . esc($db, $postGroup) . "','" . esc($db, $accno) . "')";

    if (mysqli_query($db, $sql)) {
      $response['success'] = true;
      $response['accno'] = $accno;
    } else {
      $response['message'] = mysqli_error($db);
    }
    break;

  default:
    $response['message'] = 'Unknown action';
}

echo json_encode($response);
?>
