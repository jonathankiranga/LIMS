<?php
// Send email reminders for tasks due soon and include an iCalendar invite.

// For CLI mode: set safe $_SERVER defaults (config.php reads PHP_SELF)
if (PHP_SAPI === 'cli') {
    $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? 'cron/cron_task_reminders.php';
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? 'cron/cron_task_reminders.php';
}

date_default_timezone_set('Africa/Nairobi');

$PathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;
include($PathPrefix . 'config.php');
include($PathPrefix . 'includes/AutomationDB.inc');
require_once($PathPrefix . 'Mailer/PHPMailerAutoload.php');

$db = getAutomationDB();
if (!$db) {
    echo "[ERROR] Failed to connect to database\n";
    exit(1);
}

$statusLabels = array(
    "0" => 'Not yet begun',
    "1" => 'In progress',
    "2" => 'Almost Done',
    "3" => 'Taking Longer than expected',
    "4" => 'Complete'
);

// Window: today and tomorrow
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$SQL = "
    SELECT t.pkey, t.Taskname, t.Status, t.Priority, t.TaskOwner, t.datedue, t.taskdetails,
           u.email, u.realname
      FROM Tasks t
 LEFT JOIN www_users u ON u.userid = t.TaskOwner
     WHERE t.datedue IS NOT NULL
       AND t.Status <> 4
       AND t.datedue BETWEEN '$today' AND '$tomorrow'
";
$Result = DB_query($SQL, $db, '', '', false, false);

if ($Result === false) {
    echo "DB error retrieving tasks\n";
    exit(1);
}

$smtpConfigPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smtp_config.php';
if (!file_exists($smtpConfigPath)) {
    echo "[ERROR] SMTP config not found at $smtpConfigPath\n";
    exit(1);
}
require $smtpConfigPath;

if (!isset($smtp_config) || !is_array($smtp_config)) {
    echo "[ERROR] smtp_config.php did not define \$smtp_config\n";
    exit(1);
}

function buildMailer($smtpConfig, $fallbackFromEmail) {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'utf-8';
    $mail->isSMTP();
    $mail->SMTPDebug = 0;

    $mail->Host       = $smtpConfig['host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpConfig['username'] ?? '';
    $mail->Password   = $smtpConfig['password'] ?? '';
    $mail->Port       = $smtpConfig['port'] ?? 25;

    $secure = strtolower($smtpConfig['secure'] ?? '');
    if ($secure === 'ssl' || $secure === 'tls') {
        $mail->SMTPSecure = $secure;
    }

    $fromEmail = $smtpConfig['from_email'] ?? ($fallbackFromEmail ?: 'no-reply@localhost');
    $fromName  = $smtpConfig['from_name'] ?? 'Smarternow CRM';

    $mail->setFrom($fromEmail, $fromName);
    $mail->SMTPOptions = array('ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true));
    return $mail;
}

$sent = 0;

while ($row = DB_fetch_array($Result)) {
    $email = $row['email'] ?: $SysAdminEmail;
    if (!$email) {
        continue;
    }

    $title = $row['Taskname'];
    $due = $row['datedue'];
    $statusLabel = $statusLabels[$row['Status']] ?? $row['Status'];
    $priority = $row['Priority'];
    $owner = $row['TaskOwner'];
    $details = $row['taskdetails'];

    $subject = "Reminder: {$title} (due {$due})";

    $body = "<p><strong>{$title}</strong></p>"
          . "<p>Due: {$due}<br>Status: {$statusLabel}<br>Priority: {$priority}<br>Owner: {$owner}</p>";
    if (!empty($details)) {
        $body .= "<p>Details:<br>" . nl2br(htmlspecialchars($details)) . "</p>";
    }
    $body .= "<p>This is an automated reminder from Smarternow CRM.</p>";

    // Build a simple one-hour ICS event at 09:00 local time on due date
    $dtStart = new DateTime($due . ' 09:00', new DateTimeZone('Africa/Nairobi'));
    $dtEnd = clone $dtStart;
    $dtEnd->modify('+1 hour');
    $uid = 'task-' . $row['pkey'] . '@smarternow';

    $ics = "BEGIN:VCALENDAR\r\n"
         . "VERSION:2.0\r\n"
         . "PRODID:-//Smarternow//TaskReminder//EN\r\n"
         . "BEGIN:VEVENT\r\n"
         . "UID:$uid\r\n"
         . "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n"
         . "DTSTART:" . $dtStart->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\n"
         . "DTEND:" . $dtEnd->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\n"
         . "SUMMARY:" . addcslashes($title, ",;") . "\r\n"
         . "DESCRIPTION:" . addcslashes(strip_tags($details), ",;") . "\r\n"
         . "END:VEVENT\r\n"
         . "END:VCALENDAR\r\n";

    $mailer = buildMailer($smtp_config, $SysAdminEmail ?? '');
    $mailer->addAddress($email, $email);
    $mailer->Subject = $subject;
    $mailer->msgHTML($body, dirname(__FILE__), true);
    $mailer->addStringAttachment($ics, 'reminder.ics', 'base64', 'text/calendar; charset=utf-8; method=PUBLISH');

    try {
        $mailer->send();
        $sent++;
    } catch (Exception $e) {
        // silent fail per original behavior
    }
    $mailer->clearAddresses();
    $mailer->clearAttachments();
}

echo "Reminders sent: {$sent}\n";
