<?php

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function certificate_email_table_exists(mysqli $conn, $tableName)
{
    $tableName = $conn->real_escape_string((string)$tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");

    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function certificate_email_column_exists(mysqli $conn, $tableName, $columnName)
{
    $tableName = $conn->real_escape_string((string)$tableName);
    $columnName = $conn->real_escape_string((string)$columnName);
    $result = $conn->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");

    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function certificate_email_get_config_value(mysqli $conn, $configName, $defaultValue = null)
{
    if (!certificate_email_table_exists($conn, 'config')) {
        return $defaultValue;
    }

    $stmt = $conn->prepare("SELECT confvalue FROM config WHERE confname = ? LIMIT 1");
    if (!$stmt) {
        return $defaultValue;
    }

    $configName = trim((string)$configName);
    $stmt->bind_param('s', $configName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? ($result->fetch_assoc() ?: null) : null;
    $stmt->close();

    if (!$row || !array_key_exists('confvalue', $row)) {
        return $defaultValue;
    }

    return $row['confvalue'];
}

function certificate_email_set_config_value(mysqli $conn, $configName, $configValue, $configType = 'text')
{
    if (!certificate_email_table_exists($conn, 'config')) {
        throw new RuntimeException('Config table is not available.');
    }

    $configName = trim((string)$configName);
    $configValue = (string)$configValue;
    $configType = trim((string)$configType);

    $selectStmt = $conn->prepare("SELECT confname FROM config WHERE confname = ? LIMIT 1");
    if (!$selectStmt) {
        throw new RuntimeException('Failed to prepare config lookup: ' . $conn->error);
    }

    $selectStmt->bind_param('s', $configName);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $selectStmt->close();

    if ($exists) {
        if (certificate_email_column_exists($conn, 'config', 'type')) {
            $updateStmt = $conn->prepare("UPDATE config SET confvalue = ?, type = ? WHERE confname = ?");
            if (!$updateStmt) {
                throw new RuntimeException('Failed to prepare config update: ' . $conn->error);
            }
            $updateStmt->bind_param('sss', $configValue, $configType, $configName);
        } else {
            $updateStmt = $conn->prepare("UPDATE config SET confvalue = ? WHERE confname = ?");
            if (!$updateStmt) {
                throw new RuntimeException('Failed to prepare config update: ' . $conn->error);
            }
            $updateStmt->bind_param('ss', $configValue, $configName);
        }
        $updateStmt->execute();
        $updateStmt->close();
        return;
    }

    if (certificate_email_column_exists($conn, 'config', 'type')) {
        $insertStmt = $conn->prepare("INSERT INTO config (confname, confvalue, type) VALUES (?, ?, ?)");
        if (!$insertStmt) {
            throw new RuntimeException('Failed to prepare config insert: ' . $conn->error);
        }
        $insertStmt->bind_param('sss', $configName, $configValue, $configType);
    } else {
        $insertStmt = $conn->prepare("INSERT INTO config (confname, confvalue) VALUES (?, ?)");
        if (!$insertStmt) {
            throw new RuntimeException('Failed to prepare config insert: ' . $conn->error);
        }
        $insertStmt->bind_param('ss', $configName, $configValue);
    }
    $insertStmt->execute();
    $insertStmt->close();
}

function ensure_certificate_email_runtime_config(mysqli $conn)
{
    if (!certificate_email_table_exists($conn, 'config')) {
        return;
    }

    if (certificate_email_get_config_value($conn, 'coa_email_delivery_mode', null) === null) {
        certificate_email_set_config_value($conn, 'coa_email_delivery_mode', 'auto');
    }
}

function get_certificate_email_delivery_mode(mysqli $conn)
{
    ensure_certificate_email_runtime_config($conn);

    $mode = strtolower(trim((string)certificate_email_get_config_value($conn, 'coa_email_delivery_mode', 'auto')));
    if (!in_array($mode, ['live', 'queue', 'auto'], true)) {
        return 'auto';
    }

    return $mode;
}

function touch_certificate_email_worker_heartbeat(mysqli $conn)
{
    if (!certificate_email_table_exists($conn, 'config')) {
        return;
    }

    certificate_email_set_config_value($conn, 'coa_email_worker_last_seen_at', date('Y-m-d H:i:s'), 'datetime');
}

function get_certificate_email_worker_health(mysqli $conn, $freshSeconds = 180)
{
    $lastSeen = trim((string)certificate_email_get_config_value($conn, 'coa_email_worker_last_seen_at', ''));
    if ($lastSeen === '') {
        return [
            'healthy' => false,
            'last_seen_at' => '',
        ];
    }

    $timestamp = strtotime($lastSeen);
    if ($timestamp === false) {
        return [
            'healthy' => false,
            'last_seen_at' => $lastSeen,
        ];
    }

    return [
        'healthy' => (time() - $timestamp) <= max(30, (int)$freshSeconds),
        'last_seen_at' => $lastSeen,
    ];
}

function resolve_certificate_email_dispatch_mode(mysqli $conn)
{
    $configuredMode = get_certificate_email_delivery_mode($conn);
    $worker = get_certificate_email_worker_health($conn);
    $effectiveMode = $configuredMode;

    if ($configuredMode === 'auto') {
        $effectiveMode = $worker['healthy'] ? 'queue' : 'live';
    }

    return [
        'configured_mode' => $configuredMode,
        'effective_mode' => $effectiveMode,
        'worker_healthy' => $worker['healthy'],
        'worker_last_seen_at' => $worker['last_seen_at'],
    ];
}

function ensure_certificate_email_tracking_schema(mysqli $conn)
{
    $columnDefinitions = [
        'coa_email_send_count' => "ALTER TABLE sample_tests ADD COLUMN coa_email_send_count INT NOT NULL DEFAULT 0",
        'coa_last_emailed_at' => "ALTER TABLE sample_tests ADD COLUMN coa_last_emailed_at DATETIME DEFAULT NULL",
        'coa_last_email_to' => "ALTER TABLE sample_tests ADD COLUMN coa_last_email_to VARCHAR(255) DEFAULT NULL",
        'coa_last_email_status' => "ALTER TABLE sample_tests ADD COLUMN coa_last_email_status VARCHAR(20) DEFAULT NULL",
        'coa_last_email_message' => "ALTER TABLE sample_tests ADD COLUMN coa_last_email_message TEXT DEFAULT NULL",
    ];

    foreach ($columnDefinitions as $columnName => $sql) {
        if (!certificate_email_column_exists($conn, 'sample_tests', $columnName) && !$conn->query($sql)) {
            throw new RuntimeException('Failed to update sample_tests email tracking columns: ' . $conn->error);
        }
    }

    $logTableSql = "
        CREATE TABLE IF NOT EXISTS certificate_email_logs (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            TestID INT NOT NULL,
            report_option TINYINT NOT NULL,
            recipient_email VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            status ENUM('success','failure') NOT NULL,
            message TEXT DEFAULT NULL,
            sent_by VARCHAR(100) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_certificate_email_logs_testid (TestID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($logTableSql)) {
        throw new RuntimeException('Failed to create certificate email log table: ' . $conn->error);
    }
}

function ensure_certificate_email_queue_schema(mysqli $conn)
{
    $queueTableSql = "
        CREATE TABLE IF NOT EXISTS certificate_email_queue (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            TestID INT NOT NULL,
            report_option TINYINT NOT NULL,
            status ENUM('pending','processing','success','failure') NOT NULL DEFAULT 'pending',
            requested_by VARCHAR(100) DEFAULT NULL,
            recipient_email VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            mail_log LONGTEXT DEFAULT NULL,
            queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            attempt_count INT NOT NULL DEFAULT 0,
            locked_by VARCHAR(100) DEFAULT NULL,
            KEY idx_certificate_email_queue_status (status),
            KEY idx_certificate_email_queue_testid (TestID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($queueTableSql)) {
        throw new RuntimeException('Failed to create certificate email queue table: ' . $conn->error);
    }
}

function enqueue_certificate_email_job(mysqli $conn, $testId, $reportOption, $requestedBy = '')
{
    ensure_certificate_email_queue_schema($conn);

    $testId = (int)$testId;
    $reportOption = (int)$reportOption;
    $requestedBy = trim((string)$requestedBy);

    $stmt = $conn->prepare("
        INSERT INTO certificate_email_queue (TestID, report_option, status, requested_by)
        VALUES (?, ?, 'pending', ?)
    ");
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare certificate email queue insert: ' . $conn->error);
    }

    $stmt->bind_param('iis', $testId, $reportOption, $requestedBy);
    $stmt->execute();
    $jobId = (int)$stmt->insert_id;
    $stmt->close();

    return $jobId;
}

function get_certificate_email_context(mysqli $conn, $testId)
{
    $sql = "
        SELECT
            st.TestID,
            st.SampleID,
            st.StandardID,
            COALESCE(st.coa_email_send_count, 0) AS coa_email_send_count,
            st.coa_last_emailed_at,
            st.coa_last_email_status,
            sh.HeaderID,
            sh.DocumentNo,
            sh.Date,
            sh.CustomerName,
            sh.CustomerID,
            dr.customer,
            dr.company,
            dr.email,
            ts.StandardName
        FROM sample_tests st
        INNER JOIN sample_header sh ON st.HeaderID = sh.HeaderID
        LEFT JOIN debtors dr ON sh.CustomerID = dr.itemcode
        LEFT JOIN teststandards ts ON st.StandardID = ts.StandardID
        WHERE st.TestID = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare certificate email context query: ' . $conn->error);
    }

    $stmt->bind_param('i', $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $context = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();

    return $context;
}

function get_certificate_email_template(mysqli $conn)
{
    $defaultTemplate = [
        'subject' => 'Certificate of Analysis for Sample {{batchno}}',
        'body' => 'Dear {{customer_name}},<br><br>'
            . 'Please find attached the Certificate of Analysis for sample {{batchno}}'
            . '{{sample_id_line}}{{sample_type_line}}.<br><br>'
            . 'Regards,<br>{{company_name}}',
    ];

    if (!certificate_email_table_exists($conn, 'email_templates')) {
        return $defaultTemplate;
    }

    $lookupKeys = ['coa_email', 'certificate_email', 'test_approved'];
    $stmt = $conn->prepare("SELECT subject, body FROM email_templates WHERE event_name = ? LIMIT 1");
    if (!$stmt) {
        return $defaultTemplate;
    }

    foreach ($lookupKeys as $lookupKey) {
        $stmt->bind_param('s', $lookupKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? ($result->fetch_assoc() ?: []) : [];
        if (!empty($row['subject']) && !empty($row['body'])) {
            $stmt->close();
            return $row;
        }
    }

    $stmt->close();

    return $defaultTemplate;
}

function render_certificate_email_text($text, array $context)
{
    $sampleIdLine = empty($context['SampleID']) ? '' : ' with internal sample ID ' . $context['SampleID'];
    $sampleTypeLine = empty($context['StandardName']) ? '' : ' for ' . $context['StandardName'];
    $companyName = trim((string)($context['company_name'] ?? 'Lab Team'));
    $customerName = !empty($context['customer'])
        ? (string)$context['customer']
        : (!empty($context['CustomerName']) ? (string)$context['CustomerName'] : 'Customer');

    $replacements = [
        '{{customer_name}}' => trim($customerName),
        '{{batchno}}' => trim((string)($context['DocumentNo'] ?? '')),
        '{{sample_id}}' => trim((string)($context['SampleID'] ?? '')),
        '{{sample_type}}' => trim((string)($context['StandardName'] ?? '')),
        '{{company_name}}' => $companyName === '' ? 'Lab Team' : $companyName,
        '{{sample_id_line}}' => htmlspecialchars($sampleIdLine, ENT_QUOTES, 'UTF-8'),
        '{{sample_type_line}}' => htmlspecialchars($sampleTypeLine, ENT_QUOTES, 'UTF-8'),
    ];

    return strtr((string)$text, $replacements);
}

function get_certificate_sender_details(mysqli $conn)
{
    $details = [
        'from_email' => '',
        'from_name' => '',
    ];

    if (certificate_email_table_exists($conn, 'company_master')) {
        $result = $conn->query("SELECT company_name, email, authorisation FROM company_master ORDER BY company_id DESC LIMIT 1");
        if ($result instanceof mysqli_result && ($row = ($result->fetch_assoc() ?: []))) {
            $companyEmail = trim((string)($row['email'] ?? ''));
            $companyName = trim((string)($row['company_name'] ?? ''));
            $authorisationUserId = (int)($row['authorisation'] ?? 0);

            if ($companyEmail !== '' && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                $details['from_email'] = $companyEmail;
            }
            if ($companyName !== '') {
                $details['from_name'] = $companyName;
            }

            if ($authorisationUserId > 0 && certificate_email_table_exists($conn, 'users')) {
                $userStmt = $conn->prepare("SELECT email, full_name FROM users WHERE user_id = ? LIMIT 1");
                if ($userStmt) {
                    $userStmt->bind_param('i', $authorisationUserId);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    $userRow = $userResult ? ($userResult->fetch_assoc() ?: []) : [];
                    $userStmt->close();

                    $userEmail = trim((string)($userRow['email'] ?? ''));
                    $userFullName = trim((string)($userRow['full_name'] ?? ''));

                    if ($userEmail !== '' && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                        $details['from_email'] = $userEmail;
                    }
                    if ($userFullName !== '') {
                        $details['from_name'] = $userFullName;
                    }
                }
            }
        }
    }

    if ($details['from_email'] === '' || !filter_var($details['from_email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('No valid sender email found in company_master.authorisation user or company_master.email.');
    }

    if ($details['from_name'] === '') {
        $details['from_name'] = $details['from_email'];
    }

    return $details;
}

function get_certificate_smtp_settings(mysqli $conn)
{
    $configPath = __DIR__ . '/../config.php';
    if (!is_file($configPath)) {
        return [];
    }

    $config = include $configPath;
    if (!is_array($config) || empty($config['smtp_host']) || empty($config['smtp_username'])) {
        return [];
    }

    return [
        'host' => trim((string)($config['smtp_host'] ?? '')),
        'port' => (int)($config['smtp_port'] ?? 25),
        'heloaddress' => '',
        'username' => trim((string)($config['smtp_username'] ?? '')),
        'password' => (string)($config['smtp_password'] ?? ''),
        'from_email' => trim((string)($config['from_email'] ?? '')),
        'from_name' => trim((string)($config['from_name'] ?? '')),
        'timeout' => (int)($config['smtp_timeout'] ?? 20),
        'auth' => array_key_exists('smtp_auth', $config) ? (bool)$config['smtp_auth'] : true,
        'secure' => strtolower(trim((string)($config['smtp_secure'] ?? ''))),
    ];
}

function configure_certificate_mailer(mysqli $conn, PHPMailer $mail, array &$logLines)
{
    $smtp = get_certificate_smtp_settings($conn);

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Timeout = 20;

    if (!empty($smtp)) {
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'] > 0 ? $smtp['port'] : 25;
        $mail->SMTPAuth = (bool)$smtp['auth'];
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $mail->Helo = $smtp['heloaddress'] !== '' ? $smtp['heloaddress'] : $smtp['host'];
        $mail->Timeout = $smtp['timeout'] > 0 ? $smtp['timeout'] : 20;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        if ($smtp['secure'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['secure'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (in_array($mail->Port, [465], true)) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (in_array($mail->Port, [587], true)) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $logLines[] = 'Mailer configured for SMTP delivery via ' . $smtp['host'] . ':' . $mail->Port . '.';
    } else {
        $mail->isMail();
        $logLines[] = 'Mailer configured for local PHP mail() delivery.';
    }

    $fromEmail = '';
    if (!empty($smtp['from_email']) && filter_var($smtp['from_email'], FILTER_VALIDATE_EMAIL)) {
        $fromEmail = $smtp['from_email'];
    } elseif (!empty($smtp['username']) && filter_var($smtp['username'], FILTER_VALIDATE_EMAIL)) {
        $fromEmail = $smtp['username'];
    }

    if ($fromEmail === '') {
        throw new RuntimeException('No valid SMTP from email found in config.php.');
    }

    $configuredName = trim((string)($smtp['from_name'] ?? ''));
    $fromName = $configuredName !== '' ? 'No Reply - ' . $configuredName : 'No Reply';

    $mail->setFrom($fromEmail, $fromName);
    $mail->clearReplyTos();
    $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
    $mail->addCustomHeader('X-Auto-Response-Suppress', 'All');

    return [
        'from_email' => $fromEmail,
        'from_name' => $fromName,
        'reply_to_email' => '',
        'reply_to_name' => '',
    ];
}

function send_certificate_email(mysqli $conn, array $context, $pdfContent, $filename, array &$logLines)
{
    $recipientEmail = trim((string)($context['email'] ?? ''));
    if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'status' => 'failure',
            'message' => 'The selected customer does not have a valid email address.',
            'recipient_email' => $recipientEmail,
            'subject' => '',
        ];
    }

    $template = get_certificate_email_template($conn);
    $subject = render_certificate_email_text((string)$template['subject'], $context);
    $body = render_certificate_email_text((string)$template['body'], $context);

    try {
        $mail = new PHPMailer(true);
        configure_certificate_mailer($conn, $mail, $logLines);
        $recipientName = !empty($context['customer'])
            ? (string)$context['customer']
            : (!empty($context['CustomerName']) ? (string)$context['CustomerName'] : 'Customer');
        $mail->addAddress($recipientEmail, trim($recipientName));
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body))));
        $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
        $mail->send();

        $logLines[] = 'Attachment prepared: ' . $filename . '.';
        $logLines[] = 'Email successfully sent to ' . $recipientEmail . '.';

        return [
            'success' => true,
            'status' => 'success',
            'message' => 'Email sent successfully.',
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
        ];
    } catch (PHPMailerException $exception) {
        $logLines[] = 'Mailer exception: ' . $exception->getMessage();

        return [
            'success' => false,
            'status' => 'failure',
            'message' => $exception->getMessage(),
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
        ];
    } catch (Throwable $throwable) {
        $logLines[] = 'Unexpected mail error: ' . $throwable->getMessage();

        return [
            'success' => false,
            'status' => 'failure',
            'message' => $throwable->getMessage(),
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
        ];
    }
}

function process_certificate_email_delivery(mysqli $conn, $sampleID, $reportOption, $sentBy = '')
{
    $sampleID = (int)$sampleID;
    $reportOption = (int)$reportOption;
    $allowedOptions = [1, 2, 3];
    $logLines = [];

    if ($sampleID <= 0 || !in_array($reportOption, $allowedOptions, true)) {
        return [
            'success' => false,
            'message' => 'A valid sample and report option are required.',
            'mail_log' => [],
            'send_count' => 0,
            'recipient_email' => '',
            'status' => 'failure',
            'test_id' => $sampleID,
            'report_option' => $reportOption,
            'document_no' => '',
            'recent_logs' => [],
            'subject' => '',
        ];
    }

    try {
        ensure_certificate_email_tracking_schema($conn);
        $context = get_certificate_email_context($conn, $sampleID);

        if (empty($context)) {
            throw new RuntimeException('No approved sample record was found for the selected report.');
        }

        $logLines[] = 'Preparing Certificate of Analysis PDF for batch ' . ($context['DocumentNo'] ?? 'N/A') . '.';
        $reportFile = __DIR__ . '/certificateofanalysis' . $reportOption . '.php';

        if (!is_file($reportFile)) {
            throw new RuntimeException('The selected report template could not be found.');
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        $_POST['sampleID'] = (string)$sampleID;
        $_POST['reportoption'] = (string)$reportOption;
        $_POST['include_watermark'] = '1';

        ob_start();
        include $reportFile;
        $pdfContent = ob_get_clean();

        if (!is_string($pdfContent) || $pdfContent === '') {
            throw new RuntimeException('The report generator returned an empty PDF response.');
        }

        if (strpos($pdfContent, '%PDF') !== 0) {
            throw new RuntimeException('The report generator did not return a valid PDF document.');
        }

        $pdfFilename = 'COA-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)($context['DocumentNo'] ?? $sampleID)) . '.pdf';
        $mailResult = send_certificate_email($conn, $context, $pdfContent, $pdfFilename, $logLines);
        $trackingMessage = $mailResult['success']
            ? 'COA email sent successfully.'
            : 'COA email failed: ' . $mailResult['message'];

        $sendCount = update_certificate_email_tracking(
            $conn,
            $sampleID,
            $reportOption,
            (string)($mailResult['recipient_email'] ?? ''),
            (string)($mailResult['subject'] ?? ''),
            (string)($mailResult['status'] ?? 'failure'),
            $trackingMessage,
            (string)$sentBy
        );

        $recentLogs = get_recent_certificate_email_logs($conn, $sampleID, 5, $reportOption);

        return [
            'success' => (bool)$mailResult['success'],
            'message' => $mailResult['success'] ? 'Email sent successfully.' : $mailResult['message'],
            'mail_log' => $logLines,
            'send_count' => $sendCount,
            'recipient_email' => $mailResult['recipient_email'] ?? '',
            'status' => $mailResult['status'] ?? 'failure',
            'test_id' => $sampleID,
            'report_option' => $reportOption,
            'document_no' => $context['DocumentNo'] ?? '',
            'recent_logs' => $recentLogs,
            'subject' => $mailResult['subject'] ?? '',
        ];
    } catch (Throwable $throwable) {
        $safeRecipient = '';
        try {
            $context = isset($context) && is_array($context) ? $context : [];
            $safeRecipient = (string)($context['email'] ?? '');
            if ($sampleID > 0) {
                update_certificate_email_tracking(
                    $conn,
                    $sampleID,
                    $reportOption > 0 ? $reportOption : 1,
                    $safeRecipient,
                    '',
                    'failure',
                    'COA email failed: ' . $throwable->getMessage(),
                    (string)$sentBy
                );
            }
        } catch (Throwable $innerThrowable) {
            $logLines[] = 'Tracking update failed: ' . $innerThrowable->getMessage();
        }

        $logLines[] = $throwable->getMessage();

        return [
            'success' => false,
            'message' => $throwable->getMessage(),
            'mail_log' => $logLines,
            'send_count' => $sampleID > 0 ? get_certificate_email_send_count($conn, $sampleID) : 0,
            'recipient_email' => $safeRecipient,
            'status' => 'failure',
            'test_id' => $sampleID,
            'report_option' => $reportOption,
            'document_no' => '',
            'recent_logs' => [],
            'subject' => '',
        ];
    }
}

function process_certificate_email_queue_batch(mysqli $conn, $limit = 5, $workerName = 'worker')
{
    ensure_certificate_email_queue_schema($conn);

    $limit = max(1, (int)$limit);
    $processedJobs = [];

    for ($i = 0; $i < $limit; $i++) {
        $job = null;
        $jobId = 0;

        $conn->begin_transaction();
        try {
            $selectSql = "
                SELECT id, TestID, report_option, requested_by
                FROM certificate_email_queue
                WHERE status = 'pending'
                ORDER BY id ASC
                LIMIT 1
                FOR UPDATE
            ";
            $result = $conn->query($selectSql);
            $job = $result instanceof mysqli_result ? ($result->fetch_assoc() ?: null) : null;

            if (!$job) {
                $conn->commit();
                break;
            }

            $jobId = (int)$job['id'];
            $status = 'processing';
            $workerName = trim((string)$workerName);
            $updateStmt = $conn->prepare("
                UPDATE certificate_email_queue
                SET status = ?, started_at = NOW(), locked_by = ?, attempt_count = attempt_count + 1
                WHERE id = ?
            ");
            if (!$updateStmt) {
                throw new RuntimeException('Failed to prepare queue job update: ' . $conn->error);
            }
            $updateStmt->bind_param('ssi', $status, $workerName, $jobId);
            $updateStmt->execute();
            $updateStmt->close();
            $conn->commit();
        } catch (Throwable $throwable) {
            $conn->rollback();
            throw $throwable;
        }

        $deliveryResponse = process_certificate_email_delivery(
            $conn,
            (int)$job['TestID'],
            (int)$job['report_option'],
            (string)($job['requested_by'] ?? '')
        );

        $finalStatus = !empty($deliveryResponse['success']) ? 'success' : 'failure';
        $recipientEmail = (string)($deliveryResponse['recipient_email'] ?? '');
        $subject = (string)($deliveryResponse['subject'] ?? '');
        $message = (string)($deliveryResponse['message'] ?? '');
        $mailLog = json_encode($deliveryResponse['mail_log'] ?? [], JSON_UNESCAPED_UNICODE);
        $lockedBy = trim((string)$workerName);

        $finalStmt = $conn->prepare("
            UPDATE certificate_email_queue
            SET status = ?, recipient_email = ?, subject = ?, message = ?, mail_log = ?, completed_at = NOW(), locked_by = ?
            WHERE id = ?
        ");
        if (!$finalStmt) {
            throw new RuntimeException('Failed to prepare queue completion update: ' . $conn->error);
        }
        $finalStmt->bind_param('ssssssi', $finalStatus, $recipientEmail, $subject, $message, $mailLog, $lockedBy, $jobId);
        $finalStmt->execute();
        $finalStmt->close();

        $processedJobs[] = [
            'job_id' => $jobId,
            'test_id' => (int)$job['TestID'],
            'report_option' => (int)$job['report_option'],
            'status' => $finalStatus,
            'message' => $message,
        ];
    }

    return $processedJobs;
}

function update_certificate_email_tracking(mysqli $conn, $testId, $reportOption, $recipientEmail, $subject, $status, $message, $sentBy = null)
{
    ensure_certificate_email_tracking_schema($conn);

    $statusValue = $status === 'success' ? 'success' : 'failure';
    $updateSql = "
        UPDATE sample_tests
        SET
            coa_last_emailed_at = NOW(),
            coa_last_email_to = ?,
            coa_last_email_status = ?,
            coa_last_email_message = ?,
            coa_email_send_count = coa_email_send_count + ?
        WHERE TestID = ?
    ";

    $increment = $statusValue === 'success' ? 1 : 0;
    $stmt = $conn->prepare($updateSql);
    if (!$stmt) {
        throw new RuntimeException('Failed to update certificate email tracking data: ' . $conn->error);
    }

    $stmt->bind_param('sssii', $recipientEmail, $statusValue, $message, $increment, $testId);
    $stmt->execute();
    $stmt->close();

    $insertSql = "
        INSERT INTO certificate_email_logs
            (TestID, report_option, recipient_email, subject, status, message, sent_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    $insert = $conn->prepare($insertSql);
    if (!$insert) {
        throw new RuntimeException('Failed to insert certificate email log: ' . $conn->error);
    }

    $sentBy = trim((string)$sentBy);
    $insert->bind_param('iisssss', $testId, $reportOption, $recipientEmail, $subject, $statusValue, $message, $sentBy);
    $insert->execute();
    $insert->close();

    return get_certificate_email_send_count($conn, $testId);
}

function get_certificate_email_send_count(mysqli $conn, $testId)
{
    if (!certificate_email_column_exists($conn, 'sample_tests', 'coa_email_send_count')) {
        return 0;
    }

    $stmt = $conn->prepare("SELECT COALESCE(coa_email_send_count, 0) AS send_count FROM sample_tests WHERE TestID = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();

    return (int)($row['send_count'] ?? 0);
}

function get_recent_certificate_email_logs(mysqli $conn, $testId, $limit = 5, $reportOption = null)
{
    if (!certificate_email_table_exists($conn, 'certificate_email_logs')) {
        return [];
    }

    $limit = max(1, (int)$limit);
    $reportOption = $reportOption !== null ? (int)$reportOption : null;

    if ($reportOption !== null && $reportOption > 0) {
        $sql = "
            SELECT status, recipient_email, subject, message, sent_by, created_at, report_option
            FROM certificate_email_logs
            WHERE TestID = ? AND report_option = ?
            ORDER BY id DESC
            LIMIT {$limit}
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $testId, $reportOption);
    } else {
        $sql = "
            SELECT status, recipient_email, subject, message, sent_by, created_at, report_option
            FROM certificate_email_logs
            WHERE TestID = ?
            ORDER BY id DESC
            LIMIT {$limit}
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $testId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $logs[] = $row;
    }
    $stmt->close();

    return $logs;
}
