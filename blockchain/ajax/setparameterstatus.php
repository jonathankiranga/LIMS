<?php
require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['parameterID'], $_POST['standardID'], $_POST['isActive'])) {
    echo json_encode($response);
    exit;
}

$parameterID = filter_var($_POST['parameterID'], FILTER_VALIDATE_INT);
$standardID = filter_var($_POST['standardID'], FILTER_VALIDATE_INT);
$isActive = filter_var($_POST['isActive'], FILTER_VALIDATE_INT);

if (!$parameterID || $parameterID <= 0 || !$standardID || $standardID <= 0 ||
    ($isActive !== 0 && $isActive !== 1)) {
    $response['message'] = 'Valid ParameterID, StandardID, and IsActive (0 or 1) are required.';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE testparameters SET IsActive = ? WHERE ParameterID = ? AND StandardID = ?"
);

if (!$stmt) {
    error_log(date('c') . " STATUS PREPARE ERROR: " . $conn->errno . " - " . $conn->error . PHP_EOL,
        3, __DIR__ . '/parameterstatus_debug.log');
    $response['message'] = 'Could not prepare parameter status update.';
    echo json_encode($response);
    exit;
}

$stmt->bind_param('iii', $isActive, $parameterID, $standardID);

if (!$stmt->execute()) {
    error_log(date('c') . " STATUS EXECUTE ERROR: " . $stmt->errno . " - " . $stmt->error . PHP_EOL,
        3, __DIR__ . '/parameterstatus_debug.log');
    $response['message'] = 'Could not update parameter status.';
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
            $response['isActive'] = (int)$row['IsActive'];
            $response['message'] = $isActive === 1
                ? 'Parameter activated for new work.'
                : 'Parameter hidden from new work. Existing records were preserved.';
        } else {
            $response['message'] = 'No parameter matched the supplied ParameterID and StandardID.';
        }
        $check->close();
    } else {
        $response['message'] = 'Status updated, but confirmation query could not be prepared.';
    }
}

$stmt->close();
$conn->close();
echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
?>