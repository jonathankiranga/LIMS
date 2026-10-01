<?php

// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
require_once '../functions/functions.php'; // Include PHPMailer via Composer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$username= $_POST['username'];
$user_id = $_POST['user_id'];

$dir = "userkeys/$user_id";
$privateKeyPath = "$dir/private_key.pem";
if (file_exists($privateKeyPath)) { // Check if the file exists
  $privateKey = file_get_contents($privateKeyPath); // Read the file content
  if ($privateKey !== false) {
      // Successfully retrieved private key
      $publicKey = file_get_contents("$dir/public_key.pem");
  } else {
      // Handle error: Unable to read the file
     echo json_encode(['success' => false, 'message' => 'Error: Unable to read private key file']);
     exit;
  }
} else {
  // Handle error: File does not exist
   echo json_encode(['success' => false, 'message' => 'Error: Private key file does not exist.']);
   exit;
}

    // Get POST data
    $sampleID = $_POST['custodySampleID'];
    $handlerName = $_POST['handlerName'];
    $action    = $_POST['action'];
    $location  = $_POST['location'];
    $narration = $_POST['narration'];
    
    $enviromental[] = [
        'sample_id' => $sampleID,        
        'handlerName' => $handlerName,
        'action' => $action,
        'location' => $location,
        'narration' => $narration
        ];
    
    $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery->fetch_assoc();
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);
  
    
   
    $data = $enviromental[0];
    $dataString = implode('|', array_values($data)) . '|' . $previousHash;
    $current_hash = hash('sha256',$dataString);
    $digital_signature = signData($current_hash,$privateKey);
    // Collect the previous block hash (for linking to previous block in the chain)
    $encryptdata = encryptPrivateKey($dataString,$privateKey);
    $conn->autocommit(0);
    try {
        $stmt = $conn->prepare("INSERT INTO `blockchain_ledger`"
                . "(`timestamp`,`previous_hash`,`current_hash`,`digital_signature`,encrypted_data,`status`,`userid`)"
                . " VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, 'active',?)");
        $stmt->bind_param("ssss",$previousHash,$current_hash,$digital_signature,$encryptdata,$user_id);
        $stmt->execute();
        $blockId = $conn->insert_id;
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
        $stmt->bind_param("sssss",$sampleID, $handlerName,$action,$location,$narration);
        
        $CustodyIDId = $conn->insert_id;
        log_transaction_metadata($conn, $blockId,$CustodyIDId,'sample_custody') ;
  
        // Execute and respond
        if (!$stmt->execute()) {
             echo json_encode([
              'success' => false,
              'message' => "Error executing statement sample_custody " . $stmt->error
             ]);
            exit; // Stop further processing
        } 
        
         $stmt->close();
        
        logAction($conn, 'INSERT',$sampleID, $current_hash,$user_id , 'SUCCESS');
       } catch (Exception $e) {
        $conn->rollback();
        logAction($conn, 'INSERT',$sampleID, $current_hash,$user_id , 'FAILED', $e->getMessage());
       } finally {
        $conn->commit();
       }
  
    $stmt->close();
 } else {
    $conn->autocommit(1);
    $mysqli->close();
    
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}



 echo json_encode([ 'success' => true,'message' => "Saved Successful"]);
?>
