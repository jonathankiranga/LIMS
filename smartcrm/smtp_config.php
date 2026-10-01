<?php
/**
 * SMTP Configuration for SmartCRM
 * Used for sending emails and calendar invites via PHPMailer 5.2
 * 
 * Usage:
 *   include('smtp_config.php');
 *   sendSMTPEmail($to, $subject, $body, $from = '', $fromName = '');
 *   sendICSInvite($to, $subject, $description, $start, $end, $location = '');
 */

// SMTP Server Settings
$smtp_config = array(
    'host'       => 'mail.erplabworks.co.ke',
    'port'       => 587,
    'username'   => 'recovery@erplabworks.co.ke',
    'password'   => 'd0?f8m28N',
    'from_email' => 'recovery@erplabworks.co.ke',
    'from_name'  => 'SmartCRM',
    'secure'     => 'tls',
    'debug'      => true   // writes full SMTP log to logs/mail_debug.log
);

/**
 * Send email using SMTP configuration
 * 
 * @param string $to Email address to send to
 * @param string $subject Email subject
 * @param string $body Email body (HTML supported)
 * @param string $from From email address (optional, uses default)
 * @param string $fromName From name (optional)
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendSMTPEmail($to, $subject, $body, $from = '', $fromName = '') {
    global $smtp_config;
    
    if (empty($to) || empty($subject)) {
        return ['success' => false, 'error' => 'Missing recipient or subject'];
    }
    
    $from     = $from     ?: $smtp_config['from_email'];
    $fromName = $fromName ?: $smtp_config['from_name'];
    
    // Use PHPMailer from Mailer directory
    $mailerPath = __DIR__ . '/Mailer/class.phpmailer.php';
    if (!file_exists($mailerPath)) {
        return ['success' => false, 'error' => 'PHPMailer not found at ' . $mailerPath];
    }
    
    require_once $mailerPath;
    require_once __DIR__ . '/Mailer/class.smtp.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Debug logging
        if ($smtp_config['debug']) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                $logFile = __DIR__ . '/logs/mail_debug.log';
                if (!is_dir(dirname($logFile))) { mkdir(dirname($logFile), 0755, true); }
                file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . trim($str) . "\n", FILE_APPEND | LOCK_EX);
            };
        }
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = $smtp_config['host'];
        $mail->Port       = $smtp_config['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_config['username'];
        $mail->Password   = $smtp_config['password'];
        
        // Encryption - PHPMailer 5.2 uses SMTPSecure string
        $secure = strtolower($smtp_config['secure']);
        if ($secure === 'ssl') {
            $mail->SMTPSecure = 'ssl';
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = 'tls';
        }
        
        // REMOVED: SSL verification bypass (security risk)
        
        // Recipients
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return ['success' => true, 'error' => null];
        
    } catch (Exception $e) {
        $errDetail = $e->getMessage() . ' | PHPMailer: ' . $mail->ErrorInfo;
        error_log('SMTP Error: ' . $errDetail);
        return ['success' => false, 'error' => $errDetail];
    }
}

/**
 * Generate RFC 5545 compliant ICS calendar content
 */
function generateICS($summary, $description, $start, $end, $location = '', $uid = '', $organizerEmail = '', $organizerName = '') {
    $uid = $uid ?: md5(uniqid()) . '@smartcrm';
    $dtstamp = gmdate('Ymd\THis\Z');
    $dtstart = date('Ymd\THis', strtotime($start));
    $dtend = date('Ymd\THis', strtotime($end));
    
    // Escape text for ICS (RFC 5545)
    $escape = function($text) {
        $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);
        // Fold lines at 75 chars
        return wordwrap($text, 75, "\r\n ", true);
    };
    
    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//SmartCRM//Calendar//EN\r\n";
    $ics .= "CALSCALE:GREGORIAN\r\n";
    $ics .= "METHOD:REQUEST\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:" . $escape($uid) . "\r\n";
    $ics .= "DTSTAMP:" . $dtstamp . "\r\n";
    $ics .= "DTSTART:" . $dtstart . "\r\n";
    $ics .= "DTEND:" . $dtend . "\r\n";
    $ics .= "SUMMARY:" . $escape($summary) . "\r\n";
    $ics .= "DESCRIPTION:" . $escape($description) . "\r\n";
    if ($location) {
        $ics .= "LOCATION:" . $escape($location) . "\r\n";
    }
    if ($organizerEmail) {
        $ics .= "ORGANIZER;CN=" . $escape($organizerName) . ":mailto:" . $organizerEmail . "\r\n";
    }
    $ics .= "STATUS:CONFIRMED\r\n";
    $ics .= "SEQUENCE:0\r\n";
    $ics .= "TRANSP:OPAQUE\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";
    
    return $ics;
}

/**
 * Send ICS calendar invite
 * 
 * @param string $to Recipient email
 * @param string $subject Meeting subject
 * @param string $description Meeting description
 * @param string $start Start datetime (Y-m-d H:i:s)
 * @param string $end End datetime (Y-m-d H:i:s)
 * @param string $location Location (optional)
 * @param string $from From email (optional)
 * @param string $fromName From name (optional)
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendICSInvite($to, $subject, $description, $start, $end, $location = '', $from = '', $fromName = '') {
    global $smtp_config;
    
    if (empty($to) || empty($subject) || empty($start) || empty($end)) {
        return ['success' => false, 'error' => 'Missing required parameters'];
    }
    
    $from     = $from     ?: $smtp_config['from_email'];
    $fromName = $fromName ?: $smtp_config['from_name'];
    
    // Generate ICS content
    $icsContent = generateICS($subject, $description, $start, $end, $location, '', $from, $fromName);
    
    // Use PHPMailer
    $mailerPath = __DIR__ . '/Mailer/class.phpmailer.php';
    if (!file_exists($mailerPath)) {
        return ['success' => false, 'error' => 'PHPMailer not found'];
    }
    
    require_once $mailerPath;
    require_once __DIR__ . '/Mailer/class.smtp.php';
    
    $mail = new PHPMailer(true);
    
    try {
        if ($smtp_config['debug']) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                $logFile = __DIR__ . '/logs/mail_debug.log';
                if (!is_dir(dirname($logFile))) { mkdir(dirname($logFile), 0755, true); }
                file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . trim($str) . "\n", FILE_APPEND | LOCK_EX);
            };
        }
        
        $mail->isSMTP();
        $mail->Host       = $smtp_config['host'];
        $mail->Port       = $smtp_config['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_config['username'];
        $mail->Password   = $smtp_config['password'];
        
        $secure = strtolower($smtp_config['secure']);
        if ($secure === 'ssl') {
            $mail->SMTPSecure = 'ssl';
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = 'tls';
        }
        
        // REMOVED: SSL verification bypass
        
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($description);
        $mail->AltBody = strip_tags($description);
        
        // Attach ICS as calendar invitation
        $mail->addStringAttachment($icsContent, 'invite.ics', 'base64', 'text/calendar; method=REQUEST; charset=UTF-8');
        
        $mail->send();
        return ['success' => true, 'error' => null];
        
    } catch (Exception $e) {
        $errDetail = $e->getMessage() . ' | PHPMailer: ' . $mail->ErrorInfo;
        error_log('ICS SMTP Error: ' . $errDetail);
        return ['success' => false, 'error' => $errDetail];
    }
}

/**
 * Test SMTP connection
 */
function testSMTPConnection() {
    global $smtp_config;
    
    $mailerPath = __DIR__ . '/Mailer/class.phpmailer.php';
    if (!file_exists($mailerPath)) {
        return ['success' => false, 'message' => 'PHPMailer not found'];
    }
    
    require_once $mailerPath;
    require_once __DIR__ . '/Mailer/class.smtp.php';
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_config['host'];
        $mail->Port = $smtp_config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        
        $secure = strtolower($smtp_config['secure']);
        if ($secure === 'ssl') $mail->SMTPSecure = 'ssl';
        elseif ($secure === 'tls') $mail->SMTPSecure = 'tls';
        
        $mail->SMTPConnect();
        return ['success' => true, 'message' => 'Connection successful'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}