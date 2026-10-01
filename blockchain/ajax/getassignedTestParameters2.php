<?php
// Database connection
require '../db_connection.php'; 
//// Ensure this connects to your MySQLi instance
// Set number of records per page sampleID=LW005140&userid=10
$sampleID = isset($_POST['sampleID']) ? trim($_POST['sampleID']) : '';
$userid = isset($_POST['userid']) ? trim($_POST['userid']) : '';

// Prepare the query to fetch samples
$query ="
SELECT 
    ST.*,
    tp.*,
    TR.*
FROM sample_tests ST
 join `test_results` TR 
    on  ST.`TestID` = TR.`TestID`
 JOIN TestStandards ts 
    ON TR.StandardID = ts.StandardID
 JOIN TestParameters tp 
    ON TR.ParameterID = tp.ParameterID AND TR.StandardID = tp.StandardID
 JOIN test_assignments ta 
    ON TR.resultsID = ta.resultsID   
WHERE ST.SampleID = ? and ta.subcontractor = ? 
AND (TR.MRL_Result IS NULL AND TR.ResultStatus IS NULL  AND TR.RangeResult IS NULL )";
      
// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}

// Bind parameters
$stmt->bind_param("ss",$sampleID,$userid);   
$stmt->execute();
if ($stmt->error) {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]);
    exit; // Stop further processing
}

// Fetch the results
$result = $stmt->get_result();
$samples = [];
while ($row = $result->fetch_assoc()) {
    $samples[] = $row;
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
?>
