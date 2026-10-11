<?php
// Search ERP sales quotations and exclude quotations already used for sample registration.

header('Content-Type: application/json; charset=utf-8');

// Keep endpoint failures as JSON so the caller can report the real cause instead
// of receiving an empty response that fails while parsing JSON.
function searchQuotesLog($message) {
    @error_log('[searchquotes.php] ' . $message);
}

try {
$config = include '../include/config.php';
$erpApiUrl = $config['ERP_API_URL'];

if (!isset($_GET['query']) && !isset($_POST['query'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Query parameter required']);
    exit;
}

$query = trim((string)($_GET['query'] ?? $_POST['query'] ?? ''));

$postData = json_encode([
    'action' => 'search_quotes',
    'query' => $query
]);

if (!function_exists('curl_init')) {
    throw new RuntimeException('PHP cURL extension is not enabled.');
}

$ch = curl_init($erpApiUrl);
if ($ch === false) {
    throw new RuntimeException('Could not initialize the ERP API request.');
}
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    searchQuotesLog('ERP API request failed; HTTP=' . $httpCode . '; cURL=' . $curlError . '; response=' . substr((string)$response, 0, 1000));
    http_response_code(502);
    echo json_encode([
        'message' => 'ERP quotation search failed.',
        'detail' => ($curlError !== '' ? $curlError : ('ERP API returned HTTP ' . $httpCode))
    ]);
    exit;
}

$result = json_decode($response, true);
if (!is_array($result)) {
    http_response_code(502);
    echo json_encode(['message' => 'Invalid response from ERP API']);
    exit;
}

$quotes = $result['quotes'] ?? [];
if (!is_array($quotes)) {
    $quotes = [];
}

// The accounting API now filters quotations that already have a sales order.
// Return its available quotation list directly; no local LIMS query is needed.
echo json_encode($quotes);

} catch (Throwable $e) {
    searchQuotesLog(get_class($e) . ': ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['message' => 'Quotation search failed.', 'detail' => $e->getMessage()]);
}
?>