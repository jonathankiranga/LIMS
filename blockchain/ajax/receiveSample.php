<?php
// Simulating the database connection
// Replace with your actual database logic
require_once '../db_connection.php'; 
require_once '../functions/functions.php'; // Include PHPMailer via Composer

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $username= $_POST['username'];
 $user_id = $_POST['user_id'];
$dir      = "userkeys/$user_id";
 $privateKeyPath = "$dir/private_key.pem";
 if (file_exists($privateKeyPath)) { // Check if the file exists
    $privateKey = file_get_contents($privateKeyPath); // Read the file content
    if ($privateKey !== false) {
        $publicKey = file_get_contents("$dir/public_key.pem");
    } else {
       echo json_encode(['success' => false, 'message' => 'Error: Unable to read private key file']);
       exit;
    }
} else {
    // Handle error: File does not exist
     echo json_encode(['success' => false, 'message' => 'Error: Private key file does not exist.']);
     exit;
}

  
// Collect required fields
$requiredFields = [
    'sampleId' => $_POST['sampleId'] ?? null,
    'storageLocation' => $_POST['storageLocation'] ?? null,
    'remarks' => $_POST['remarks'] ?? null,
    'assignedDepartment' => $_POST['assignedDepartment'] ?? null,
    'condition' => $_POST['condition'] ?? null
];

   
// Check for any missing fields
$missingFields = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value) && $value !== '0') { // Check if value is empty or null, excluding zero as valid
        $missingFields[] = $field;
    }
}

// Respond if there are missing fields
if (!empty($missingFields)) {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit; // Stop further processing
}
    // Retrieve form fields
    $sampleId = $conn->real_escape_string($_POST['sampleId']);
    $storageLocation = $conn->real_escape_string($_POST['storageLocation']);
    $remarks = $conn->real_escape_string($_POST['remarks']);
    $assignedDepartment = $conn->real_escape_string($_POST['assignedDepartment']);
    $condition = $conn->real_escape_string($_POST['condition']);

    $receiveSamples = [
        'sampleId' => $_POST['sampleId'] ?? null,
        'storageLocation' => $_POST['storageLocation'] ?? null,
        'remarks' => $_POST['remarks'] ?? null,
        'assignedDepartment' => $_POST['assignedDepartment'] ?? null,
        'condition' => $_POST['condition'] ?? null,
        'User_name' => $username
    ];
    // sample header
         
    $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery->fetch_assoc();
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);
  
    
    $dataString = implode('|', array_values($receiveSamples)) . '|' . $previousHash;
    $current_hash = hash('sha256',$dataString);
    $digital_signature = signData($current_hash,$privateKey);
    // Collect the previous block hash (for linking to previous block in the chain)
    $encryptdata = encryptPrivateKey($dataString,$privateKey);
    $conn->autocommit(0);
    try {
   
        $stmt = $conn->prepare("INSERT INTO `blockchain_ledger`(`timestamp`,`previous_hash`,`current_hash`,`digital_signature`,encrypted_data,`status`,`userid`) VALUES (CURRENT_TIMESTAMP, ?, ?, ?,?, 'active',?)");
        $stmt->bind_param("ssssi",$previousHash,$current_hash,$digital_signature,$encryptdata,$user_id);
        $stmt->execute();
        $blockId = $conn->insert_id;
        $stmt->close();
        // Bind parameters
        $stmt = $conn->prepare("INSERT INTO samples_received ("
                . "`sample_id`, "
                . "`storage_location`, "
                . "`remarks`, "
                . "`assigned_department`, "
                . "`condition`,"
                . "`received_at`) "
                . "VALUES (?, ?, ?, ?, ?,CURRENT_TIMESTAMP)");
        if (!$stmt) {
            echo json_encode(['success' => false,'message' => "Prepare statement samples_received failed: " . $conn->error]);
            exit; // Stop further processing
        }
        $stmt->bind_param("sssss", $sampleId, $storageLocation, $remarks, $assignedDepartment, $condition);
        // Execute the statement
        if ($stmt->execute()) {
             $samples_receivedID = $conn->insert_id;
             log_transaction_metadata($conn, $blockId,$samples_receivedID,'samples_received') ;
  
        } else {
              echo json_encode(['success' => false,'message' => "Error executing statement samples_received: " . $stmt->error]);
              $stmt->close();
              exit; // Stop further processing
        }
         
        $stmt->close();
               
         // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO `sample_custody`(`SampleID`,`HandlerName`,`Action`,`Location`,`Notes`,`DateTime`)"
                . " VALUES (?, ?, ?, ?, ? ,CURRENT_TIMESTAMP)");
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => "Prepare statement sample_custody: " . $conn->error
            ]);
            exit; // Stop further processing
        }
                
        //CURRENT_TIMESTAMP
        $stmt->bind_param("sssss", $sampleId,$username,"Lab Testing",$storageLocation,$remarks);
        if (!$stmt->execute()) {
              echo json_encode(['success' => false,'message' => "Error executing statement sample_custody: " . $stmt->error]);
              $stmt->close();
              exit; // Stop further processing
        }
         
        $stmt->close();
        
        logAction($conn, 'INSERT',$sampleId, $current_hash,$user_id , 'SUCCESS');
} catch (Exception $e) {
    // If there's an error, rollback and log the error
    $conn->rollback();
    logAction($conn, 'INSERT',$sampleId, $current_hash,$user_id , 'FAILED', $e->getMessage());
} finally {
    $conn->commit();
}
    
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$conn->autocommit(1);
// Close the database connection
$conn->close();

 echo json_encode([ 'success' => true,'message' => "Sample Registration no: $documentNo Successful"  ]);
                 
?>

