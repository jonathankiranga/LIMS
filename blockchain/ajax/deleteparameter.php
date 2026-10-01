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
            $stmt = $conn->prepare("DELETE FROM testparameters WHERE ParameterID = ?");
            $stmt->bind_param('i', $parameterID);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Parameter deleted successfully.';
            } else {
                $conn->rollback();
                $response['message'] = 'Failed to delete parameter. It may still be referenced by sample tests or results.';
            }
            $stmt->close();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>