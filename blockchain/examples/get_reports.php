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
if ($result === false) {
    error_log("Top Tests SQL Error: " . $conn->error);
    $top_tests = [];
} else {
    while ($row = $result->fetch_assoc()) {
        $top_tests[] = $row;
    }
}

// Fetch Turnaround Time (TAT) Analysis
$sql = "SELECT tp.ParameterName,
               AVG(TIMESTAMPDIFF(
                   DAY, 
                   tr.created_at,
                   CASE
                       WHEN tr.StatusID = 1 THEN NOW() 
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
if ($result === false) {
    error_log("TAT Analysis SQL Error: " . $conn->error);
    $tat_analysis = [];
} else {
    while ($row = $result->fetch_assoc()) {
        $tat_analysis[] = $row;
    }
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
if ($result === false) {
    error_log("Pending/Completed SQL Error: " . $conn->error);
    $pending_completed = [];
} else {
    while ($row = $result->fetch_assoc()) {
        $status_id = (int) $row['StatusID'];
        if (isset($statusmaping[$status_id])) {
            $txt = $statusmaping[$status_id];
            $pending_completed[$txt] = $row['count'];
        }
    }
}

// Fetch Consolidated Report
$sql = "SELECT tr.resultsID,
               tr.TestID,
               tr.HeaderID,
               tr.SampleID,
               tp.StandardID,
               tp.ParameterID,
               tp.ParameterName,
               TIMESTAMPDIFF(
                   DAY,
                   tr.created_at,
                   CASE
                       WHEN tr.StatusID = 1 THEN NOW()
                       ELSE IFNULL(tr.updated_at, NOW())
                   END
               ) AS days
        FROM test_results tr
        JOIN testparameters tp
            ON tr.ParameterID = tp.ParameterID
            AND tr.StandardID = tp.StandardID
        JOIN teststandards ts
            ON tr.StandardID = ts.StandardID
        WHERE tr.StatusID < 4
        ORDER BY days ASC";
$result = $conn->query($sql);

$consolidated = [];
if ($result === false) {
    error_log("Consolidated Report SQL Error: " . $conn->error);
} else {
    while ($row = $result->fetch_assoc()) {
        $consolidated[] = $row;
    }
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
