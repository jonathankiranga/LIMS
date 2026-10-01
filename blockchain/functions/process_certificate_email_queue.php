<?php

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/certificate_email_support.php';

$isCli = PHP_SAPI === 'cli';
$workerName = $isCli ? 'cli-worker' : 'web-worker';
$limit = 5;

if ($isCli && isset($argv[1]) && is_numeric($argv[1])) {
    $limit = (int)$argv[1];
} elseif (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
    $limit = (int)$_GET['limit'];
}

try {
    ensure_certificate_email_tracking_schema($conn);
    ensure_certificate_email_queue_schema($conn);
    touch_certificate_email_worker_heartbeat($conn);

    $processedJobs = process_certificate_email_queue_batch($conn, $limit, $workerName);

    $response = [
        'success' => true,
        'message' => 'Queue worker completed.',
        'processed_count' => count($processedJobs),
        'processed_jobs' => $processedJobs,
        'worker_name' => $workerName,
        'heartbeat_at' => date('Y-m-d H:i:s'),
    ];
} catch (Throwable $throwable) {
    $response = [
        'success' => false,
        'message' => $throwable->getMessage(),
        'processed_count' => 0,
        'processed_jobs' => [],
        'worker_name' => $workerName,
    ];
}

if (!$isCli) {
    header('Content-Type: application/json');
}

echo json_encode($response);
