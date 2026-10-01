<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');

$Title = _('Bank Reconciliation');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/budgetbalance.php');
include('includes/chartbalancing.inc');

Global $statementstartdate, $DRamount, $CRamount,$tableObject, $PostingGroup,$Uncleared;
      
$DRamount=0; $CRamount=0; $Openbalance=0; $Difference=0;$Uncleared=0;
$SQL = array() ;
$banklist = array();

$resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,`lastreconbalance`,`lastChequeno`,`PostingGroup`   FROM `BankAccounts`", $db);
while($row=DB_fetch_array($resultindex)){
    $banklist[trim($row['accountcode'])]=$row['bankName'].' '.$row['BranchName'].' '.$row['currency'];
}

 
    
if(isset($_POST['SaveRecon'])){
    
    if(mb_strlen($_POST['bankstno'])==0){
      prnMsg('You need to select a bank statement No','warn');
    }
        
    if(mb_strlen($_POST['date'])==0){
     prnMsg('You need to select a bank statement End Date','warn');
    }
       
   if($_POST['newreconciliation']==$_SESSION['newreconciliation']){
     if($_POST['Totalcleared']==$_POST['bankendbalance']){
          UpdateStatus();
          SaveBankreconciliation();
          $_GET['new']='1';
     }else{
         prnMsg('Your Reconciliation does not balance','warn');
     }
   }else{
        prnMsg('You cannot resend this page. ','warn');
    }

}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Bank Reconciliation') .'" alt="" />'. _('Bank Reconciliation') . '</p>';
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';

if(isset($_GET['new'])){
    $_SESSION['newreconciliation']=date('U');
    $_SESSION['Printreconciliation']=array();
}

if(isset($_POST['bankstno']) and isset($_POST['Bank_Code'])){
    echo '<a href="PDFbankreconciliation.php?id='.trim($_POST['bankstno']).'&bankcode='.$_POST['Bank_Code'].'">PRINT RECONCILIATION</a>';
}
   
echo '<input type="hidden" name="newreconciliation" value="'.$_SESSION['newreconciliation'].'"/>';
echo '<table class="table-bordered">';
echo  constructBanks(); 

if(isset($_POST['Bank_Code'])){
    $TRBanks = new ShowBankAccounts();
    $ArrayBanks = $TRBanks->GetBankDetails($_POST['Bank_Code']);

If(!isset($_POST['bankstno'])){ 

    if(mb_strlen($ArrayBanks['StatementNo'])>0){
       Echo '<tr><td>Bank Recon No:</td><td><input tabindex="1" type="text" class="number" name="bankstno" size="11" maxlength="10" readonly="readonly"  value="'. $ArrayBanks['StatementNo'] .'"/></td></tr>';
    } else {
       Echo '<tr><td>Bank Recon No:</td><td><input tabindex="1" type="text" class="number"  name="bankstno" size="11" maxlength="10"   value="'. $_POST['bankstno'] .'"/></td></tr>';
    }

} else {
    Echo '<tr><td>Bank Recon No:</td><td><input tabindex="1" type="text" class="number"  name="bankstno" size="11" maxlength="10"   value="'. $_POST['bankstno'] .'"/></td></tr>';
}
    
$PostingGroup       = $ArrayBanks['PostingGroup'];
$statementstartdate = $ArrayBanks['lastreconcileddate'];
$Openbalance        = $ArrayBanks['lastreconbalance'];

 echo '<input type="hidden" id="statementstartdate"  value="' .$statementstartdate. '"/><input type="hidden" id="bankrecondate"  value="' . FormatDateForSQL($_POST['date']). '"/>';
   
if(!isset($_POST['bankendbalance'])){
    $_POST['bankendbalance']=$ArrayBanks['lastreconbalance'];
}
   
Echo '<tr><td>Statement Start Date:</td><td><input tabindex="2" type="text" class="number"  name="statementstartdate" size="11" maxlength="10" readonly="readonly" value="' . ConvertSQLDate($ArrayBanks['lastreconcileddate']). '"/></td></tr>';
Echo '<tr><td>Statement Start Balance:</td><td><input tabindex="2" type="text" class="number" id="lastreconbalance"  name="bankstartbalance" size="11" maxlength="10" readonly="readonly" value="' .$ArrayBanks['lastreconbalance']. '"/></td></tr>';
Echo '<tr><td colspan="2"><input type="hidden" class="number" name="Cashbookstartbalance" size="11" maxlength="10" readonly="readonly" value="' .GetClearedDB(). '"/></td></tr>';
echo '<tr><td>Statement End Date</td><td><input tabindex="3" type="text" class="date" id="bankrecondate" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10"  required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>Statement End Balance</td><td><input tabindex="4" type="text" class="number"  id="bankendbalance" name="bankendbalance" size="11" maxlength="10"  value="' .$_POST['bankendbalance']. '"   onchange="GetAllCleared(this.value)"/></td></tr>';

echo '<tr><td><B>Cleared Debit Balance:</B></td>'
     . '<td><input type="text" class="number" id="clearedDB" name="TotalDebit" value=""  readonly="readonly"/></td>';

echo '<td><B>Cleared Total Balance:</B></td>'
      . '<td><input type="text" class="number" id="cleared" name="Totalcleared" value=""  readonly="readonly"/>'
      . '</td></tr>';

echo '<tr><td><B>Cleared Credit Balance:</B></td>'
     . '<td><input type="text" class="number" id="clearedCR" name="TotalCredit" value="" readonly="readonly"/></td>'
     . '<td><B>Diff(end balance-cleared):</B></td>'
     . '<td><input type="text" class="number" id="Uncleared" name="Uncleared" value="" readonly="readonly"/></td>';

echo '</tr></table>';
    
 
}
 
echo '<span id="loadbankdata"></span>';

echo '</div></form>' ;

include('includes/footer.inc');

Function UpdateStatus(){
    Global $db,$PostingGroup;
    
    if(isset($_POST['Cheq'])){
        foreach ($_POST['Cheq'] as $Journal => $value){
           if($_POST['check'][$Journal]=='on'  ||  $_POST['check2'][$Journal]=='on'){
                $SQL="Update `BankTransactions` set cleared=1 where `journal`='".$Journal."' and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
                DB_query($SQL,$db);
            } else {
               $SQL="Update  `BankTransactions` set cleared=NULL where `journal`='".$Journal."' and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
               DB_query($SQL,$db);
            }
       }
    }
    
    
}
 
function constructBanks() {
   Global $db,$banklist;
    
      $banknames=$banklist[trim($_POST['Bank_Code'])];
      
    if(isset($_POST['Bank_Code'])){
        
        $BankObject='<tr><td><input type="hidden" name="GetBankData" value="Accept Selection"/>Select Bank:</td>'
                      . '<td><input type="text" id="bankselected" name="Bank_Code" readonly="readonly" value="'.$_POST['Bank_Code'].'" /></td>'
                       . '<td>'. $banknames.'</td><td><input type="button" id="GetBankData" value="load Bank Data"/></td></tr>';
 
        echo  $BankObject;
        
    } else{    
        
        $BankObject='<tr><td>Select Bank:</td>'
                . '<td colspan="3"><Select id="bankselected" name="Bank_Code" required="required">';
        $resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,"
                . "`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,"
                . "`lastreconbalance`,`lastChequeno`,`PostingGroup` "
                . " FROM `BankAccounts`", $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $BankObject .= '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                $BankObject .= '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
       }
        
        $BankObject .='</select>';
        $BankObject .= (isset($_POST['Bank_Code'])?'':'<input type="submit" name="GetBankData" value="Accept Selection"/>');
        $BankObject .= '<br/><em>Click and Please wait for the transactions to load</em></td></tr>';
        
        echo $BankObject;
    
     }
}

function CreateRow($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $DRamount,$Uncleared,$PostingGroup,$Difference,$db;
   
    if($_POST['check'][$Journal]=='on'){
      $currentStatus = 'checked="checked"';
    }else{
      $currentStatus = (($cleared==1)?'checked="checked"':'unchecked="unchecked"');
    }
    
    if($cleared==1 or $_POST['check'][$Journal]=='on'){
        $DRamount += $A ;
        $SQL="Update  `BankTransactions` set cleared=1 where `journal`='".$Journal."'  and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
            DB_query($SQL,$db);
    
        }else{
        $Uncleared +=$A ;
        $SQL="Update  `BankTransactions` set cleared=NULL where `journal`='".$Journal."'  and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
            DB_query($SQL,$db);
        
        }
       
    
    
   return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden" name="Cheq[%s]" value="%s"/></td>' 
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check[%s]" %s  onchange="GetDBCleared(%s,this)"/></td>'
            . '</tr>',$D,$N,$Journal,$N,$M,$M2, $Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

function CreateRow2($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $CRamount,$Uncleared,$PostingGroup,$db;
     
    if($_POST['check2'][$Journal]=='on'){
      $currentStatus = 'checked="checked"';
    }else{
      $currentStatus = (($cleared==1)?'checked="checked"':'unchecked="unchecked"');
    }
    
    if($cleared==1 or $_POST['check2'][$Journal]=='on'){
        $CRamount += $A ;
        $SQL="Update  `BankTransactions` set cleared=1 where `journal`='".$Journal."'  and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
        DB_query($SQL,$db);
     }else{
        $Uncleared +=$A ;
        $SQL="Update  `BankTransactions` set cleared=NULL where `journal`='".$Journal."'  and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
        DB_query($SQL,$db);
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

function Insert($StatementNo,$bankcode,$narration,$amount=''){
      $SQL="";
        
        if(mb_strlen($amount)>0){
            $SQL=SPRINTF("INSERT INTO `BankReconciliation`(`StatementNo`,`bankcode`,`narration`,`amount`)
            VALUES ('%s','%s','%s',%f)", $StatementNo,$bankcode,$narration,$amount);
        }else{
             $SQL=SPRINTF("INSERT INTO `BankReconciliation`(`StatementNo`,`bankcode`,`narration`)
            VALUES ('%s','%s','%s')", $StatementNo,$bankcode,$narration);
        }
        
        
  return $SQL;
}

function SaveBankreconciliation(){
  Global $SQL,$db ;
  $SQL = array();
     
  if(isset($_POST['Cheq'])){
        foreach ($_POST['Cheq'] as $Journal => $value){
           if($_POST['check'][$Journal]=='on'  ||  $_POST['check2'][$Journal]=='on'){
                $SQL="Update `BankTransactions` set reconciled='R' where `journal`='".$Journal."' and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
                DB_query($SQL,$db);
            } else {
               $SQL="Update  `BankTransactions` set cleared=NULL where `journal`='".$Journal."' and (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')";
               DB_query($SQL,$db);
            }
       }
    }
    
    if(isset($_POST['bankstno'])){
        $state = 1;
        
        $SQL[]="Update `BankAccounts` set "
         . " `StatementNo` ='".($state + $_POST['bankstno'])."',"
         . " `lastreconcileddate` =iif('".FormatDateForSQL($_POST['date'])."'>`lastreconcileddate`,'".FormatDateForSQL($_POST['date'])."',`lastreconcileddate`) ,"
         . " `lastreconbalance` ='".$_POST['bankendbalance']."'"
         . " where `accountcode`='".$_POST['Bank_Code']."'";
 
         UpdateCashbook($SQL);
    }
}
 
function UpdateCashbook($SQLREC=array()){
    Global $db,$statementstartdate,$PostingGroup;
    
    
     
    $check=array(); 
    $check2=array();
    $narration=array(); 
    $narration2=array();
    $amount=array(); 
    $amount2=array();
    $RunningT = $_POST['bankendbalance'];
    $Totaldebit=0;
    $TotalCredit=0;
    
    $ResultIndex = DB_query("Select `DocDate`,`amount`,`journal`,`narrative` from `BankTransactions`  where (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."')  and   (`BankTransactions`.`cleared` is NULL and `BankTransactions`.`DocDate`<='".FormatDateForSQL($_POST['date'])."') ",$db);
    while($row=DB_fetch_array($ResultIndex)){
            $jornal = $row['journal'];
          
            if($row['amount']>0){
                 $date[$jornal] = $row['DocDate'];
               $amount[$jornal] = abs($row['amount']);
            $narration[$jornal] = trim($row['narrative']);
            $Totaldebit += $row['amount'];
            } 
            
            if($row['amount']<0){
                 $date2[$jornal] = $row['DocDate'];
               $amount2[$jornal] = abs($row['amount']);
            $narration2[$jornal] = trim($row['narrative']);
            $TotalCredit += $row['amount'];
            }
        
    }
    
    $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],"  Reconciliation As at ".$_POST['date']);
    $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],"  Bank Statement Balance ",$_POST['bankendbalance']);
    $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],"  ADD:Uncredited Cheques");
     foreach ($date as $journal => $doc2no) {
        $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],$date[$journal].' '.$narration[$journal],$amount[$journal]);        
    }
    $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],"  Total Uncredited Cheques",$Totaldebit);
    
    $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code']," LESS:UnPresented Cheques");
    foreach ($date2 as $jorn => $docno) {           
        $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],$date2[$jorn].' '.$narration2[$jorn],$amount2[$jorn]); 
     }
     $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code']," Total UnPresented Cheques",$TotalCredit);
     
    $SQLREC[]=Insert($_POST['bankstno'],$_POST['Bank_Code'],"  Balance as Per Cash BOOK ",$_POST['bankendbalance']+$Totaldebit+$TotalCredit);
        
    
    
    DB_Txn_Begin($db);
    foreach ($SQLREC as $value ) {
      DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    }else{
       DB_Txn_Commit($db);
       $_SESSION['newreconciliation']=date('U');
    }
    
    
   }
    
Function GetClearedDB(){
    global $db,$statementstartdate,$PostingGroup;
    
     $ResultIndex = DB_query("Select sum(`amount`) from `BankTransactions`"
     . "  where (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."') and `cleared`=1 "
     . "  and `DocDate` <= '".$statementstartdate."'",$db);
   
    $cashbookbalance=DB_fetch_row($ResultIndex);
    return $cashbookbalance[0];
}  

Function GetUnClearedDB(){
    global $db,$statementstartdate,$PostingGroup;
    
     $ResultIndex = DB_query("Select sum(`amount`) from `BankTransactions`"
     . "  where (`bankcode`='".$_POST['Bank_Code']."' or `bankcode`='".$PostingGroup."') "
     . "  and (`cleared`=0 or `cleared` is null)  "
     . "  and `DocDate` <= '".$statementstartdate."'",$db);
   
    $cashbookbalance=DB_fetch_row($ResultIndex);
    return $cashbookbalance[0];
}  

   
?>
