<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BlockchainLIMS');
    session_start();
}

require '../db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userID = isset($_SESSION['UserID']) ? trim((string)$_SESSION['UserID']) : '';
if ($userID === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please sign in again.']);
    exit;
}

$sampleID = isset($_POST['sampleID']) ? trim((string)$_POST['sampleID']) : '';
$reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

if ($sampleID === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sample ID is required.']);
    exit;
}
if ($reason === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A reason is required before returning the sample to Analysis.']);
    exit;
}
if (strlen($reason) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The reason cannot exceed 2,000 characters.']);
    exit;
}

try {
    $conn->begin_transaction();

    // Lock and inspect every result row belonging to this sample.
    $checkStmt = $conn->prepare(
        "SELECT resultsID, StatusID
         FROM test_results
         WHERE SampleID = ?
         FOR UPDATE"
    );
    if (!$checkStmt) {
        throw new RuntimeException('Failed to prepare sample validation: ' . $conn->error);
    }

    $checkStmt->bind_param('s', $sampleID);
    if (!$checkStmt->execute()) {
        throw new RuntimeException('Failed to inspect sample results: ' . $checkStmt->error);
    }

    $checkResult = $checkStmt->get_result();
    $totalRows = 0;
    $approvedRows = 0;
    while ($row = $checkResult->fetch_assoc()) {
        $totalRows++;
        if ((int)$row['StatusID'] === 4) {
            $approvedRows++;
        }
    }
    $checkStmt->close();

    if ($totalRows === 0) {
        throw new RuntimeException('No result records were found for this sample.');
    }
    if ($approvedRows === 0) {
        throw new RuntimeException('This sample no longer has approved results to roll back.');
    }

    // Reset the complete sample block to Analysis (StatusID 1).
    $updateStmt = $conn->prepare(
        "UPDATE test_results
         SET StatusID = 1,
             rollback = IFNULL(rollback, 0) + 1
         WHERE SampleID = ?
           AND StatusID <> 1"
    );
    if (!$updateStmt) {
        throw new RuntimeException('Failed to prepare the sample-block reset: ' . $conn->error);
    }

    $updateStmt->bind_param('s', $sampleID);
    if (!$updateStmt->execute()) {
        throw new RuntimeException('Failed to reset the sample block: ' . $updateStmt->error);
    }

    $updatedRows = (int)$updateStmt->affected_rows;
    $updateStmt->close();

    if ($updatedRows < 1) {
        throw new RuntimeException('No result records required resetting. The sample may already be in Analysis.');
    }

    // Keep the business audit record in the same transaction as the status reset.
    $previousStatus = 4;
    $newStatus = 1;
    $auditStmt = $conn->prepare(
        "INSERT INTO sample_rollback_audit
            (sample_id, reason, initiated_by, previous_status, new_status, affected_rows)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$auditStmt) {
        throw new RuntimeException('Failed to prepare sample rollback audit: ' . $conn->error);
    }

    $auditStmt->bind_param('sssiii', $sampleID, $reason, $userID, $previousStatus, $newStatus, $updatedRows);
    if (!$auditStmt->execute()) {
        throw new RuntimeException('Failed to record sample rollback audit: ' . $auditStmt->error);
    }
    $auditID = (int)$auditStmt->insert_id;
    $auditStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'The complete sample block has been returned to Analysis and the reason has been recorded.',
        'sampleID' => $sampleID,
        'auditID' => $auditID,
        'approvedRows' => $approvedRows,
        'updatedRows' => $updatedRows,
        'newStatus' => $newStatus
    ]);
} catch (Throwable $throwable) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $throwable->getMessage()]);
}

$conn->close();
?>
