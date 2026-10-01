<?php
// Database connection
require '../db_connection.php'; 

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT ParameterID, ParameterName FROM ParameterMatrix WHERE ParameterID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
header('Content-Type: application/json');
echo json_encode($result->fetch_assoc() ?: []);



?>


