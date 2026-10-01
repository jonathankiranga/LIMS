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

if(isset($_POST['leavid'])){
    echo GetNextPrNo('51', $db);
}

if(isset($_POST['PrType'])){
    $P=$_POST['PrType'];
    $array=array('0'=>'11','1'=>'10','2'=>'12','3'=>'13');
    echo GetNextPrNo($array[$P], $db);
}


Function padR($value){
    $S = strlen(trim($value));
    $multiplier =((4-$S)>0)?(4-$S):0;
    $P = str_repeat('0',$multiplier);
    return $P.$value;
}

Function GetNextPrNo ($TransType, &$odb){
    global $db;
   
    $SQL = SPRINTF( "UPDATE systypes_1 SET typeno = typeno + 1  WHERE typeid = '%s' ",$TransType);
    $UpdTransNoResult = DB_query($SQL,$db);

    $SQL = SPRINTF("SELECT ifnull(prefix,'E-') as prefix , typeno FROM systypes_1 WHERE typeid='%s'",$TransType);
    $GetTransNoResult = DB_query($SQL,$db);
    $myrow = DB_fetch_row($GetTransNoResult);

   $prefix = $myrow[0];
   $nexno  = padR($myrow[1]);
   
   return trim($prefix).trim($nexno) ;
}



?>