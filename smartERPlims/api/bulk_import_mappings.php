<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['success' => false, 'message' => 'File upload failed']);
  exit;
}

$tmpPath = $_FILES['csv_file']['tmp_name'];
$handle = fopen($tmpPath, 'r');
if (!$handle) {
  echo json_encode(['success' => false, 'message' => 'Cannot read uploaded file']);
  exit;
}

$header = fgetcsv($handle);
if (!$header) {
  fclose($handle);
  echo json_encode(['success' => false, 'message' => 'Empty CSV file']);
  exit;
}
$header = array_map('trim', $header);

$colMap = [];
foreach ($header as $i => $name) {
  $lower = strtolower($name);
  if ($lower === 'testcode' || $lower === 'test_itemcode' || $lower === 'test_code') $colMap['testcode'] = $i;
  elseif ($lower === 'reagentcode' || $lower === 'reagent_itemcode' || $lower === 'reagent_code') $colMap['reagentcode'] = $i;
  elseif ($lower === 'reagentcost' || $lower === 'unit_cost' || $lower === 'cost') $colMap['reagentcost'] = $i;
  elseif ($lower === 'reagentqty' || $lower === 'qty' || $lower === 'quantity') $colMap['reagentqty'] = $i;
}

if (!isset($colMap['testcode']) || !isset($colMap['reagentcode']) || !isset($colMap['reagentqty'])) {
  fclose($handle);
  echo json_encode(['success' => false, 'message' => 'CSV must have testcode, reagentcode, and reagentqty columns']);
  exit;
}

$mappingsInserted = 0;
$mappingsUpdated = 0;
$costsUpdated = 0;
$errors = 0;
$errorDetails = [];

mysqli_begin_transaction($db);

try {
  while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < count($header)) continue;
    $row = array_map('trim', $row);

    $testCode = $row[$colMap['testcode']];
    $reagentCode = $row[$colMap['reagentcode']];
    $qty = (float)($row[$colMap['reagentqty']] ?? 0);

    if (empty($testCode) || empty($reagentCode) || $qty <= 0) {
      $errors++;
      $errorDetails[] = "Skipped row: test=$testCode, reagent=$reagentCode, qty=$qty (invalid data)";
      continue;
    }

    // Upsert mapping
    $check = mysqli_prepare($db, "SELECT id FROM test_reagent_mapping WHERE test_itemcode = ? AND reagent_itemcode = ?");
    mysqli_stmt_bind_param($check, 'ss', $testCode, $reagentCode);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $exists = mysqli_stmt_num_rows($check) > 0;
    mysqli_stmt_close($check);

    if ($exists) {
      $upd = mysqli_prepare($db, "UPDATE test_reagent_mapping SET quantity = ? WHERE test_itemcode = ? AND reagent_itemcode = ?");
      mysqli_stmt_bind_param($upd, 'dss', $qty, $testCode, $reagentCode);
      mysqli_stmt_execute($upd);
      mysqli_stmt_close($upd);
      $mappingsUpdated++;
    } else {
      $ins = mysqli_prepare($db, "INSERT INTO test_reagent_mapping (test_itemcode, reagent_itemcode, quantity) VALUES (?, ?, ?)");
      mysqli_stmt_bind_param($ins, 'ssd', $testCode, $reagentCode, $qty);
      mysqli_stmt_execute($ins);
      mysqli_stmt_close($ins);
      $mappingsInserted++;
    }

    // Update reagent cost if provided and non-empty
    if (isset($colMap['reagentcost'])) {
      $costStr = $row[$colMap['reagentcost']];
      if ($costStr !== '' && $costStr !== null && is_numeric($costStr)) {
        $cost = (float)$costStr;
        $costUpd = mysqli_prepare($db, "UPDATE stockmaster SET averagestock = ? WHERE itemcode = ? AND isstock_2 = 1");
        mysqli_stmt_bind_param($costUpd, 'ds', $cost, $reagentCode);
        mysqli_stmt_execute($costUpd);
        if (mysqli_stmt_affected_rows($costUpd) > 0) {
          $costsUpdated++;
        }
        mysqli_stmt_close($costUpd);
      }
    }
  }

  mysqli_commit($db);
  fclose($handle);

  echo json_encode([
    'success' => true,
    'message' => "Mappings: $mappingsInserted inserted, $mappingsUpdated updated | Reagent costs updated: $costsUpdated | Errors: $errors",
    'mappings_inserted' => $mappingsInserted,
    'mappings_updated' => $mappingsUpdated,
    'costs_updated' => $costsUpdated,
    'errors' => $errors,
    'error_details' => $errorDetails
  ]);

} catch (Exception $e) {
  mysqli_rollback($db);
  fclose($handle);
  echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
}

mysqli_close($db);
