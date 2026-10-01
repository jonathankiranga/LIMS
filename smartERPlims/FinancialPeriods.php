<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php');
// To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Financial Periods');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/AccountBalance.inc');

$REsults = DB_query('Select periodno from `FinancialPeriods` B where B.closed=0 Group by periodno',$db);
$financialPeriods = DB_fetch_row($REsults);
$YearPeriod = is_null($financialPeriods[0])?0:$financialPeriods[0];

if(is_null($financialPeriods[0])){
   CreateNewFinancialPeriod();
}

DB_Profit_loss();
     
$_SESSION['p&lbalancecfwdclosing'] = 0;
 


if(isset($_POST['submit'])){
    DB_Txn_Begin($db);
    
    DB_query("Update `config` set `confvalue`='".FormatDateForSQL($_POST['FinancialYearBegins'])."'  where `confname`='FinancialYearBegins'", $db);
    
    if(Is_date($_POST['FinancialYearBegins'])){
        RollOverFinancialPeriods();
        CreateNewFinancialPeriod();
    }
    
     $ob = new journalentries();
     $SQL= $ob->SaveJournal();
     
     foreach ($SQL as $sql) {
        DB_query($sql,$db);
     }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('The system has not created the new period','warn');
    }else{
        DB_Txn_Commit($db);
    }
    
}

If(isset($_POST['CloseMonth'])){
    
    DB_query("UPDATE companies set `PeriodRollover`=DATE_ADD(`PeriodRollover`, INTERVAL 1 MONTH) WHERE coycode=1",$db);

    DB_query("UPDATE `FinancialPeriods` set `closed`=1 where `end_date` <=(select `PeriodRollover`"
            . " from companies WHERE coycode=1)",$db);
}

$ForceConfigReload = True; 
include('includes/GetConfig.php');
$ForceConfigReload = False;

  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Financial Periods') .'" alt="" />'. _('Financial Periods') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post" id="FP"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
  
  $ResultIndex=DB_query("SELECT `start_date`,`end_date`,`Name`,`newyear`,`closed`,`periodno`  
 FROM `FinancialPeriods` order by start_date desc limit 12", $db);
 
  
  echo '<table class="table table-bordered">'
          . '<thead><tr>'
          . '<th>Start date</th>'
          . '<th>End Date</th>'
          . '<th>Name</th>'
          . '<th>First month<br/> of the <br/>Financial <br/>Period</th>'
          . '<th>Is it Closed ?</th></tr>'
          . '</thead>';
  
    if(!isset($_POST['ClosingDate'])){
       $selectdate=true;
    }
        
  while($row=DB_fetch_array($ResultIndex)){
       if(isset($selectdate)){
           $_POST['ClosingDate'] = ConvertSQLDate($row['end_date']);
        }
      
      echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
           ConvertSQLDate($row['start_date']), ConvertSQLDate($row['end_date']), $row['Name'], 
           (($row['newyear']==true)?'First Month':''), (($row['closed']==true)?'Closed':''));
  }
  
 
echo '<table class="table table-bordered">';
echo '<tr><td colspan="4">Next Period begins 12 months from :'
   . '<input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" value="'.ConvertSQLDate($_SESSION['FinancialYearBegins']).'" name="FinancialYearBegins" required="required"/></td></tr></table>';
  echo '<tfoot><tr><td>'
          . '<input type="submit" name="update" value="Refresh"/>'
          . '<input type="submit" name="CloseMonth" value="Close Month"   '
          . ' onclick="return confirm(\''._('Are you sure you wish to Close this Month. You cannot undo this action ?').'\');" />'
          . '<input type="submit" name="submit" value="Close and create Financial Period"   '
          . ' onclick="return confirm(\''._('Are you sure you wish to Close this Financial Period. You cannot undo this action ?').'\');" /></td>'
          . '</tr>';
  echo '</tfoot></table>';

echo '</div></form>';

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
                         'balaccount'=>"",
                         'balaccountdescription'=>"",
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
                          'balaccount'=>"",
                          'balaccountdescription'=>"",
                          'acctype'=>"",
                          'itemcode'=>"",
                          'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
        
        return $this->JOURNAL;
    }
    
    Function GetGL($account,$db){
        $array= array();
        
        $ResultIndex = DB_query("SELECT `accno`,`accdesc` FROM `acct` where `accno`='".$account."'", $db);
        $row = DB_fetch_row($ResultIndex);
        
        $array['accno']=$row[0];
        $array['accdesc']=$row[1];
        
        
        return $array;
    }
        
    function PersonalAccounts($itemcode,$type,$db){
        $array= array();
        
        if($type=='debtors'){
             $sql="SELECT `itemcode`,`customer` FROM `debtors` where `itemcode`='".$itemcode."'";
             $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        } 
        
        if($type=='creditors'){
            $sql="SELECT `itemcode`,`customer` FROM `creditors` where `itemcode`='".$itemcode."'";
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
        
      
        
        
        return $array;
    }
        
    Function GLentries($account,$balaccount,$acctype,$itemcode,$amount){
        Global $db;
        
        $gl = $this->GetGL($account,$db);
        $balgl = $this->GetGL($balaccount,$db);
        $pacct = $this->PersonalAccounts($itemcode,$acctype,$db);
         $_SESSION['JournalEntryDetails'][$this->I] = 
            array('account'=>$gl['accno'],
                  'accountdescription'=>$gl['accdesc'],
                  'balaccount'=>$balgl['accno'],
                  'balaccountdescription'=>$balgl['accdesc'],
                  'acctype'=>$acctype,
                  'itemcode'=>$pacct['accno'],
                  'amount'=>$amount);
        
         $this->set();
         
         $this->JOURNAL = $_SESSION['JournalEntryDetails'];
    }
       
    Function cmbGL($objname,$value){
        Global $db;
        
        $array='<select name="'.$objname.'" onchange="ReloadForm(FP.update)">';
               
        $ResultIndex = DB_query("SELECT `accno`,`accdesc` FROM `acct` where `ReportStyle`=0 and `balance_income`=0 and `inactive`=0 order by accdesc", $db);
        while($row = DB_fetch_array($ResultIndex)){
            $array .='<option value="'.$row['accno'].'" '.(trim($row['accno'])==trim($value)?'selected="selected"':'').'>'.$row['accdesc'].'</option>';
        }
        
        $array .='</select>';
    
        return $array;
    }
    
    Function cmbPL($objname,$value){
        Global $db;
        
        $array='<select name="'.$objname.'" onchange="ReloadForm(FP.update)">';
               
        $ResultIndex = DB_query("SELECT `accno`,`accdesc` FROM `acct` where `ReportStyle`=0 and `balance_income`=1 and `inactive`=0 order by accdesc", $db);
        while($row = DB_fetch_array($ResultIndex)){
            $array .='<option value="'.$row['accno'].'" '.(trim($row['accno'])==trim($value)?'selected="selected"':'').'>'.$row['accdesc'].'</option>';
        }
        
        $array .='</select>';
    
        return $array;
    }
        
    function cmbPersonal($type,$objname,$value){
        Global $db;
              
        $array ='<select name="'.$objname.'"  onchange="ReloadForm(FP.update)">';
        if($type=='debtors'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `debtors` order by customer", $db);
            while($row = DB_fetch_array($ResultIndex)){
             $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';       
               
               $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
        
        }
            if($type=='creditors'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `creditors`", $db);
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
        
        
          $array .='</select>';
                     
        
        return $array;
    }
       
    function types($objname,$value){
        
        $array = '<select name="'.$objname.'" onchange="ReloadForm(FP.update)"><option></option>'
               . '<option value="debtors" '.($value=='debtors'?'selected="selected"':'').'>Accounts Receivable</option>'
               . '<option value="creditors" '.($value=='creditors'?'selected="selected"':'').'>Accounts Payable</option>'
               . '<option value="bank" '.($value=='bank'?'selected="selected"':'').'>Bank Account</option>'
                . '</select>';
     
       return $array;
    }
    
    function set(){
        
        $this->cmbGL('account'.$this->I,$_POST['account'.$this->I]);
        $this->cmbGL('balaccount'.$this->I,$_POST['balaccount'.$this->I]);
        $this->types('acctype'.$this->I,$_POST['acctype'.$this->I]);
        $this->cmbPersonal($_POST['acctype'.$this->I],'itemcode'.$this->I,$_POST['itemcode'.$this->I]);
  
    }
       
    function ShowTable(){
        if(isset($this->JOURNAL)){
                foreach ($this->JOURNAL as $key => $value) {
                    $NewKey=$key+1;
                    
                    echo sprintf('<tr>'
                        . '<td>%s</td>'
                        . '<td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" value="'.$_POST['ClosingDate'].'" name="ClosingDate" size="10" required="required"/></td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td><input type="text" class="number" name="amount'.$key.'" value="%s" size="10" maxlength="11" readonly="readonly"/></td></tr>',
                        $NewKey,
                        $this->cmbPL('balaccount'.$key,$value['balaccount']),
                        $value['accountdescription'],
                        $this->cmbGL('account'.$key,$value['account']),
                        $value['balaccountdescription'],
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
                $SQL="SELECT `PostingGroup` FROM `dbo`.`BankAccounts` where accountcode='".$acount."'";

                break;
            default:
                break;
        } 
        
        $ResultIndex=DB_query($SQL,$db);
        $pgrow=DB_fetch_row($ResultIndex);
        
        return $pgrow[0];
        
    }
    
    
    Function SaveJournal(){
        Global $db;
        
         $periodno = GetPeriod($_POST['ClosingDate'],$db,false);
         $JOURNAL  = GetNextTransNo(0,$db);
         
         $sql = "SELECT currabrev FROM currencies where `rate`=1";
         $result = DB_query($sql,$db);
         $Curow=DB_fetch_row($result);
         $_POST['currency']=$Curow[0];
                 
       $this->JOURNAL=$_SESSION['JournalEntryDetails'];
       foreach ($this->JOURNAL as $key => $row)  {
           
         if(mb_strlen($row['account'])>0 and mb_strlen($row['balaccount'])>0){
               
           $this->JournalArray[]=  sprintf("INSERT INTO `JournalEntries`
          (`Docdate`,`JournalNo`,`Currency`,`Dimension_1`,`Dimension_2`,`Account`,`BalAccount`,`transtype`,`itemcode`,`narration`,`amount`)
           VALUES ('%s','%s','%s','%s','%s','%s' ,'%s' ,'%s','%s','%s',%f)", FormatDateForSQL($_POST['ClosingDate']),$_POST['JournalNo'],$_POST['currency'],$_POST['DimensionOne'],$_POST['DimensionTwo'],
           $row['account'], $row['balaccount'], $row['acctype'],$row['itemcode'],$_POST['comments'],$row['amount']);
                          
            $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['ClosingDate']),$periodno,
            $_POST['JournalNo'],'0',$row['account'], $row['balaccount'],abs($row['amount']),
            $_POST['currency'],1,(($row['acctype']=='debtors')?$row['itemcode']:''),
            (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
            $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
          
         }
          
       }
        
        return  $this->JournalArray;
    }
    
         
}

?>
