<?php
header('Content-Type: application/json');
include('../config.php');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $testCode = isset($_GET['test']) ? trim($_GET['test']) : '';
  if (empty($testCode)) {
    echo json_encode(['success' => false, 'message' => 'Missing test parameter']);
    exit;
  }

  // List all users with their permission for this test
  $result = mysqli_query($db, "SELECT userid, realname FROM www_users WHERE blocked = 0 OR blocked IS NULL ORDER BY realname");
  $users = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $uid = trim($row['userid']);
    $users[$uid] = [
      'user_id' => $uid,
      'realname' => trim($row['realname']),
      'can_write' => false
    ];
  }

  // Get current permissions
  $permResult = mysqli_query($db, "SELECT user_id, can_write FROM spreadsheet_permissions WHERE test_itemcode = '" . mysqli_real_escape_string($db, $testCode) . "'");
  while ($row = mysqli_fetch_assoc($permResult)) {
    $uid = trim($row['user_id']);
    if (isset($users[$uid])) {
      $users[$uid]['can_write'] = (bool)$row['can_write'];
    }
  }

  echo json_encode([
    'success' => true,
    'data' => array_values($users)
  ]);

} elseif ($method === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
  }

  $testCode = trim($input['test'] ?? '');
  $userId = trim($input['user_id'] ?? '');
  $canWrite = !empty($input['can_write']) ? 1 : 0;

  if (empty($testCode) || empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'Missing test or user_id']);
    exit;
  }

  $stmt = mysqli_prepare($db, "INSERT INTO spreadsheet_permissions (test_itemcode, user_id, can_write) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE can_write = VALUES(can_write)");
  mysqli_stmt_bind_param($stmt, 'ssi', $testCode, $userId, $canWrite);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Permission saved' : 'Save failed'
  ]);

} else {
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

mysqli_close($db);
