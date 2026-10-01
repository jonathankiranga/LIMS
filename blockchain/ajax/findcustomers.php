<?php
// Find customer by itemcode - call smartERPlims API

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'] ?? 'http://localhost:90/smartERPlims/api/LimsSalesApi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['itemcode'])) {
    $itemcode = $_POST['itemcode'];
    
    $postData = json_encode([
        'action' => 'get_customer',
        'itemcode' => $itemcode
    ]);

    $ch = curl_init($erpApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        header('Content-Type: application/json');
        echo $response;
    } else {
        echo json_encode(['success' => false, 'message' => 'API error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}