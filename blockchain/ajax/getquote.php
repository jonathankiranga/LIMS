<?php
// Call smartERPlims API for a single sales quotation (documenttype 54) with its lines

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'];

if (isset($_GET['documentno']) or isset($_POST['documentno'])) {
    $documentno = $_GET['documentno'] ?? $_POST['documentno'];

    $postData = json_encode([
        'action' => 'get_quote',
        'documentno' => $documentno
    ]);

    $ch = curl_init($erpApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    header('Content-Type: application/json');
    if ($httpCode === 200 && $response) {
        echo $response;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to connect to ERP API']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'documentno parameter required']);
}

?>