<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Prepare the query to fetch samples
$query = "
SELECT 
    DISTINCT ST.*, ta.*, sc.*
FROM sample_tests ST
JOIN test_results TR ON ST.TestID = TR.TestID
JOIN TestStandards ts ON TR.StandardID = ts.StandardID
JOIN TestParameters tp ON TR.ParameterID = tp.ParameterID AND TR.StandardID = tp.StandardID
JOIN test_assignments ta ON TR.resultsID = ta.resultsID  
JOIN subcontractors sc ON ta.subcontractor = sc.id  
WHERE TR.StatusID = 1   
AND (TR.MRL_Result IS NULL 
AND TR.ResultStatus IS NULL  
AND TR.RangeResult IS NULL)";

// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}


// Execute the statement
$stmt->execute();
if ($stmt->error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]);
    exit; // Stop further processing
}

// Fetch the results
$result = $stmt->get_result();
$samples = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $samples[] = $row;
    }
} else {
    // Handle no results case
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No samples found.']);
    exit;
}


// Close the statement and connection
$stmt->close();
$conn->close();

// Prepare the response
$response = [
    'success' => true,
    'samples' => $samples
];

// Send the response
 header('Content-Type: application/json');
echo json_encode($response);