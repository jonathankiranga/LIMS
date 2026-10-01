<?php
// Call smartERPlims API for sales quotations (documenttype 54)

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'];

if (isset($_GET['query']) or isset($_POST['query'])) {
    $query = $_GET['query'] ?? $_POST['query'];

    $postData = json_encode([
        'action' => 'search_quotes',
        'query' => $query
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

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $quotes = $result['quotes'] ?? [];
        header('Content-Type: application/json');
        echo json_encode($quotes);
    } else {
        echo json_encode(['message' => 'Failed to connect to ERP API']);
    }
} else {
    echo json_encode(['message' => 'Query parameter required']);
}

?>