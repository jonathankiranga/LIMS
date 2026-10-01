<?php
require '../db_connection.php'; // Ensure this connects to your PDO instance and sets $pdo

$response = ['success' => false, 'message' => 'Invalid request'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['StandardID'])) {
    $standardID = intval($_POST['StandardID']);
    $stmt = $conn->prepare("DELETE FROM TestStandards WHERE StandardID = ?");
    $stmt->bind_param('i', $standardID);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Test standard deleted successfully.';
    } else {
        $response['message'] = 'Failed to delete test standard.';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
