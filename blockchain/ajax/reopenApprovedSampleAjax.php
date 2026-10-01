<?php
require '../db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$resultsID = isset($_POST['resultsID']) ? (int) $_POST['resultsID'] : 0;
$sampleID = trim($_POST['sampleID'] ?? '');
$currentStatus = isset($_POST['currentStatus']) ? (int) $_POST['currentStatus'] : 0;

if ($resultsID <= 0 || $sampleID === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Sample information is incomplete.'
    ]);
    exit;
}

if ($currentStatus !== 4) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Only approved samples with status 4 can be reopened.'
    ]);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE test_results
     SET StatusID = 1, rollback = IFNULL(rollback, 0) + 1
     WHERE resultsID = ? AND SampleID = ? AND StatusID = 4"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare the status update.'
    ]);
    exit;
}

$stmt->bind_param('is', $resultsID, $sampleID);
$stmt->execute();

if ($stmt->error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update sample status: ' . $stmt->error
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($stmt->affected_rows < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'No approved sample row was updated. The status may already have changed.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($stmt->affected_rows < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'No approved sample row was updated. The status may already have changed.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

// Get the rollback count
$rollbackCount = 0;
$getRollback = $conn->prepare("SELECT rollback FROM test_results WHERE resultsID = ?");
$getRollback->bind_param('i', $resultsID);
$getRollback->execute();
$getRollback->bind_result($rollbackCount);
$getRollback->fetch();
$getRollback->close();

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Sample status Rolled back successfully.',
    'resultsID' => $resultsID,
    'sampleID' => $sampleID,
    'oldStatus' => 4,
    'newStatus' => 1,
    'rollback' => $rollbackCount
]);
?>
