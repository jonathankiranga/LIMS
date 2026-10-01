<?php

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/certificate_email_support.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.',
    ]);
    exit;
}

$sampleID = isset($_POST['sampleID']) ? (int)$_POST['sampleID'] : 0;
$reportOption = isset($_POST['reportoption']) ? (int)$_POST['reportoption'] : 0;
$allowedOptions = [1, 2, 3];
$sentBy = $_SESSION['UserID'] ?? $_SESSION['User_name'] ?? $_SESSION['username'] ?? '';

if ($sampleID <= 0 || !in_array($reportOption, $allowedOptions, true)) {
    echo json_encode([
        'success' => false,
        'queued' => false,
        'message' => 'A valid sample and report option are required.',
        'mail_log' => [],
        'send_count' => 0,
        'recipient_email' => '',
        'status' => 'failure',
        'test_id' => $sampleID,
        'report_option' => $reportOption,
        'dispatch_mode' => 'live',
    ]);
    exit;
}

try {
    ensure_certificate_email_tracking_schema($conn);
    ensure_certificate_email_queue_schema($conn);

    $dispatch = resolve_certificate_email_dispatch_mode($conn);
    $context = get_certificate_email_context($conn, $sampleID);

    if (empty($context)) {
        throw new RuntimeException('No approved sample record was found for the selected report.');
    }

    if ($dispatch['effective_mode'] === 'queue') {
        $jobId = enqueue_certificate_email_job($conn, $sampleID, $reportOption, (string)$sentBy);
        $recentLogs = get_recent_certificate_email_logs($conn, $sampleID, 5, $reportOption);

        echo json_encode([
            'success' => true,
            'queued' => true,
            'message' => 'Email queued for background sending.',
            'mail_log' => [
                'Background worker is healthy; this email has been queued for processing.',
                'Queue job #' . $jobId . ' created for report option ' . $reportOption . '.',
            ],
            'send_count' => $sampleID > 0 ? get_certificate_email_send_count($conn, $sampleID) : 0,
            'recipient_email' => $context['email'] ?? '',
            'status' => 'queued',
            'test_id' => $sampleID,
            'report_option' => $reportOption,
            'document_no' => $context['DocumentNo'] ?? '',
            'recent_logs' => $recentLogs,
            'dispatch_mode' => $dispatch['effective_mode'],
            'configured_mode' => $dispatch['configured_mode'],
            'worker_healthy' => $dispatch['worker_healthy'],
            'worker_last_seen_at' => $dispatch['worker_last_seen_at'],
            'queue_job_id' => $jobId,
        ]);
        exit;
    }

    $response = process_certificate_email_delivery($conn, $sampleID, $reportOption, (string)$sentBy);
    $response['queued'] = false;
    $response['dispatch_mode'] = $dispatch['effective_mode'];
    $response['configured_mode'] = $dispatch['configured_mode'];
    $response['worker_healthy'] = $dispatch['worker_healthy'];
    $response['worker_last_seen_at'] = $dispatch['worker_last_seen_at'];

    if ($dispatch['configured_mode'] === 'auto' && !$dispatch['worker_healthy']) {
        array_unshift(
            $response['mail_log'],
            'Background worker heartbeat not detected recently; sending this email live.'
        );
    }

    echo json_encode($response);
} catch (Throwable $throwable) {
    echo json_encode([
        'success' => false,
        'queued' => false,
        'message' => $throwable->getMessage(),
        'mail_log' => [$throwable->getMessage()],
        'send_count' => $sampleID > 0 ? get_certificate_email_send_count($conn, $sampleID) : 0,
        'recipient_email' => '',
        'status' => 'failure',
        'test_id' => $sampleID,
        'report_option' => $reportOption,
        'dispatch_mode' => 'live',
    ]);
}
