<?php
require_once '../db_connection.php';

header('Content-Type: application/json');

$erpApiUrl = $config['ERP_API_URL'] ;
$action = 'create_stock_from_baseparameter';

$stmt = $conn->prepare("SELECT ParameterID, ParameterName FROM baseparameters ORDER BY ParameterID");
$stmt->execute();
$result = $stmt->get_result();

$parameters = [];
while ($row = $result->fetch_assoc()) {
    $name = $row['ParameterName'];
   // If it's not valid UTF-8, assume it's Latin-1/Windows-1252 and convert it
    if (!mb_check_encoding($name, 'UTF-8')) {
        $name = mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1');
    }
    $parameters[] = [
        'id' => $row['ParameterID'],
        'name' =>$name
    ];
}

$stmt->close();

$successCount = 0;
$failedCount = 0;
$errors = [];

foreach ($parameters as $param) {
     
$postData = json_encode([
    'action'        => $action,
    'ParameterID'   => $param['id'],
    'ParameterName' => $param['name'],
], JSON_UNESCAPED_UNICODE);
     
if (in_array($param['id'], [111, 144, 169])) {
        error_log("DEBUG payload for {$param['id']}: $postData");
    }
    
    $ch = curl_init($erpApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $failedCount++;
        $errors[] = "Param {$param['id']}: cURL error - " . $curlError;
        continue;
    }

    if ($httpCode == 200) {
        $respData = json_decode($response, true);
        if (isset($respData['success']) && $respData['success']) {
            $successCount++;
        } else {
            $failedCount++;
            $errors[] = "Param {$param['id']}: " . ($respData['message'] ?? 'Unknown error');
        }
    } else {
        $failedCount++;
        $errors[] = "Param {$param['id']}: HTTP $httpCode - Response: " . substr($response, 0, 200);
    }
}

echo json_encode([
    'success' => $failedCount === 0,
    'count' => $successCount,
    'failed' => $failedCount,
    'message' => "Synced $successCount parameters. Failed: $failedCount.",
    'errors' => array_slice($errors, 0, 10)
]);