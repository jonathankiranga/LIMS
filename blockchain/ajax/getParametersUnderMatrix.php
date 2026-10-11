<?php
require_once '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$stdId = isset($_GET['stdId']) ? (int)$_GET['stdId'] : 0;
$baseIds = [];

if (isset($_GET['baseIds']) && trim((string)$_GET['baseIds']) !== '') {
    foreach (explode(',', (string)$_GET['baseIds']) as $id) {
        $id = (int)trim($id);
        if ($id > 0) {
            $baseIds[] = $id;
        }
    }
    $baseIds = array_values(array_unique($baseIds));
}

if ($stdId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid stdId is required.']);
    exit;
}

/*
 * Parameter mappings come from testparameters and teststandards only.
 * Keep the join on StandardID and never use OR to pull BaseIDs from other
 * standards; that caused duplicate-looking rows across many standards.
 */
$sql = "
    SELECT
        s.StandardID,
        s.StandardName,
        s.StandardCode,
        p.ParameterID,
        p.BaseID,
        p.ParameterName,
        p.Method
    FROM teststandards s
    INNER JOIN testparameters p
        ON p.StandardID = s.StandardID
    WHERE s.StandardID = ?
";

if (!empty($baseIds)) {
    $placeholders = implode(',', array_fill(0, count($baseIds), '?'));
    $sql .= " AND p.BaseID IN ($placeholders)";
}

$sql .= " ORDER BY p.ParameterName, p.ParameterID";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$types = 'i' . str_repeat('i', count($baseIds));
$params = array_merge([$stdId], $baseIds);
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query execution failed: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>