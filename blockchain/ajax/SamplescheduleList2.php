<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
if(isset($_POST['department']) ){
$department = $conn->real_escape_string($_POST['department']); // Escape user input to prevent SQL injection
//'chemical','admin','microbiological'
// Prepare the query to fetch samples
$query = "SELECT distinct
    ts.StandardName, 
    ST.SampleID, 
    ST.SampleFileKey, 
    ST.TestID, 
    ST.SKU, 
    ST.BatchNo, 
    ST.BatchSize, 
    ST.ManufactureDate, 
    ST.ExpDate, 
    ST.ExternalSample, 
    SH.SamplingDate,
    SH.Date,
    ST.HeaderID
FROM sample_tests ST
JOIN Sample_Header SH 
    ON ST.HeaderID = SH.HeaderID
JOIN TestStandards ts 
    ON ST.StandardID = ts.StandardID
join test_results TRR
    ON ST.HeaderID = TRR.HeaderID
LEFT JOIN test_assignments SS 
    ON TRR.resultsID = SS.resultsID and SS.category=?
WHERE SS.resultsID IS NULL";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]));
}
$stmt->bind_param("s",$department);
$stmt->execute();
if ($stmt->error) {
    die(json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]));
}

// Fetch the results
$result = $stmt->get_result();
$samples = [];
while ($row = $result->fetch_assoc()) {
    $SampleID=trim($row['SampleID']);
    $SampleFileKey=trim($row['SampleFileKey']);
    if(file_exists($SampleFileKey)){
        $fileExtension= pathinfo($SampleFileKey, PATHINFO_EXTENSION); // Extract file extension
  
        $row['SampleFileKey']="uploads/$SampleID.$fileExtension";
    }else{
        $row['SampleFileKey']='uploads/icons8-no-image-100.png';
    }
   $samples[] = $row;
}

$stmt->close();
$conn->close();

    // Send the response
    header('Content-Type: application/json');
    echo json_encode($samples);
}else{
    header('Content-Type: application/json');
    echo json_encode(array());
}
?>
