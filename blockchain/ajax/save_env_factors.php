<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username       = $_POST['username'];
    $user_id        = $_POST['user_id'];
  
    // Get POST data
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $notes = $_POST['notes'];
    
   
   $conn->autocommit(0);
    try {
    // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO environmental_parameters (temperature, humidity, notes) VALUES ( ?, ?, ?)");
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => "Prepare statement environmental_parameters: " . $conn->error
            ]);
            exit; // Stop further processing
        }
        $stmt->bind_param("dds", $temperature, $humidity, $notes);
        if ($stmt->execute()) {
            $envId = $conn->insert_id;
         // Update the Sample_Tests table with the new environmental_id
            $updateStmt = $conn->prepare("UPDATE Sample_Tests ST SET environmental_id = ? "
                    . " join test_results TR on ST.TestID=TR.TestID WHERE TR.StatusID = 1 ");
            $updateStmt->bind_param('i', $envId);
         // Execute and respond
             if (!$updateStmt->execute()) {
                  echo json_encode([
                   'success' => false,
                   'message' => "Error executing statement environmental_parameters" . $updateStmt->error
                  ]);
                 exit; // Stop further processing
             } 
        } else{
            echo json_encode([
              'success' => false,
              'message' => "Error executing statement environmental_parameters" . $updateStmt->error
             ]);
            exit; // Stop further processing
        }
        $updateStmt->close();
    } catch (Exception $e) {
        $conn->rollback();
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
