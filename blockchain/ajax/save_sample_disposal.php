<?php

// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

if($_SERVER['REQUEST_METHOD'] === 'POST') {
$username= $_POST['username'];
$user_id = $_POST['user_id'];
// Get POST data
$sampleID = $_POST['disposeSampleID'];
$headerID = $_POST['headerID'];
$reason   = $_POST['reason'];
$disposed_by = $user_id;
// Collect the previous block hash (for linking to previous block in the chain)
$conn->autocommit(0);
    try {
    // Prepare the SQL statement
        $stmt = $conn->prepare("UPDATE  sample_tests set disposal_reason=?, disposal_timestamp=CURRENT_TIMESTAMP, disposed_by=? where SampleID=? and HeaderID=?");
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => "Prepare statement disposal: " . $conn->error
            ]);
            exit; // Stop further processing
        }
        $stmt->bind_param("sdsd",$reason,$disposed_by,$sampleID,$headerID);
        // Execute and respond
        if (!$stmt->execute()) {
             echo json_encode([
              'success' => false,
              'message' => "Error executing statement disposal" . $stmt->error
             ]);
            exit; // Stop further processing
        } 
        
        $stmt->close();
            logAction($conn, 'UPDATE',$sampleID, $current_hash,$user_id , 'SUCCESS');
        } catch (Exception $e) {
            // If there's an error, rollback and log the error
            $conn->rollback();
            logAction($conn, 'UPDATE',$sampleID, $current_hash,$user_id , 'FAILED', $e->getMessage());
        } finally {
            $conn->commit();
        }
  
    $stmt->close();
 } else {
    $conn->autocommit(1);
    $conn->close();
    
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

 echo json_encode([ 'success' => true,'message' => "Saved Successful"]);
?>
