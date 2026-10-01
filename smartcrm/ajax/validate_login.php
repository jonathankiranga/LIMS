<?php
$PageSecurity = 0;
$AllowAnyone = true;
$PathPrefix = '../';

ini_set('session.gc_maxlifetime', 3600);
session_name('ErpWithCRM');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include($PathPrefix . 'config.php');

$DatabaseName = isset($DefaultDatabase) ? $DefaultDatabase : (isset($DatabaseName) ? $DatabaseName : '');
if (empty($DatabaseName)) {
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}
$_SESSION['DatabaseName'] = $DatabaseName;
$_SESSION['CompanyName'] = $DatabaseName;

include($PathPrefix . 'includes/ConnectDB.inc');

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

function CryptPass($Password) {
    return sha1($Password);
}

header('Content-Type: application/json');

if (!isset($db) || $db === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

$safeUsername = $db->real_escape_string($username);
$sha1Password = CryptPass($password);

$SQL = "SELECT userid, realname, password, blocked, phone, email 
        FROM www_users 
        WHERE userid = '$safeUsername' 
        AND (password = '$sha1Password' OR password = '$password')";

$result = $db->query($SQL);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;
}

$user = $result->fetch_assoc();

if ($user['blocked'] == 0) {
    $_SESSION['UserID'] = $user['userid'];
    $_SESSION['UsersRealName'] = $user['realname'];
    $_SESSION['DatabaseName'] = $DatabaseName;
    $_SESSION['AllowedPageSecurityTokens'] = array(0);
    
    echo json_encode([
        'success'    => true,
        'message'    => 'Welcome ' . $user['realname'],
        'full_name'  => $user['realname'],
        'department' => 'User',
        'username'   => $username,
        'role'       => 'User',
        'user_id'    => $user['userid'],
        'telephone'  => $user['phone'],
        'email'      => $user['email']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Account is blocked.']);
}
