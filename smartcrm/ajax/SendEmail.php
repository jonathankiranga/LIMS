<?php
/**
 * SmartCRM Password Reset Handler
 * Called by login-modern.js sendReset() with POST { getemail: email }
 * Looks up the user, generates a temp password, updates the DB, and emails it.
 */

// Validate input before anything else
if (empty($_POST['getemail']) || !filter_var(trim($_POST['getemail']), FILTER_VALIDATE_EMAIL)) {
    echo 'Invalid email address.';
    exit;
}
$requestedEmail = trim($_POST['getemail']);

// Load config (provides $host, $DBUser, $DBPassword, $DefaultDatabase)
include('../config.php');

// Load SMTP config (provides sendSMTPEmail function)
include('../smtp_config.php');

// Connect to DB
$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) {
    echo 'Database connection error. Please contact the administrator.';
    exit;
}

/**
 * Look up a user by email.
 * Returns array with userid, realname, phone or false if not found.
 */
function getUser($db, $email) {
    $email = mysqli_real_escape_string($db, $email);
    $result = mysqli_query($db, "SELECT `userid`, `realname`, `phone` FROM `www_users` WHERE `email`='" . $email . "' LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }
    return mysqli_fetch_assoc($result);
}

/**
 * Update the user password in the DB.
 */
function setPassword($db, $email, $password) {
    $email    = mysqli_real_escape_string($db, $email);
    $password = mysqli_real_escape_string($db, $password);
    mysqli_query($db, "UPDATE `www_users` SET `password`='" . $password . "' WHERE `email`='" . $email . "'");
}

// 1. Check the user exists BEFORE changing anything
$user = getUser($db, $requestedEmail);
if (!$user) {
    echo 'The system cannot find this email address.';
    mysqli_close($db);
    exit;
}

$userid  = $user['userid'];
$names   = $user['realname'];
$phone   = $user['phone'];

// 2. Generate new password but DO NOT save yet
$newPassword = rand(100000, 9856245);

// 3. Build login URL (protocol-aware)
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$basePath   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$loginURL   = $protocol . $_SERVER['HTTP_HOST'] . $basePath . '/index.php';

// 4. Attempt email FIRST — only update DB if email succeeds
$subject = 'Your SmartCRM Login Credentials';
$body    = "Dear " . htmlspecialchars($names) . "<br/><br/>"
         . "A password reset was requested for your account.<br/>"
         . "Mobile: " . htmlspecialchars($phone) . "<br/>"
         . "User ID: " . htmlspecialchars($userid) . "<br/>"
         . "Temporary Password: <strong>" . $newPassword . "</strong><br/><br/>"
         . "Please login and change your password immediately.<br/>"
         . "Login: <a href='" . $loginURL . "'>" . $loginURL . "</a><br/>";

try {
    $result = sendSMTPEmail($requestedEmail, $subject, $body);
} catch (Exception $e) {
    $result = ['success' => false, 'error' => 'Unexpected error: ' . $e->getMessage()];
}

if ($result['success'] === true) {
    // Email confirmed sent — now safe to update the password
    setPassword($db, $requestedEmail, $newPassword);
    mysqli_close($db);
    echo 'Password reset email sent. Please check your inbox.';
} else {
    // Email failed — do NOT update the password
    mysqli_close($db);
    $logDir  = __DIR__ . '/../logs';
    if (!is_dir($logDir)) { mkdir($logDir, 0755, true); }
    $logFile = $logDir . '/mail_debug.log';
    $logMsg  = '[' . date('Y-m-d H:i:s') . '] Reset email failed for: ' . $requestedEmail
             . ' | Error: ' . ($result['error'] ?? 'unknown') . "\n";
    file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);
    echo 'Email could not be sent. Your password has NOT been changed. Please contact the administrator.';
}