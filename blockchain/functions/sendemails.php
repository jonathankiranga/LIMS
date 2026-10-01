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
 * Backward compatibility for existing POST calls to this file
 */
if (basename($_SERVER['SCRIPT_FILENAME']) === 'sendemails.php') {
    $sendcc  = $_POST['sendcc'] ?? '';
    $sendto  = $_POST['sendto'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['content'] ?? '';
    
    $result = sendEmail($sendto, $subject, $content, $sendcc);
    header('Content-Type: application/json');
    echo json_encode($result);
}