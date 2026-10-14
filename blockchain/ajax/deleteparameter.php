<?php
require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['parameterID'], $_POST['standardID'])) {
    echo json_encode($response);
    exit;
}

$parameterID = filter_var($_POST['parameterID'], FILTER_VALIDATE_INT);
$standardID  = filter_var($_POST['standardID'], FILTER_VALIDATE_INT);

if (!$parameterID || $parameterID <= 0 || !$standardID || $standardID <= 0) {
    $response['message'] = 'A valid ParameterID and StandardID are required.';
    echo json_encode($response);
    exit;
}

/*
 * Delete only the selected testparameters row within the selected standard.
 * Do not delete from sample_tests, test_results, or any other table.
 * Existing foreign-key references may still cause MySQL to reject deletion.
 */
$stmt = $conn->prepare(
    "DELETE FROM testparameters WHERE ParameterID = ? AND StandardID = ?"
);

if (!$stmt) {
    error_log(date('c') . " DELETE PREPARE ERROR: " . $conn->errno . " - " . $conn->error . PHP_EOL,
        3, __DIR__ . '/deleteparameter_debug.log');
    $response['message'] = 'Could not prepare parameter deletion.';
    echo json_encode($response);
    exit;
}

$stmt->bind_param('ii', $parameterID, $standardID);

if (!$stmt->execute()) {
    $dbError = 'DELETE EXECUTE ERROR: ' . $stmt->errno . ' - ' . $stmt->error;
    error_log(date('c') . ' ParameterID=' . $parameterID .
        ' StandardID=' . $standardID . ' ' . $dbError . PHP_EOL,
        3, __DIR__ . '/deleteparameter_debug.log');

    $response['message'] = 'Could not delete this parameter from the selected standard: ' . $dbError;
} elseif ($stmt->affected_rows > 0) {
    $response['success'] = true;
    $response['standardID'] = $standardID;
    $response['message'] = 'Parameter deleted successfully from the selected standard.';
} else {
    $response['message'] = 'No parameter matched the supplied ParameterID and StandardID.';
}

$stmt->close();
$conn->close();

echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
?>