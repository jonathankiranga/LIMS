<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  echo json_encode(['success' => false, 'message' => 'Invalid request body']);
  exit;
}

$action = $input['action'] ?? '';
$testItemcode = trim($input['test_itemcode'] ?? '');
$reagentItemcode = trim($input['reagent_itemcode'] ?? '');
$quantity = (float)($input['qty'] ?? 0);
$mappingId = (int)($input['id'] ?? 0);

if ($action !== 'update_qty' && $action !== 'delete' && empty($testItemcode)) {
  echo json_encode(['success' => false, 'message' => 'Missing test_itemcode']);
  exit;
}

if ($action === 'add') {
  if (empty($reagentItemcode) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reagent or quantity']);
    exit;
  }

  // Check if this reagent already mapped to this test
  $checkStmt = mysqli_prepare($db, "SELECT id FROM test_reagent_mapping WHERE test_itemcode = ? AND reagent_itemcode = ?");
  mysqli_stmt_bind_param($checkStmt, 'ss', $testItemcode, $reagentItemcode);
  mysqli_stmt_execute($checkStmt);
  mysqli_stmt_store_result($checkStmt);

  if (mysqli_stmt_num_rows($checkStmt) > 0) {
    // Update existing
    mysqli_stmt_close($checkStmt);
    $updateStmt = mysqli_prepare($db, "UPDATE test_reagent_mapping SET quantity = ? WHERE test_itemcode = ? AND reagent_itemcode = ?");
    mysqli_stmt_bind_param($updateStmt, 'dss', $quantity, $testItemcode, $reagentItemcode);
    $ok = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Mapping updated' : 'Update failed']);
  } else {
    mysqli_stmt_close($checkStmt);
    $insertStmt = mysqli_prepare($db, "INSERT INTO test_reagent_mapping (test_itemcode, reagent_itemcode, quantity) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insertStmt, 'ssd', $testItemcode, $reagentItemcode, $quantity);
    $ok = mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Mapping added' : 'Insert failed']);
  }

} elseif ($action === 'update_qty') {
  if ($mappingId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid mapping id or quantity']);
    exit;
  }
  $updStmt = mysqli_prepare($db, "UPDATE test_reagent_mapping SET quantity = ? WHERE id = ?");
  mysqli_stmt_bind_param($updStmt, 'di', $quantity, $mappingId);
  $ok = mysqli_stmt_execute($updStmt);
  mysqli_stmt_close($updStmt);
  echo json_encode(['success' => $ok, 'message' => $ok ? 'Quantity updated' : 'Update failed']);

} elseif ($action === 'delete') {
  if ($mappingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid mapping id']);
    exit;
  }
  $delStmt = mysqli_prepare($db, "DELETE FROM test_reagent_mapping WHERE id = ?");
  mysqli_stmt_bind_param($delStmt, 'i', $mappingId);
  $ok = mysqli_stmt_execute($delStmt);
  mysqli_stmt_close($delStmt);
  echo json_encode(['success' => $ok, 'message' => $ok ? 'Mapping deleted' : 'Delete failed']);

} else {
  echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
}

mysqli_close($db);
