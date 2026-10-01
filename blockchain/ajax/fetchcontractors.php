<?php
// Fetch contractors/suppliers from smartERPlims API (single source of truth)

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'] ;

$records_per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, $page);
$offset = ($page - 1) * $records_per_page;

// Get suppliers from API (creditors table in ERP)
$postData = json_encode([
    'action' => 'search_suppliers',
    'query' => $searchTerm
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

$contractors = [];
$total_records = 0;

if ($httpCode === 200 && $response) {
    $result = json_decode($response, true);
    if (($result['success'] ?? false) && isset($result['suppliers'])) {
        $contractors = $result['suppliers'];
        $total_records = count($contractors);
    }
}

$total_pages = max(1, (int)ceil($total_records / $records_per_page));

// Output in the same format as before
header('Content-Type: application/json');
echo json_encode([
    'contractors' => $contractors,
    'total_records' => $total_records,
    'total_pages' => $total_pages,
    'current_page' => $page
]);