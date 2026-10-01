<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
require '../functions/certificate_email_support.php';

try {
    ensure_certificate_email_tracking_schema($conn);
} catch (Throwable $throwable) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to initialise COA email tracking: ' . $throwable->getMessage()]);
    exit;
}
// Set number of records per page
$records_per_page = 50;
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;
// Ensure `query` is sanitized and defaults to an empty string
$searchtext = isset($_GET['query']) ? trim($_GET['query']) : '';

// Prepare the query to fetch samples
$query = "SELECT 
    st.*, 
    sh.*, 
	    dr.*, 
	    tr.SampleID AS ResultSampleID ,
	    tr.resultsID,
	    tr.StatusID AS ResultStatusID,
	    COALESCE(st.coa_email_send_count, 0) AS COAEmailSendCount,
	    st.coa_last_emailed_at AS COALastEmailedAt,
	    st.coa_last_email_status AS COALastEmailStatus
FROM Sample_Tests st
JOIN sample_header sh ON st.HeaderID = sh.HeaderID
JOIN debtors dr ON sh.CustomerID = dr.itemcode
JOIN test_results tr ON sh.HeaderID = tr.HeaderID 
WHERE tr.StatusID = 4";
            if(mb_strlen($searchtext)>2){
               $query .= " AND (st.SampleID LIKE CONCAT('%', ?, '%') OR sh.DocumentNo LIKE CONCAT('%', ?, '%'))";
            }
            $query .=  " order by st.sampleid asc LIMIT ?, ?    ";
      
// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit; // Stop further processing
}
// Bind parameters
if(mb_strlen($searchtext)>2){
    $stmt->bind_param("ssii",$searchtext, $searchtext, $offset, $records_per_page);
}else{
    $stmt->bind_param("ii", $offset, $records_per_page);   
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
$total_query = "SELECT COUNT(*) AS total 
    FROM sample_tests ST
         join `test_results` TR on  ST.`TestID` = TR.`TestID` 
    WHERE TR.StatusID =4";
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
    'sucess' => true,
    'samples' => $samples,
    'total_records' => $total_records,
    'total_pages' => $total_pages,
    'current_page' => $page
];

// Send the response
 header('Content-Type: application/json');
echo json_encode($response);
?>
