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

$testItemcode = trim($input['test_itemcode'] ?? '');
$price = (float)($input['price'] ?? 0);
$markupPct = (float)($input['markup_pct'] ?? 0);
$unitsCode = trim($input['units_code'] ?? 'PCS');
$qty = (int)($input['qty'] ?? 1);

if (empty($testItemcode)) {
  echo json_encode(['success' => false, 'message' => 'Missing test_itemcode']);
  exit;
}
if ($price <= 0) {
  echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
  exit;
}

// Check if default price already exists and preserve TAT
$existingStmt = mysqli_prepare($db, "SELECT id, tat FROM PriceList WHERE stockcode = ? AND (customerCode IS NULL OR customerCode = '') AND approved = 1 LIMIT 1");
mysqli_stmt_bind_param($existingStmt, 's', $testItemcode);
mysqli_stmt_execute($existingStmt);
$existingResult = mysqli_stmt_get_result($existingStmt);
$existingRow = mysqli_fetch_assoc($existingResult);
$existingId = $existingRow ? (int)$existingRow['id'] : 0;
$existingTat = $existingRow ? (int)$existingRow['tat'] : 0;
mysqli_stmt_close($existingStmt);

if ($existingId > 0) {
  $updateStmt = mysqli_prepare($db, "UPDATE PriceList SET price = ?, DateTime = NOW() WHERE id = ?");
  mysqli_stmt_bind_param($updateStmt, 'di', $price, $existingId);
  $ok = mysqli_stmt_execute($updateStmt);
  mysqli_stmt_close($updateStmt);
  if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Price updated', 'price' => $price, 'markup_pct' => $markupPct]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
  }
} else {
  $insertStmt = mysqli_prepare($db, "INSERT INTO PriceList (customerCode, stockcode, approved, approvedby, DateTime, units_code, quantity, price, tat) VALUES ('', ?, 1, '', NOW(), ?, ?, ?, ?)");
  mysqli_stmt_bind_param($insertStmt, 'ssidi', $testItemcode, $unitsCode, $qty, $price, $existingTat);
  $ok = mysqli_stmt_execute($insertStmt);
  mysqli_stmt_close($insertStmt);
  if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Price created', 'price' => $price, 'markup_pct' => $markupPct]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
  }
}

mysqli_close($db);
