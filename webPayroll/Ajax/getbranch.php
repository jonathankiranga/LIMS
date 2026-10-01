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

if(mb_strlen($_POST['parentcode'])>0){
    $SQL = sprintf("SELECT code,bankbranch as branch,address "
        . "FROM employeebnkbranch where parentcode='%s' ",$_POST['parentcode']);
} else {
   $SQL = sprintf("SELECT code,bankbranch as branch,address FROM employeebnkbranch ");
 }
 
$ResultIndex= DB_query($SQL,$db);
while($row=DB_fetch_array($ResultIndex)){
   $banksbranch .='<option value="'. $row['code'] .'"  '.($_POST['code']==$row['code']?'selected="selected"':'').'>'. trim($row['branch']) .' '. trim($row['address']) .'</option>';
}
    
echo $banksbranch;
    
?>