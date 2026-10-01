<?php

function gettestparametersFromTestID($testid,$stdId,$matrixid){
    global $conn;

    $sql = "
    WITH RECURSIVE matrix_tree AS (
        SELECT 
            ParameterID,
            ParentID,
            0 AS level
        FROM parametermatrix
        WHERE ParameterID = ?

        UNION ALL

        SELECT 
            pm.ParameterID,
            pm.ParentID,
            mt.level + 1
        FROM parametermatrix pm
        INNER JOIN matrix_tree mt 
            ON pm.ParentID = mt.ParameterID
    )
    SELECT 
        c.ConfigID,
        p.StandardID,
        s.StandardName,
        c.ParameterID,
        p.ParameterName,
        c.MatrixID,
        p.BaseID,
        c.IsDefault,
        c.Notes,
        c.CreatedAt,
        c.UpdatedAt,
        tr.resultsID,
        tr.TestID,
        tr.HeaderID,
        tr.SampleID,
        tr.StandardID AS ResultStandardID,
        tr.ParameterID AS ResultParameterID
    FROM standard_parameter_matrix_config c
    JOIN testparameters p ON c.ParameterID = p.ParameterID
    JOIN teststandards s ON p.StandardID = s.StandardID
    JOIN parametermatrix m ON c.MatrixID = m.ParameterID
    LEFT JOIN test_results tr 
        ON tr.StandardID = p.StandardID
       AND tr.ParameterID = c.ParameterID
       AND tr.TestID = ?
    WHERE p.StandardID = ?
      AND c.MatrixID IN (SELECT ParameterID FROM matrix_tree)
    ORDER BY s.StandardName, p.ParameterName
    ";      

    // Prepare & bind
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit; // Stop further processing
    }

    // Order of binds matches placeholders (matrixid, testid, stdId)
    $stmt->bind_param("iii", $matrixid, $testid, $stdId);
    $stmt->execute();

    // Fetch result
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function getALLtestparametersFromTestID($testid,$stdId){
    global $conn;

    $sql = "
    SELECT 
        s.StandardID,
        s.StandardName,
        p.ParameterID,
        p.ParameterName,
        p.BaseID,
        tr.resultsID,
        tr.TestID,
        tr.HeaderID,
        tr.SampleID,
        tr.StandardID AS ResultStandardID,
        tr.ParameterID AS ResultParameterID
    FROM teststandards s  
    JOIN testparameters p ON  p.StandardID = s.StandardID
    LEFT JOIN test_results tr 
        ON tr.StandardID = s.StandardID
       AND tr.ParameterID = p.ParameterID
       AND tr.TestID = ?
    WHERE s.StandardID = ?
    ORDER BY s.StandardName, p.ParameterName
    ";      

    // Prepare & bind
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
        exit; // Stop further processing
    }

    // Order of binds matches placeholders (matrixid, testid, stdId)
    $stmt->bind_param("ii",  $testid, $stdId);
    $stmt->execute();

    // Fetch result
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function gettestparameters($stdId,$matrixid){
global $conn;

$sql = "
WITH RECURSIVE matrix_tree AS (
    SELECT 
        ParameterID,
        ParentID,
        0 AS level
    FROM parametermatrix
    WHERE ParameterID = ?

    UNION ALL

    SELECT 
        pm.ParameterID,
        pm.ParentID,
        mt.level + 1
    FROM parametermatrix pm
    INNER JOIN matrix_tree mt 
        ON pm.ParentID = mt.ParameterID
)
SELECT 
    c.ConfigID,
    p.StandardID,
    s.StandardName,
    c.ParameterID,
    p.ParameterName,
    c.MatrixID,
    p.BaseID ,
    c.IsDefault,
    c.Notes,
    c.CreatedAt,
    c.UpdatedAt
FROM standard_parameter_matrix_config c
JOIN testparameters p ON c.ParameterID = p.ParameterID
JOIN teststandards s ON p.StandardID = s.StandardID
JOIN parametermatrix m ON c.MatrixID = m.ParameterID
WHERE p.StandardID = ?
  AND c.MatrixID IN (SELECT ParameterID FROM matrix_tree)
ORDER BY s.StandardName, p.ParameterName
";
        
// Prepare & bind
$stmt = $conn->prepare($sql);
if (!$stmt) {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}
$stmt->bind_param("ii",$matrixid,$stdId);
$stmt->execute();

// Fetch result
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
 
$stmt->close();
        
return  $data;
}

function gettestparametersALL($stdId){
global $conn;

$sql = "
SELECT 
    s.StandardID,
    s.StandardName,
    p.ParameterID,
    p.ParameterName,
    p.BaseID   
FROM teststandards s 
JOIN testparameters p ON p.StandardID = s.StandardID
WHERE s.StandardID = ?
ORDER BY s.StandardName, p.ParameterName
";
        
// Prepare & bind
$stmt = $conn->prepare($sql);
if (!$stmt) {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}
$stmt->bind_param("i",$stdId);
$stmt->execute();

// Fetch result
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
 
$stmt->close();
        
return  $data;
}

function updatetest_results($conn,$TestID,$HeaderID,$SampleID){
    $lastBlockQuery = $conn->prepare("SELECT * from `test_results` where `TestID`=? and `HeaderID`=? and `SampleID`=? ");
    $lastBlockQuery->bind_param("iis",$TestID,$HeaderID,$SampleID);
    $lastBlockQuery->execute() ;
    $result = $lastBlockQuery->get_result();
    $headers = [];
    while ($row = $result->fetch_assoc()) {
        $headers[$row['StandardID']][$row['ParameterID']] = [
            'MRL_Result' => $row['MRL_Result'],
            'ResultStatus' => $row['ResultStatus'],
            'RangeResult' => $row['RangeResult'],
            'StatusID' => $row['StatusID'] ?? '1'
        ];
    }
     $lastBlockQuery->close;
    
      $lastBlockQuery = $conn->prepare("Delete from `test_results` where `TestID`=? and `HeaderID`=? and `SampleID`=? ");
      $lastBlockQuery->bind_param("iis",$TestID,$HeaderID,$SampleID);
      $lastBlockQuery->execute() ;
      $lastBlockQuery->close;
  
    return $headers;
}

function decryptPrivateKey($encryptedData, $password) {
    $method = 'aes-256-cbc';
   // Derive the 32-byte key from the password
    $key = substr(hash('sha256', $password), 0, 32);
   // Decode the Base64-encoded encrypted data
    $data = base64_decode($encryptedData);
   // Extract the IV (first 16 bytes) and the actual encrypted data
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
   // Decrypt the data
    $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);

    return $decrypted;
}
// Helper function to encrypt the private key
function encryptPrivateKey($privateKey, $password) {
    $method = 'aes-256-cbc';
   // Derive a 32-byte key from the password
    $key = substr(hash('sha256', $password), 0, 32);
   // Generate a random 16-byte IV
    $iv = openssl_random_pseudo_bytes(16);
   // Encrypt the private key
    $encrypted = openssl_encrypt($privateKey, $method, $key, 0, $iv);
// Combine IV and encrypted data, and encode as Base64
    return base64_encode($iv . $encrypted);
}

function formatDate($rawDate, $format='YYYY-MM-DD') {
    if ($rawDate === null) {
        return '';
    }

    $rawDate = trim((string)$rawDate);
    if ($rawDate === '') {
        return '';
    }

    $timestamp = strtotime($rawDate);
    if ($timestamp === false) {
        return $rawDate;
    }

    $year = (int)date('Y', $timestamp);
    $month = (int)date('m', $timestamp);
    $day = (int)date('d', $timestamp);

    switch ($format) {
        case 'dd-mm-yy':
            return sprintf('%02d-%02d-%02d', $day, $month, $year % 100);
        case 'mm-dd-yy':
            return sprintf('%02d-%02d-%02d', $month, $day, $year % 100);
        case 'dd/mm/yyyy':
            return sprintf('%02d/%02d/%04d', $day, $month, $year);
        case 'mm/dd/yyyy':
            return sprintf('%02d/%02d/%04d', $month, $day, $year);
        case 'YYYY-MM-DD':
            return sprintf('%04d/%02d/%02d', $year, $month, $day);
        default:
            return $rawDate;
    }
}
// Function to create a JWT (Bearer Token)
function createToken($username, $role, $secretKey) {
    global $expireInSeconds;
    
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'username' => $username,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + $expireInSeconds  // Token valid for 1 hour
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
// Helper function to hash the password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT); // Using bcrypt for secure password hashing
}

function calculateHash($data) {
    // Use SHA-256 to generate a hash of the input data
    return hash('sha256', $data);
}
// Helper function to generate a random reset token
function generateResetToken() {
    return bin2hex(random_bytes(16)); // Generate a random 32-character token
}

function softDeleteBlock($mysqli, $blockId, $userId) {
    $stmt = $mysqli->prepare("UPDATE blockchain_ledger SET is_deleted = TRUE WHERE block_id = ?");
    $stmt->bind_param("i", $blockId);
    $stmt->execute();

    logAction($mysqli, $blockId, 'DELETE', $userId, 'SUCCESS');
}

function createNewBlock($mysqli, $blockData, $userId, $privateKey, $previousVersionId = null) {
    // Get the last block's hash
    $lastBlockQuery = $mysqli->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery->fetch_assoc();
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);

    // Prepare the data string for hashing
    $dataString = implode('|', array_values($blockData)) . '|' . $previousHash;
    $currentHash = calculateHash($dataString);

    // Sign the data
    $digitalSignature = signData($dataString, $privateKey);

    // Start transaction
    $mysqli->autocommit(0);
    try {
        // Mark the previous block as superseded, if applicable
        if ($previousVersionId) {
            $updateStmt = $mysqli->prepare("UPDATE blockchain_ledger SET status = 'superseded' WHERE block_id = ?");
            $updateStmt->bind_param("i", $previousVersionId);
            $updateStmt->execute();
        }

        // Insert new block
        $stmt = $mysqli->prepare("
            INSERT INTO blockchain_ledger (
                sample_id, test_date, substance, concentration, mrl, temperature,
                humidity, sample_age, batch_source, machine_id, test_method,
                above_mrl, previous_hash, current_hash, digital_signature,
                previous_version_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param(
            "issdddddssssssssi",
            $blockData['sample_id'], $blockData['test_date'], $blockData['substance'],
            $blockData['concentration'], $blockData['mrl'], $blockData['temperature'],
            $blockData['humidity'], $blockData['sample_age'], $blockData['batch_source'],
            $blockData['machine_id'], $blockData['test_method'], $blockData['above_mrl'],
            $previousHash, $currentHash, $digitalSignature, $previousVersionId
        );
        $stmt->execute();

        $mysqli->commit();

        // Log action as successful
        logAction($mysqli, $mysqli->insert_id, 'INSERT', $userId, 'SUCCESS');
    } catch (Exception $e) {
        $mysqli->rollback();

        // Log action as failed
        logAction($mysqli, null, 'INSERT', $userId, 'FAILED', $e->getMessage());
    }
}

function verifyBlockchain($mysqli) {
    $query = $mysqli->query("SELECT * FROM blockchain_ledger ORDER BY block_id");
    $blocks = $query->fetch_all(MYSQLI_ASSOC);

    $isValid = true;
    for ($i = 1; $i < count($blocks); $i++) {
        $previousHash = $blocks[$i - 1]['current_hash'];
        $currentDataString = implode('|', array_values($blocks[$i])) . '|' . $previousHash;
        $calculatedHash = calculateHash($currentDataString);

        if ($calculatedHash !== $blocks[$i]['current_hash']) {
            $isValid = false;
            echo "Blockchain invalid at block ID: " . $blocks[$i]['block_id'];
            break;
        }
    }

    return $isValid;
}
     
function logAction($conn, $actionType, $documentNo, $hashValue, $userId, $status, $errorMessage = null) {
    $stmt = $conn->prepare("INSERT INTO audit_log (action_type, document_no, hash_value, user_id , status, error_message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $actionType, $documentNo, $hashValue,$userId, $status, $errorMessage);
    $stmt->execute();
}

function log_transaction_metadata($conn, $blockId, $recordId,$tablename) {
    $stmt = $conn->prepare(" INSERT INTO transaction_metadata (`block_id`,`record_id`,`table_name`) VALUES (?, ?, ?) ");
    $stmt->bind_param("iis",$blockId, $recordId,$tablename);
    $stmt->execute();
}

function signData($data, $privateKey) {
    openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);  // Encode to store in the database
}

function verifySignature($data, $signature, $publicKey) {
    $decodedSignature = base64_decode($signature);
    $isVerified = openssl_verify($data, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
    return $isVerified === 1; // Returns true if verified, false otherwise
}

