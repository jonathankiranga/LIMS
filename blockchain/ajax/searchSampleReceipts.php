<?php
require_once __DIR__ . '/../db_connection.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') {
    echo json_encode(['success' => false, 'message' => 'Enter a Batch Ref or Sample ID.']);
    exit;
}

$like = '%' . $q . '%';
$sql = "
    SELECT sh.HeaderID, sh.DocumentNo, sh.Date,
           COUNT(DISTINCT st.TestID) AS SampleCount
    FROM sample_header sh
    LEFT JOIN sample_tests st ON st.HeaderID = sh.HeaderID
    WHERE sh.DocumentNo LIKE ? OR st.SampleID LIKE ?
    GROUP BY sh.HeaderID, sh.DocumentNo, sh.Date
    ORDER BY sh.HeaderID DESC
    LIMIT 30
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to search registrations.']);
    exit;
}
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$result = $stmt->get_result();
$registrations = [];
while ($result && ($row = $result->fetch_assoc())) {
    $registrations[] = [
        'header_id' => (int)$row['HeaderID'],
        'document_no' => (string)$row['DocumentNo'],
        'date' => (string)$row['Date'],
        'sample_count' => (int)$row['SampleCount']
    ];
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'registrations' => $registrations]);
