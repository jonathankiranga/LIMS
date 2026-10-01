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

// Read header row
$header = fgetcsv($handle);
if (!$header) {
  fclose($handle);
  echo json_encode(['success' => false, 'message' => 'Empty CSV file']);
  exit;
}
$header = array_map('trim', $header);

// Detect columns: prefer test_itemcode,reagent_itemcode,qty or reagent_itemcode,qty
$hasTestCol = in_array('test_itemcode', $header);
$hasReagentCol = in_array('reagent_itemcode', $header);
$hasQtyCol = in_array('qty', $header) || in_array('quantity', $header);

if (!$hasReagentCol || !$hasQtyCol) {
  fclose($handle);
  echo json_encode(['success' => false, 'message' => 'CSV must have reagent_itemcode and qty columns']);
  exit;
}

$qtyCol = in_array('quantity', $header) ? 'quantity' : 'qty';

// Optional: if no test column, use query param
$defaultTest = isset($_GET['test']) ? trim($_GET['test']) : '';

$inserted = 0;
$updated = 0;
$errors = 0;

// Use transaction
mysqli_begin_transaction($db);

try {
  while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < count($header)) continue;

    $data = array_combine($header, array_map('trim', $row));
    $testCode = $hasTestCol ? $data['test_itemcode'] : $defaultTest;
    $reagentCode = $data['reagent_itemcode'];
    $qty = (float)($data[$qtyCol] ?? 0);

    if (empty($testCode) || empty($reagentCode) || $qty <= 0) {
      $errors++;
      continue;
    }

    // Check if mapping exists
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
      $updated++;
    } else {
      $ins = mysqli_prepare($db, "INSERT INTO test_reagent_mapping (test_itemcode, reagent_itemcode, quantity) VALUES (?, ?, ?)");
      mysqli_stmt_bind_param($ins, 'ssd', $testCode, $reagentCode, $qty);
      mysqli_stmt_execute($ins);
      mysqli_stmt_close($ins);
      $inserted++;
    }
  }

  mysqli_commit($db);
  fclose($handle);

  echo json_encode([
    'success' => true,
    'message' => "Imported: $inserted inserted, $updated updated, $errors skipped",
    'inserted' => $inserted,
    'updated' => $updated,
    'errors' => $errors
  ]);

} catch (Exception $e) {
  mysqli_rollback($db);
  fclose($handle);
  echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
}

mysqli_close($db);
