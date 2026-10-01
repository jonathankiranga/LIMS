<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Edit General Journal');
include('includes/header.inc');

  
if(isset($_GET['DocumentNo'])){
    unset($_SESSION['SelectedJournal']);
    $_POST['JournalNo'] = $_GET['DocumentNo'];
    $_POST['date'] = ConvertSQLDate($_GET['Docdate']);
    $_SESSION['SelectedJournal']= $_GET['DocumentNo'];
    
    $JouClass = new journalentries();
    $JouClass->NewJournal();
    
    $array=array();
    $sql=sprintf("select Account,transtype,itemcode,amount,narration from"
            . " JournalEntries where `Docdate`='%s' and `JournalNo`='%s'",
            trim($_GET['Docdate']),trim($_GET['DocumentNo']));
    
   
    $ResultIndex=DB_query($sql,$db);
     while($row= DB_fetch_array($ResultIndex)){
         $array[]=$row;
     }
     
     foreach ($array as $key => $row) {
        $_POST['comments'] = trim($row['narration']);
        $_POST['account'.$JouClass->I] = trim($row['Account']);
        $_POST['acctype'.$JouClass->I] = trim($row['transtype']);
        $_POST['itemcode'.$JouClass->I]= trim($row['itemcode']);
        $_POST['amount'.$JouClass->I]  = trim($row['amount']);
        $JouClass->GLentries(trim($row['Account']),trim($row['transtype']),trim($row['itemcode']),trim($row['amount']));
 
        $JouClass->cmbGL('account'.$JouClass->I,$_POST['account'.$JouClass->I],$_POST['account'.$JouClass->I]);
        $JouClass->types('acctype'.$JouClass->I,$_POST['acctype'.$JouClass->I]);
        $JouClass->cmbPersonal($_POST['acctype'.$JouClass->I],'itemcode'.$JouClass->I,$_POST['itemcode'.$JouClass->I]);
  
     }
    
    
    $_POST['currency'] = $_SESSION['CompanyRecord']['currencydefault'];
    $_SESSION['JournalID']=date('U');

} else {
     $JouClass = new journalentries();
}
   
$account    = $_POST['account'.$JouClass->I];
$acctype    = $_POST['acctype'.$JouClass->I];
$itemcode   = $_POST['itemcode'.$JouClass->I];
$amount     = $_POST['amount'.$JouClass->I];
$JouClass->GLentries($account,$acctype,$itemcode,$amount);
    
if(isset($_POST['savejournal'])){
$errors=0;
$ob = new journalentries();
     
if($_POST['account'.$ob->I]==$_POST['itemcode'.$ob->I]){
    prnMsg('The Credit account should not be the same as the Debit account','warn');
    $errors++;
} 
                  
if($_POST['JournalID']!=$_SESSION['JournalID']){
        prnMsg('This journal has already been posted','warn');
        $errors++;
    }
    
if(mb_strlen($_POST['comments'])==0){
     prnMsg('This journal has must have a narration','warn');
     $errors++;
}    
           
if($errors==0){ 

        $SQL= $ob->SaveJournal();
        DB_Txn_Begin($db);
        foreach ($SQL as $sql) {
           DB_query($sql,$db);
        }
        
        if(DB_error_no($db)>0){
            DB_Txn_Rollback($db);
        } else {
            DB_Txn_Commit($db);
            
            $JouClass = new journalentries();
            $JouClass->NewJournal();

            $_POST['currency']  = $_SESSION['CompanyRecord']['currencydefault'];
            
            prnMsg('The Journal has been saved');
        }
    }              
  unset($_SESSION['SelectedJournal']);
}
 
if(isset($_POST['cancel'])){
    $JouClass->NewJournal();
    $JouClass = new journalentries();
}

if(isset($_POST['addline'])){
         $JouClass = new journalentries();
         
         if($_POST['account'.$JouClass->I]==$_POST['itemcode'.$JouClass->I]){
              prnMsg('The Credit account should not be the same as the Debit account','warn');
          } else {
              
                $JouClass->NextJournal();
            
          }
        
     }
     
     
if(!isset($_SESSION['SelectedJournal']) or isset($_GET['New'])){     
     
  Getlist();
  
}else{ 
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Edit General Journal') .'" alt="" />' . ' ' . _('Edit General Journal') . '</p>';
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="Journal"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<input type="hidden" name="JournalID" value="'. $_SESSION['JournalID'] .'"/>';
echo '</div><div class="container-fluid"><table class="table-bordered">';
echo '<tr><td>Date</td><td><input  type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr><tr>';
echo '<td>Journal No</td><td><input  type="text" name="JournalNo" value="'.$_POST['JournalNo'].'" size="11" maxlength="10" readonly="readonly"/></td>';

$sql = "SELECT currabrev,country,hundredsname,rate,decimalplaces FROM currencies";
$result = DB_query($sql,$db);
echo '<td>Currency</td><td><select tabindex="3" name="currency">';
while ($myrow = DB_fetch_array($result)) {
    echo '<option value="'.$myrow['currabrev'].'" '.((trim($myrow['currabrev'])==$_POST['currency'])?'selected="selected"':'').'  >'.$myrow['currabrev']."</option>";
}

echo '</select></td></tr>';
echo '<tr>'. $_SESSION['SelectObject']['dimensionone'];
echo  $_SESSION['SelectObject']['dimensiontwo'].'</tr>';

echo '<tr><td>Narration</td><td><input type="text" name="comments" size="50" value="'.$_POST['comments'].'" maxlenth="100" /></td></tr>';
  
echo '<tr><td colspan="5">'
        . '<table class="table-bordered">'
        . '<tr>'
        . '<th>No</th>'
        . '<th>DR:Account</th>'
        . '<th>Account Description</th>'
        . '<th>CR:Account Type</th>'
        . '<th>Name</th>'
        . '<th>Amount</th>'
        . '</tr>';

$JouClass->ShowTable();

echo '</table></td></tr><tr><td colspan="5">Enter -ve to Reverse the journal Effect';
echo '<div><input type="submit" name="update" value="Update"/>  '
. '  <input type="submit" name="addline" value="Add Line"  onclick="return confirm(\''._('Do you want to add a new line ?').'\');" />'
. '  <input type="submit" name="cancel" value="Cancel Entries"  onclick="return confirm(\''._('Do you want to cancel these entries ?').'\');" />'
. '  <input type="submit" name="savejournal" value="Save Journal"  onclick="return confirm(\''._('Do you want to save this journal ?').'\');" />'
. '  </div></td></tr></table></div></form>';
}
include('includes/footer.inc');


class journalentries {
    var $I; 
    var $JOURNAL;
    var $JournalArray=array();
    
    function __construct() {
          $this->I = $_SESSION['JournalEntryIndexNo'];
    }
    
    function getrates($fromcurrency,$tocurrency){
        
        $FromRate =$this->ExtractRate($fromcurrency);
        $Torate =$this->ExtractRate($tocurrency);
        
        return ($Torate/$FromRate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT rate FROM currencies WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
       
    function GetBankDetails($accountCode){
         Global $db;
         
        $resultindex=DB_query("SELECT `BankAccounts`.`currency`,`currencies`.`rate`
        FROM `BankAccounts` join `currencies` on `BankAccounts`.`currency`=`currencies`.`currabrev` where `accountcode`='".$accountCode."'", $db);
        
        $row=DB_fetch_row($resultindex);
        $BankArray['currency']=$row[0];
        $BankArray['rate']=$row[1];
        
        return $BankArray;
    }
       
    function GetCustomerCurrency($itemcode){
        global $db;
        
        $ResultIndex = DB_query("select `curr_cod` FROM `debtors` where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $postingGroups['curr_cod'] = $rows[0];
        
        return $postingGroups;
    }
       
    function GetSupplierCurrency($itemcode){
        global $db;
        
        $ResultIndex = DB_query("select `curr_cod` FROM `creditors` where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $postingGroups['curr_cod'] = $rows[0];
        
        return $postingGroups;
    }
       
    function NextJournal() {
        $_SESSION['JournalEntryIndexNo']++;
        $this->I = $_SESSION['JournalEntryIndexNo'];
 
        $_SESSION['JournalEntryDetails'][$_SESSION['JournalEntryIndexNo']]= 
                    array('account'=>"",
                         'accountdescription'=>"",
                         'acctype'=>"",
                         'itemcode'=>"",
                         'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
              
        return $this->JOURNAL ;
    }
    
    Function NewJournal(){
        $_SESSION['JournalEntryDetails']=array();
        $_SESSION['JournalEntryIndexNo']= 0;
        $this->I = $_SESSION['JournalEntryIndexNo'];
          
        $_SESSION['JournalEntryDetails'][$this->I] =
                    array('account'=>"",
                          'accountdescription'=>"",
                          'acctype'=>"",
                          'itemcode'=>"",
                          'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
        
        return $this->JOURNAL;
    }
    
         
    function PersonalAccounts($itemcode,$type,$db){
        $array=array();
        
        if($type=='debtors'){
               $sql="SELECT `itemcode`,`customer` FROM `debtors` where `itemcode`='".$itemcode."'";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        } 
        
        if($type=='creditors'){
               $sql="SELECT `itemcode`,`customer` FROM `creditors` where (IsEmployee is null) and `itemcode`='".$itemcode."'";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        }
        
        if($type=='employee'){
            $sql="SELECT `itemcode`,`customer` FROM `creditors` where IsEmployee=1 and `itemcode`='".$itemcode."'";
            $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
    
        }
        
        if($type=='bank'){
               $sql="SELECT `accountcode`,`bankName` FROM `BankAccounts` where `accountcode`='".$itemcode."'";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        }
        
        if($type=='GL'){
               $sql="SELECT `accno`,`accdesc` FROM `acct` where `accno`='".$itemcode."' ";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        }
       return $array;
    }
        
    Function GLentries($account,$acctype,$itemcode,$amount){
        Global $db;
        
        $gl = $this->GetGL($account,$db);
        $pacct = $this->PersonalAccounts($itemcode,$acctype,$db);
        
         $_SESSION['JournalEntryDetails'][$this->I] = 
            array('account'=>$gl['accno'],
                  'accountdescription'=>$gl['accdesc'],
                  'acctype'=>$acctype,
                  'itemcode'=>$pacct['accno'],
                  'amount'=>$amount);
        
         $this->set();
         
         $this->JOURNAL = $_SESSION['JournalEntryDetails'];
    }
       
     Function GetGL($account,$db){
        $array= array();
        
        $ResultIndex = DB_query("SELECT `accno`,`accdesc` FROM `acct` where `accno`='".$account."' ", $db);
        $row = DB_fetch_row($ResultIndex);
        
        $array['accno']=$row[0];
        $array['accdesc']=$row[1];
        
        
        return $array;
    }
  
    Function cmbGL($objname,$value,$default=false){
        Global $db;
      
        
        $BalanceSheet=array();
        $BalanceSheet[0]=":BS";
        $BalanceSheet[1]=":P&L";
        $BalanceSheet[2]=":Suspense";
      
        if($default==false){
          $sql = "Select accno,CAST(RTRIM(`accdesc`) AS CHAR(50)) as accdesc,ReportCode,ReportStyle,`balance_income` FROM `acct` "
              . " where `ReportStyle`=0 and `direct`=1 and `inactive`=0 order by accdesc,ReportCode asc";
        }else{
          $sql = "Select accno,CAST(RTRIM(`accdesc`) AS CHAR(50)) as accdesc,ReportCode,ReportStyle,`balance_income` FROM `acct` "
               . " where accno='".$default."' order by accdesc,ReportCode asc";
        }
                     
       
        $ResultIndex = DB_query($sql, $db);
        $array='<select name="'.$objname.'" onchange="ReloadForm(Journal.update)"><option></option>';
        while($row = DB_fetch_array($ResultIndex)){
            $array .='<option value="'.$row['accno'].'" '.(trim($row['accno'])==trim($value)?'selected="selected"':'').'>'.$row['accdesc'].' ('.$BalanceSheet[$row['balance_income']].')</option>';
        }
        
        $array .='</select>';
    
        return $array;
    }
        
    function cmbPersonal($type,$objname,$value){
        Global $db;
        
        $BalanceSheet=array();
        $BalanceSheet[0]="Balance Sheet";
        $BalanceSheet[1]="Profit and Loss";
    
        $array ='<select name="'.$objname.'"  onchange="ReloadForm(Journal.update)">';
        
        if($type=='debtors'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `debtors` order by customer", $db);
            while($row = DB_fetch_array($ResultIndex)){
             $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';       
               
               $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
        
        }
            if($type=='creditors'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `creditors`  where IsEmployee is null order by customer", $db);
             while($row = DB_fetch_array($ResultIndex)){
                $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';
                $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
        } 
        
        if($type=='employee'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `creditors` where IsEmployee=1  order by customer asc", $db);
             while($row = DB_fetch_array($ResultIndex)){
                $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';
                $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
          } 
         
          if($type=='bank'){
                $ResultIndex = DB_query("SELECT `accountcode`,`bankName` FROM `BankAccounts`", $db);
                 while($row = DB_fetch_array($ResultIndex)){
                    $selected=(trim($row['accountcode']) == trim($value))?'selected="selected"':'';
                   $array .='<option value="'.$row['accountcode'].'" '.$selected.'>'.$row['bankName'].'</option>';
                }
           }
        
        
        if($type=='GL'){
                $ResultIndex = DB_query("Select accno,accdesc,ReportCode,ReportStyle,`balance_income` FROM `acct` where `ReportStyle`=0 and `direct`=1 and `inactive`=0 order by ReportCode,accdesc asc", $db);
                while($row = DB_fetch_array($ResultIndex)){
                    $selected= (trim($row['accno'])==trim($value)?'selected="selected"':'');
                    $array .='<option value="'.$row['accno'].'" '.$selected.'>'.$row['accdesc'].' "'.$BalanceSheet[$row['balance_income']].'"</option>';
                }
        }
        
          $array .='</select>';
                     
        
        return $array;
    }
       
    function types($objname,$value){
        
        $array = '<select name="'.$objname.'" onchange="ReloadForm(Journal.update)"><option></option>'
               . '<option value="debtors" '.($value=='debtors'?'selected="selected"':'').'>Accounts Receivable</option>'
               . '<option value="creditors" '.($value=='creditors'?'selected="selected"':'').'>Accounts Payable</option>'
               . '<option value="employee" '.($value=='employee'?'selected="selected"':'').'>Employee</option>'
               . '<option value="bank" '.($value=='bank'?'selected="selected"':'').'>Bank Account</option>'
               . '<option value="GL" '.($value=='GL'?'selected="selected"':'').'>General Ledger</option>'
               . '</select>';
     
       return $array;
    }
    
    function set(){
        
        $this->cmbGL('account'.$this->I,$_POST['account'.$this->I]);
        $this->types('acctype'.$this->I,$_POST['acctype'.$this->I]);
        $this->cmbPersonal($_POST['acctype'.$this->I],'itemcode'.$this->I,$_POST['itemcode'.$this->I]);
  
    }
       
    function ShowTable(){
        if(isset($this->JOURNAL)){
                foreach ($this->JOURNAL as $key => $value) {
                    $NewKey=$key+1;
                    echo sprintf('<tr>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td><input type="text" class="number" name="amount'.$key.'" value="%s" size="10" maxlength="11"/></td></tr>',
                        $NewKey,
                        $this->cmbGL('account'.$key,$value['account']),
                        $value['accountdescription'],
                        $this->types('acctype'.$key,$value['acctype']),
                        $this->cmbPersonal($value['acctype'],'itemcode'.$key,$value['itemcode']),
                        $value['amount']);

       }
        }
    }
       
    Function PostingGroup($flag,$acount){
        global $db;
        
        switch ($flag) {
            case 1:
                // debtors
                 $SQL="SELECT
                    `postinggroups`.`debtorsaccount`
                    FROM `debtors` 
                    join `postinggroups` on `debtors`.`customerposting`=`postinggroups`.`code`
                    where `itemcode`='".$acount."'";

                break;
            case 2: 
                // creditors
               $SQL="SELECT
                    `arpostinggroups`.creditorsaccount
                    FROM `creditors` 
                    join `arpostinggroups` on `creditors`.`supplierposting`=`arpostinggroups`.`code`
                    where `itemcode`='".$acount."'";

                break;
            case 3: 
                // bank
                $SQL="SELECT `PostingGroup` FROM `BankAccounts` where accountcode='".$acount."'";

                break;
            case 4: 
                // bank
                $SQL="SELECT `accno` FROM `acct` where `accno`='".$acount."' ";
               break;
            default:
                break;
        } 
        
        $ResultIndex=DB_query($SQL,$db);
        $pgrow=DB_fetch_row($ResultIndex);
        
        return $pgrow[0];
        
    }
    
    Function DeleteJournal($Documenttype,$TransDate,$DocumentNO){
        global $db;
     $SQLARRAY=array();
       
    $SQLARRAY[]="Delete FROM `JournalEntries` where `JournalNo` in (select `journalno` from `Generalledger` G where  G.`Docdate`='".$TransDate."'  and  G.`DocumentNo`='".$DocumentNO."')";
   
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$Documenttype."' and  `journalno` in (select `journalno` from `Generalledger` G where  G.`Docdate`='".$TransDate."'  and  G.`DocumentNo`='".$DocumentNO."')";
           
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$Documenttype."' and  `journalno` in (select `journal` from `BankTransactions` G where G.`Docdate`='".$TransDate."'  and  G.`DocumentNo` ='".$DocumentNO."')";
    
    $SQLARRAY[]="delete from `BankTransactions` where `doctype`='".$Documenttype."' and  `journal` in (select `journal` from `BankTransactions` G where G.`Docdate`='".$TransDate."'  and G.`DocumentNo` ='".$DocumentNO."')";
    
    $SQLARRAY[]="delete from `BankTransactions` where `doctype`='".$Documenttype."' and  `journal` in (select `journal` from `creditorsledger` C  where  C.`date`='".$TransDate."'  and  C.`systypes_1`='".$Documenttype."' and  C.`invref`='".$DocumentNO."')";
    
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$Documenttype."' and  `journalno` in (select `journal` from `creditorsledger` C where C.`date`='".$TransDate."'  and  C.`systypes_1`='".$Documenttype."' and  C.`invref`='".$DocumentNO."')";
     
    $SQLARRAY[]="delete from `creditorsledger` where `systypes_1`='".$Documenttype."' and `journal` in (select `journal` from `creditorsledger` C where C.`date`='".$TransDate."' and  C.`systypes_1`='".$Documenttype."' and C.`invref`='".$DocumentNO."')";
    
    $SQLARRAY[]="delete from `SupplierStatement` where `Documenttype`='".$Documenttype."' and  `Date`='".$TransDate."'  and   `Documentno`='".$DocumentNO."'";
    
    $SQLARRAY[]="delete from `BankTransactions` where `doctype`='".$Documenttype."' and  `journal` in (select `journal` from `debtorsledger` C where  C.`date`='".$TransDate."' and  C.`invref`='".$DocumentNO."')";
  
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$Documenttype."' and  `journalno` in (select `journal` from `debtorsledger` C where C.`date`='".$TransDate."' and  C.`invref`='".$DocumentNO."')";
     
    $SQLARRAY[]="delete from `debtorsledger` where `journal` in (select `journal` from `debtorsledger` C where C.`date`='".$TransDate."' and  C.`invref`='".$DocumentNO."')";
    
    $SQLARRAY[]="delete from `CustomerStatement` where `Date`='".$TransDate."' and  `Documentno`='".$_POST['DocumentNO']."'  and `Documenttype`='".$Documenttype."'";
      
    $SQLARRAY[]="delete from `stockledger` where `date`='".$TransDate."' and  `invref`='".$DocumentNO."'";
 
    $SQLARRAY[]="delete from `tanktrans` where `date`='".$TransDate."' and  `batchno`='".$DocumentNO."'";
 
    $SQLARRAY[]="Delete FROM `SupplierStatement` where `Documenttype`='".$Documenttype."' and `JournalNo` in (select `journal` FROM `pettdoc` where `petteycashno`='".$DocumentNO."')";
    $SQLARRAY[]="Delete FROM `creditorsledger` where `systypes_1`='".$Documenttype."' and `journal` in (select `journal` FROM `pettdoc` where `petteycashno`='".$DocumentNO."')";
    
    $SQLARRAY[]="Delete FROM `BankTransactions` where `doctype`='".$Documenttype."' and  `journal` in (select `journal` FROM `pettdoc` where `petteycashno`='".$DocumentNO."')";
    $SQLARRAY[]="Delete FROM `Generalledger` where `journalno` in (select `journal` FROM `pettdoc` where `petteycashno`='".$DocumentNO."')";
    
    $SQLARRAY[]="Delete FROM `CustomerStatement` where `JournalNo` in (select `journal` FROM `pettdoc` where `petteycashno`='".$DocumentNO."')";
    $SQLARRAY[]="Delete FROM `debtorsledger` where `journal` in (select `journal` FROM `pettdoc` where `petteycashno`='".$DocumentNO."')";
    
    $SQLARRAY[]="Delete FROM `pettdoc` where `petteycashno`='".$DocumentNO."'";
   
    $SQLARRAY[]="Delete FROM `PurchaseLine` where `documentno` ='".$DocumentNO."'";
    $SQLARRAY[]="Delete FROM `PurchaseHeader` where `documentno` ='".$DocumentNO."'";
   
    $SQLARRAY[]="Delete FROM `SalesLine` where `documentno` ='".$DocumentNO."'";
    $SQLARRAY[]="Delete FROM `SalesHeader` where `documentno` ='".$DocumentNO."'";
    
    
    
     $rowcount=0;
    DB_Txn_Begin($db);
    foreach ($SQLARRAY as $SQL) {
      $ResultIndex=DB_query($SQL, $db);  
      $rowcount += DB_num_rows($ResultIndex);
    }
    
    if(DB_error_no($db)==0){
        DB_Txn_Commit($db);
    }else{
        DB_Txn_Rollback($db);
    }
    

return  $rowcount;
    }
    
    Function SaveJournal(){
        Global $db;
        
        $affectedrows = $this->DeleteJournal('0',FormatDateForSQL($_POST['date']),$_SESSION['SelectedJournal']);
        if($affectedrows>0){
            
         unset($_SESSION['SelectedJournal']);
         $periodno = GetPeriod($_POST['date'],$db,true);
         $JOURNAL  = GetNextTransNo(0,$db);
         $Conrate  = $this->ExtractRate($_POST['currency']);
       
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
        foreach ($this->JOURNAL as $key => $row)  {
           
          $this->JournalArray[]=  sprintf("INSERT INTO `JournalEntries`
          (`Docdate`,`JournalNo`,`Currency`,`Dimension_1`,`Dimension_2`,`Account`,`Balaccount`,`transtype`,`itemcode`,`narration`,`amount`)
           VALUES ('%s','%s','%s','%s','%s','%s' ,'%s','%s','%s','%s',%f)", FormatDateForSQL($_POST['date']),$_POST['JournalNo'],$_POST['currency'],$_POST['DimensionOne'],$_POST['DimensionTwo'],
           $row['account'],$row['account'], $row['acctype'],$row['itemcode'],$_POST['comments'],$row['amount']);
           
          if($row['acctype']=='GL'){
           $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
            $_POST['JournalNo'],'0',$row['account'],$row['itemcode'],abs($row['amount']),
            $_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
            (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
            $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
          }
          
          
          if($row['acctype']=='bank'){
            $BankD = $this->GetBankDetails($row['itemcode']);
            $Conrate = $this->getrates($BankD['currency'],$_POST['currency']) ;
            $bankpostingaccount=$this->PostingGroup(3,$row['itemcode']);
            
            if($row['amount']>0){
                $debit  = $row['account'] ;
                $credit = $bankpostingaccount ;
            }else{
                $debit  = $bankpostingaccount ;
                $credit = $row['account'];
            }
            
            $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
            $_POST['JournalNo'],'0',$debit,$credit,abs($row['amount']),$_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
            (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
            $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
            
            
            $this->JournalArray[]=  sprintf("INSERT INTO `BankTransactions`
           (`bankcode`,`DocDate`,`doctype`,`DocumentNo`,`TransType`,`journal`,
           `amount`,`narrative` ,`exchangerate`,`itemcode`) values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
            $row['itemcode'],FormatDateForSQL($_POST['date']),'0',$_POST['JournalNo'],$row['acctype'],$JOURNAL,
            ($row['amount'] * $Conrate)* -1,$_POST['comments'] ,$Conrate ,$row['account']);
         }
        
          if($row['acctype']=='debtors'){
                 $Cust = $this->GetCustomerCurrency($row['itemcode']);
                 $Conrate = $this->getrates($Cust['curr_cod'],$_POST['currency']) ;
                 $bankpostingaccount=$this->PostingGroup(1,$row['itemcode']);
            
                 if($row['amount']>0){
                       $debit  = $row['account'] ;
                       $credit = $bankpostingaccount ;
                 }else{
                     $debit  = $bankpostingaccount ;
                     $credit = $row['account'];
                 }
            
                $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
               (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
               `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,`dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
                '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
                $_POST['JournalNo'],'0',$debit,$credit,abs($row['amount']),$_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
                (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
                $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );

                 
                 $this->JournalArray[]=  sprintf("INSERT INTO `CustomerStatement`
                (`Date`,`Documentno` ,`Documenttype` ,`Accountno`,`Grossamount` ,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`) VALUES ('%s','%s','%s' ,'%s' ,%f ,'%s' ,'%s' ,'%s' ,'%s')",
                FormatDateForSQL($_POST['date']),$_POST['JournalNo'],'0',$row['itemcode'],($row['amount'] * $Conrate)* -1,
                $JOURNAL,$_POST['DimensionOne'],$_POST['DimensionTwo'],$_POST['currency']);
                 
                 
                $this->JournalArray[]=sprintf("INSERT INTO `debtorsledger`
                (`date`,`details`,`flag`,`invref` ,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
                 VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",FormatDateForSQL($_POST['date']),
                 $_POST['comments'],'DR',$_POST['JournalNo'],$row['itemcode'],($row['amount'] * $Conrate)* -1,'J',$_POST['currency'],
                 $Conrate,'J',$periodno,$JOURNAL,'J','0', $row['account'] );
              
          }
          
          if($row['acctype']=='creditors' || $row['acctype']=='employee'){
                 $Cust = $this->GetSupplierCurrency($row['itemcode']);
                 $Conrate = $this->getrates($Cust['curr_cod'],$_POST['currency']) ;
                 $bankpostingaccount=$this->PostingGroup(2,$row['itemcode']);
            
                if($row['amount']>0){
                   $debit  = $row['account'] ;
                   $credit = $bankpostingaccount ;
                 }else{
                    $debit  = $bankpostingaccount ;
                    $credit = $row['account'];
                }
            
                $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
               (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,`bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
               `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
                '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno, $_POST['JournalNo'],'0',$debit,$credit,abs($row['amount']),$_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
                (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
                $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );

                 
                 
      
               $this->JournalArray[] = sprintf("INSERT INTO `SupplierStatement`
                (`Date`,`Documentno` ,`Documenttype` ,`Accountno`,`Grossamount` ,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`) VALUES
                ('%s','%s','%s' ,'%s' ,%f ,'%s' ,'%s' ,'%s' ,'%s')", FormatDateForSQL($_POST['date']),$_POST['JournalNo'],'0',$row['itemcode'],($row['amount'] * $Conrate)* -1,
                $JOURNAL,$_POST['DimensionOne'],$_POST['DimensionTwo'],$_POST['currency']);
                 
                 
                $this->JournalArray[] = sprintf("INSERT INTO `creditorsledger`
                (`date`,`details`,`flag`,`invref` ,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
                 VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",FormatDateForSQL($_POST['date']),
                 $_POST['comments'],'DR',$_POST['JournalNo'],$row['itemcode'],($row['amount'] * $Conrate)* -1,'J',$_POST['currency'],
                 $Conrate,'J',$periodno,$JOURNAL,'J','0', $row['account'] );
          }
 
       }
        }
        return  $this->JournalArray;
    }
    
         
}


function Getlist(){
    global $RootPath,$Theme;
    
    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Edit General Journal') .'" alt="" />' . ' ' . _('Edit General Journal') . '</p>';
    echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">'
         . '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
    echo '<table class="table table-bordered"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Enter The Document No</caption>';
    echo '<tr><td>Document NO</td>'
       . '<td><input type="text"  maxlength="10" size="10" name="DocumentNO" id="DocumentNO"/></td></tr>';
    echo '<tr><td>Document Dated</td>'
       . '<td><input type="text" class="date" id="date" name="date" size="11" maxlength="10" autofocus="autofocus"  alt="'.$_SESSION['DefaultDateFormat'].'"   onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
    echo '</table></td></tr></table>';
    echo '<div><input type="button" id="selectjournaltoedit" value="'._('Select Entry').'"/></div>';
    echo '</form><span id="journalspan"></span>' ;
}


 class AjaxRawUnsafeCrypto{
    const METHOD = 'aes-256-ctr';

    /**
     * Encrypts (but does not authenticate) a message
     * 
     * @param string $message - plaintext message
     * @param string $key - encryption key (raw binary expected)
     * @param boolean $encode - set to TRUE to return a base64-encoded 
     * @return string (raw binary)
     */
    public static function encrypt($message, $key, $encode = false)
    {
        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        if ($encode) {
            return base64_encode($nonce.$ciphertext);
        }
        return $nonce.$ciphertext;
    }

    /**
     * Decrypts (but does not verify) a message
     * 
     * @param string $message - ciphertext message
     * @param string $key - encryption key (raw binary expected)
     * @param boolean $encoded - are we expecting an encoded string?
     * @return string
     */
    public static function decrypt($message, $key, $encoded = false)
    {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        return $plaintext;
    }
}
 
?>
