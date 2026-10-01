<?php

session_name('Smartpayrollsystem');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
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

require_once $PathPrefix . 'includes/ConnectDB.inc';

if(isset($_POST['parentcode'])){
    $SQL=sprintf("SELECT count(*) as items FROM employeebnkbranch "
            . " where parentcode='%s' and code ='%s' ",$_POST['parentcode'],$_POST['branchcode']);
    $ResultIndex = DB_query($SQL, $db);
    $row=DB_fetch_row($ResultIndex);
   
    echo $row[0];
}


if(isset($_POST['code'])){
    $SQL=sprintf("SELECT count(*) as items FROM employeebanks "
            . " where code='%s' ",$_POST['code']);
    $ResultIndex = DB_query($SQL, $db);
    $row=DB_fetch_row($ResultIndex);        
    echo $row[0];
}


?>