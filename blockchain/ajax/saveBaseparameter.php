<?php

require '../db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// Sanitize all incoming POST strings — strip invalid UTF-8 bytes
array_walk_recursive($_POST, function (&$v) {
    if (is_string($v)) {
        $v = iconv('UTF-8', 'UTF-8//IGNORE', $v);
    }
});

$response = ['success' => false, 'message' => 'Invalid request'];

try {

    $ParameterID   = isset($_POST['ParameterIDForm']) ? intval($_POST['ParameterIDForm']) : null;
    $name          = trim($_POST['ParameterNameForm'] ?? '');
    $NeutralityID  = intval($_POST['neutralityID'] ?? 0);
    $TdsID         = intval($_POST['tdsID'] ?? 0);
    $ResultType    = $_POST['resultType'] ?? '';
    $limits        = trim($_POST['limits'] ?? '');
    $minLimit      = floatval($_POST['minLimit'] ?? 0);
    $maxLimit      = floatval($_POST['maxLimit'] ?? 0);
    $unitOfMeasure = trim($_POST['unitOfMeasure'] ?? '');
    $category      = trim($_POST['category'] ?? 'chemical');
    $method        = trim($_POST['method'] ?? '');
    $updatedAt     = date('Y-m-d H:i:s');
    $createdAt     = date('Y-m-d H:i:s');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Parameter name is required.']);
        exit;
    }

    // Check NeutralityID uniqueness
    if ($NeutralityID > 0) {
        $chk = $conn->prepare("SELECT ParameterID, ParameterName FROM baseparameters WHERE NeutralityID = ? AND ParameterID != ?");
        if (!$chk) throw new RuntimeException("SQL prepare failed (neutrality_check): " . $conn->error);
        $excludeID = $ParameterID ?: 0;
        $chk->bind_param("ii", $NeutralityID, $excludeID);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->bind_result($eid, $enm);
            $chk->fetch();
            echo json_encode(['success' => false, 'message' => "NeutralityID {$NeutralityID} is already assigned to: {$enm}"]);
            $chk->close();
            exit;
        }
        $chk->close();
    }

    // Check TdsID uniqueness
    if ($TdsID > 0) {
        $chk = $conn->prepare("SELECT ParameterID, ParameterName FROM baseparameters WHERE TdsID = ? AND ParameterID != ?");
        if (!$chk) throw new RuntimeException("SQL prepare failed (tds_check): " . $conn->error);
        $excludeID = $ParameterID ?: 0;
        $chk->bind_param("ii", $TdsID, $excludeID);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->bind_result($eid, $enm);
            $chk->fetch();
            echo json_encode(['success' => false, 'message' => "TdsID {$TdsID} is already assigned to: {$enm}"]);
            $chk->close();
            exit;
        }
        $chk->close();
    }

    if ($ParameterID) {

        // UPDATE
        $chk = $conn->prepare("SELECT ParameterID FROM baseparameters WHERE ParameterName = ? AND ParameterID != ?");
        if (!$chk) throw new RuntimeException("SQL prepare failed (update_name_check): " . $conn->error);
        $chk->bind_param("si", $name, $ParameterID);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Parameter name already exists.']);
            $chk->close();
            exit;
        }
        $chk->close();

        $q = "UPDATE baseparameters SET ParameterName = ?, NeutralityID = ?, TdsID = ?, ResultType = ?,
              Limits = ?, MinLimit = ?, MaxLimit = ?, UnitOfMeasure = ?, Category = ?, Method = ?, UpdatedAt = ?
              WHERE ParameterID = ?";
        $stmt = $conn->prepare($q);
        if (!$stmt) throw new RuntimeException("SQL prepare failed (update_baseparameters): " . $conn->error);
        $stmt->bind_param("siissssssssi", $name, $NeutralityID, $TdsID, $ResultType,
            $limits, $minLimit, $maxLimit, $unitOfMeasure, $category, $method, $updatedAt, $ParameterID);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Parameter updated successfully.'];
            $prop = $conn->prepare("UPDATE testparameters SET ParameterName = ?, Limits = ?, MinLimit = ?, MaxLimit = ?, UnitOfMeasure = ?, Method = ?, Category = ?, UpdatedAt = ? WHERE BaseID = ?  and Customized = 0");
            if ($prop) {
                $prop->bind_param("ssddssssi", $name, $limits, $minLimit, $maxLimit, $unitOfMeasure, $method, $category, $updatedAt, $ParameterID);
                $prop->execute();
                $prop->close();
            }
        } else {
            $response['message'] = $stmt->error;
        }
        $stmt->close();

    } else {

        // CREATE
        $chk = $conn->prepare("SELECT ParameterID FROM baseparameters WHERE ParameterName = ?");
        if (!$chk) throw new RuntimeException("SQL prepare failed (create_name_check): " . $conn->error);
        $chk->bind_param("s", $name);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->bind_result($existingID);
            $chk->fetch();
            echo json_encode(['success' => true, 'message' => 'Parameter already exists.', 'ParameterID' => $existingID]);
            $chk->close();
            exit;
        }
        $chk->close();

        $q = "INSERT INTO baseparameters (ParameterName, NeutralityID, TdsID, ResultType, Limits, MinLimit, MaxLimit, UnitOfMeasure, Category, Method, CreatedAt)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($q);
        if (!$stmt) throw new RuntimeException("SQL prepare failed (insert_baseparameters): " . $conn->error);
        $stmt->bind_param("siissssssss", $name, $NeutralityID, $TdsID, $ResultType,
            $limits, $minLimit, $maxLimit, $unitOfMeasure, $category, $method, $createdAt);

        if ($stmt->execute()) {
            $newID = $stmt->insert_id;
            $response = ['success' => true, 'message' => 'Parameter created successfully.', 'ParameterID' => $newID];
            $prop = $conn->prepare("UPDATE testparameters SET ParameterName = ?, Limits = ?, MinLimit = ?, MaxLimit = ?, UnitOfMeasure = ?, Method = ?, Category = ?, UpdatedAt = ? WHERE BaseID = ?");
            if ($prop) {
                $prop->bind_param("ssddssssi", $name, $limits, $minLimit, $maxLimit, $unitOfMeasure, $method, $category, $updatedAt, $newID);
                $prop->execute();
                $prop->close();
            }
        } else {
            $response['message'] = $stmt->error;
        }
        $stmt->close();
    }

} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
