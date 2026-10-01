<?php
// Call smartERPlims API for customers (single source of truth)

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'] ;

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    
    $postData = json_encode([
        'action' => 'search_customers',
        'query' => $query
    ]);
    
    $ch = curl_init($erpApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        // Return just the customers array
        $customers = $result['customers'] ?? [];
        header('Content-Type: application/json');
        echo json_encode($customers);
    } else {
        echo json_encode(['message' => 'Failed to connect to ERP API']);
    }
} else {
    echo json_encode(['message' => 'Query parameter required']);
}

?>
