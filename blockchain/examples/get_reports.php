<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Fetch Top 10 Testing
$sql = "SELECT tp.ParameterName, COUNT(*) AS test_count 
        FROM test_results tr 
        JOIN testparameters tp 
            ON tp.ParameterID = tr.ParameterID 
            AND tp.StandardID = tr.StandardID
        JOIN teststandards ts 
            ON ts.StandardID = tr.StandardID 
        where tr.StatusID = 4
        GROUP BY ParameterName 
        ORDER BY test_count DESC 
        LIMIT 10";
$result = $conn->query($sql);

$top_tests = [];
while ($row = $result->fetch_assoc()) {
    $top_tests[] = $row;
}

// Fetch Turnaround Time (TAT) Analysis
$sql = "SELECT tp.ParameterName,
               AVG(TIMESTAMPDIFF(
                   DAY, 
                   tr.created_at,
                   CASE
                       WHEN TR.StatusID = 1 THEN NOW() 
                       ELSE IFNULL(tr.updated_at, NOW())
                   END
               ) ) AS avg_tat 
        FROM test_results tr 
        JOIN testparameters tp 
            ON tp.ParameterID = tr.ParameterID 
            AND tp.StandardID = tr.StandardID
        JOIN teststandards ts 
            ON ts.StandardID = tr.StandardID 
        JOIN sample_tests st 
            ON tr.HeaderID = st.HeaderID
        where tr.StatusID = 4
        GROUP BY ParameterName 
        ORDER BY avg_tat DESC 
        LIMIT 10";
$result = $conn->query($sql);

$tat_analysis = [];
while ($row = $result->fetch_assoc()) {
    $tat_analysis[] = $row;
}

// Status mapping
$statusmaping = [
    1 => 'Sample Received',
    2 => 'In Progress',
    3 => 'Waiting Approval'
];

// Fetch Pending vs. Completed Tests
$sql = "SELECT StatusID, COUNT(*) AS count 
        FROM test_results 
        WHERE StatusID < 4 
        GROUP BY StatusID";
$result = $conn->query($sql);

$pending_completed = [];
while ($row = $result->fetch_assoc()) {
    $txt = $statusmaping[$row['StatusID']];
    $pending_completed[$txt] = $row['count'];
}

// Fetch Consolidated Report
$sql = "SELECT TR.resultsID, 
               TR.TestID, 
               TR.HeaderID,   
               TR.SampleID,  
               tp.StandardID,  
               tp.ParameterID,
               tp.ParameterName,
               TIMESTAMPDIFF(
                   DAY, 
                   tr.created_at,
                   CASE
                       WHEN TR.StatusID = 1 THEN NOW() 
                       ELSE IFNULL(tr.updated_at, NOW())
                   END
               ) AS days
        FROM test_results TR
        JOIN TestParameters tp 
            ON TR.ParameterID = tp.ParameterID 
            AND TR.StandardID = tp.StandardID
        JOIN TestStandards ts 
            ON TR.StandardID = ts.StandardID
        WHERE TR.StatusID < 4  order by days asc";
$result = $conn->query($sql);

$consolidated = [];
while ($row = $result->fetch_assoc()) {
    $consolidated[] = $row;
}

// ✅ Close connection only once at the end
$conn->close();

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
    "top_tests" => $top_tests,
    "tat_analysis" => $tat_analysis,
    "consolidated" => $consolidated,
    "pending_completed" => $pending_completed
]);
?>
