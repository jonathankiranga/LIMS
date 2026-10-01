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
$banksbranch='';

$SQL=sprintf("SELECT code,qualifications
      ,min,annual_inc,max 
      FROM prlsalaryscale where jbgroup='%s'",$_POST['jobgroup']);

$ResultIndex= DB_query($SQL, $db);
while($row=DB_fetch_array($ResultIndex)){
   $banksbranch .='<option value="'. $row['code'] .'"  '.($_POST['salaryscale2']==$row['code']?'selected="selected"':'').'>'. $row['code'] .'= Minimum '.number_format($row['min'],0).' Annual Increment '.number_format($row['annual_inc'],0) .'</option>';
}
    
echo $banksbranch;
    
?>