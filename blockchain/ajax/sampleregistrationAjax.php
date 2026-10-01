<?php
require_once '../db_connection.php';
require_once '../functions/functions.php';
require_once '../functions/tasks.php';
require_once 'getrefferencesfunction.inc';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- Basic required inputs ---
$username = trim($_POST['username'] ?? '');
$user_id  = trim($_POST['user_id'] ?? '');

if ($username === '' || $user_id === '') {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Missing username or user_id.']);
    exit;
}

// keys folder and reading keys
$dir = __DIR__ . "/userkeys/$user_id";
$privateKeyPath = "$dir/private_key.pem";
$publicKeyPath  = "$dir/public_key.pem";

if (!file_exists($privateKeyPath)) {
    echo json_encode(['success' => false, 'message' => 'Error: Private key file does not exist.']);
    exit;
}
$privateKey = file_get_contents($privateKeyPath);
if ($privateKey === false) {
    echo json_encode(['success' => false, 'message' => 'Error: Unable to read private key file.']);
    exit;
}
$publicKey = file_exists($publicKeyPath) ? file_get_contents($publicKeyPath) : null;

// --- Required form fields validation (explicit keys) ---
$requiredKeys = [
    'date' => $_POST['date'] ?? null,
    'documentno' => $_POST['documentno'] ?? null,
    'CustomerName' => $_POST['CustomerName'] ?? null,
    'CustomerID' => $_POST['CustomerID'] ?? null,
    'sampledby' => $_POST['sampledby'] ?? null,
    'SamplingMethod' => $_POST['SamplingMethod'] ?? null,
    'samplingdate' => $_POST['samplingdate'] ?? null,
    'Orderno' => $_POST['Orderno'] ?? null
];

$missing = [];
foreach ($requiredKeys as $label => $val) {
    if ($val === null || trim((string)$val) === '') {
        $missing[] = $label;
    }
}

if (!empty($missing)) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
    exit;
}

// sanitize/assign top-level fields
$date           = $_POST['date'];
$documentNo     = $_POST['documentno'];
$customerName   = $_POST['CustomerName'];
$customerId     = $_POST['CustomerID'];
$sampledBy      = $_POST['sampledby'];
$samplingMethod = $_POST['SamplingMethod'];
$samplingDate   = $_POST['samplingdate'];
$orderNo        = $_POST['Orderno'];
$quoteNo        = trim((string) ($_POST['quoteno'] ?? ''));
$rowsposted     = (int) ($_POST['tablecount'] ?? 0);
$shouldSendSampleReceivedEmail = isset($_POST['send_sample_received_email']) && (string)$_POST['send_sample_received_email'] === '1';

// Format dates as Y-m-d H:i:s (inline to avoid opcache issues)
$dateFormatted = ($date !== null && $date !== '' && $date !== '0000-00-00' && $date !== '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($date)) : null;
$samplingDateFormatted = ($samplingDate !== null && $samplingDate !== '' && $samplingDate !== '0000-00-00' && $samplingDate !== '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($samplingDate)) : null;

// date validation (ensure sampling date is NOT later than registration date)
try {
    // Use raw date for validation, formatted for storage
    $registrationDate = new DateTime($date);
    $sampleDate = new DateTime($samplingDate);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}
if ($sampleDate > $registrationDate) { // sampleDate later than registration is invalid
    echo json_encode(['success' => false, 'message' => 'The sampling date cannot be later than the registration date.']);
    exit;
}

// ensure upload dir exists
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

// --- Prepare structures to store rows ---
$parameterData = []; // per row metadata that goes into sample_tests insert
$testData = [];      // nested test results data

// Accept uploaded file array if provided
$sampleFiles = $_FILES['sample_images'] ?? null;

// Loop over posted rows (supports per-header `sample_headers` blocks posted by the UI)
$globalIndex = 0;
for ($i = 0; $i < $rowsposted; $i++) {
    $sampletype   = isset($_POST['standard_id'][$i]) ? (int)$_POST['standard_id'][$i] : null;
    $matrix_id    = isset($_POST['matrix_id'][$i]) ? (int)$_POST['matrix_id'][$i] : null;
    $samplesCount = isset($_POST['samples'][$i]) ? (int)$_POST['samples'][$i] : 0;
    $sku          = $_POST['kit_units'][$i] ?? null;
    $batchNo      = $_POST['batch_no'][$i] ?? null;
    $batchSize    = isset($_POST['batch_size'][$i]) ? (int)$_POST['batch_size'][$i] : 0;
    $mandate      = $_POST['date_mfg'][$i] ?? null;
    $expDate      = $_POST['date_exp'][$i] ?? null;
    $externalSample = $_POST['sample_source'][$i] ?? null;
    $sampleName = $_POST['sample_name'][$i] ?? null;
    $sampleMethod = $_POST['sample_method'][$i] ?? null;
    $conditionOfSample = $_POST['condition_of_sample'][$i] ?? null;
    $chilledDateExp = $_POST['chilled_date_exp'][$i] ?? null;
    $frozenDateExp = $_POST['frozen_date_exp'][$i] ?? null;

    // file upload for this row (if provided)
    $uploadedFile = null;
    if ($sampleFiles && isset($sampleFiles['name'][$i]) && $sampleFiles['error'][$i] === UPLOAD_ERR_OK) {
        $originalFileName = $sampleFiles['name'][$i];
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $tempName = GetTempBarcoderfNo(19, $i + 1);
        $newFileName = $tempName . '.' . $fileExtension;
        $destination = $uploadDir . $newFileName;
        if (move_uploaded_file($sampleFiles['tmp_name'][$i], $destination)) {
            $uploadedFile = $destination;
        }
    }

    // Determine parameters for this row
    $filteredParameters = [];
    if (!empty($matrix_id)) {
        $filteredParameters = gettestparameters($sampletype, $matrix_id);
    } else {
        $filteredParameters = gettestparametersALL($sampletype);
        if (isset($_POST['selected_params'][$i]) && is_array($_POST['selected_params'][$i])) {
            $paramsFromFrontend = [];
            foreach ($_POST['selected_params'][$i] as $rowParam) {
                $parameterId = isset($rowParam['parameterId']) ? (int)$rowParam['parameterId'] : (isset($rowParam['ParameterID']) ? (int)$rowParam['ParameterID'] : null);
                $standardId  = isset($rowParam['standardId']) ? $rowParam['standardId'] : (isset($rowParam['StandardID']) ? $rowParam['StandardID'] : null);
                $baseId      = isset($rowParam['baseId']) ? (int)$rowParam['baseId'] : (isset($rowParam['BaseID']) ? (int)$rowParam['BaseID'] : null);
                if ($parameterId !== null) {
                    $paramsFromFrontend[] = [
                        'ParameterID' => $parameterId,
                        'StandardID'  => $standardId,
                        'BaseID'      => $baseId
                    ];
                }
            }
            if (!empty($paramsFromFrontend)) {
                $filteredParameters = $paramsFromFrontend;
            }
        }
    }

    // If UI posted explicit headers for this row, create one sample_tests entry per header
    if (isset($_POST['sample_headers'][$i]) && is_array($_POST['sample_headers'][$i])) {
        foreach ($_POST['sample_headers'][$i] as $hdrIndex => $hdr) {
            $imageNo = GetTempBarcoderfNo(19, $globalIndex + 1);
            $hdrSku = $hdr['sku'] ?? $sku;
            $hdrBatch = $hdr['batchNo'] ?? $batchNo;
            $hdrSize = $hdr['batchSize'] ?? $batchSize;
            $hdrMfg = $hdr['date_mfg'] ?? $mandate;
            $hdrExp = $hdr['date_exp'] ?? $expDate;
            $hdrExt = $hdr['externalSample'] ?? $externalSample;

            $parameterData[$globalIndex] = [
                'imageno' => $imageNo,
                'sampleType' => $sampletype,
                'matrixID' => $matrix_id,
                'sku' => $hdrSku,
                'batchNo' => $hdrBatch,
                'batchSize' => $hdrSize,
                'mandate' => $hdrMfg,
                'expDate' => $hdrExp,
                'externalSample' => $hdrExt,
                'sampleFileKey' => $uploadedFile,
                'User_name' => $username,
                'sample_name' => $hdr['sample_name'] ?? $sampleName,
                'sample_method' => $hdr['sample_method'] ?? $sampleMethod,
                'condition_of_sample' => $hdr['condition_of_sample'] ?? $conditionOfSample,
                'chilled_date_exp' => $hdr['chilled_date_exp'] ?? $chilledDateExp,
                'frozen_date_exp' => $hdr['frozen_date_exp'] ?? $frozenDateExp
            ];

            // parameters for this header: prefer explicit header parameters, else fall back to filteredParameters
            $headerParams = [];
            if (isset($hdr['parameters']) && is_array($hdr['parameters']) && count($hdr['parameters']) > 0) {
                foreach ($hdr['parameters'] as $hp) {
                    $paramId = (int)$hp;
                    if ($paramId) $headerParams[] = ['ParameterID' => $paramId, 'StandardID' => $hdr['StandardID'] ?? $sampletype, 'BaseID' => null];
                }
} else {
                foreach ($filteredParameters as $parameter) {
                    $pid = isset($parameter['ParameterID']) ? (int)$parameter['ParameterID'] : (isset($parameter['parameterId']) ? (int)$parameter['parameterId'] : null);
                    $std = $parameter['StandardID'] ?? $parameter['standardID'] ?? $sampletype;
                    $base = 0;
                    if (isset($parameter['BaseID'])) {
                        $base = (int)$parameter['BaseID'];
                    } elseif (isset($parameter['BasePID'])) {
                        $base = (int)$parameter['BasePID'];
                    }
                    if ($pid !== null) $headerParams[] = ['ParameterID' => $pid, 'StandardID' => $std, 'BaseID' => $base];
                }
            }

            $testData[$globalIndex] = [];
            foreach ($headerParams as $parameter) {
                $testData[$globalIndex][] = [
                    'parameterId' => (int)$parameter['ParameterID'],
                    'sampleType' => $parameter['StandardID'] ?? $sampletype,
                    'User_name' => $username,
                    'BasePID' => $parameter['BaseID'] ?? null
                ];
            }

            $globalIndex++;
        }
    } else {
        // No explicit headers - create one sample_tests entry for the row with the row-level values
        $imageNo = GetTempBarcoderfNo(19, $globalIndex + 1);
        $parameterData[$globalIndex] = [
            'imageno' => $imageNo,
            'sampleType' => $sampletype,
            'matrixID' => $matrix_id,
            'sku' => $sku,
            'batchNo' => $batchNo,
            'batchSize' => $batchSize,
            'mandate' => $mandate,
            'expDate' => $expDate,
            'externalSample' => $externalSample,
            'sampleFileKey' => $uploadedFile,
            'User_name' => $username,
            'sample_name' => $sampleName,
            'sample_method' => $sampleMethod,
            'condition_of_sample' => $conditionOfSample,
            'chilled_date_exp' => $chilledDateExp,
            'frozen_date_exp' => $frozenDateExp
        ];

        $testData[$globalIndex] = [];
        foreach ($filteredParameters as $parameter) {
            $pid = isset($parameter['ParameterID']) ? (int)$parameter['ParameterID'] : (isset($parameter['parameterId']) ? (int)$parameter['parameterId'] : null);
            $std = $parameter['StandardID'] ?? $parameter['standardID'] ?? $sampletype;
            $base = 0;
            if (isset($parameter['BaseID'])) {
                $base = (int)$parameter['BaseID'];
            } elseif (isset($parameter['BasePID'])) {
                $base = (int)$parameter['BasePID'];
            }
            if ($pid !== null) {
                $testData[$globalIndex][] = [
                    'parameterId' => $pid,
                    'sampleType' => $std,
                    'User_name' => $username,
                    'BasePID' => $base
                ];
            }
        }

        $globalIndex++;
    }
}

try {
    $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery ? $lastBlockQuery->fetch_assoc() : null;
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);

    // Merge header / parameter and a small stable string for hashing.
    $sampleHeader = [
        'date' => $date,
        'documentNo' => $documentNo,
        'customerName' => $customerName,
        'customerId' => $customerId,
        'sampledBy' => $sampledBy,
        'samplingMethod' => $samplingMethod,
        'samplingDate' => $samplingDate,
        'orderNo' => $orderNo,
        'quoteNo' => $quoteNo,
        'User_name' => $username
    ];

    // For the purpose of creating a deterministic string to hash, serialize JSON
    $dataToHash = json_encode([
        'header' => $sampleHeader,
        'parameterData' => $parameterData,
        'testData' => $testData,
        'previousHash' => $previousHash
    ], JSON_UNESCAPED_UNICODE);

    $current_hash = hash('sha256', $dataToHash);
    $digital_signature = signData($current_hash, $privateKey);
    $encryptdata = encryptPrivateKey($dataToHash, $privateKey);

    // Start transaction
    $conn->autocommit(false);

    // Insert into blockchain_ledger
    $stmt = $conn->prepare("INSERT INTO `blockchain_ledger` (`timestamp`, `previous_hash`, `current_hash`, `digital_signature`, `encrypted_data`, `status`, `userid`) VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, 'active', ?)");
    if (!$stmt) throw new Exception("Prepare blockchain insert failed: " . $conn->error);
    $stmt->bind_param("sssss", $previousHash, $current_hash, $digital_signature, $encryptdata, $user_id);
    $stmt->execute();
    $blockId = $conn->insert_id;
    $stmt->close();

    // Now insert into sample_header
    $documentNo = GetNextLabrefNo('10'); // looks like you reset documentNo here intentionally
    $stmt = $conn->prepare("INSERT INTO sample_header (Date, DocumentNo, CustomerName, CustomerID, SampledBy, SamplingMethod, SamplingDate, OrderNo, QuoteNo, User_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception("Prepare statement sample_header failed: " . $conn->error);
    $stmt->bind_param("ssssssssss", $dateFormatted, $documentNo, $customerName, $customerId, $sampledBy, $samplingMethod, $samplingDateFormatted, $orderNo, $quoteNo, $username);
    if (!$stmt->execute()) throw new Exception("Execute sample_header failed: " . $stmt->error);
    $HeaderID = $conn->insert_id;
    $stmt->close();

    // Log blockchain linkage
    log_transaction_metadata($conn, $blockId, $HeaderID, 'sample_header');

    // Prepare sample_tests insert
    $stmt = $conn->prepare("INSERT INTO `sample_tests` "
            . "(`HeaderID`,`SampleID`,`StandardID`,`SampleFileKey`,`SKU`,`BatchNo`,"
            . "`BatchSize`,`ManufactureDate`,`ExpDate`,`ExternalSample`,`User_name`,`BaseID`,"
            . "`sample_name`,`sample_method`,`condition_of_sample`,`chilled_date_of_expiry`,`frozen_date_of_expiry`)"
            . " VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) throw new Exception("Prepare sample_tests failed: " . $conn->error);

    // Prepare test_results insert
    $teststmt = $conn->prepare("INSERT INTO `test_results` (`TestID`,`HeaderID`,`SampleID`,`StandardID`,`ParameterID`,`User_name`,`StatusID`,`BaseID`) VALUES (?,?,?,?,?,?,1,?)");
    if (!$teststmt) throw new Exception("Prepare test_results failed: " . $conn->error);

    // Loop through parameterData and testData and insert
    foreach ($parameterData as $id => $datarows) {
        $SampleID_for_tests = GetNextLabrefNo(19);
        $datarows['imageno']= $SampleID_for_tests;
        
        // Format dates (inline to avoid opcache issues)
        $mandateFormatted = (!empty($datarows['mandate']) && $datarows['mandate'] !== '0000-00-00') ? date('Y-m-d', strtotime($datarows['mandate'])) : null;
        $expDateFormatted = (!empty($datarows['expDate']) && $datarows['expDate'] !== '0000-00-00') ? date('Y-m-d', strtotime($datarows['expDate'])) : null;
        $chilledDateFormatted = (!empty($datarows['chilled_date_exp']) && $datarows['chilled_date_exp'] !== '0000-00-00') ? date('Y-m-d', strtotime($datarows['chilled_date_exp'])) : null;
        $frozenDateFormatted = (!empty($datarows['frozen_date_exp']) && $datarows['frozen_date_exp'] !== '0000-00-00') ? date('Y-m-d', strtotime($datarows['frozen_date_exp'])) : null;
        
        // Bind and execute sample_tests
        $stmt->bind_param(
            "isisssssssssissss",
            $HeaderID,
            $datarows['imageno'],
            $datarows['sampleType'],
            $datarows['sampleFileKey'],
            $datarows['sku'],
            $datarows['batchNo'],
            $datarows['batchSize'],
            $mandateFormatted,
            $expDateFormatted,
            $datarows['externalSample'],
            $datarows['User_name'],
            $datarows['matrixID'],
            $datarows['sample_name'],
            $datarows['sample_method'],
            $datarows['condition_of_sample'],
            $chilledDateFormatted,
            $frozenDateFormatted
        );
        if (!$stmt->execute()) {
            throw new Exception("Error executing sample_tests: " . $stmt->error);
        }
        $TestID = $conn->insert_id;

        // Insert parameters once per row (do not multiply per sample instance)
        if (isset($testData[$id]) && is_array($testData[$id])) {
            // Use the sample identifier created for the sample_tests row (imageno) as the SampleID
            $SampleID = $datarows['imageno'] ?? null;
            foreach ($testData[$id] as $tedata) {
                // Guard fields
                $std = $tedata['sampleType'] ?? null;
                $param = $tedata['parameterId'] ?? null;
                $user_here = $tedata['User_name'] ?? $username;
                $basepid = $tedata['BasePID'] ?? null;

                $teststmt->bind_param("iisiisi", $TestID, $HeaderID, $SampleID, $std, $param, $user_here, $basepid);
                if (!$teststmt->execute()) {
                    throw new Exception("Error executing test_results: " . $teststmt->error);
                }
            }
        }
    }

    $teststmt->close();
    $stmt->close();

    // Always track user's email decision/action
    // Use SUCCESS for skip so this remains compatible with strict enum schemas.
    $emailLogStatus = 'SUCCESS';
    $emailLogError = 'Email skipped by user selection.';

    if ($shouldSendSampleReceivedEmail) {
        $emailLogStatus = 'FAILED';
        $emailLogError = null;

        if (!class_exists('EventManager')) {
            $emailLogError = 'Event manager unavailable.';
        } else {
            $customerEmail = null;
            $emailStmt = $conn->prepare("SELECT email FROM debtors WHERE itemcode = ? LIMIT 1");
            if ($emailStmt) {
                $emailStmt->bind_param("s", $customerId);
                $emailStmt->execute();
                $emailRes = $emailStmt->get_result();
                if ($emailRes && ($emailRow = $emailRes->fetch_assoc())) {
                    $candidateEmail = trim((string)($emailRow['email'] ?? ''));
                    if ($candidateEmail !== '') {
                        $customerEmail = $candidateEmail;
                    }
                }
                $emailStmt->close();
            }

            if ($customerEmail !== null) {
                $Alertdata = ['test_id' => $documentNo, 'customer_id' => $customerId];
                try {
                    $eventManager = new EventManager();
                    $eventManager->trigger_event('sample_received', $Alertdata);
                    $emailLogStatus = 'SUCCESS';
                } catch (Throwable $emailEx) {
                    $emailLogStatus = 'FAILED';
                    $emailLogError = 'Email trigger error: ' . $emailEx->getMessage();
                }
            } else {
                $emailLogStatus = 'FAILED';
                $emailLogError = 'Customer email not found.';
            }
        }
    } else {
        // Also record skipped choice in event logs so it is visible in alert reports
        if (class_exists('EventManager')) {
            try {
                $eventManager = new EventManager();
                $eventManager->log_event_decision(
                    'sample_received',
                    $documentNo,
                    'success',
                    'Email not sent because user selected "Skip Email".'
                );
            } catch (Throwable $skipLogEx) {
                // keep registration successful even if secondary logging fails
            }
        }
    }

    // Track email action outcome in audit_log for both send and skip choices
    logAction($conn, 'EMAIL_SAMPLE_RECEIVED', $documentNo, $current_hash, $user_id, $emailLogStatus, $emailLogError);
    logAction($conn, 'INSERT', $documentNo, $current_hash, $user_id, 'SUCCESS');

    $conn->commit();
    $conn->autocommit(true);

    // --- ERP API sync (call external LIMS sales API using curl + JSON) ---
    $erpSyncResult = null;
    $configPath = __DIR__ . '/../include/config.php';
    if (file_exists($configPath)) {
        $cfg = include $configPath;
        $erpUrl = $cfg['ERP_API_URL'] ?? null;
        if (!empty($erpUrl)) {
            // Build payload that matches LimsSalesApi::registerSample expectations
            $payload = [
                'action' => 'sync_sample',
                'documentno' => $documentNo,
                'docdate' => $dateFormatted,
                'oderdate' => $dateFormatted,
                'duedate' => $dateFormatted,
                'customercode' => $customerId,
                'customername' => $customerName,
                'yourreference' => $orderNo,
                'externaldocumentno' => $HeaderID,
                'userid' => $username,
                'quoteno' => $quoteNo,
                'lines' => []
            ];

            if ($quoteNo === '') {
                foreach ($parameterData as $idx => $pd) {
                $paramsForHeader = $testData[$idx] ?? [];
                
                foreach ($paramsForHeader as $p) {
                    $paramId = (int)($p['parameterId'] ?? 0);
                    if ($paramId <= 0) continue;

                    // get parameter name and baseid from testparameters
                    $paramName = null;
                    $code = 0;
                    $pstmt = $conn->prepare("SELECT p.ParameterName, p.BaseID FROM testparameters p WHERE p.ParameterID = ? LIMIT 1");
                    if ($pstmt) {
                        $pstmt->bind_param('i', $paramId);
                        $pstmt->execute();
                        $pres = $pstmt->get_result();
                        if ($pres && ($prow = $pres->fetch_assoc())) {
                            $paramName = $prow['ParameterName'] ?? null;
                            $code = (int)($prow['BaseID'] ?? 0);
                        }
                        $pstmt->close();
                    }
                    
                    // fallback to parameterId if baseid is 0
                    if ($code <= 0) {
                        $code = $paramId;
                    }

                    $lineItem = [
                        'documentno' => $documentNo,
                        'code' => (string)$code,
                        'description' => $paramName ?? ('Parameter ' . $code),
                        'sampleID' => $pd['imageno'] ?? '',
                        'Quantity' =>  1,
                        'Qunatity_delivered' =>  1,
                        'UnitPrice' => 0,
                        'locationcode' => $cfg['locationcode'] ?? null
                    ];
                    $payload['lines'][] = $lineItem;
                }
            }
            }

            $ch = curl_init($erpUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $erpResp = curl_exec($ch);
            $erpHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erpErr = curl_error($ch);
            curl_close($ch);

            $erpSyncResult = [
                'url' => $erpUrl,
                'http_code' => $erpHttp,
                'response' => $erpResp,
                'error' => $erpErr
            ];
        }
    }

    $responsePayload = [
        'success' => true,
        'message' => "Sample Registration no: $documentNo Successful",
        'email_status' => $emailLogStatus,
        'email_note' => $emailLogError
    ];

    if ($erpSyncResult !== null) {
        $responsePayload['erp_sync'] = $erpSyncResult;
    }

    echo json_encode($responsePayload);
    exit;
} catch (Exception $e) {
    // rollback, log and return error
    if ($conn) {
        $conn->rollback();
        $conn->autocommit(true);
    }
    logAction($conn, 'INSERT', $documentNo ?? '', $current_hash ?? '', $user_id ?? '', 'FAILED', $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
