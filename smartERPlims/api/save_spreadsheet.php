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
$sheetData = $input['sheet_data'] ?? null;
$userId = trim($input['user_id'] ?? '');

if (empty($testItemcode)) {
  echo json_encode(['success' => false, 'message' => 'Missing test_itemcode']);
  exit;
}

if ($sheetData === null) {
  echo json_encode(['success' => false, 'message' => 'Missing sheet_data']);
  exit;
}

// Serialize to JSON string if it's an array
$sheetJson = is_array($sheetData) ? json_encode($sheetData) : $sheetData;

// Upsert
$stmt = mysqli_prepare($db, "INSERT INTO test_spreadsheet (test_itemcode, sheet_data, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE sheet_data = VALUES(sheet_data), updated_by = VALUES(updated_by), updated_at = NOW()");
mysqli_stmt_bind_param($stmt, 'sss', $testItemcode, $sheetJson, $userId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
  'success' => $ok,
  'message' => $ok ? 'Sheet saved' : 'Save failed'
]);

mysqli_close($db);
