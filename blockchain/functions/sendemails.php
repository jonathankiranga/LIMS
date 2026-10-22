<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Unified email sending function
 * Uses port-based encryption detection (consistent with register_user.php)
 * NO SSL verification bypass (security)
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $content Email body (HTML)
 * @param string $cc CC email (optional)
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendEmail(string $to, string $subject, string $content, string $cc = ''): array {
    $config = include __DIR__ . '/../config.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = $config['smtp_auth'];
        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];
        
        // Port-based encryption (THE FIX - consistent logic)
        // Port 465 = SSL (SMTPS), Port 587 = TLS (STARTTLS)
        $mail->Port       = $config['smtp_port'];
        $mail->SMTPSecure = ((int)$config['smtp_port'] === 465)
            ? PHPMailer::ENCRYPTION_SMTPS      // SSL on port 465
            : PHPMailer::ENCRYPTION_STARTTLS;  // TLS on port 587

        // Without an explicit timeout PHPMailer waits 300s. When the SMTP host
        // silently drops packets (firewalled port 587) that stalls a cron run
        // for five minutes per message instead of failing fast.
        $mail->Timeout = 20;

        // REMOVED: SMTPOptions that disable SSL verification (was causing silent failures)
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        if ($cc) $mail->addCC($cc);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $content;
        $mail->AltBody = strip_tags($content);
        
        $mail->send();
        
        return ['success' => true, 'error' => null];
        
    } catch (Exception $e) {
        $error = $e->getMessage() . ' | PHPMailer: ' . $mail->ErrorInfo;
        error_log("Email send failed: $error");
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Backward-compatible POST endpoint for existing callers.
 */
if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === 'sendemails.php') {
    header('Content-Type: application/json; charset=utf-8');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST method required.']);
        exit;
    }

    $sendcc  = trim((string)($_POST['sendcc'] ?? ''));
    $sendto  = trim((string)($_POST['sendto'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $content = (string)($_POST['content'] ?? '');

    if ($sendto === '' || !filter_var($sendto, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'A valid recipient email address is required.']);
        exit;
    }

    if ($sendcc !== '' && !filter_var($sendcc, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'The CC email address is invalid.']);
        exit;
    }

    if ($subject === '' || trim(strip_tags($content)) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Email subject and message are required.']);
        exit;
    }

    if (strlen($subject) > 255 || strlen($content) > 1000000) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Email subject or message exceeds the allowed length.']);
        exit;
    }

    $result = sendEmail($sendto, $subject, $content, $sendcc);
    if (!$result['success']) {
        http_response_code(500);
    }
    echo json_encode($result);
}
