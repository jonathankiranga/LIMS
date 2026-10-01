<?php
// Send email reminders for tasks due soon and include an iCalendar invite.

$PathPrefix = '../';
$PageSecurity = 0;
$AllowAnyone = true;

date_default_timezone_set('Africa/Nairobi');

include($PathPrefix . 'config.php');
// Ensure DB connect uses the default database
$_SESSION['DatabaseName'] = $DefaultDatabase ?? null;
include($PathPrefix . 'includes/ConnectDB.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');
require_once($PathPrefix . 'Mailer/PHPMailerAutoload.php');

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

$smtpConfigPath = __DIR__ . '/smtp_config.php';
if (file_exists($smtpConfigPath)) {
    require $smtpConfigPath;
}

function buildMailer($SMTP_CONFIG, $CompanyEmail) {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'utf-8';
    $mail->isSMTP();
    $mail->SMTPDebug = 0;

    if (isset($SMTP_CONFIG) && is_array($SMTP_CONFIG)) {
        $mail->Host       = $SMTP_CONFIG['Host'] ?? '';
        $mail->SMTPAuth   = $SMTP_CONFIG['SMTPAuth'] ?? false;
        $mail->Username   = $SMTP_CONFIG['Username'] ?? '';
        $mail->Password   = $SMTP_CONFIG['Password'] ?? '';
        $mail->SMTPSecure = $SMTP_CONFIG['SMTPSecure'] ?? '';
        $mail->Port       = $SMTP_CONFIG['Port'] ?? 25;
        $fromEmail        = $SMTP_CONFIG['FromEmail'] ?? ($CompanyEmail ?? 'no-reply@localhost');
        $fromName         = $SMTP_CONFIG['FromName'] ?? 'Smarternow CRM';
    } else {
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'user';
        $mail->Password = 'secret';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $fromEmail = $CompanyEmail ?? 'no-reply@localhost';
        $fromName  = 'Smarternow CRM';
    }

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

    $tmpFile = tempnam(sys_get_temp_dir(), 'taskics_');
    file_put_contents($tmpFile, $ics);

    $mailer = buildMailer($SMTP_CONFIG ?? null, $CompanyEmail ?? null);
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
    @unlink($tmpFile);
}

echo "Reminders sent: {$sent}\n";
