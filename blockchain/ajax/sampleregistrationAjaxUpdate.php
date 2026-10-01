<?php
require_once '../db_connection.php'; 
require_once '../functions/functions.php'; // Include PHPMailer via Composer
require_once '../functions/tasks.php'; // Include PHPMailer via Composer
require_once 'getrefferencesfunction.inc'; 
 // Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $username     = $_POST['username'];
 $user_id      = $_POST['user_id'];
 $dir          = "userkeys/$user_id";
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
    'date' => $_POST['date'] ?? null,
    'batch No' => $_POST['documentno'] ?? null,
    'customer Name' => $_POST['CustomerName'] ?? null,
    'customerId' => $_POST['CustomerID'] ?? null,
    'sampled By' => $_POST['sampledby'] ?? null,
    'sampling Method' => $_POST['SamplingMethod'] ?? null,
    'sampling Date' => $_POST['samplingdate'] ?? null,
    'order No' => $_POST['Orderno'] ?? null
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

$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
// Retrieve form fields
$date           = $_POST['date'] ?? null;
$documentNo     = $_POST['batchno'] ?? null;
$customerName   = $_POST['CustomerName'] ?? null;
$customerId     = $_POST['CustomerID'] ?? null;
$sampledBy      = $_POST['sampledby'] ?? null;
$samplingMethod = $_POST['SamplingMethod'] ?? null;
$samplingDate   = $_POST['samplingdate'] ?? null;
$orderNo        = $_POST['Orderno'] ?? null;
$rowsposted     = $_POST['tablecount'] ?? 0;
$HeaderID       = $_POST['HeaderID'] ?? 0;
 
// sample header
$samplehaeder = [
'documentno' => $documentNo,
'HeaderID' => $HeaderID,
'date' => $date,
'customerName' => $customerName,
'customerId' => $customerId,
'sampledBy' => $sampledBy,
'samplingMethod' => $samplingMethod,
'samplingDate' => $samplingDate,
'orderNo' => $orderNo,
'User_name' => $username ,
'tablecount' => $rowsposted
];

    if ($date && $samplingDate) {
    // Convert to DateTime objects
    $registrationDate = new DateTime($date);
    $sampleDate = new DateTime($samplingDate);
    // Compare dates
    if ($sampleDate > $registrationDate) {
            // Sampling date is earlier than registration date
            echo json_encode([
                'success' => false,
                'message' => 'The sampling date cannot be later than the registration date.'
            ]);
            exit; // Stop further processing
    }    
        
  } else {
        // Handle missing dates
        echo json_encode([
            'success' => false,
            'message' => 'Both registration date and sampling date are required.'
        ]);
        exit;
    }
     
    $samples=array();
    $parameterData=array();
   
    for ($i = 0; $i < ($rowsposted); $i++) {
        $TestID         =(int) $_POST['rowindex'][$i];
        $sampletype     =(int) $_POST['standard_id'][$i];
        $matrix_id      =(int) $_POST['matrix_id'][$i];  //new
        $samplesCount   =(int) $_POST['samples'][$i];  // new config
        $sku            = $_POST['kit_units'][$i];
        $batchNo        = $_POST['batch_no'][$i];
        $batchSize      = $_POST['batch_size'][$i];
        $mandate        = $_POST['date_mfg'][$i];
        $expDate        = $_POST['date_exp'][$i];
        $SampleID       = $_POST['SampleID'][$i];
        $externalSample = $_POST['sample_source'][$i];
        $sampleName = $_POST['sample_name'][$i] ?? null;
        $sampleMethod = $_POST['sample_method'][$i] ?? null;
        $conditionOfSample = $_POST['condition_of_sample'][$i] ?? null;
        $chilledDateExp = $_POST['chilled_date_exp'][$i] ?? null;
        $frozenDateExp = $_POST['frozen_date_exp'][$i] ?? null;
        $sampleFileKey  = $_FILES["sample_images"];
   // Handle file upload if provided
          $uploadedFile     = null;
         if (isset($sampleFileKey['name'][$i]) && $sampleFileKey['error'][$i] === UPLOAD_ERR_OK) {
            $originalFileName = $sampleFileKey['name'][$i];
            $fileExtension    = pathinfo($originalFileName, PATHINFO_EXTENSION); // Extract file extension
            $newFileName      = $SampleID. '.' . $fileExtension;
            $destination      = "../uploads/$newFileName"  ;
            if(move_uploaded_file($sampleFileKey['tmp_name'][$i],$destination)){
               $uploadedFile = $destination;
            }
          }
$parameterData[$i] = [
                'HeaderID'       => $HeaderID ,
                'TestID'         => $TestID,  
                'SampleID'       => $SampleID,
                'sampleType'     => $sampletype,
                'matrixID'       => $matrix_id ,
                'sku'            => $sku,
                'batchNo'        => $batchNo,
                'batchSize'      => $batchSize,
                'mandate'        => $mandate,
                'expDate'        => $expDate,
                'externalSample' => $externalSample ,  
                'sampleFileKey'  => $uploadedFile,
                'User_name'      => $username,
                'samplesCount'   => $samplesCount,
                'sample_name' => $sampleName,
                'sample_method' => $sampleMethod,
                'condition_of_sample' => $conditionOfSample,
                'chilled_date_exp' => $chilledDateExp,
                'frozen_date_exp' => $frozenDateExp
            ];
              
         for ($smpcnt = 0; $smpcnt < ($samplesCount); $smpcnt++) {
               
            if(mb_strlen($matrix_id)>0){
                $FilteredParameters = gettestparametersFromTestID($TestID,$sampletype,$matrix_id);}
            else { 
                $FilteredParameters = getALLtestparametersFromTestID($TestID,$sampletype);
                    // If front-end sent explicit selected_params for this row, prefer them
                    if (isset($_POST['selected_params'][$i]) && is_array($_POST['selected_params'][$i])) {
                        $paramsFromFrontend = [];
                        foreach ($_POST['selected_params'][$i] as $rowParam) {
                            // allow either associative or simple arrays from frontend
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
                            $FilteredParameters = $paramsFromFrontend;
                        }
                    }
            }
              foreach ($FilteredParameters as $parameter) {
                  $testData[$i][$smpcnt][] = [
                      'resultsID'   => $resultsID,
                      'HeaderID'    => $HeaderID ,
                      'TestID'      => $TestID,  
                      'parameterId' => $parameter['ParameterID'],        
                      'sampleType'  => $parameter['StandardID'],
                      'sampleNo'    => $parameter['SampleID'],
                      'User_name'   => $username,
                      'BasePID'     => $parameter['BaseID']
                  ];
              }

         } 


}
        


    //here is the log_transaction_metadata($mysqli, $blockId, $recordId,$tablename)
    $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
    $lastBlock = $lastBlockQuery->fetch_assoc();
    $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat("0", 64);
     
    $mergedData = array_merge($samplehaeder,$parameterData,$testData);
    $data = $mergedData[0];
    $dataString = implode('|', array_values($data)) . '|' . $previousHash;
    $current_hash = hash('sha256',$dataString);
    $digital_signature = signData($current_hash,$privateKey);
    // Collect the previous block hash (for linking to previous block in the chain)
    $encryptdata = encryptPrivateKey($dataString,$privateKey);
    $conn->autocommit(0);
    try {
   
        $stmt = $conn->prepare("INSERT INTO `blockchain_ledger`(`timestamp`,`previous_hash`,`current_hash`,`digital_signature`,encrypted_data,`status`,`userid`)"
                . " VALUES (CURRENT_TIMESTAMP, ?, ?, ?,?, 'active',?)");
        $stmt->bind_param("sssss",$previousHash,$current_hash,$digital_signature,$encryptdata,$user_id);
        $stmt->execute();
        $blockId = $conn->insert_id;
        $stmt->close();
         
                
        $stmt = $conn->prepare("UPDATE sample_header SET  "
                . "Date=?, CustomerName=?,CustomerID=?, SampledBy=?, SamplingMethod=?, "
                . "SamplingDate=?,  OrderNo=?,  User_name=?  where HeaderID=?");
        if (!$stmt) {
            echo json_encode(['success' => false,'message' => "Prepare statement sample_header failed: " . $conn->error]);
            exit; // Stop further processing
        }
        
        $stmt->bind_param("ssssssssi", 
                $date, $customerName,$customerId, $sampledBy, $samplingMethod, $samplingDate,$orderNo,$username,$HeaderID);
        
    // Execute the statement
        if ($stmt->execute()) {
            log_transaction_metadata($conn,$blockId,$HeaderID,'sample_header') ;
        }else{
             echo json_encode(['success' => false,'message' => "Error executing statement sample_header: " . $stmt->error]);
              $stmt->close();
              exit; // Stop further processing
        }
        
        $stmt->close();
          
        $stmt = $conn->prepare("
            INSERT INTO `sample_tests` (
                SampleID, StandardID, SampleFileKey, SKU, BatchNo, BatchSize, ManufactureDate, ExpDate, ExternalSample, User_name, BaseID,  HeaderID, TestID,
                sample_name, sample_method, condition_of_sample, chilled_date_of_expiry, frozen_date_of_expiry
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                SampleID = VALUES(SampleID),
                StandardID = VALUES(StandardID),
                SampleFileKey = VALUES(SampleFileKey),
                SKU = VALUES(SKU),
                BatchNo = VALUES(BatchNo),
                BatchSize = VALUES(BatchSize),
                ManufactureDate = VALUES(ManufactureDate),
                ExpDate = VALUES(ExpDate),
                ExternalSample = VALUES(ExternalSample),
                User_name = VALUES(User_name),
                BaseID = VALUES(BaseID),
                sample_name = VALUES(sample_name),
                sample_method = VALUES(sample_method),
                condition_of_sample = VALUES(condition_of_sample),
                chilled_date_of_expiry = VALUES(chilled_date_of_expiry),
                frozen_date_of_expiry = VALUES(frozen_date_of_expiry)
              ");

        
        $stmt2 = $conn->prepare("
            INSERT INTO `sample_tests` (
                SampleID, StandardID, SKU, BatchNo, BatchSize,ManufactureDate, ExpDate, ExternalSample, User_name, BaseID, HeaderID, TestID,
                sample_name, sample_method, condition_of_sample, chilled_date_of_expiry, frozen_date_of_expiry
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                SampleID = VALUES(SampleID),
                StandardID = VALUES(StandardID),
                SKU = VALUES(SKU),
                BatchNo = VALUES(BatchNo),
                BatchSize = VALUES(BatchSize),
                ManufactureDate = VALUES(ManufactureDate),
                ExpDate = VALUES(ExpDate),
                ExternalSample = VALUES(ExternalSample),
                User_name = VALUES(User_name),
                BaseID = VALUES(BaseID),
                sample_name = VALUES(sample_name),
                sample_method = VALUES(sample_method),
                condition_of_sample = VALUES(condition_of_sample),
                chilled_date_of_expiry = VALUES(chilled_date_of_expiry),
                frozen_date_of_expiry = VALUES(frozen_date_of_expiry)
              ");

        if (!$stmt) {
              echo json_encode(['success' => false,'message' => "Prepare statement sample_tests failed : " . $conn->error]);
              exit; // Stop further processing
        }
              
         $teststmt = $conn->prepare("INSERT INTO `test_results`"
                 . " (`TestID`,`HeaderID`,`SampleID`,`StandardID`,`ParameterID`,`User_name`,`StatusID`,`BaseID`) "
                 . " values  (?,?,?,?,?,?,1,?)  ON DUPLICATE KEY UPDATE
                TestID = VALUES(TestID),
                HeaderID = VALUES(HeaderID),
                SampleID = VALUES(SampleID),
                StandardID = VALUES(StandardID),
                ParameterID = VALUES(ParameterID),
                BaseID = VALUES(BaseID)");
        if (!$teststmt) {
            echo json_encode([ 'success' => false,'message' => "Prepare statement test_results failed: " . $conn->error ]);
            exit; // Stop further processing
        }
         
        foreach ($parameterData as $id => $datarows) {
             
          if($datarows['sampleFileKey']==null){
              
                
            $stmt2->bind_param("sississssiiiii",$datarows['SampleID'],$datarows['sampleType'],
            $datarows['sku'], $datarows['batchNo'], $datarows['batchSize'],
            $datarows['mandate'],$datarows['expDate'],$datarows['externalSample'],$datarows['User_name'],
            $datarows['matrixID'],$HeaderID,$TestID,
            $datarows['sample_name'],$datarows['sample_method'],$datarows['condition_of_sample'],
            $datarows['chilled_date_exp'],$datarows['frozen_date_exp']);
           // Execute the statement
            if ($stmt2->execute()) {
                      foreach ($testData[$id] as $aID => $testdatachild) {
                               // $SampleID = GetNextLabrefNo('19');
                                foreach ($testdatachild as $ri => $tedata) {

                                  if($tedata['resultsID']==null){
                                   
                                     $teststmt->bind_param("iisiisi",
                                     $TestID,$HeaderID,$tedata['SampleID'],$tedata['sampleType'],
                                     $tedata['parameterId'],$tedata['User_name'],$tedata['BasePID']);

                                        if (!$teststmt->execute()) {
                                          echo json_encode(['success' => false, 'message' => "Error executing statement test_results: $SampleID " . $teststmt->error ]);
                                          exit; // Stop further processing
                                        }
                                  }

                                }
                }
            }else{
                      echo json_encode(['success' => false,'message' => "Error executing statement sample_2tests: $SampleID" . $stmt2->error]);
                      exit; // Stop further processing
            }
                
          }else{
                
            $stmt->bind_param("sisssissssiiiii",$datarows['SampleID'],$datarows['sampleType'],
            $datarows['sampleFileKey'], $datarows['sku'], $datarows['batchNo'], $datarows['batchSize'],
            $datarows['mandate'],$datarows['expDate'],$datarows['externalSample'],$datarows['User_name'],
            $datarows['matrixID'],$HeaderID,$TestID,
            $datarows['sample_name'],$datarows['sample_method'],$datarows['condition_of_sample'],
            $datarows['chilled_date_exp'],$datarows['frozen_date_exp']);
            // Execute the statement
            if ($stmt->execute()) {
                foreach ($testData[$id] as $aID => $testdatachild) {
                      // $SampleID = GetNextLabrefNo('19');
                    foreach ($testdatachild as $ri => $tedata) {
                      
                      if($tedata['resultsID']==null){
                         
                         $teststmt->bind_param("iisiisi",$TestID,$HeaderID,$tedata['SampleID'],$tedata['sampleType'],
                         $tedata['parameterId'],$tedata['User_name'],$tedata['BasePID']);
                       
                            if (!$teststmt->execute()) {
                              echo json_encode(['success' => false, 'message' => "Error executing statement test_results: $SampleID " . $teststmt->error ]);
                              exit; // Stop further processing
                            }
                      }
                      
                    }
                }
            }else{
                    echo json_encode(['success' => false,'message' => "Error executing statement sample_tests: $SampleID" . $stmt->error]);
                  exit; // Stop further processing
            }
            
        }

       
             
        }
         
        $teststmt->close();
        $stmt->close();
 

// Trigger the "test_approved" event
    // Log successful action
    logAction($conn, 'INSERT', $documentNo, $current_hash,$user_id , 'SUCCESS');
} catch (Exception $e) {
    // If there's an error, rollback and log the error
    $conn->rollback();
    logAction($conn, 'INSERT', $documentNo, $current_hash,$user_id , 'FAILED', $e->getMessage());
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

echo json_encode(['success' => true,'message' => "Edit Registration no: $documentNo Successful"  ]);
 
foreach ($samplehaeder as $key => $value) {
    foreach ($samplehaeder[$value['tablecount']] as $key2 => $value2) {
        foreach ($testData[$value['tablecount']][$value2['samplesCount']] as $key3 => $row) {
            if (!empty($row['sampleType'])) {
                
                $hid = (int)$row['HeaderID'];
                $tid = (int)$row['TestID'];
        
                $key = $hid . ':' . $tid;
                $standardIdsFromUser[$key][] = (int)$row['sampleType']; // StandardID
                
            }
        }
  }
}

foreach ($standardIdsFromUser as $k => $arr) {
    $selectedByPair[$k] = array_values(array_unique($arr, SORT_NUMERIC));
}
// Remove duplicates just in case

$conn->begin_transaction();

try {
    foreach ($selectedByPair as $key => $stds) {
        list($HeaderID, $TestID) = explode(':', $key);
        $HeaderID = (int)$HeaderID;
        $TestID = (int)$TestID;

        // Build placeholders for the NOT IN list
        $placeholders = implode(',', array_fill(0, count($stds), '?'));
        $sql = "DELETE FROM test_results
                WHERE HeaderID=? AND TestID=? 
                AND StandardID NOT IN ($placeholders)";

        $stmt = $conn->prepare($sql);

        // Merge params: HeaderID, TestID, then all StandardIDs
        $params = array_merge([$HeaderID, $TestID], $stds);
        $types = str_repeat('i', count($params)); // all integers

        // Bind dynamically. Try modern splat first, fallback to call_user_func_array for older PHP
        if (method_exists($stmt, 'bind_param')) {
            // modern PHP (5.6+)
            $bound = @ $stmt->bind_param($types, ...$params);
            if ($bound === false) {
                // fallback for older PHP versions: build references
                $refs = [];
                foreach ($params as $idx => $val) {
                    $refs[$idx] = &$params[$idx];
                }
                array_unshift($refs, $types);
                call_user_func_array([$stmt, 'bind_param'], $refs);
            }
        }

        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    // handle/log error as appropriate
    throw $e;
}

?>

