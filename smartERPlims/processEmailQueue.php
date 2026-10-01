<?php
/**
 * Email Queue Processor - Run as cron job
 * Usage: Run this script periodically (e.g., every minute) via cron job
 * Example crontab: * * * * * php /path/to/processEmailQueue.php
 */

include('includes/session.inc');
include('Mailer/PHPMailerAutoload.php');

global $db;

// Check if email config exists
if(!file_exists('chats/EmailConfig.php')){
    die('Email configuration not found. Please configure SMTP settings.');
}
include('chats/EmailConfig.php');

// Function to send email
function sendQueuedEmail($id, $recipientEmail, $recipientName, $subject, $body) {
    global $db;
    
    // Update attempts
    DB_query("UPDATE email_queue SET attempts = attempts + 1 WHERE id = " . (int)$id, $db);
    
    // Create PHPMailer instance
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';
    $mail->Host = HOST;
    $mail->Port = PORT;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->Username = username;
    $mail->Password = trim(decrypt(password));
    $mail->setFrom(username,'SmarternowERP');
    
    // Add recipient
    $mail->addAddress($recipientEmail, $recipientName);
    
    // Set subject and body
    $mail->Subject = $subject;
    $mail->msgHTML($body, dirname(__FILE__), true);
    $mail->WordWrap = 78;
    
    // Send email
    if ($mail->send()) {
        DB_query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = " . (int)$id, $db);
        echo "Email ID $id sent successfully to $recipientEmail\n";
        return true;
    } else {
        DB_query("UPDATE email_queue SET status = 'failed' WHERE id = " . (int)$id, $db);
        echo "Email ID $id failed: " . $mail->ErrorInfo . "\n";
        return false;
    }
}

// Get pending emails (limit to 10 at a time to avoid timeout)
$sql = "SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 10";
$result = DB_query($sql, $db);

$processed = 0;
while ($row = DB_fetch_array($result)) {
    $sent = sendQueuedEmail(
        $row['id'],
        $row['recipient_email'],
        $row['recipient_name'],
        $row['subject'],
        $row['body']
    );
    if($sent) {
        $processed++;
    }
}

echo "Processed $processed emails from queue.\n";

?>