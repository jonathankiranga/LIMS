<?php
/* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
session_start();
include('../config.php');
include('DateFunctions.inc');

global $db;
// Make sure it IS global, regardless of our context
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);
if (!$db) {
    die('Database connection failed');
}
mysqli_set_charset($db, 'utf8mb4');
 //DB wrapper functions to change only once for whole application

function DB_query($SQL , $Conn){
  $result = mysqli_query($Conn,$SQL);
    return $result;
}

function DB_fetch_array ($ResultIndex) {
  return  mysqli_fetch_assoc($ResultIndex);
}

function DB_num_rows ($ResultIndex){
  return mysqli_num_rows($ResultIndex);
}

function DB_fetch_row($ResultIndex) {
     return mysqli_fetch_row($ResultIndex);
 }   

$sql="SELECT `coycode`,`coyname`,`PIN`,`vat` ,`regoffice1`,`regoffice2` ,`regoffice3`  ,`regoffice4` ,`regoffice5` ,`regoffice6` ,`telephone`,`fax` ,`email`,`currencydefault`, currencies.decimalplaces
FROM companies  INNER JOIN currencies ON companies.currencydefault=currencies.currabrev  WHERE coycode=1";
$ReadCoyResult = DB_query($sql,$db);
if (DB_num_rows($ReadCoyResult)>0) {
    $_SESSION['CompanyRecord'] =  DB_fetch_array($ReadCoyResult);
}

$sql = "SELECT confname, confvalue FROM config";
$ConfigResult = DB_query($sql,$db);
while($myrow = DB_fetch_array($ConfigResult)) {
        if (is_numeric($myrow['confvalue']) AND $myrow['confname']!='DefaultPriceList' AND $myrow['confname']!='VersionNumber'){
                //the variable name is given by $myrow[0]
                $_SESSION[$myrow['confname']] = (double) $myrow['confvalue'];
        } else {
                $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
        }
} 



function myjavascript($DEBIT='DEBIT',$amount=0){
    
    if($DEBIT=='DEBIT'){
         echo '<script type="text/javascript"> ';
         echo "var TotalCleared=0,Difference=0,Balbfwd=0, CRamount=0,bankendbalance=0, DBamount=0;
              document.getElementById('clearedDB').value =Math.round( ".$amount.",1) ;
              Balbfwd   = document.getElementById('lastreconbalance').value;
              CRamount  = document.getElementById('clearedCR').value;
              bankendbalance = document.getElementById('bankendbalance').value;
              TotalCleared = (((+Balbfwd) + (+ ".$amount." ))- (+CRamount));
              Difference= (+bankendbalance)- (+TotalCleared);
              document.getElementById('cleared').value = Math.round(TotalCleared,1);
            </script>";
    }else{
        echo '<script type="text/javascript"> ';
        echo "var TotalCleared=0,Difference=0,Balbfwd=0,CRamount=0,bankendbalance=0,DBamount=0;
            document.getElementById('clearedCR').value = Math.round(".$amount.",1) ;
            Balbfwd = document.getElementById('lastreconbalance').value;
            DBamount =  document.getElementById('clearedDB').value;
            bankendbalance = document.getElementById('bankendbalance').value;
            TotalCleared = (((+Balbfwd) + (+DBamount))- (+".$amount."));
            Difference= (+bankendbalance)- (+TotalCleared);
            document.getElementById('cleared').value =Math.round(TotalCleared,1);";
        echo "</script>";
    }
}

function CreateRow($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $DRamount,$db;
    $D = ConvertSQLDate($D);
    
     $currentStatus = (($cleared==1)?'checked="checked"':'unchecked="unchecked"');
     if($cleared==1){
        $DRamount += $A ;
     }
    
   return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden" name="Cheq[%s]" value="%s"/></td>' 
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check[%s]" %s  onchange="GetDBCleared(%s,this)"/></td>'
            . '</tr>',$D,$N,$Journal,$N,$M,$M2,$Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

function CreateRow2($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $CRamount,$db;
    $D = ConvertSQLDate($D);
   
    $currentStatus = (($cleared==1)?'checked="checked"':'unchecked="unchecked"');
     if($cleared==1){
        $CRamount += $A ;
     }
  
     return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden"  name="Cheq[%s]" value="%s"/></td>' 
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check2[%s]" %s  onchange="GetCRCleared(%s,this)" /></td>'
            . '</tr>', $D,$N,$Journal,$N,$M,$M2,$Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

Global $db,$tableObject,$PostingGroup;
Global $CRamount,$DRamount,$Uncleared,$Difference;
Global $banklist;

$DRamount=0;$Uncleared=0;$Difference=0;$CRamount=0;

$banklist=array();

$resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,`lastreconbalance`,`lastChequeno`,`PostingGroup`   FROM `BankAccounts`", $db);
while($row=DB_fetch_array($resultindex)){
    $banklist[trim($row['accountcode'])]=$row['bankName'].' '.$row['BranchName'].' '.$row['currency'];
}

    $SQL="SELECT 
       `bankcode`
      ,`DocDate`
      ,`systypes_1`.`typename`
      ,`DocumentNo`
      ,(case TransType 
	   when 'DR' then (select customer from debtors where debtors.itemcode=banktransactions.itemcode)  
	   when 'CR' then (select customer from creditors where creditors.itemcode=banktransactions.itemcode)  
	   else (select customer from debtors where debtors.itemcode=banktransactions.itemcode) end) as Payee
      ,`journal`
      ,`amount`
      ,`narrative`
      ,`exchangerate`
      ,IFNULL(`cleared`,0) AS `cleared`
      ,`BankTransactions`.`reconciled`
  FROM `BankTransactions` 
  left join `systypes_1` on `BankTransactions`.`doctype`=`systypes_1`.typeid 
  where (`bankcode`='".$_POST['Bank_Code']."')";
   
  if(mb_strlen($_POST['statementstartdate'])>6 and mb_strlen($_POST['enddate'])>6){
        $SQL .= " and (`BankTransactions`.`DocDate` between '".$_POST['statementstartdate']."' and '".$_POST['enddate']."')"
           . " or (`BankTransactions`.`reconciled` is NULL)";
  }elseif(mb_strlen($_POST['statementstartdate'])>6){
        $SQL .= " and (`BankTransactions`.`DocDate` > '".$_POST['statementstartdate']."') or (`BankTransactions`.`reconciled` is NULL)";
  }
    
$SQL .=" order by `DocDate` Asc" ;
 $ResultIndex=DB_query($SQL,$db);
   
$tableObject='<div id="filtereddata"><div class="centre">'
           . '<input type="submit" name="refresh" value="Update"/>'
           . '<input type="submit" name="SaveRecon" value="Save Bank Reconciliation"/>'
           . '</div>';

$tableObject.= '<p><table class="table-bordered"><tr><td valign="top">'
        . '<table id="cashbookdebit" class="display table-bordered"><thead><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr></thead><tbody>';
   
while($row =DB_fetch_array($ResultIndex)){
        $amounttocheck=$row['amount'];
            if($amounttocheck>0){
                $tableObject .=  CreateRow($row['DocDate']
                         ,$row['DocumentNo']
                         ,trim($row['narrative'])
                         ,trim($row['typename']).'  '.$row['Payee']
                         ,$row['amount']
                         ,$row['journal']
                         ,$row['cleared']);
            }
    }
 
     myjavascript('DEBIT',$DRamount);
   
    
  $tableObject .= '</tbody><tfoot><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr>'
          . '</tfoot></table></td><td valign="top">';
      
  $tableObject .= '<table  id="cashbookcredit"  class="display table-bordered"><thead><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr></thead><tbody>';

 $ResultIndex=DB_query($SQL,$db);
    while($row =DB_fetch_array($ResultIndex)){
        $amounttocheck = $row['amount'];
            if($amounttocheck<0){
                $tableObject .=  CreateRow2($row['DocDate']
                         ,$row['DocumentNo']
                         ,trim($row['narrative'])
                         ,trim($row['typename']).'  '.$row['Payee']
                         ,$row['amount']
                         ,$row['journal']
                         ,$row['cleared']);
            }
    }
  myjavascript('CREDIT',$CRamount);
        
 
  $tableObject .= '</tbody><tfoot><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr>'
          . '</tfoot></table></td>'
          . '</tr></table></div>';
         
echo $tableObject;