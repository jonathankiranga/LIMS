<?php

// Include database connection
require_once '../db_connection.php';

// Check if the request is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['headerId'])) {
    $headerId = $_POST['headerId'];

    try {
        // Fetch sample header details
        $stmtHeader = $conn->prepare("SELECT * FROM sample_header WHERE HeaderID = ?");
        $stmtHeader->bind_param("i", $headerId);
        $stmtHeader->execute();
        $headerResult = $stmtHeader->get_result();

        if ($headerResult->num_rows > 0) {
            $sampleHeader = $headerResult->fetch_assoc();
      // Fetch sample tests
            $stmtTests = $conn->prepare("SELECT ST.*,TS.StandardName FROM sample_tests ST  join teststandards TS on ST.StandardID=TS.StandardID WHERE HeaderID = ?");
            $stmtTests->bind_param("i", $headerId);
            $stmtTests->execute();
            $testsResult = $stmtTests->get_result();

            $sampleTests = [];
            while ($row = $testsResult->fetch_assoc()) {
                $sampleTests[] = $row;
            }
       // Fetch test results for each TestID
            $testResults = [];  // Initialize test results array
            $stmtResults = $conn->prepare("SELECT * FROM test_results WHERE HeaderID = ? AND TestID = ? and StandardID=? and SampleID=?");

            foreach ($sampleTests as $test) {
                $stmtResults->bind_param("iiis", $headerId,$test['TestID'],$test['StandardID'],$test['SampleID']);
                $stmtResults->execute();
                $resultsResult = $stmtResults->get_result();
                 while ($row = $resultsResult->fetch_assoc()) {
                    $testResults[] = $row;  // Group results by TestID
                }
            }
            // Return data as JSON
            echo json_encode([
                'success' => true,
                'sampleHeader' => $sampleHeader,
                'sampleTests' => $sampleTests,
                'testResults' => $testResults
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'No record found for the given HeaderID.']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching data: ' . $e->getMessage()]);
    } finally {
        $stmtHeader->close();
        $stmtTests->close();
        $stmtResults->close();
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
