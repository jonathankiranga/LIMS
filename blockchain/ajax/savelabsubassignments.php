<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = file_get_contents('php://input');
    $assignments = json_decode($input, true);

    if (!$assignments) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    $getcontorID='';
    // Insert assignments into the database
    $stmt = $conn->prepare("INSERT INTO test_assignments (subcontractor, resultsID,category, assigned_at) VALUES (?, ?,'admin', NOW()) ON DUPLICATE KEY UPDATE assigned_at = NOW(); ");
    foreach ($assignments as $userID => $tests) {
        $getcontorID = $userID;
        foreach ($tests as $resultsID) {
            $stmt->bind_param('ii', $userID, $resultsID);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Failed to save assignments.']);
                exit;
            }
        }
    }

    $stmt->close();
    
    $lastBlockQuery = $conn->query("SELECT `name`,city FROM subcontractors where id='$getcontorID'");
    $lastBlock   = $lastBlockQuery->fetch_assoc();
    $handlerName = $lastBlock['name'] ;
    $location    = $lastBlock['city'] ;
    
    foreach ($assignments as $userID => $tests) {
       foreach ($tests as $resultsID) {
        $lastBlockQuery = $conn->query("SELECT `SampleID` FROM `test_results` where `resultsID`='$resultsID'");
        $lastBlock = $lastBlockQuery->fetch_assoc();
        $sid = trim($lastBlock['SampleID']) ;
        $sampleID[$sid] = $lastBlock['SampleID'] ;
       }
    }
    
        $stmt = $conn->prepare("INSERT INTO `sample_custody`(`SampleID`,`HandlerName`,`Action`,`Location`,`Notes`,`DateTime`) VALUES (?, ?, ?, ?, ? ,CURRENT_TIMESTAMP)");
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => "Prepare statement sample_custody: " . $conn->error
            ]);
            exit; // Stop further processing
        }
         
        foreach ($sampleID as $value) {
            $action = 'Sub-contracting';
            $narration = "Sub Contract sample ID $value";
            $stmt->bind_param("sssss", $value, $handlerName, $action, $location, $narration);

            if (!$stmt->execute()) {
                echo json_encode([
                    'success' => false,
                    'message' => "Execute statement error: " . $stmt->error
                ]);
                exit; // Stop further processing on error
            }
        }
        
         $stmt->close();
    
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Assignments saved successfully.']);
}
