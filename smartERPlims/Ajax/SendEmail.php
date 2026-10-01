<?php
include('../Mailer/PHPMailerAutoload.php');
include('../config.php');

if (!file_exists("../chats/EmailConfig.php")) {
    header('Location:../EmailConfigurator.php');
    exit;
} else {
    include('../chats/EmailConfig.php');
}

// Validate input first — before touching the database
if (empty($_POST['getemail']) || !filter_var(trim($_POST['getemail']), FILTER_VALIDATE_EMAIL)) {
    echo 'Invalid email address.';
    exit;
}
$requestedEmail = trim($_POST['getemail']);

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
    die('Database connection failed');
}

function DB_query($SQL, $Conn) {
    return mysqli_query($Conn, $SQL);
}

function DB_fetch_array($ResultIndex) {
    return mysqli_fetch_assoc($ResultIndex);
}

function GetCredentials($email) {
    global $db;
    $arr   = [];
    $jsonData = '{"results":[';
    $email = mysqli_real_escape_string($db, $email);
    $SQL   = "SELECT `userid`,`password`,`realname`,`phone`,`email` FROM `www_users` WHERE `email`='" . $email . "'";
    $ResultIndex = DB_query($SQL, $db);
    while ($rows = DB_fetch_array($ResultIndex)) {
        $line               = new stdClass;
        $line->membershipNo = $rows['phone'];
        $line->userid       = $rows['userid'];
        $line->userpassword = $rows['password'];
        $line->names        = $rows['realname'];
        $arr[]              = json_encode($line);
    }
    $jsonData .= implode(",", $arr);
    $jsonData .= ']}';
    return $jsonData;
}

function Resetpassword($email, $password) {
    global $db;
    $email    = mysqli_real_escape_string($db, $email);
    $password = mysqli_real_escape_string($db, $password);
    $SQL      = "UPDATE `www_users` SET `password`='" . $password . "' WHERE `email`='" . $email . "'";
    DB_query($SQL, $db);
}

// 1. Check the email exists BEFORE changing anything
$callbackJSONData = GetCredentials($requestedEmail);
$callbackData     = json_decode($callbackJSONData);

if (!isset($callbackData->results) || count($callbackData->results) !== 1) {
    echo 'The system cannot find this email address.';
    exit;
}

$pf_no        = $callbackData->results[0]->membershipNo;
$userid       = $callbackData->results[0]->userid;
$names        = $callbackData->results[0]->names;

// 2. Generate new password but DO NOT save yet
$newPassword = rand(100000, 9856245);

// 3. Build the login URL correctly (protocol-aware, no hardcoded port)
$protocol        = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$basePath        = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$ConfirmationURL = $protocol . $_SERVER['HTTP_HOST'] . $basePath . '/index.php';

// 4. Attempt to send email FIRST — only update DB if email succeeds
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug  = 2; // Full SMTP conversation logged
$mail->Debugoutput = function ($str, $level) {
    $logFile = __DIR__ . '/../chats/mail_debug.log';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . trim($str) . "\n", FILE_APPEND);
};
$mail->Host       = HOST;
$mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
$mail->Port       = PORT;
$mail->SMTPSecure = defined('SECURE') ? SECURE : '';
$mail->SMTPAuth   = true;
$mail->Username   = username;
$mail->Password   = trim(password);
$mail->setFrom(username, 'SmartERP');
$mail->addAddress($requestedEmail, $names);
$mail->Subject = 'Your ERP Login Credentials';
$mailbody = "Dear " . htmlspecialchars($names) . "<br/>"
    . "Mobile No: " . htmlspecialchars($pf_no) . "<br/>"
    . "Once you login, please change your password immediately.<br/>"
    . "Your user ID: " . htmlspecialchars($userid) . "<br/>"
    . "Your temporary password: " . $newPassword . "<br/>"
    . "Login URL: <a href='" . $ConfirmationURL . "'>" . $ConfirmationURL . "</a><br/>";
$mail->WordWrap = 78;
$mail->msgHTML($mailbody, dirname(__FILE__), true);

try {
    $sent = $mail->send();
} catch (Exception $e) {
    $sent = false;
    $logFile = __DIR__ . '/../chats/mail_debug.log';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] EXCEPTION: ' . $e->getMessage() . "\n", FILE_APPEND);
}

if (!$sent) {
    // Email failed — do NOT update the password in the database
    $logFile = __DIR__ . '/../chats/mail_debug.log';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] SEND FAILED: ' . $mail->ErrorInfo . "\n", FILE_APPEND);
    echo "Mail sending failed. Your password has NOT been changed. Check chats/mail_debug.log for details.";
} else {
    // Email sent successfully — now safe to update the password
    Resetpassword($requestedEmail, $newPassword);
    echo "Password reset email sent!";
}
?>
