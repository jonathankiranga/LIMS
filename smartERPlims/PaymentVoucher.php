<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Payment voucher');
include('includes/header.inc');
include('includes/budgetbalance.php');

$TRBanks = new klassbanksAccounts();
$chart = new Classpaymentvoucher();
 
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Payment Voucher Preparation') .'" alt="" />' . ' ' . _('Payment Voucher Preparation') . '</p>'
        . '<div class="centre">';

if(isset($_GET['SelectedCustomer'])){
    $SelectedCustomer = $_GET['SelectedCustomer'];
}elseif(isset($_POST['VendorID'])){
   $SelectedCustomer = $_POST['VendorID'];
}elseif(isset($_POST['SelectedCustomer'])){
    $SelectedCustomer = $_POST['SelectedCustomer'];
}

if(isset($_GET['edit'])) {
    $selectedrec = $_GET['edit'];
}elseif(isset($_POST['edit'])){
    $selectedrec = $_POST['edit'];
}
 
if(isset($_GET['new'])){
    $_SESSION['locked'] = true;
    $_POST['documentno'] = GetTempNextNo(53);
    prnMsg('Payment Voucher :'.$_POST['documentno'].' has been created');
}

if($_POST['totalamount']>0){
        
        if(isset($_POST['delete'])){
           if($_SESSION['locked'] == true and mb_strlen($_POST['edit'])>0){
                  
            $chart->JournalArray=array();
            $chart->JournalArray[]="Delete from `paymentvoucherline` where `journal`='".$_POST['edit']."'";
            $chart->JournalArray[]="Delete from `paymentvoucherheader` where `journal`='".$_POST['edit']."'";
         
            DB_Txn_Begin($db);
            foreach ($chart->JournalArray as $value) {
               DB_query($value,$db);
            }

            if(DB_error_no($db)>0){
                DB_Txn_Rollback($db);
            }else{
                DB_Txn_Commit($db);
                unset($_POST);
                $_SESSION['locked'] = false ;
            }
            
          }
              
        }elseif(isset($_POST['receiptedit'])){
            
            if($_SESSION['locked'] == true){
                $chart->JournalArray = array();
                $chart->Receipt_Journal= GetNextTransNo(0,$db);
                $chart->GetFormUpdate();
                
            DB_Txn_Begin($db);
            foreach ($chart->JournalArray as $value) {
               DB_query($value,$db);
            }

            if(DB_error_no($db)>0){
                DB_Txn_Rollback($db);
            }else{
                DB_Txn_Commit($db);
                
                unset($_POST);
                $_SESSION['locked'] = false ;
            }
            
          }else{
             prnMsg('You could have attempted to re submit the page more than once','warn');
          }
          
        }elseif(isset($_POST['receipt']) ){
            if($_SESSION['locked'] == true){
                $journalno = GetNextTransNo(0,$db);
                $chart->JournalArray=array();
                $chart->Receipt_Journal=  $journalno;
                $chart->GetForm();
                                
            DB_Txn_Begin($db);
            foreach ($chart->JournalArray as $value) {
               DB_query($value, $db);
            }

            if(DB_error_no($db)>0){
                DB_Txn_Rollback($db);
            }else{
                DB_Txn_Commit($db);
                
               echo '<a href="PDFpaymentvoucher.php?jonal='. $journalno .'">Print Payment Voucher</a><br />';
               
               unset($_POST);
                $_SESSION['locked'] = false ;
            }
            
          }else{
             prnMsg('You could have attempted to re submit the page more than once','warn');
          }
        }
        
    }

if(isset($selectedrec)){
$_SESSION['locked'] = true;
prnMsg('Your editing Payment Voucher :'.$selectedrec);
       
$sql="SELECT
       `docno`
      ,`paymentvoucherheader`.`date`
      ,`creditors`.`customer`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`paymentvoucherheader`.`itemcode`
      ,`journal`
      ,`currency`
  FROM `paymentvoucherheader` 
  join `creditors` on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode`
  where `paymentvoucherheader`.`journal`='".$selectedrec."'";
     $ResultIndex=DB_query($sql,$db);
     $Row=DB_fetch_row($ResultIndex);
 
$_POST['documentno'] = $Row[0];
$_POST['date'] = ConvertSQLDate($Row[1]);
$_POST['reference'] = $Row[3];
$_POST['VendorID'] = $Row[6];
$_POST['VendorName'] = $Row[2];
$_POST['currencycode'] = $Row[8];
}

if(!isset($_POST['date'])){
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]);
}

  
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new Voucher click here</a>';
echo '</div><form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="custreseipts"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<input type="hidden" name="SelectedCustomer" value="'.$SelectedCustomer.'"/>';

if(isset($selectedrec)){
    echo '<input type="hidden" name="edit" value="'.$selectedrec.'"/>';
}

Echo '<table class="table-striped table-bordered">';
echo '<tr><td>Date</td><td colspan="4"><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>Document No</td><td colspan="4"><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required" /></td></tr>'
   . '<tr><td>Your Reference</td><td colspan="4"><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td><input tabindex="4" type="hidden" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"/>'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td>'
        . '<td>Supplier Name:</td><td><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code </td><td colspan="4"><input tabindex="6" type="text" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td></tr>';
       
echo $TRBanks->Get();
echo '</tr>';

if(isset($selectedrec) and (mb_strlen($selectedrec)>0)){
     
    $sql="SELECT 
        `SupplierStatement`.`Date`  ,
        `SupplierStatement`.`Documentno`  ,
        `SupplierStatement`.`Accountno`  ,
        `SupplierStatement`.`Grossamount`  ,
        `SupplierStatement`.`JournalNo`
      FROM `paymentvoucherheader` 
      join `paymentvoucherline` on `paymentvoucherheader`.`journal`=`paymentvoucherline`.`journal`
      join SupplierStatement on `paymentvoucherline`.`invoice_journal`=`SupplierStatement`.`JournalNo`
      where `paymentvoucherheader`.`journal`='".$selectedrec."'";
      
        if(isset($_GET['edit'])){
            $ResultIndex=DB_query($sql,$db);
             while($row=DB_fetch_array($ResultIndex)){
                $_POST['topay'][$row['JournalNo']]= abs($row['Grossamount']);
                
            }
        }
         
}elseif(mb_strlen($SelectedCustomer)>0) {

$sql="SELECT `Date`,`Documentno`,`Documenttype`,`Accountno`
      ,`Grossamount`,`JournalNo`,`Dimension_One` ,`Dimension_Two`
      ,(SELECT 
         IFNULL(sum(`amount`),0) FROM `PaymentsAllocation` 
         where `itemcode`=`SupplierStatement`.`Accountno` 
          and `date`=`SupplierStatement`.`date` 
          and `invoiceno`=`SupplierStatement`.`Documentno` 
          and `journalno`=`SupplierStatement`.`JournalNo`) as Pamount
      FROM `SupplierStatement` where
      `SupplierStatement`.`Accountno`='".$SelectedCustomer."'";

    
}

Echo '<tr><td colspan="4">'
        . '<table class="table-striped table-bordered"><tr>'
        . '<th>Date</th>'
        . '<th>Doc No</th>'
        . '<th>AMOUNT</th>'
        . '<th>Unpaid AMOUNT</th>'
        . '<th>Pay This</th>'
        . '</tr>';
     
$k=0; 
$TotalAmount=0;

$ResultIndex=DB_query($sql,$db);
if(DB_num_rows($ResultIndex)>0){
while($row=DB_fetch_array($ResultIndex)){
    
    $gross_amount = $row['Grossamount'];
    $p_amount = $row['Pamount'];
    
    if(($gross_amount+$p_amount)< 0){ 
        $amounttopay = ($gross_amount+$p_amount) ;
                     
        if(isset($_POST['topay'][$row['JournalNo']])){
            $amount = $_POST['topay'][$row['JournalNo']];
        }else{
            $amount='';
        }
        
        $TotalAmount += $amount;
        
        if($k==1){
            $k=0;
        } else {
          $k++;
        }
        
        
        $linerow = sprintf('<tr>'
        . '<td>%s</td>'
        . '<td>%s</td>'
        . '<td><input type="text" class="number" size="12" value="%s" readonly="readonly"/></td>'
        . '<td><input type="text" class="number" size="12" value="%s" readonly="readonly"/></td>'
        . '<td><input type="number" class="number increment" max="'.($amounttopay * -1).'" min="0" value="'.$amount.'" step="0.01" name="topay['.$row['JournalNo'].']"/></td>'
        . '</tr>',
                ConvertSQLDate($row['Date']), 
                $row['Documentno'], 
                number_format($row['Grossamount'] * -1,2), 
                number_format($amounttopay * -1,2));
        
        echo $linerow;
    } 
}
}


echo '<tr><td colspan="4">Total Amount Posted :</td><td>'
    . '<input type="text" class="number" value="'.$TotalAmount.'"  size="12"  name="totalamount" readonly="readonly"/>'
    . '</td></tr>';

echo '</table></td></tr><tr>';

    if(isset($selectedrec)){
        echo  '<td colspan="4"><input type="submit" name="submit" value="Calculate & Refresh"/>'
            . '<input type="submit" name="receiptedit" value="Edit Payment Voucher" onclick="return confirm(\''._('Do you want to EDIT this Payment Voucher ?').'\');" />';
        echo  '<input type="submit" name="delete" value="Delete Payment Voucher" onclick="return confirm(\''._('Do you want to "DELETE" this Payment Voucher ?').'\');" /></td>';
     }else{
        echo  '<td colspan="4"><input type="submit" name="submit" value="Calculate & Refresh"/>'
            . '<input type="submit" name="receipt" value="Create Payment Voucher" onclick="return confirm(\''._('Do you want to save this Payment Voucher ?').'\');" /></td>';
     }
     
echo '</tr></table>';
echo '</div></form>';
 
include('includes/footer.inc');






class Classpaymentvoucher {
    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio; 
    Var $Receipt_Journal;
    Var $JournalArray= array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postingGroups;
    Var $checkbalance=0;
    Var $DimensionsBudget;
    VAR $DocNo=0;
    var $BankArray = array();
    var $amountpaid=0;
    var $amountconverted=0;
    var $withholdamt=0;
    var $SuppliersInvoiceNo;
    
    function __construct() {
        $this->DimensionsBudget = new DimensionsBudget();
    }
    
    function GetFormUpdate(){
        $this->JournalArray[]="Delete from `paymentvoucherline` where `journal`='".$_POST['edit']."'";
        $this->JournalArray[]="Delete from `paymentvoucherheader` where `journal`='".$_POST['edit']."'";
        $this->GetForm();
    }
    
    
    function GetBankDetails($accountCode){
         Global $db;
        $resultindex=DB_query("SELECT 
            `accountcode`,`bankName`,
            `BankAccounts`.`currency`,
            `lastreconcileddate`,`AccountNo`,
           `BranchCode`,
           `BranchName`,
           `lastreconbalance`,`lastChequeno`,
           `PostingGroup`,
           `currencies`.`rate`,
           `StatementNo`
            FROM `BankAccounts`  join `currencies` on `BankAccounts`.`currency`=`currencies`.`currabrev`
            where `accountcode`='".$accountCode."'", $db);
        
        $row=DB_fetch_row($resultindex);
        $this->BankArray['accountcode'] = $row[0];
        $this->BankArray['bankName']    = $row[1];
        $this->BankArray['currency']    = $row[2];
        $this->BankArray['lastreconcileddate'] = $row[3];
        $this->BankArray['AccountNo'] = $row[4];
        $this->BankArray['BranchCode']= $row[5];
        $this->BankArray['BranchName']= $row[6];
        $this->BankArray['lastreconbalance'] = $row[7];
        $this->BankArray['lastChequeno'] = $row[8];
        $this->BankArray['PostingGroup'] = $row[9];
        $this->BankArray['rate'] = $row[10];
        $this->BankArray['StatementNo'] = $row[11];
        return $this->BankArray;
    }
    
    
    
    function GetForm(){
        Global $db;
        
            $this->FormVariables = $_POST;
            foreach ($this->FormVariables as $key=>$value) {
                
                if($key=='date'){
                    $this->date = FormatDateForSQL($value);
                    $this->periodNo= GetPeriod($value,$db,TRUE);
                }
                
                if($key=='Bank_Code'){
                    $this->bankcode=$value;
                    $this->GetBankDetails($this->bankcode);
                }
                    
                if($key=='documentno'){
                      if(($_SESSION['ManualNumber']==0)){
                       $value = GetNextTransNo(53,$db);
                      }
                       $this->documentno = $value;
                
                }
                if($key=='reference'){
                $this->reference = $value;
                
                }
                
                if($key=='VendorID'){
                    $this->CustomerID = $value;
                }
                
                if($key=='VendorName'){
                     $this->CustomerName = $value;
                
                }
                if($key=='currencycode'){
                     $this->currencycode = $value;
                
                }

                if($key=='totalamount'){
                   $this->totalamount = $value;
                }
                      
                
                if($key=='topay'){
                         foreach ($value as $journalno => $amountval) {
                            if($amountval>0){
                                 $this->GetBudgetsForAll($journalno,$amountval);
                              }
                          }
                   
                   }
                   
                   
             
            }
            
            $this->PVheader();
                 
    }
   
    
    
    function PVheader(){
        
            $this->JournalArray[] = sprintf("INSERT INTO `paymentvoucherheader`
                                        (`docno`
                                        ,`date`
                                        ,`itemcode`
                                        ,`externalref`
                                        ,`narrative`
                                        ,`amount`
                                        ,`journal`
                                        ,`currency`
                                        ,`status`)
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s'
                                        ,0)",
                                        $this->documentno ,
                                        $this->date, 
                                        $this->CustomerID,
                                        $this->reference,
                                        'Paid in '.$this->BankArray['currency'] ,
                                        $this->totalamount,
                                        $this->Receipt_Journal,
                                        $this->BankArray['currency']);
        
    }
    
    
  
    function GetBudgetsForAll($journal,$amount1){
        global $db;
        
        $this->amountpaid=$amount1;
        
        $SQL = SPRINTF("SELECT
            `SupplierStatement`.`Grossamount`,
            `SupplierStatement`.`Dimension_One` ,
            `SupplierStatement`.`Dimension_Two` ,
            `currencies`.`rate`,
            `SupplierStatement`.`whtax`,
            `SupplierStatement`.Documentno
            FROM `SupplierStatement` 
            join creditors on itemcode=Accountno
            left join `currencies` on `currencies`.`currabrev`=creditors.curr_cod 
            where `Accountno`='%s' and `JournalNo`='%s' ",$this->CustomerID,$journal);
            
        $ResultIndex = DB_query($SQL,$db);
        $INVOICES = DB_fetch_row($ResultIndex);
        $this->SuppliersInvoiceNo =$INVOICES[5];
        $this->amountconverted = ($this->amountpaid * ($INVOICES[3]/$this->BankArray['rate']));
        
        if($INVOICES[4]==1){
            $this->withholdamt = ($this->amountpaid * ($INVOICES[3]/$this->BankArray['rate'])) * ($_SESSION['WithholdingTaxRate'] *.01);
        }else{
            $this->withholdamt=0;
        }
        $VOTEARRAY = $this->DimensionsBudget->Calculate($INVOICES[1],$INVOICES[2]);         
        $budgetbalance =($VOTEARRAY['Budget'] - ($VOTEARRAY['Committement']+ $VOTEARRAY['Expensed'] + $this->amountconverted));
              
        
        $this->JournalArray[] = sprintf("INSERT INTO `paymentvoucherline`
                                        (`docno`
                                        ,`itemcode`
                                        ,`narrative`
                                        ,`amount`
                                        ,`journal`
                                        ,`invoice_journal`
                                        ,`Budget`
                                        ,`Committed`
                                        ,`Expensed`
                                        ,`Balance`
                                        ,`Dimension_1`
                                        ,`Dimension_2`
                                        ,`whtax`)
                                  VALUES ('%s','%s','%s',%f,'%s','%s',%f,%f,%f,%f,'%s','%s',%f)",
                                        $this->documentno ,
                                        $this->CustomerID,
                                        'Paid in '.$this->BankArray['currency'] ,
                                        $this->amountconverted,
                                        $this->Receipt_Journal,
                                        $journal,
                                        $VOTEARRAY['Budget'],
                                        $VOTEARRAY['Committement'],
                                        $VOTEARRAY['Expensed'],
                                        $budgetbalance,
                                        $INVOICES[1],
                                        $INVOICES[2],
                                        $this->withholdamt);
        
        
        
        
        
    }
    
    
}

Class klassbanksAccounts {
    var $BankObject = '';
    var $BankArray = array();
    
    function __construct() {
        Global $db;
        $this->BankObject='<tr><td>Select Bank:</td><td><Select name="Bank_Code" required="required">';
        $resultindex=DB_query("SELECT `accountcode`,`bankName`
                                     ,`currency`,`lastreconcileddate`
                                     ,`AccountNo`,`BranchCode`
                                     ,`BranchName`,`lastreconbalance`
                                     ,`lastChequeno`,`PostingGroup`
                                    FROM `BankAccounts`", $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $this->BankObject .= '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                $this->BankObject .= '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
            }
        
        $this->BankObject .='</select></td></tr>';
    }
    
    function Get(){
        ECHO  $this->BankObject;
    }
    
    
    function GetBankDetails($accountCode){
         Global $db;
        $resultindex=DB_query("SELECT `accountcode`,`bankName`,`BankAccounts`.`currency`,`lastreconcileddate`
                                     ,`AccountNo`,`BranchCode`,`BranchName`,`lastreconbalance`
                                     ,`lastChequeno`,`PostingGroup`,`currencies`.`rate`,`StatementNo`
                                    FROM `BankAccounts` join `currencies` on `BankAccounts`.`currency`=`currencies`.`currabrev`
                                    where `accountcode`='".$accountCode."'", $db);
        
        $row=DB_fetch_row($resultindex);
        
        $this->BankArray['accountcode']=$row[0];
        $this->BankArray['bankName']=$row[1];
        $this->BankArray['currency']=$row[2];
        $this->BankArray['lastreconcileddate']=$row[3];
        $this->BankArray['AccountNo']=$row[4];
        $this->BankArray['BranchCode']=$row[5];
        $this->BankArray['BranchName']=$row[6];
        $this->BankArray['lastreconbalance']=$row[7];
        $this->BankArray['lastChequeno']=$row[8];
        $this->BankArray['PostingGroup']=$row[9];
        $this->BankArray['rate']=$row[10];
        $this->BankArray['StatementNo']=$row[11];
        
        return $this->BankArray;
    }
}

?>
 