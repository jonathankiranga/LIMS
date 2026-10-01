<?php
require '../db_connection.php';

header('Content-Type: application/json');

$ParameterID = $_POST['ParameterID'] ?? null;
$ResultType = $_POST['ResultType'] ?? '';

if (!$ParameterID) {
    echo json_encode(['success' => false, 'message' => 'ParameterID required']);
    exit;
}

$stmt = $conn->prepare("UPDATE baseparameters SET ResultType = ? WHERE ParameterID = ?");
$stmt->bind_param("si", $ResultType, $ParameterID);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();