<?php
include('includes/session.inc');
require_once('Mailer/CustomMailerclass.php');

function ProcessNotificationQueue() {
    global $db;
    
    $sql = "SELECT * FROM prlleavenotifications WHERE status = 'QUEUED' ORDER BY created_at LIMIT 100";
    $result = DB_query($sql, $db);
    $count = 0;
    $failed = 0;
    
    $mymailer = new MyMailer();
    
    while($notif = DB_fetch_array($result)) {
        if(empty($notif['recipient_email'])) {
            DB_query("UPDATE prlleavenotifications SET status = 'FAILED', error_message = 'No recipient email' WHERE notification_id = " . $notif['notification_id'], $db);
            $failed++;
            continue;
        }
        
        $result_sent = $mymailer->sendmail($notif['recipient_email'], $notif['subject'], $notif['message']);
        
        if($result_sent !== false) {
            DB_query("UPDATE prlleavenotifications SET status = 'SENT', sent_at = NOW() WHERE notification_id = " . $notif['notification_id'], $db);
            $count++;
        } else {
            DB_query("UPDATE prlleavenotifications SET status = 'FAILED', error_message = 'Mail delivery failed', sent_at = NOW() WHERE notification_id = " . $notif['notification_id'], $db);
            $failed++;
        }
    }
    
    return ['sent' => $count, 'failed' => $failed];
}

if(php_sapi_name() === 'cli' || isset($_GET['cron'])) {
    $result = ProcessNotificationQueue();
    echo "Notifications processed: {$result['sent']} sent, {$result['failed']} failed\n";
}
?>
