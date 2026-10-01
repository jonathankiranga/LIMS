<?php
header('Content-Type: application/json');

session_name('Smartpayrollsystem');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$username   = isset($input['username']) ? trim($input['username']) : '';
$password   = isset($input['password']) ? $input['password'] : '';
$formId     = isset($input['formId']) ? $input['formId'] : '';
$companyKey = isset($input['company']) ? $input['company'] : null;

if (!isset($_SESSION['FormID'])) {
    $_SESSION['FormID'] = sha1(uniqid(mt_rand(), true));
}

if ($formId !== '' && !hash_equals((string)$_SESSION['FormID'], (string)$formId)) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Reload the page and try again.']);
    exit;
}

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

$PathPrefix = '../';
require $PathPrefix . 'config.php';

// Select company/database in the same way as the normal login flow
if (!isset($_SESSION['DatabaseName'])) {
    if ($companyKey !== null && isset($CompanyList[$companyKey])) {
        $_SESSION['DatabaseName'] = $CompanyList[$companyKey]['database'];
        $_SESSION['CompanyName']  = $CompanyList[$companyKey]['company'];
    } else {
        $_SESSION['DatabaseName'] = $DefaultDatabase ?? ($CompanyList[0]['database'] ?? '');
        $_SESSION['CompanyName']  = $CompanyList[0]['company'] ?? $_SESSION['DatabaseName'];
    }
    $DatabaseName = $_SESSION['DatabaseName'];
}

if (empty($_SESSION['DatabaseName'])) {
    echo json_encode(['success' => false, 'message' => 'No company database selected.']);
    exit;
}

if (!isset($_SESSION['AttemptsCounter']) || $AllowDemoMode === true) {
    $_SESSION['AttemptsCounter'] = 0;
}

require_once $PathPrefix . 'includes/ConnectDB.inc';
require_once $PathPrefix . 'includes/UserLogin.php';

// Use the existing login routine so session variables mirror the normal flow
$rc = userLogin($username, $password, $SysAdminEmail, $db);

switch ($rc) {
    case UL_OK:
        echo json_encode([
            'success' => true,
            'message' => 'Welcome ' . ($_SESSION['UsersRealName'] ?? $username),
            'user'    => $_SESSION['UserID'] ?? $username,
            'company' => $_SESSION['CompanyName'] ?? ''
        ]);
        break;

    case UL_BLOCKED:
        echo json_encode(['success' => false, 'message' => 'Account locked after too many attempts. Contact the administrator.']);
        break;

    case UL_MAINTENANCE:
        echo json_encode(['success' => false, 'message' => 'System in maintenance mode. Only administrators can sign in.']);
        break;

    case UL_CONFIGERR:
        echo json_encode(['success' => false, 'message' => 'User role has no access configured. Contact the administrator.']);
        break;

    case UL_NOTVALID:
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        break;
}

session_write_close();
