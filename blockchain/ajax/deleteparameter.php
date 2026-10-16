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

/* Compatibility endpoint: "delete" means hide. Never physically delete rows. */
$stmt = $conn->prepare(
    "UPDATE testparameters SET IsActive = 0 WHERE ParameterID = ? AND StandardID = ?"
);

if (!$stmt) {
    error_log(date('c') . " HIDE PREPARE ERROR: " . $conn->errno . " - " . $conn->error . PHP_EOL,
        3, __DIR__ . '/deleteparameter_debug.log');
    $response['message'] = 'Could not prepare parameter status change.';
    echo json_encode($response);
    exit;
}

$stmt->bind_param('ii', $parameterID, $standardID);

if (!$stmt->execute()) {
    $dbError = 'HIDE EXECUTE ERROR: ' . $stmt->errno . ' - ' . $stmt->error;
    error_log(date('c') . ' ParameterID=' . $parameterID .
        ' StandardID=' . $standardID . ' ' . $dbError . PHP_EOL,
        3, __DIR__ . '/deleteparameter_debug.log');
    $response['message'] = 'Could not hide this parameter: ' . $dbError;
} else {
    $check = $conn->prepare(
        "SELECT IsActive FROM testparameters WHERE ParameterID = ? AND StandardID = ?"
    );
    if ($check) {
        $check->bind_param('ii', $parameterID, $standardID);
        $check->execute();
        $result = $check->get_result();
        if ($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['standardID'] = $standardID;
            $response['isActive'] = (int)$row['IsActive'];
            $response['message'] = 'Parameter hidden. Existing records were preserved.';
        } else {
            $response['message'] = 'No parameter matched the supplied ParameterID and StandardID.';
        }
        $check->close();
    } else {
        $response['message'] = 'Status changed, but confirmation query could not be prepared.';
    }
}

$stmt->close();
$conn->close();
echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
?>