<?php
require_once dirname(__FILE__) . '/../limsconfig.php';

$records_per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;
$searchtext = isset($_GET['query']) ? trim($_GET['query']) : '';

$query = "SELECT 
    st.*, 
    sh.*, 
    dr.*, 
    tr.SampleID AS ResultSampleID,
    tr.resultsID
FROM Sample_Tests st
JOIN sample_header sh ON st.HeaderID = sh.HeaderID
JOIN debtors dr ON sh.CustomerID = dr.itemcode
JOIN test_results tr ON sh.HeaderID = tr.HeaderID
WHERE tr.StatusID = 4";

if (mb_strlen($searchtext) > 2) {
    $query .= " AND (sh.DocumentNo LIKE CONCAT('%', ?, '%') OR dr.customer LIKE CONCAT('%', ?, '%'))";
}

$query .= " ORDER BY st.sampleid ASC LIMIT ?, ?";

$stmt = lims_prepare($query);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed']);
    exit;
}

if (mb_strlen($searchtext) > 2) {
    $stmt->bind_param("ssii", $searchtext, $searchtext, $offset, $records_per_page);
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}

$stmt->execute();
if ($stmt->error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statement execution failed']);
    exit;
}

$result = $stmt->get_result();
$samples = [];
while ($row = $result->fetch_assoc()) {
    $samples[] = $row;
}

$total_query = "SELECT COUNT(*) AS total
    FROM sample_tests ST
    JOIN test_results TR ON ST.TestID = TR.TestID
    WHERE TR.StatusID = 4";
$total_result = lims_query($total_query);

if (!$total_result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Total records query failed']);
    exit;
}

$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);

$stmt->close();

$response = [
    'success' => true,
    'samples' => $samples,
    'total_records' => $total_records,
    'total_pages' => $total_pages,
    'current_page' => $page
];

header('Content-Type: application/json');
echo json_encode($response);
