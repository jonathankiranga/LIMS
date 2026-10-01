<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Set number of records per page
$records_per_page = 50;
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;
// Ensure `query` is sanitized and defaults to an empty string
$category   = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchtext = isset($_GET['query']) ? trim($_GET['query']) : '';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';

if ($category !== $department) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You are not in this department']);
    // Prepare the response

    exit; // Stop further processing
}

// Prepare the query to fetch samples
$query = "SELECT 
        tp.ParameterName, 
        tp.Category, 
        ST.SampleID, 
        TR.ParameterID, 
        ST.SampleFileKey, 
        ST.SampleFee, 
        ST.SKU, 
        ST.BatchNo, 
        ST.BatchSize, 
        ST.ManufactureDate, 
        ST.ExpDate, 
        ST.ExternalSample, 
        SH.SamplingDate,
        SH.Date,
        ST.HeaderID,
        TR.resultsID
    FROM sample_tests ST
     join Sample_Header SH 
        on ST.HeaderID = SH.HeaderID
     join `test_results` TR 
        on  ST.`TestID` = TR.`TestID`
     JOIN TestStandards ts 
        ON TR.StandardID = ts.StandardID
     JOIN TestParameters tp 
        ON TR.ParameterID = tp.ParameterID AND TR.StandardID = tp.StandardID
     JOIN samples_received sr 
        ON ST.SampleID = sr.sample_id   
    WHERE TR.StatusID =1 and tp.Category = ? ";
    if(mb_strlen($searchtext)>2){
       $query .= " AND ( ST.SampleID LIKE CONCAT('%', ?, '%') OR tp.ParameterName LIKE CONCAT('%', ?, '%')  )";
    }
       $query .=  " LIMIT ?, ?";
      
// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}
// Bind parameters
if(mb_strlen($searchtext)>2){
$stmt->bind_param("sssii",$category,$searchtext, $searchtext, $offset, $records_per_page);
}else{
 $stmt->bind_param("sii",$category, $offset, $records_per_page);   
}
// Execute the query
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
    $SampleID=trim($row['SampleID']);
    $SampleFileKey=trim($row['SampleFileKey']);
    if(file_exists($SampleFileKey)){
        $fileInfo = pathinfo($SampleFileKey);
        $fileName = $fileInfo['filename'];     // Output: photo
        $extension = $fileInfo['extension'];   // Output: jpg
        $row['SampleFileKey']="uploads/$fileName.$extension";
    }else{
        $row['SampleFileKey']='uploads/icons8-no-image-100.png';
    }
    $samples[] = $row;
}

// Get total records for pagination
$total_query = "SELECT COUNT(*) AS total FROM sample_tests";
$total_result = $conn->query($total_query);
if (!$total_result) {
     header('Content-Type: application/json');
     echo json_encode(['success' => false, 'message' => 'Total records query failed: ' . $conn->error]);
     exit; // Stop further processing
}
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Close the statement and connection
$stmt->close();
$conn->close();

// Prepare the response
$response = [
    'success' => true,
    'samples' => $samples,
    'total_records' => $total_records,
    'total_pages' => $total_pages,
    'current_page' => $page
];

// Send the response
 header('Content-Type: application/json');
echo json_encode($response);
?>
