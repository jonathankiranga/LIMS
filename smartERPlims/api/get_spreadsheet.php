<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$testCode = isset($_GET['test']) ? trim($_GET['test']) : '';
$userId = isset($_GET['user']) ? trim($_GET['user']) : '';

if (empty($testCode)) {
  echo json_encode(['success' => false, 'message' => 'Missing test parameter']);
  exit;
}

// Get saved sheet data
$sheetData = null;
$stmt = mysqli_prepare($db, "SELECT sheet_data FROM test_spreadsheet WHERE test_itemcode = ?");
mysqli_stmt_bind_param($stmt, 's', $testCode);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
  $sheetData = $row['sheet_data'];
}
mysqli_stmt_close($stmt);

// Get user permission
$canWrite = null;
if (!empty($userId)) {
  $permStmt = mysqli_prepare($db, "SELECT can_write FROM spreadsheet_permissions WHERE test_itemcode = ? AND user_id = ?");
  mysqli_stmt_bind_param($permStmt, 'ss', $testCode, $userId);
  mysqli_stmt_execute($permStmt);
  $permResult = mysqli_stmt_get_result($permStmt);
  if ($permRow = mysqli_fetch_assoc($permResult)) {
    $canWrite = (bool)$permRow['can_write'];
  }
  mysqli_stmt_close($permStmt);
}

echo json_encode([
  'success' => true,
  'data' => [
    'sheet_data' => $sheetData,
    'permission' => [
      'can_write' => $canWrite
    ]
  ]
]);

mysqli_close($db);
