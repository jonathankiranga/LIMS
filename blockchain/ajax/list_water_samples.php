<?php
require '../db_connection.php';

header('Content-Type: application/json');

// Water samples (standard name contains 'water') that have at least one
// quantitative result linked to an allocated neutrality ion or TDS element.
$sql = "
    SELECT DISTINCT st.SampleID, sh.DocumentNo, sh.CustomerName, sh.Date
    FROM test_results tr
    JOIN Sample_Tests st ON tr.TestID = st.TestID
    JOIN Sample_Header sh ON tr.HeaderID = sh.HeaderID
    JOIN teststandards ts ON tr.StandardID = ts.StandardID
    JOIN testparameters tp ON tp.ParameterID = tr.ParameterID AND tp.StandardID = tr.StandardID
    JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
    WHERE ts.StandardName LIKE '%water%'
      AND (bp.NeutralityID IS NOT NULL OR bp.TdsID IS NOT NULL)
      AND tr.MRL_Result IS NOT NULL AND tr.MRL_Result <> ''
    ORDER BY sh.Date DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$samples = [];
while ($row = $result->fetch_assoc()) {
    $samples[] = [
        'SampleID' => $row['SampleID'],
        'DocumentNo' => $row['DocumentNo'],
        'CustomerName' => $row['CustomerName'],
        'Date' => $row['Date']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'samples' => $samples]);