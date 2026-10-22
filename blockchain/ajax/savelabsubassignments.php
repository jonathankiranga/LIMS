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

    // Validate the entire request before writing anything. Only parameters explicitly
    // marked as contracted may be assigned to a subcontractor.
    $validatedAssignments = [];
    $checkContracted = $conn->prepare("
        SELECT tr.resultsID
        FROM test_results tr
        INNER JOIN testparameters tp
            ON tp.ParameterID = tr.ParameterID
           AND tp.StandardID = tr.StandardID
        WHERE tr.resultsID = ?
          AND tp.AccreditationStatus = 'contracted'
        LIMIT 1
    ");

    if (!$checkContracted) {
        echo json_encode(['success' => false, 'message' => 'Could not validate subcontractor assignments.']);
        exit;
    }

    foreach ($assignments as $userID => $tests) {
        $userID = filter_var($userID, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userID === false || !is_array($tests) || count($tests) === 0) {
            $checkContracted->close();
            echo json_encode(['success' => false, 'message' => 'Invalid subcontractor assignment data.']);
            exit;
        }

        foreach ($tests as $resultsID) {
            $resultsID = filter_var($resultsID, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($resultsID === false) {
                $checkContracted->close();
                echo json_encode(['success' => false, 'message' => 'Invalid test result in assignment.']);
                exit;
            }

            $checkContracted->bind_param('i', $resultsID);
            if (!$checkContracted->execute()) {
                $checkContracted->close();
                echo json_encode(['success' => false, 'message' => 'Failed to validate test accreditation status.']);
                exit;
            }

            $eligible = $checkContracted->get_result();
            if (!$eligible || $eligible->num_rows !== 1) {
                $checkContracted->close();
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Assignment rejected: only test parameters marked contracted can be subcontracted.'
                ]);
                exit;
            }

            $validatedAssignments[$userID][] = $resultsID;
        }
    }
    $checkContracted->close();
    $assignments = $validatedAssignments;

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
