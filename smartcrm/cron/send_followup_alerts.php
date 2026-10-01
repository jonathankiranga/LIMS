<?php
/**
 * CLI helper: send follow-up calendar alerts for due/overdue tasks.
 * Usage (cron): php /path/to/smartcrm/cron/send_followup_alerts.php
 */

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$PathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

// For CLI mode: set safe $_SERVER defaults (config.php reads PHP_SELF)
if (PHP_SAPI === 'cli') {
    $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? 'cron/send_followup_alerts.php';
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? 'cron/send_followup_alerts.php';
}

require $PathPrefix . 'config.php';
require $PathPrefix . 'includes/AutomationDB.inc';
require $PathPrefix . 'Mailer/PHPMailerAutoload.php';

$db = getAutomationDB();
if (!$db) {
    fwrite(STDERR, "Failed to connect to database\n");
    exit(1);
}

$smtpConfigPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smtp_config.php';
if (!file_exists($smtpConfigPath)) {
    fwrite(STDERR, "SMTP config not found at $smtpConfigPath\n");
    exit(1);
}
require $smtpConfigPath;
if (!isset($smtp_config) || !is_array($smtp_config)) {
    fwrite(STDERR, "smtp_config.php did not define \$smtp_config\n");
    exit(1);
}

$lookAheadDays = 1; // include today and tomorrow
$nowUtc = gmdate('Y-m-d\TH:i:s\Z');
$maxDue = date('Y-m-d', strtotime("+{$lookAheadDays} day"));

function columnExists($table, $column, $db)
{
    $sql = "SHOW COLUMNS FROM `$table` LIKE '" . DB_escape_string($column) . "'";
    $res = DB_query($sql, $db);
    return $res && DB_num_rows($res) > 0;
}

$hasLastAlert = columnExists('Tasks', 'lastalert', $db);

$where = [
    "t.datedue IS NOT NULL",
    "t.datedue <= '" . DB_escape_string($maxDue) . "'",
    "t.Status <> 4" // not Complete
];
if ($hasLastAlert) {
    $where[] = "(t.lastalert IS NULL OR t.lastalert < CURDATE())";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT t.pkey, t.Taskname, t.TaskOwner, t.datedue, t.taskdetails, t.Status,
           u.email, u.realname
    FROM Tasks t
    LEFT JOIN www_users u ON u.userid = t.TaskOwner
    $whereSql
    ORDER BY t.datedue ASC
";

$result = DB_query($sql, $db);
if ($result === false) {
    fwrite(STDERR, "DB query failed: " . DB_error_msg($db) . PHP_EOL);
    exit(1);
}

$sentCount = 0;
$failCount = 0;

while ($row = DB_fetch_array($result)) {
    $email = trim($row['email'] ?? '');
    if ($email === '') {
        continue; // no destination
    }
    $taskId   = (int)$row['pkey'];
    $title    = $row['Taskname'];
    $dueDate  = $row['datedue'];
    $details  = $row['taskdetails'];
    $owner    = $row['realname'] ?: $row['TaskOwner'];

    $dtStart = date('Ymd', strtotime($dueDate));
    $dtEnd   = date('Ymd', strtotime($dueDate . ' +1 day'));
    $uid     = "task-$taskId@smarternow";

    $ics  = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//Smarternow CRM//Followups//EN\r\n";
    $ics .= "METHOD:PUBLISH\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:$uid\r\n";
    $ics .= "DTSTAMP:$nowUtc\r\n";
    $ics .= "SUMMARY:" . addcslashes($title, ",;\\") . "\r\n";
    $ics .= "DESCRIPTION:" . addcslashes($details, "\n\r,;\\") . "\r\n";
    $ics .= "DTSTART;VALUE=DATE:$dtStart\r\n";
    $ics .= "DTEND;VALUE=DATE:$dtEnd\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";

    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->CharSet = 'utf-8';

    $mail->Host       = $smtp_config['host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_config['username'] ?? '';
    $mail->Password   = $smtp_config['password'] ?? '';
    $mail->Port       = $smtp_config['port'] ?? 25;

    $secure = strtolower($smtp_config['secure'] ?? '');
    if ($secure === 'ssl' || $secure === 'tls') {
        $mail->SMTPSecure = $secure;
    }

    $fromEmail = $smtp_config['from_email'] ?? ($SysAdminEmail ?: 'no-reply@localhost');
    $fromName  = $smtp_config['from_name'] ?? 'Smarternow CRM';

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($email, $owner);
    $mail->Subject = "Follow-up due: $title ($dueDate)";
    $mail->Body    = "Hi $owner,\n\nFollow-up is due on $dueDate.\n\n$details\n\n-- Smarternow CRM";
    $mail->addStringAttachment($ics, 'followup.ics', 'base64', 'text/calendar; charset=utf-8; method=PUBLISH');

    if (!$mail->send()) {
        $failCount++;
        fwrite(STDERR, "Failed to send task #$taskId to $email: " . $mail->ErrorInfo . PHP_EOL);
        continue;
    }

    $sentCount++;
    if ($hasLastAlert) {
        DB_query("UPDATE Tasks SET lastalert = NOW() WHERE pkey = '$taskId'", $db);
    }
}

echo "Sent: $sentCount, Failed: $failCount\n";
