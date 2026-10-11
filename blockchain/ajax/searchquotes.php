<?php
// Search ERP sales quotations and exclude quotations already used for sample registration.

header('Content-Type: application/json; charset=utf-8');

// Keep endpoint failures as JSON so the caller can report the real cause instead
// of receiving an empty response that fails while parsing JSON.
function searchQuotesLog($message) {
    @error_log('[searchquotes.php] ' . $message);
}

try {
require_once __DIR__ . '/../db_connection.php';

$config = include __DIR__ . '/../include/config.php';
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

// One quotation represents one complete sample registration.
// Exclude any quotation number already present in sample_header.QuoteNo.
$registeredQuotes = [];
$sql = "SELECT DISTINCT TRIM(QuoteNo) AS QuoteNo
        FROM sample_header
        WHERE QuoteNo IS NOT NULL AND TRIM(QuoteNo) <> ''";
$dbResult = $conn->query($sql);

if ($dbResult === false) {
    $dbError = $conn->error;
    searchQuotesLog('Could not read registered quotation numbers: ' . $dbError);
    http_response_code(500);
    echo json_encode(['message' => 'Unable to check existing sample registrations.', 'detail' => $dbError]);
    exit;
}

while ($row = $dbResult->fetch_assoc()) {
    $quoteNo = trim((string)($row['QuoteNo'] ?? ''));
    if ($quoteNo !== '') {
        $registeredQuotes[strtoupper($quoteNo)] = true;
    }
}
$dbResult->free();

$availableQuotes = [];
foreach ($quotes as $quote) {
    if (!is_array($quote)) {
        continue;
    }

    $documentNo = trim((string)($quote['documentno'] ?? ''));
    if ($documentNo === '') {
        // Keep records without a document number out of the selectable results.
        continue;
    }

    if (!isset($registeredQuotes[strtoupper($documentNo)])) {
        $availableQuotes[] = $quote;
    }
}

echo json_encode($availableQuotes);

} catch (Throwable $e) {
    searchQuotesLog(get_class($e) . ': ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['message' => 'Quotation search failed.', 'detail' => $e->getMessage()]);
}
?>