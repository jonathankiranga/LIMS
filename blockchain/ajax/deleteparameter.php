<?php
require '../db_connection.php';

$response = ['success' => false, 'message' => 'Invalid request'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parameterID'])) {
    $parameterID = intval($_POST['parameterID']);
    $standardID  = isset($_POST['standardID']) ? intval($_POST['standardID']) : null;

    // Confirm the row exists (optionally scoped to the given standard)
    $sql = "SELECT ParameterID FROM testparameters WHERE ParameterID = ?";
    if ($standardID > 0) {
        $sql .= " AND StandardID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $parameterID, $standardID);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $parameterID);
    }
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$exists) {
        $response['message'] = 'No matching parameter found for this standard. It may already be deleted.';
    } else {
        // Give a clear message when the parameter is referenced by test results
        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM test_results WHERE ParameterID = ?");
        $check->bind_param('i', $parameterID);
        $check->execute();
        $used = intval($check->get_result()->fetch_assoc()['cnt']);
        $check->close();

        if ($used > 0) {
            $response['message'] = 'Parameter is in use by ' . $used . ' test result(s) and cannot be deleted.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM testparameters WHERE ParameterID = ?");
                if (!$stmt) {
                    throw new Exception('DELETE PREPARE ERROR: ' . $conn->errno . ' - ' . $conn->error);
                }

                $stmt->bind_param('i', $parameterID);
                if (!$stmt->execute()) {
                    $dbError = 'DELETE EXECUTE ERROR: ' . $stmt->errno . ' - ' . $stmt->error;
                    error_log(date('c') . ' ParameterID=' . $parameterID . ' StandardID=' . (string)$standardID . ' ' . $dbError . PHP_EOL, 3, __DIR__ . '/deleteparameter_debug.log');
                    throw new Exception($dbError);
                }

                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = 'Parameter deleted successfully.';
                } else {
                    $conn->rollback();
                    $response['message'] = 'No row was deleted. Check that the parameter still exists and matches the selected standard.';
                }
                $stmt->close();
            } catch (Throwable $e) {
                $conn->rollback();
                $dbError = $e->getMessage();
                error_log(date('c') . ' ParameterID=' . $parameterID . ' StandardID=' . (string)$standardID . ' ' . $dbError . PHP_EOL, 3, __DIR__ . '/deleteparameter_debug.log');
                $response['message'] = 'Database could not delete this parameter: ' . $dbError;
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>