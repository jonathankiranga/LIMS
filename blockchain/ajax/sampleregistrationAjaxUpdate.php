<?php
require_once '../db_connection.php';
require_once '../functions/functions.php';
require_once '../functions/tasks.php';
require_once 'getrefferencesfunction.inc';

header('Content-Type: application/json; charset=utf-8');

function editRegistrationResponse($success, $message, $extra = array()) {
    echo json_encode(array_merge(array(
        'success' => $success,
        'status' => $success ? 'success' : 'error',
        'message' => $message
    ), $extra));
    exit;
}

function editRegistrationDate($value) {
    $value = trim((string)$value);
    if ($value === '') return null;
    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    editRegistrationResponse(false, 'Invalid request method.');
}

$username = trim((string)($_POST['username'] ?? ''));
$userId = trim((string)($_POST['user_id'] ?? ''));
$headerId = isset($_POST['HeaderID']) ? (int)$_POST['HeaderID'] : 0;
$documentNo = trim((string)($_POST['batchno'] ?? $_POST['documentno'] ?? ''));
$date = editRegistrationDate($_POST['date'] ?? '');
$samplingDate = editRegistrationDate($_POST['samplingdate'] ?? '');
$customerName = trim((string)($_POST['CustomerName'] ?? ''));
$customerId = trim((string)($_POST['CustomerID'] ?? ''));
$sampledBy = trim((string)($_POST['sampledby'] ?? ''));
$samplingMethod = trim((string)($_POST['SamplingMethod'] ?? ''));
$orderNo = trim((string)($_POST['Orderno'] ?? ''));

if ($username === '' || $userId === '' || $headerId < 1) {
    editRegistrationResponse(false, 'Your session or registration reference is missing. Reload the registration and try again.');
}

$required = array(
    'Registration date' => $date,
    'Sampling date' => $samplingDate,
    'Client name' => $customerName,
    'Client account' => $customerId,
    'Sampled by' => $sampledBy,
    'Sampling method' => $samplingMethod,
    'Customer LPO number' => $orderNo
);
$missing = array();
foreach ($required as $label => $value) {
    if ($value === null || $value === '') $missing[] = $label;
}
if ($missing) {
    editRegistrationResponse(false, 'Complete the following fields: ' . implode(', ', $missing));
}
if (strtotime($samplingDate) > strtotime($date)) {
    editRegistrationResponse(false, 'The sampling date cannot be later than the registration date.');
}

$testIds = $_POST['rowindex'] ?? array();
$sampleIds = $_POST['SampleID'] ?? array();
$standardIds = $_POST['standard_id'] ?? array();
$matrixIds = $_POST['matrix_id'] ?? array();
$kitUnits = $_POST['standard_kit_units'] ?? array();
$batchNos = $_POST['product_batch_no'] ?? array();
$batchSizes = $_POST['batch_size'] ?? array();
$manufactureDates = $_POST['date_of_manufacture'] ?? array();
$expiryDates = $_POST['date_of_expiry'] ?? array();
$chilledDates = $_POST['chilled_date_of_expiry'] ?? array();
$frozenDates = $_POST['frozen_date_of_expiry'] ?? array();
$sampleSources = $_POST['sample_source'] ?? array();
$sampleNames = $_POST['sample_name'] ?? array();
$sampleMethods = $_POST['sample_method'] ?? array();
$conditions = $_POST['condition_of_sample'] ?? array();

$rowCount = is_array($testIds) ? count($testIds) : 0;
if ($rowCount < 1 || !is_array($sampleIds) || count($sampleIds) !== $rowCount) {
    editRegistrationResponse(false, 'No valid sample rows were submitted. Reload the registration and try again.');
}

$headerStmt = $conn->prepare("SELECT HeaderID, DocumentNo FROM sample_header WHERE HeaderID = ? LIMIT 1");
if (!$headerStmt) editRegistrationResponse(false, 'Could not validate registration: ' . $conn->error);
$headerStmt->bind_param('i', $headerId);
if (!$headerStmt->execute()) editRegistrationResponse(false, 'Could not validate registration: ' . $headerStmt->error);
$headerResult = $headerStmt->get_result();
$header = $headerResult ? $headerResult->fetch_assoc() : null;
$headerStmt->close();

if (!$header) editRegistrationResponse(false, 'The selected registration no longer exists.');
$documentNo = $header['DocumentNo'];

$uploadDir = '../uploads/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    editRegistrationResponse(false, 'The sample image upload directory is unavailable.');
}

$uploadedFiles = array();
$transactionStarted = false;

try {
    if (!$conn->begin_transaction()) {
        throw new Exception('Could not start the registration update transaction: ' . $conn->error);
    }
    $transactionStarted = true;

    $headerUpdate = $conn->prepare(
        "UPDATE sample_header
         SET Date = ?, CustomerName = ?, CustomerID = ?, SampledBy = ?,
             SamplingMethod = ?, SamplingDate = ?, OrderNo = ?, User_name = ?
         WHERE HeaderID = ?"
    );
    if (!$headerUpdate) throw new Exception('Preparing registration update failed: ' . $conn->error);
    $headerUpdate->bind_param(
        'ssssssssi',
        $date, $customerName, $customerId, $sampledBy,
        $samplingMethod, $samplingDate, $orderNo, $username, $headerId
    );
    if (!$headerUpdate->execute()) throw new Exception('Updating registration details failed: ' . $headerUpdate->error);
    $headerUpdate->close();

    $sampleLookup = $conn->prepare(
        "SELECT TestID, SampleID, StandardID, BaseID, SampleFileKey
         FROM sample_tests
         WHERE TestID = ? AND HeaderID = ?
         LIMIT 1"
    );
    if (!$sampleLookup) throw new Exception('Preparing sample validation failed: ' . $conn->error);

    $sampleUpdate = $conn->prepare(
        "UPDATE sample_tests
         SET SKU = ?, BatchNo = ?, BatchSize = ?, ManufactureDate = ?, ExpDate = ?,
             chilled_date_of_expiry = ?, frozen_date_of_expiry = ?,
             ExternalSample = ?, sample_name = ?, sample_method = ?,
             condition_of_sample = ?, User_name = ?, StandardID = ?, BaseID = ?
         WHERE TestID = ? AND HeaderID = ? AND SampleID = ?"
    );
    if (!$sampleUpdate) throw new Exception('Preparing sample update failed: ' . $conn->error);

    $resultCheck = $conn->prepare("SELECT COUNT(*) AS result_count FROM test_results WHERE TestID = ? AND HeaderID = ?");
    if (!$resultCheck) throw new Exception('Preparing result protection check failed: ' . $conn->error);

    $resultInsert = $conn->prepare(
        "INSERT IGNORE INTO test_results
         (TestID, HeaderID, SampleID, StandardID, ParameterID, User_name, StatusID, BaseID)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?)"
    );
    if (!$resultInsert) throw new Exception('Preparing missing-parameter insert failed: ' . $conn->error);

    $existingRowIds = array();
    $ledgerRows = array();
    for ($i = 0; $i < $rowCount; $i++) {
        $testId = isset($testIds[$i]) ? (int)$testIds[$i] : 0;
        $sampleId = trim((string)($sampleIds[$i] ?? ''));
        $standardId = isset($standardIds[$i]) ? (int)$standardIds[$i] : 0;
        $matrixId = isset($matrixIds[$i]) ? (int)$matrixIds[$i] : 0;

        if ($testId < 1 || $sampleId === '' || $standardId < 1) {
            throw new Exception('A sample row is missing its TestID, SampleID or standard. Reload the registration before saving.');
        }
        if (isset($existingRowIds[$testId])) {
            throw new Exception('The same sample row was submitted more than once.');
        }
        $existingRowIds[$testId] = true;

        $sampleLookup->bind_param('ii', $testId, $headerId);
        if (!$sampleLookup->execute()) throw new Exception('Checking sample row failed: ' . $sampleLookup->error);
        $sampleResult = $sampleLookup->get_result();
        $existing = $sampleResult ? $sampleResult->fetch_assoc() : null;
        if (!$existing || (string)$existing['SampleID'] !== $sampleId) {
            throw new Exception('A sample row does not belong to this registration. Reload the page before saving.');
        }

        $oldStandardId = (int)$existing['StandardID'];
        $oldMatrixId = (int)$existing['BaseID'];
        $resultCheck->bind_param('ii', $testId, $headerId);
        if (!$resultCheck->execute()) throw new Exception('Checking existing test results failed: ' . $resultCheck->error);
        $countResult = $resultCheck->get_result()->fetch_assoc();
        $hasResults = (int)($countResult['result_count'] ?? 0) > 0;

        if (($standardId !== $oldStandardId || $matrixId !== $oldMatrixId) && $hasResults) {
            throw new Exception(
                'Standard or matrix changes are blocked for sample ' . $sampleId .
                ' because test-result records already exist. Existing results have been protected.'
            );
        }

        $sku = (string)($kitUnits[$i] ?? '');
        $batchNo = (string)($batchNos[$i] ?? '');
        $batchSize = (isset($batchSizes[$i]) && $batchSizes[$i] !== '') ? (int)$batchSizes[$i] : null;
        $manufactureDate = editRegistrationDate($manufactureDates[$i] ?? '');
        $expiryDate = editRegistrationDate($expiryDates[$i] ?? '');
        $chilledDate = editRegistrationDate($chilledDates[$i] ?? '');
        $frozenDate = editRegistrationDate($frozenDates[$i] ?? '');
        $sampleSource = (string)($sampleSources[$i] ?? '');
        $sampleName = (string)($sampleNames[$i] ?? '');
        $sampleMethod = (string)($sampleMethods[$i] ?? '');
        $condition = (string)($conditions[$i] ?? '');

        if ($manufactureDate && $expiryDate && strtotime($expiryDate) <= strtotime($manufactureDate)) {
            throw new Exception('Expiry date must be later than manufacture date for sample ' . $sampleId . '.');
        }

        $sampleFileKey = $existing['SampleFileKey'];
        if (isset($_FILES['sample_images']['error'][$i]) &&
            $_FILES['sample_images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['sample_images']['tmp_name'][$i];
            $originalName = $_FILES['sample_images']['name'][$i] ?? '';
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            if (!in_array($extension, $allowed, true)) {
                throw new Exception('Unsupported image type for sample ' . $sampleId . '. Use JPG, PNG or GIF.');
            }
            $safeSampleId = preg_replace('/[^A-Za-z0-9_-]/', '_', $sampleId);\n            $newFileName = $safeSampleId . '_' . date('YmdHis') . '_' . substr(sha1(uniqid('', true)), 0, 8) . '.' . $extension;
            $destination = $uploadDir . $newFileName;
            if (!move_uploaded_file($tmpName, $destination)) {
                throw new Exception('The image for sample ' . $sampleId . ' could not be uploaded.');
            }
            $uploadedFiles[] = $destination;
            $sampleFileKey = $destination;
        }

        $sampleUpdate->bind_param(
            'ssisssssssssiiiis',
            $sku, $batchNo, $batchSize, $manufactureDate, $expiryDate,
            $chilledDate, $frozenDate, $sampleSource, $sampleName, $sampleMethod,
            $condition, $username, $standardId, $matrixId, $testId, $headerId, $sampleId
        );
        if (!$sampleUpdate->execute()) {
            throw new Exception('Updating sample ' . $sampleId . ' failed: ' . $sampleUpdate->error);
        }

        if ($sampleFileKey !== $existing['SampleFileKey']) {
            $imageUpdate = $conn->prepare(
                "UPDATE sample_tests SET SampleFileKey = ? WHERE TestID = ? AND HeaderID = ? AND SampleID = ?"
            );
            if (!$imageUpdate) throw new Exception('Preparing image update failed: ' . $conn->error);
            $imageUpdate->bind_param('siis', $sampleFileKey, $testId, $headerId, $sampleId);
            if (!$imageUpdate->execute()) throw new Exception('Saving sample image failed: ' . $imageUpdate->error);
            $imageUpdate->close();
        }

        $ledgerRows[] = array(
            'TestID' => $testId,
            'SampleID' => $sampleId,
            'StandardID' => $standardId,
            'BaseID' => $matrixId,
            'SKU' => $sku,
            'BatchNo' => $batchNo,
            'BatchSize' => $batchSize,
            'ManufactureDate' => $manufactureDate,
            'ExpDate' => $expiryDate,
            'ChilledExpiry' => $chilledDate,
            'FrozenExpiry' => $frozenDate,
            'ExternalSample' => $sampleSource,
            'sample_name' => $sampleName,
            'sample_method' => $sampleMethod,
            'condition_of_sample' => $condition,
            'SampleFileKey' => $sampleFileKey
        );

        if (!$hasResults) {
            if ($matrixId > 0) {
                $availableParameters = gettestparameters($standardId, $matrixId);
            } else {
                $availableParameters = getALLtestparametersFromTestID($testId, $standardId);
            }

            $selectedKey = 'selected_params_' . ($i + 1);
            $selectedIds = isset($_POST[$selectedKey]) && is_array($_POST[$selectedKey])
                ? array_map('intval', $_POST[$selectedKey]) : array();

            foreach ($availableParameters as $parameter) {
                $parameterId = (int)($parameter['ParameterID'] ?? 0);
                $parameterStandardId = (int)($parameter['StandardID'] ?? $standardId);
                $baseId = isset($parameter['BaseID']) ? (int)$parameter['BaseID'] : 0;
                if ($parameterId < 1 || $parameterStandardId !== $standardId) continue;
                if ($selectedIds && $standardId !== $oldStandardId && !in_array($parameterId, $selectedIds, true)) continue;

                $resultInsert->bind_param(
                    'iisiisi',
                    $testId, $headerId, $sampleId, $standardId,
                    $parameterId, $username, $baseId
                );
                if (!$resultInsert->execute()) {
                    throw new Exception('Adding a test parameter for sample ' . $sampleId . ' failed: ' . $resultInsert->error);
                }
            }
        }
    }

    $sampleLookup->close();
    $sampleUpdate->close();
    $resultCheck->close();
    $resultInsert->close();

    // Add a signed blockchain ledger record when this user's key is available.
    $ledgerHash = '';
    $keyDirectory = 'userkeys/' . $userId;
    $privateKeyPath = $keyDirectory . '/private_key.pem';
    if (is_file($privateKeyPath) && is_readable($privateKeyPath)) {
        $privateKey = file_get_contents($privateKeyPath);
        if ($privateKey !== false && $privateKey !== '') {
            $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
            if (!$lastBlockQuery) throw new Exception('Reading the blockchain ledger failed: ' . $conn->error);
            $lastBlock = $lastBlockQuery->fetch_assoc();
            $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat('0', 64);
            $payload = json_encode(array(
                'action' => 'sample_registration_updated',
                'HeaderID' => $headerId,
                'DocumentNo' => $documentNo,
                'registration' => array(
                    'Date' => $date,
                    'CustomerName' => $customerName,
                    'CustomerID' => $customerId,
                    'SampledBy' => $sampledBy,
                    'SamplingMethod' => $samplingMethod,
                    'SamplingDate' => $samplingDate,
                    'OrderNo' => $orderNo
                ),
                'samples' => $ledgerRows,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s')
            ), JSON_UNESCAPED_UNICODE);
            $currentHash = hash('sha256', $payload . '|' . $previousHash);
            $ledgerHash = $currentHash;
            $signature = signData($currentHash, $privateKey);
            if ($signature !== false) {
                $encryptedData = encryptPrivateKey($payload . '|' . $previousHash, $privateKey);
                $ledger = $conn->prepare(
                    "INSERT INTO blockchain_ledger (timestamp, previous_hash, current_hash, digital_signature, encrypted_data, status, userid)
                     VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, 'active', ?)"
                );
                if (!$ledger) throw new Exception('Preparing blockchain ledger entry failed: ' . $conn->error);
                $ledger->bind_param('sssss', $previousHash, $currentHash, $signature, $encryptedData, $userId);
                if (!$ledger->execute()) throw new Exception('Writing blockchain ledger entry failed: ' . $ledger->error);
                $blockId = $conn->insert_id;
                $ledger->close();
                log_transaction_metadata($conn, $blockId, $headerId, 'sample_header');
            }
        }
    }

    if (function_exists('logAction')) {
        logAction($conn, 'UPDATE', $documentNo, $ledgerHash, $userId, 'SUCCESS');
    }

    $conn->commit();
    $transactionStarted = false;
    editRegistrationResponse(true, 'Registration ' . $documentNo . ' updated successfully.', array(
        'HeaderID' => $headerId,
        'rows_updated' => $rowCount
    ));
} catch (Throwable $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    foreach ($uploadedFiles as $uploadedFile) {
        if (is_file($uploadedFile)) @unlink($uploadedFile);
    }
    if (function_exists('logAction')) {
        try {
            logAction($conn, 'UPDATE', $documentNo, isset($ledgerHash) ? $ledgerHash : '', $userId, 'FAILED', $e->getMessage());
        } catch (Throwable $logError) {
            error_log('Sample registration failure audit could not be written: ' . $logError->getMessage());
        }
    }
    editRegistrationResponse(false, $e->getMessage());
} finally {
    $conn->close();
}
