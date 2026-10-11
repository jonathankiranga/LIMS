<?php
// Call smartERPlims API for a single sales quotation (documenttype 54) with its lines.
header('Content-Type: application/json; charset=utf-8');

function getQuoteLog($message) {
    @error_log('[getquote.php] ' . $message);
}

try {
    $config = include('../include/config.php');
    $erpApiUrl = trim((string)($config['ERP_API_URL'] ?? ''));

    if ($erpApiUrl === '') {
        throw new RuntimeException('ERP_API_URL is empty in blockchain/include/config.php');
    }

    if (!isset($_GET['documentno']) && !isset($_POST['documentno'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'documentno parameter required']);
        exit;
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is not enabled.');
    }

    $documentno = trim((string)($_GET['documentno'] ?? $_POST['documentno'] ?? ''));
    $postData = json_encode([
        'action' => 'get_quote',
        'documentno' => $documentno
    ]);

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
        getQuoteLog('ERP API request failed; URL=' . $erpApiUrl . '; HTTP=' . $httpCode . '; cURL=' . $curlError . '; response=' . substr((string)$response, 0, 1000));
        http_response_code(502);
        $erpError = null;
        $decodedResponse = json_decode((string)$response, true);
        if (is_array($decodedResponse)) {
            $erpError = $decodedResponse['message'] ?? $decodedResponse['error'] ?? null;
            if (isset($decodedResponse['detail'])) {
                $erpError = trim((string)$erpError . ' ' . (string)$decodedResponse['detail']);
            }
        }

        echo json_encode([
            'success' => false,
            'message' => 'ERP quotation request failed.',
            'detail' => ($curlError !== ''
                ? $curlError
                : 'ERP API returned HTTP ' . $httpCode),
            'erp_error' => $erpError,
            'erp_response_preview' => is_array($decodedResponse)
                ? null
                : substr(trim(strip_tags((string)$response)), 0, 600)
        ]);
        exit;
    }

    echo $response;
} catch (Throwable $e) {
    getQuoteLog(get_class($e) . ': ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => 'ERP quotation request failed.', 'detail' => $e->getMessage()]);
}
?>