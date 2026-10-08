<?php
// Include database connection
require '../db_connection.php';

header('Content-Type: application/json');

$debugLog = __DIR__ . '/saveparameter_debug.log';

function dbLog($message, $data = null)
{
    global $debugLog;

    $line = date('Y-m-d H:i:s') . ' | ' . $message;

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $line .= ' | ' . print_r($data, true);
        } else {
            $line .= ' | ' . $data;
        }
    }

    file_put_contents($debugLog, $line . PHP_EOL, FILE_APPEND);
}

function outputResponse($response)
{
    dbLog('FINAL RESPONSE', $response);
    dbLog('END REQUEST');
    dbLog('============================================================');

    echo json_encode($response);
    exit;
}

dbLog('============================================================');
dbLog('START saveparameter.php');
dbLog('REQUEST METHOD', $_SERVER['REQUEST_METHOD'] ?? '');
dbLog('POST DATA', $_POST);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    outputResponse([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$response = [
    'success' => false,
    'message' => 'Invalid request'
];

dbLog('DATABASE CONNECTION CHECK');

if (!isset($conn)) {
    outputResponse([
        'success' => false,
        'message' => 'Database connection variable $conn does not exist.'
    ]);
}

if ($conn->connect_errno) {
    outputResponse([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_errno . ' - ' . $conn->connect_error
    ]);
}

dbLog('DATABASE CONNECTION OK');

$ParameterID   = isset($_POST['ParameterID']) && $_POST['ParameterID'] !== '' ? intval($_POST['ParameterID']) : null;
$name          = trim($_POST['ParameterName'] ?? '');
$StandardID    = isset($_POST['StandardID']) && $_POST['StandardID'] !== '' ? intval($_POST['StandardID']) : null;
$UnitOfMeasure = trim($_POST['UnitOfMeasure'] ?? '');
$MinLimit      = isset($_POST['MinLimit']) && $_POST['MinLimit'] !== '' ? (float)$_POST['MinLimit'] : null;
$MaxLimit      = isset($_POST['MaxLimit']) && $_POST['MaxLimit'] !== '' ? (float)$_POST['MaxLimit'] : null;
$Limits        = trim($_POST['Limits'] ?? '');
$Method        = trim($_POST['Method'] ?? '');
$matrixID      = isset($_POST['GlobalParameterID']) && $_POST['GlobalParameterID'] !== '' ? intval($_POST['GlobalParameterID']) : null;
$Category      = trim($_POST['Category'] ?? '');
$AccreditationStatus = trim($_POST['AccreditationStatus'] ?? 'not_accredited');
$MRL           = isset($_POST['MRL']) && $_POST['MRL'] !== '' ? (float)$_POST['MRL'] : null;
$MRLUnit       = trim($_POST['MRLUnit'] ?? '');
$updatedAt     = date('Y-m-d H:i:s');

dbLog('PARSED VALUES', [
    'ParameterID' => $ParameterID,
    'StandardID' => $StandardID,
    'ParameterName' => $name,
    'UnitOfMeasure' => $UnitOfMeasure,
    'MinLimit' => $MinLimit,
    'MaxLimit' => $MaxLimit,
    'Limits' => $Limits,
    'Method' => $Method,
    'GlobalParameterID' => $matrixID,
    'Category' => $Category,
    'MRL' => $MRL,
    'MRLUnit' => $MRLUnit
]);

$allowedAccreditationStatuses = ['accredited', 'not_accredited', 'contracted'];
if (!in_array($AccreditationStatus, $allowedAccreditationStatuses, true)) {
    $AccreditationStatus = 'not_accredited';
}

if (empty($name)) {
    outputResponse([
        'success' => false,
        'message' => 'Name is required.'
    ]);
}

if (empty($ParameterID) && empty($matrixID)) {
    outputResponse([
        'success' => false,
        'message' => 'Select a parameter from the list of parameters.'
    ]);
}

if ($matrixID !== null) {

    dbLog('DB #1 PREPARE: CHECK BASE PARAMETER');

    $checkSql = "SELECT ParameterID FROM baseparameters WHERE ParameterID = ?";
    dbLog('DB #1 SQL', $checkSql);
    dbLog('DB #1 PARAMETER', $matrixID);

    $checkBase = $conn->prepare($checkSql);

    if (!$checkBase) {
        outputResponse([
            'success' => false,
            'message' => 'DB #1 PREPARE ERROR: ' . $conn->errno . ' - ' . $conn->error
        ]);
    }

    dbLog('DB #1 PREPARE OK');

    $checkBase->bind_param("i", $matrixID);
    dbLog('DB #1 BIND OK: type=i');

    if (!$checkBase->execute()) {
        outputResponse([
            'success' => false,
            'message' => 'DB #1 EXECUTE ERROR: ' . $checkBase->errno . ' - ' . $checkBase->error
        ]);
    }

    dbLog('DB #1 EXECUTE OK');

    $baseResult = $checkBase->get_result();

    if (!$baseResult) {
        outputResponse([
            'success' => false,
            'message' => 'DB #1 RESULT ERROR: ' . $checkBase->errno . ' - ' . $checkBase->error
        ]);
    }

    dbLog('DB #1 ROW COUNT', $baseResult->num_rows);

    if ($baseResult->num_rows === 0) {
        $checkBase->close();

        outputResponse([
            'success' => false,
            'message' => 'Invalid BaseID: ' . $matrixID . ' does not exist in baseparameters table.'
        ]);
    }

    $checkBase->close();
    dbLog('DB #1 COMPLETE');
}

try {

    if ($ParameterID) {

        dbLog('DB #2 PREPARE: UPDATE TESTPARAMETERS');

        $query = "UPDATE `testparameters`
                  SET
                  `ParameterName` = ?,
                  `Limits` = ?,
                  `MinLimit` = ?,
                  `MaxLimit` = ?,
                  `Method` = ?,
                  `BaseID` = COALESCE(?, BaseID),
                  `Category` = ?,
                  `MRL` = ?,
                  `MRLUnit` = ?,
                  `UpdatedAt` = ?,
                  `UnitOfMeasure` = ?
                  WHERE `ParameterID` = ? AND `StandardID` = ?";

        dbLog('DB #2 SQL', $query);

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            outputResponse([
                'success' => false,
                'message' => 'DB #2 PREPARE ERROR: ' . $conn->errno . ' - ' . $conn->error
            ]);
        }

        dbLog('DB #2 PREPARE OK');

        $bindTypes="ssddsisdssssii";
        dbLog('DB #2 BIND TYPES', $bindTypes);

        $stmt->bind_param(
            $bindTypes,
            $name,
            $Limits,
            $MinLimit,
            $MaxLimit,
            $Method,
            $matrixID,
            $Category,
            $AccreditationStatus,
            $MRL,
            $MRLUnit,
            $updatedAt,
            $UnitOfMeasure,
            $ParameterID,
            $StandardID
        );

        dbLog('DB #2 BIND OK');
        dbLog('DB #2 BEFORE EXECUTE');

        if (!$stmt->execute()) {
            dbLog('DB #2 EXECUTE FAILED', [
                'errno' => $stmt->errno,
                'error' => $stmt->error,
                'connection_errno' => $conn->errno,
                'connection_error' => $conn->error
            ]);

            outputResponse([
                'success' => false,
                'message' => 'DB #2 UPDATE ERROR: ' . $stmt->errno . ' - ' . $stmt->error
            ]);
        }

        dbLog('DB #2 EXECUTE OK');
        dbLog('DB #2 AFFECTED ROWS', $stmt->affected_rows);

        $stmt->close();

        $response['success'] = true;
        $response['message'] = 'Test Parameters updated successfully.';

    } else {

        dbLog('DB #2 PREPARE: INSERT TESTPARAMETERS');

        $query = "INSERT INTO TestParameters
                  (ParameterName, StandardID, MinLimit, MaxLimit, Limits, UnitOfMeasure, Method, BaseID, Category, AccreditationStatus, MRL, MRLUnit, CreatedAt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $createdAt = date('Y-m-d H:i:s');

        dbLog('DB #2 SQL', $query);

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            outputResponse([
                'success' => false,
                'message' => 'DB #2 PREPARE ERROR: ' . $conn->errno . ' - ' . $conn->error
            ]);
        }

        dbLog('DB #2 PREPARE OK');

        $bindTypes = "siddsssissdss";
        dbLog('DB #2 BIND TYPES', $bindTypes);

        $stmt->bind_param(
            $bindTypes,
            $name,
            $StandardID,
            $MinLimit,
            $MaxLimit,
            $Limits,
            $UnitOfMeasure,
            $Method,
            $matrixID,
            $Category,
            $MRL,
            $MRLUnit,
            $createdAt
        );

        dbLog('DB #2 BIND OK');
        dbLog('DB #2 BEFORE EXECUTE');

        if (!$stmt->execute()) {
            dbLog('DB #2 EXECUTE FAILED', [
                'errno' => $stmt->errno,
                'error' => $stmt->error,
                'connection_errno' => $conn->errno,
                'connection_error' => $conn->error
            ]);

            outputResponse([
                'success' => false,
                'message' => 'DB #2 INSERT ERROR: ' . $stmt->errno . ' - ' . $stmt->error
            ]);
        }

        dbLog('DB #2 EXECUTE OK');
        dbLog('DB #2 INSERT ID', $stmt->insert_id);

        $stmt->close();

        $response['success'] = true;
        $response['message'] = 'Test Parameters created successfully.';
    }

} catch (Throwable $e) {

    dbLog('PHP/THROWABLE ERROR', [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    $response['message'] = 'Error: ' . $e->getMessage();
}

outputResponse($response);
?>