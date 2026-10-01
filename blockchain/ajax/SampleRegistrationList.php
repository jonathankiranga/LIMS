<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Set number of records per page
$records_per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;
// Ensure `query` is sanitized and defaults to an empty string
$searchtext = isset($_GET['query']) ? trim($_GET['query']) : '';
// Prepare the query to fetch samples
$query = "
SELECT 
    ts.StandardName, 
    ST.SampleID, 
    ST.SampleFileKey, 
    ST.SKU, 
    ST.BatchNo, 
    ST.BatchSize, 
    ST.ManufactureDate, 
    ST.ExpDate, 
    ST.ExternalSample, 
    SH.SamplingDate,
    SH.Date,
    ST.HeaderID,
    ST.disposal_timestamp  
FROM sample_tests ST
JOIN Sample_Header SH 
    ON SH.HeaderID = ST.HeaderID
JOIN TestStandards ts 
    ON ST.StandardID = ts.StandardID 
WHERE ST.SampleID LIKE CONCAT('%', ?, '%') 
LIMIT ?, ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]));
}
// Bind parameters
$stmt->bind_param("sii", $searchtext, $offset, $records_per_page);
// Execute the query
$stmt->execute();
if ($stmt->error) {
    die(json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]));
}
// Fetch the results
$result = $stmt->get_result();
$samples = [];
while ($row = $result->fetch_assoc()) {
   $samples[] = $row;
}

// Get total records for pagination
$total_query = "SELECT COUNT(*) AS total FROM sample_tests";
$total_result = $conn->query($total_query);
if (!$total_result) {
    die(json_encode(['success' => false, 'message' => 'Total records query failed: ' . $conn->error]));
}
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Close the statement and connection
$stmt->close();
$conn->close();

// Prepare the response
$response = [
    'samples' => $samples,
    'total_records' => $total_records,
    'total_pages' => $total_pages,
    'current_page' => $page
];

// Send the response
header('Content-Type: application/json');
echo json_encode($response);
?>
