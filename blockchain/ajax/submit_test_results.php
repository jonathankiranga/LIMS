<?php
require_once '../db_connection.php'; 
require_once '../functions/functions.php'; 

// Retrieve and decode JSON data
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit;
}

// Extract variables - removed integer casting to support alpha-numeric IDs
$username      = $data['username'] ?? 'System';
$user_id       = $data['user_id'] ?? '';
$resultsID     = $data['resultsid'] ?? ''; 
$sampleID      = $data['sampleID'] ?? '';
$parameterName = $data['parameterName'] ?? '';
$resultType    = $data['resultType'] ?? '';
$resultValue   = $data['resultValue'] ?? '';

// Validation
if (empty($resultType) || $resultValue === '' || empty($resultsID)) {
    echo json_encode(['success' => false, 'message' => 'Required fields (ID, Type, Value) are missing.']);
    exit;
}

// Handle Keys
$dir = "userkeys/$user_id";
$privateKeyPath = "$dir/private_key.pem";

if (!file_exists($privateKeyPath)) {
    echo json_encode(['success' => false, 'message' => 'Error: Private key file does not exist.']);
    exit;
}

$privateKey = file_get_contents($privateKeyPath);

// Mapping logic - Handles both DB enum values (quantitativeField, qualitativeField, rangeField)
// and form field IDs (mrlResult, resultStatus, rangeResult)
$columnMap = [
    // DB enum values (from labresults_chemical.php, labresults_biological.php)
    'quantitativeField' => 'MRL_Result',
    'qualitativeField'  => 'ResultStatus',
    'rangeField'        => 'RangeResult',
    // Form field IDs (from reviewtestresults.php modal)
    'mrlResult'         => 'MRL_Result',
    'resultStatus'      => 'ResultStatus',
    'rangeResult'       => 'RangeResult'
];

$resultColumn = $columnMap[$resultType] ?? '';

if (empty($resultColumn)) {
    echo json_encode(['success' => false, 'message' => 'Invalid result type mapping.']);
    exit;
}

// Blockchain Payload Preparation
$testresults = [
    'resultsID'   => $resultsID,
    'sampleID'    => $sampleID,
    'resultType'  => $resultType,
    'resultValue' => $resultValue,
    'username'    => $username
];

$conn->autocommit(FALSE);

try {
    // 1. Ledger entry logic
    $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery->fetch_assoc();
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);
    
    $dataString = implode('|', array_values($testresults)) . '|' . $previousHash;
    $current_hash = hash('sha256', $dataString);
    $digital_signature = signData($current_hash, $privateKey);
    $encryptdata = encryptPrivateKey($dataString, $privateKey);

    $stmtLedger = $conn->prepare("INSERT INTO blockchain_ledger (timestamp, previous_hash, current_hash, digital_signature, encrypted_data, status, userid) VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, 'active', ?)");
    $stmtLedger->bind_param("sssss", $previousHash, $current_hash, $digital_signature, $encryptdata, $user_id);
    $stmtLedger->execute();
    $blockId = $conn->insert_id;
    $stmtLedger->close();
    
    // 2. Update test_results with flexible types
    // Using 'sss' (String, String, String) to accommodate alpha-numeric IDs and mixed-type values
    $updateQuery = "UPDATE test_results SET MRL_Result = NULL, ResultStatus = NULL, RangeResult = NULL, $resultColumn = ?, User_name = ?, StatusID = 2 WHERE resultsID = ?";
    $stmtUpdate = $conn->prepare($updateQuery);
    
    if (!$stmtUpdate) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Treated as sss to support UUIDs or string-based primary keys
    $stmtUpdate->bind_param('sss', $resultValue, $username, $resultsID);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception("Execute failed: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // 3. Metadata and Actions
    log_transaction_metadata($conn, $blockId, $resultsID, 'test_results');
    logAction($conn, 'UPDATE', $sampleID, $current_hash, $user_id, 'SUCCESS');

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Results saved to ledger.']);

} catch (Exception $e) {
    $conn->rollback();
    logAction($conn, 'UPDATE', $sampleID, ($current_hash ?? 'N/A'), $user_id, 'FAILED', $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}

$conn->autocommit(TRUE);
$conn->close();
?>