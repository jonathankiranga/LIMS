<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
$department = isset($_POST['department']) ? trim($_POST['department']) : null;

header('Content-Type: application/json');
// Prepare the query to fetch samples
$query = "SELECT 
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
LEFT JOIN samples_received SR 
       ON ST.SampleID = SR.sample_id 
       AND (SR.assigned_department = ? )
WHERE SR.sample_id IS NULL";
// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
     echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
     exit;
}
// Bind parameters

$stmt->bind_param("s",$department);
$stmt->execute();
if ($stmt->error) {
    echo json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]);
     exit;
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
echo json_encode(['success' => true, 'samples' => $samples]);

?>
