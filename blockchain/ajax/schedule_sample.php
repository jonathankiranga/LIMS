<?php
// Simulating the database connection
// Replace with your actual database logic
require_once '../db_connection.php'; 
require_once '../functions/functions.php'; // Include PHPMailer via Composer
require_once 'getrefferencesfunction.inc';
 
// Ensure it's a POST request
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

// Collect required fields
$requiredFields = [
    'date' => $_POST['scheduleDate'] ?? null // Include even if it's zero
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
    $date = $_POST['scheduleDate'] ?? null;
    $sampleID = $_POST['sampleID'] ?? null;
    $testID = $_POST['testID'] ?? null;
    $comments = $_POST['comments'] ?? null;
    
    
    // sample header
    $samplehaeder = [
    'date' => $date,
    'sampleID' => $sampleID,
    'testID' => $testID,
    'comments' => $comments,
    'User_name' => $username
    ];
    
    if($date) {
    // Convert to DateTime objects
    $scheduleDate = new DateTime($date);
    $todayDate = new DateTime();
    $todayDate->format('Y-m-d H:i:s');
    // Compare dates
    if ($todayDate > $scheduleDate) {
            // Sampling date is earlier than registration date
            echo json_encode([
                'success' => false,
                'message' => 'The schedule date cannot be earlier than today.'
            ]);
            exit; // Stop further processing
        } 
        
    } else {
        // Handle missing dates
        echo json_encode([
            'success' => false,
            'message' => 'schedule date is required.'
        ]);
        exit;
    }
           
    $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery->fetch_assoc();
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);
    
    $dataString = implode('|', array_values($samplehaeder)) . '|' . $previousHash;
    $current_hash = hash('sha256',$dataString);
    $digital_signature = signData($current_hash,$privateKey);
    // Collect the previous block hash (for linking to previous block in the chain)
    $encryptdata = encryptPrivateKey($dataString,$privateKey);
    $conn->autocommit(0);
    try {
   
        $stmt = $conn->prepare("INSERT INTO `blockchain_ledger`"
                . "(`timestamp`,`previous_hash`,`current_hash`,`digital_signature`,encrypted_data,`status`,`userid`)"
                . " VALUES (CURRENT_TIMESTAMP, ?, ?, ?,?, 'active',?)");
        $stmt->bind_param("ssss",$previousHash,$current_hash,$digital_signature,$encryptdata,$user_id);
        $stmt->execute();
        $blockId = $conn->insert_id;
        $stmt->close();
        // Bind parameters
        
        $stmt = $conn->prepare("INSERT INTO sample_schedule (SampleID,ScheduleDate,Notes,CreatedAt) VALUES (?, ?, ?,CURRENT_TIMESTAMP)");
        if (!$stmt) {
            echo json_encode(['success' => false,'message' => "Prepare statement sample_schedule failed: " . $conn->error]);
            exit; // Stop further processing
        }
       
        $stmt->bind_param("sss",$sampleID,$date,$comments);
        if ($stmt->execute()) {
            $sample_scheduleID = $conn->insert_id;
            log_transaction_metadata($conn,$blockId,$sample_scheduleID,'sample_schedule') ;
        }else {
            echo json_encode(['success' => false,'message' => "Error executing statement sample_schedule: " . $stmt->error]);
              $stmt->close();
              exit; // Stop further processing
        }
     
        $stmt->close();
       logAction($conn, 'INSERT', $sampleID , $current_hash,$user_id , 'SUCCESS');
    } catch (Exception $e) {
        // If there's an error, rollback and log the error
        $conn->rollback();
        logAction($conn, 'INSERT', $sampleID , $current_hash,$user_id , 'FAILED', $e->getMessage());
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

