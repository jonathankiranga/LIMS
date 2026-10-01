<?php
// Sync LIMS test standards, their test parameters, and the standard↔parameter
// links into the remote ERP (smartERPlims/api/LimsSalesApi.php).
//
// Self-contained: resolves its includes relative to this file, so it runs from
// any working directory (CLI: `php ajax/syncTeststandardsToERP.php`, or over HTTP).

require_once dirname(__DIR__) . '/db_connection.php';

header('Content-Type: application/json');

if (empty($config['ERP_API_URL'])) {
    echo json_encode(['success' => false, 'message' => 'ERP_API_URL is not configured in include/config.php']);
    exit;
}

// ── Fetch all teststandards ──
$standards = [];
$stmt = $conn->prepare("SELECT StandardID, StandardCode, StandardName, COALESCE(Description,'') AS Description FROM teststandards ORDER BY StandardID");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare standards query: ' . $conn->error]);
    exit;
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $name = $row['StandardName'];
    if (!mb_check_encoding($name, 'UTF-8')) {
        $name = mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1');
    }
    $desc = $row['Description'];
    if (!mb_check_encoding($desc, 'UTF-8')) {
        $desc = mb_convert_encoding($desc, 'UTF-8', 'ISO-8859-1');
    }
    $standards[] = [
        'StandardID'   => $row['StandardID'],
        'StandardCode' => $row['StandardCode'],
        'StandardName' => $name,
        'Description'  => $desc,
    ];
}
$stmt->close();

// ── Fetch the parameters referenced by the links (BaseID -> baseparameters) ──
$parameters = [];
$stmt = $conn->prepare("SELECT DISTINCT tp.BaseID AS ParameterID, bp.ParameterName,
                               COALESCE(bp.UnitOfMeasure,'PCS') AS UnitOfMeasure,
                               COALESCE(bp.Category,'LAB') AS Category
                        FROM testparameters tp
                        JOIN baseparameters bp ON bp.ParameterID = tp.BaseID
                        WHERE tp.BaseID IS NOT NULL
                        ORDER BY tp.BaseID");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare parameters query: ' . $conn->error]);
    exit;
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pname = $row['ParameterName'];
    if (!mb_check_encoding($pname, 'UTF-8')) {
        $pname = mb_convert_encoding($pname, 'UTF-8', 'ISO-8859-1');
    }
    $parameters[] = [
        'ParameterID'    => $row['ParameterID'],
        'ParameterName'  => $pname,
        'UnitOfMeasure'  => trim($row['UnitOfMeasure']),
        'Category'       => trim($row['Category']),
    ];
}
$stmt->close();

// ── Fetch all testparameters links (BaseID + StandardID) ──
$links = [];
$stmt = $conn->prepare("SELECT tp.BaseID, tp.StandardID, COALESCE(ts.StandardName,'') AS StandardName FROM testparameters tp JOIN teststandards ts ON tp.StandardID = ts.StandardID WHERE tp.BaseID IS NOT NULL ORDER BY tp.BaseID");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare links query: ' . $conn->error]);
    exit;
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $name = $row['StandardName'];
    if (!mb_check_encoding($name, 'UTF-8')) {
        $name = mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1');
    }
    $links[] = [
        'BaseID'       => $row['BaseID'],
        'StandardID'   => $row['StandardID'],
        'StandardName' => $name,
    ];
}
$stmt->close();

$erpApiUrl = $config['ERP_API_URL'];

$postData = json_encode([
    'action'     => 'sync_teststandard',
    'standards'  => $standards,
    'parameters' => $parameters,
    'links'      => $links,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($postData === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to encode payload: ' . json_last_error_msg()]);
    exit;
}

$ch = curl_init($erpApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpCode == 200) {
    $respData = json_decode($response, true);
    if (isset($respData['success']) && $respData['success']) {
        $msg = 'Synced '
            . ($respData['standards_synced'] ?? 0) . ' standards, '
            . ($respData['parameters_synced'] ?? 0) . ' parameters and '
            . ($respData['links_synced'] ?? 0) . ' links to ERP.';
        $payload = ['success' => true, 'message' => $msg];
        $errors = $respData['errors'] ?? [];
        if (!empty($errors)) {
            $payload['warnings'] = $errors;
        }
        if (isset($respData['skipped'])) {
            $payload['skipped'] = $respData['skipped'];
        }
        echo json_encode($payload);
    } else {
        echo json_encode(['success' => false, 'message' => $respData['message'] ?? 'Unknown ERP error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => "ERP returned HTTP $httpCode: " . substr($response, 0, 500)]);
}