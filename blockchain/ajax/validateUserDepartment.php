<?php
$data = json_decode(file_get_contents('php://input'), true);
$SampleID   = isset($data['SampleID']) ? trim($data['SampleID']) : '';
$department = isset($data['department']) ? trim($data['department']) : '';
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Set number of records per page
// Prepare the query to fetch samples
$query = "SELECT 
    COUNT(*) AS ParameterCount
FROM sample_tests ST
    JOIN test_results TR 
        ON ST.TestID = TR.TestID
    JOIN TestParameters tp 
        ON TR.ParameterID = tp.ParameterID  AND TR.StandardID = tp.StandardID
WHERE tp.Category = ? ";
 // Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}

$stmt->bind_param("s",$department);   
$stmt->execute();
if ($stmt->error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]);
    exit; // Stop further processing
}
// Fetch the results
$result = $stmt->get_result();
$row = $result->fetch_assoc();
header('Content-Type: application/json');
if($row['ParameterCount']>0){
   echo json_encode(['success' => true, 'message' => $row['ParameterCount']]);
 }else{
  echo json_encode(['success' => false, 'message' => $_POST['department']]);
}
// Send the response

?>
