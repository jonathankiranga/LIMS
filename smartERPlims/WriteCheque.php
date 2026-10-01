<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/budgetbalance.php');
include('includes/chartbalancing.inc');

if (!isset($RootPath)){
           $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
           if ($RootPath == '/' OR $RootPath == "\\") {
                   $RootPath = '';
           }
   }
        
$TRBanks = new GetBankAccounts();
$classPvs= new Paymentvouchers();

if(isset($_GET['PVNUMBER'])){
   $selectedrec = $_GET['PVNUMBER'];
}elseif($_POST['pvjounal']){
   $selectedrec = $_POST['pvjounal'];
}

if(isset($_GET['PVNUMBER'])){
    $_SESSION[$selectedrec]=TRUE;
    $classPvs->Newline();
}

if(isset($_POST['cancellAll'])){
   $classPvs->Delete($selectedrec);
}else{
   $classPvs->AddOtherPvs();
}
 
if(isset($_POST['BankCurrency'])){
        if(isset($_POST['PostCheque'])){
            
            if($_SESSION[$selectedrec]==FALSE){
                die("You cannot post twice");
            }
            
            $array=array();
            $array = $classPvs->Unloadpvs();
            
            DB_Txn_Begin($db);
            foreach ($array as $SQL) {
               DB_query($SQL,$db);
            }

            if(DB_error_no($db)>0){
                DB_Txn_Rollback($db);
            }else{
                DB_Txn_Commit($db);
                DB_query("Update `BankAccounts` set `lastChequeno`=(`lastChequeno`+1) "
                        . " where `accountcode`='".$_POST['Bank_Code']."'", $db);
                
                $_SESSION[$selectedrec]=FALSE;
                
                $classPvs->Newline();
              
 echo '<script type="text/javascript" src = "'.$RootPath.'/javascripts/3.5.1/jquery-3.5.1.js"></script>'
     . '<script type="text/javascript" src = "'.$RootPath.'/javascripts/jQueryUI-1.12.1/jquery-ui.min.js">
         </script><script type="text/javascript">
            $(document).ready(
              function() {
                     $.post("includes/autoallocatevendorsAjax.php",{
                             autoallocatevendors: "'.trim($_POST['VendorID']).'"
                     },function(data){
                       SmartDialog.info(data, "Information");
                     });
              }
            )
            </script>';      
                unset($selectedrec);
                unset($_POST);
            }
   
        }
        
        
        
}


if(isset($selectedrec)){

$Title = _('Write Cheques');
include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' .$Title .'" alt="" />' . ' ' .$Title. '</p>';

echo '<DIV class="centred">';


 
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
      ,`arpostinggroups`.`creditorsaccount`
  FROM `paymentvoucherheader` join `creditors`
  on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode`
  join `arpostinggroups` on `creditors`.`supplierposting`=`arpostinggroups`.`code`
  where `paymentvoucherheader`.`journal`='".$selectedrec."'";


    $ResultIndex = DB_query($sql,$db);
    $Row = DB_fetch_row($ResultIndex);
    $_POST['documentno'] = $Row[0];
    $_POST['date'] = ConvertSQLDate($Row[1]);
    $_POST['reference'] = $Row[3];
    $_POST['VendorID'] = $Row[6];
    $_POST['VendorName'] = $Row[2];
    $_POST['currencycode'] = $Row[8];
    $_POST['postingGroups']= $Row[9];
    $_POST['topay'][$selectedrec]= $Row[5];
             
      
echo '</div><form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="custreseipts"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<input type="hidden" name="pvjounal" value="'.$selectedrec.'"/>';
echo '<input type="hidden" name="postingGroups" value="'.$_POST['postingGroups'].'"/>';

Echo '<div class="container">
    <table class="table-striped table-bordered">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<tr><td>Payee</td>'
        . '<td><input tabindex="4" type="hidden" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"/>'
        . '<input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
   . '<input tabindex="6" type="text" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/>'
   . '</td>';
    
$TRBanks->Get();

echo '<tr>';

 if(isset($_POST['Bank_Code'])){
        $TRBanks->GetBankDetails($_POST['Bank_Code']);
        $TRBanks->BankArray['lastChequeno'];
        
        echo '<td><input tabindex="5" type="hidden" name="BankExchange" value="'.$TRBanks->BankArray['rate'].'"  size="5" />'
            . '<input type="hidden" name="BankCurrency" value="'.$TRBanks->BankArray['currency'].'"/>'
            . '<input type="hidden" name="BankPostingGroup" value="'.$TRBanks->BankArray['PostingGroup'].'"/>'
            . 'Cheque No :</td><td><input tabindex="4" type="text" name="documentno"   maxlength="10" required="required"/></td>';
    }
    
 echo '</tr>';
  
   
       $sql="SELECT 
        `paymentvoucherheader`.`date` as Date,
        `paymentvoucherheader`.`docno` as Documentno,
        `paymentvoucherheader`.`itemcode` as Accountno,
        `paymentvoucherline`.`amount`,
        `paymentvoucherline`.`whtax`,
        `paymentvoucherheader`.`journal` as JournalNo,
        (IFNULL((SELECT sum(`amount`) FROM `PaymentsAllocation`   where `receiptjournal`='".$selectedrec."'),0)) as Tamount
        ,(IFNULL((SELECT sum(`amount`) FROM `PaymentsAllocation`  where `journalno`='".$selectedrec."'),0)) -  
        (IFNULL((SELECT sum(`amount`) FROM `PaymentsAllocation`   where `receiptjournal`='".$selectedrec."'),0)) as Pamount
      FROM `paymentvoucherheader` 
      join `paymentvoucherline` on `paymentvoucherheader`.`journal`=`paymentvoucherline`.`journal`
      join SupplierStatement on `paymentvoucherline`.`invoice_journal`=`SupplierStatement`.`JournalNo`
      and `SupplierStatement`.Accountno=`paymentvoucherheader`.`itemcode`
      where `paymentvoucherheader`.`journal`='".$selectedrec."'  
      Group by
	`paymentvoucherheader`.`date` ,
        `paymentvoucherheader`.`docno` ,
        `paymentvoucherheader`.`itemcode` , 
        paymentvoucherline.amount,
        paymentvoucherline.whtax,
	`paymentvoucherheader`.`journal`,
	`paymentvoucherline`.`invoice_journal`";
             
       
Echo '<tr><td colspan="4">'
        . '<table class="table-striped table-bordered"><tr>'
        . '<th>Date</th>'
        . '<th>Doc No</th>'
        . '<th class="increment">AMOUNT</th>'
        . '<th class="increment">Unpaid balance on this invoice</th>'
        . '<th class="increment">Amount of Withholding Tax</th>'
        . '<th class="increment">Total to Pay</th>'
        . '</tr>';

      
$k=0; 
$TotalAmount=0;$WithholdingTX=0;

$ResultIndex=DB_query($sql,$db);
while($row=DB_fetch_array($ResultIndex)){
    $unpaid  = $row['Tamount'];
    $p_amount= $row['amount'];
    
    if(($p_amount-$unpaid) >=0){ 
       
        $amounttopay = $p_amount;
        $TotalAmount += ($p_amount-$row['whtax']);
        $WithholdingTX += $row['whtax'];      
        
        $linerow = sprintf('<tr>'
        . '<td>%s</td><td>%s</td>'
        . '<td><input type="text" class="number increment"  readonly="readonly"  value="%s" /></td>'
        . '<td><input type="text" class="number increment"  readonly="readonly"  value="%s" /></td>'
        . '<td><input type="text" class="number increment"  readonly="readonly"  value="%s" name="wtax[%s]"/></td>'
        . '<td><input type="text" class="number increment"  readonly="readonly"  value="%s" name="topay[%s]"/></td></tr>',
                ConvertSQLDate($row['Date']),
                $row['Documentno'],
                $p_amount, 
                $unpaid,
                $row['whtax'],
                trim($row['JournalNo']),
                ($amounttopay-$row['whtax']),
                trim($row['JournalNo']));
        
        echo $linerow;
    } 
}

echo '<tr><td colspan="3"></td><td>Total Amount Posted :</td><td>'
   . '<input type="text" class="number increment"  value="'.$WithholdingTX.'" name="WithholdingTX" readonly="readonly"/></td><td>'
   . '<input type="text" class="number increment"  value="'.$TotalAmount.'" name="totalamount" readonly="readonly"/>'
   . '<input type="hidden" name="comments" value="'.Num2Wrd($TotalAmount,$_POST['currencycode'],'CENTS').'"></td></tr>'
   . '<tr><td colspan="7">Amount in words: <label>'.Num2Wrd($TotalAmount,$_POST['currencycode'],'CENTS').'</label></td></tr></table></td></tr>'
   . '<tr>';

echo  '<td colspan="4">'
     . '<input type="submit" name="update" value="Refresh" />'
     . '<input type="submit" name="PostCheque" value="Post Cheque" onclick="return confirm(\''._('Do you want to Post this Cheque ?').'\');" />'
     . '<input type="submit" name="cancellAll" value="Delete" onclick="return confirm(\''._('Do you want to Delete this batch ?').'\');" />'
     . '</td>';

echo '</tr></table></div>';
echo '</div></form>';
 
include('includes/footer.inc');

} else {

 $Title = _('Write Cheques');

 include('includes/header.inc');
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Payment Voucher Status') .'" alt="" />' . _('Payment Voucher Status ') . '</p>';
   
  $approvals = array();
  $approvals[0]='Waiting';
  $approvals[1]='FAM Approved';
  $approvals[2]='CEO,FAM Approved';
  $approvals[3]='Cheque waiting';
  $approvals[9]='CanCelled';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  
  
  $SQL="SELECT
       `docno`
      ,`paymentvoucherheader`.`date`
      ,`creditors`.`customer`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`paymentvoucherheader`.`status`
      ,`journal`
      ,`currency`
      ,Comments
  FROM `paymentvoucherheader` 
  join `creditors` on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode`
  where `paymentvoucherheader`.`status`=2
  order by date desc" ;
  
  echo '<div class="container">
    <table class="table-striped table-bordered"><tr>'
          . '<th>Payment<br/> Voucher No</th>'
          . '<th>Date</th>'
          . '<th>Account Name</th>'
          . '<th>Amount</th>'
          . '<th>Approval Status</th>'
          . '<th>Comments</th>'
         . '</tr>';
  
  $ResultIndex=DB_query($SQL,$db);
  while($row=DB_fetch_array($ResultIndex)){
      
      echo sprintf('<tr><td><a href="%s">Write Cheque for :%s</a></td>',$_SERVER['PHP_SELF'].'?new=1&PVNUMBER='.$row['journal'],$row['docno']);
      echo sprintf('<td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
              ConvertSQLDate($row['date']),
              $row['customer'],
              $row['currency'].' '. ($row['amount']),
        $approvals[$row['status']],
              $row['Comments'] );
      
  }
  
  echo '</table></div>';
  echo '</div></form>';
  
  
include('includes/footer.inc');

  }
 
  
class Paymentvouchers  { 
  var $Exchange;
  var $lineindex;
  
  function __construct() {
      $this->Exchange=new CalculateExchange();
  }
  
  
  
  function AddOtherPvs(){
      $this->lineindex = $this->getline();
      $_SESSION['AddOtherPv'][$this->lineindex]=$_POST;
  }
  
  function getline(){
      return $_SESSION['AddOtherPv']['lineindex'];
  }
  
  function addline(){
      $_SESSION['AddOtherPv']['lineindex']++;
  }
  
  function Newline(){
        $_SESSION['AddOtherPv'] = array('lineindex');
  }
  
  function Delete($selectedrec){
      Global $db;
      $sql[]="Delete from `paymentvoucherline` where `journal`='".$selectedrec."'";
      $sql[]="Delete FROM `paymentvoucherheader` where `journal`='".$selectedrec."'";
      foreach ($sql as $value) {
           $resultindex=DB_query($value,$db);
      }
     
  }
  
  
  function Unloadpvs(){
      Global $db;
      $SQLARRAY= array();
      $JourNal = GetNextTransNo(0,$db);

      foreach ($_SESSION['AddOtherPv'] as $form){ 
          
          if(Is_date($form['date'])){
          
          $PERIODNO = GetPeriod($form['date'],$db,TRUE);
          $DATE     = FormatDateForSQL($form['date']);
          $translatedamount =($form['totalamount']+$form['WithholdingTX'] * $this->Exchange->get($form['currencycode'],$form['BankCurrency']));
          
          $SQLARRAY[]=sprintf("Update paymentvoucherheader "
                  . "set `status`='3',"
                  . "ChequePrinted=0,`Comments`='%s' "
                  . "where `journal`='%s'",$form['comments'],$form['pvjounal']);
         
            $SQLARRAY[]="INSERT INTO `SupplierStatement` (`Date`,`Documentno`,`Documenttype`,`Accountno`,`Grossamount`,`JournalNo` ,`Currency`)
            VALUES ('".FormatDateForSQL($form['date']) ."','". $form['documentno'] ."', 53 ,'". $form['VendorID']."',". $translatedamount .",'".$JourNal."','".$form['currencycode'] ."' )";
                    
            $SQLARRAY[]=SPRINTF("INSERT INTO `BankTransactions` (`bankcode`,`DocDate` ,`doctype` ,`DocumentNo`,`TransType`,`itemcode`,
            `journal`,`amount` ,`narrative`,`exchangerate`) VALUES  ('%s','%s',53,'%s','CR' ,'%s' ,'%s' ,%f,'%s',%f ) ",
            $form['Bank_Code'],FormatDateForSQL($form['date']),$form['documentno'], $form['VendorID'], $JourNal,(0 - $form['totalamount']),(trim($form['VendorName'])), $form['BankExchange']);

            $SQLARRAY[]= sprintf("Insert into `creditorsledger` 
            (`date`,`details`,`flag`,`invref`,`acctfolio` ,`amount`,`pamount`,`curr_cod`,`i_n_t`,`journal`,`typ`,`vatamt`,`systypes_1`,`ledger` ,period) 
            values ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
            FormatDateForSQL($form['date']), $form['documentno'], "CR", $form['documentno'], $form['VendorID'],
            $translatedamount,0, $form['currencycode'],"Q", $JourNal, "Q", 0, 53,$form['postingGroups'], $PERIODNO);

            $SQLARRAY[]=SPRINTF("Insert into `Generalledger` (`journalno` ,`Docdate` ,`period`,`DocumentNo`,`DocumentType`
            ,`accountcode`,`balaccountcode` ,`VATaccountcode` ,`amount`,`VATamount` ,`currencycode` ,`ExchangeRate`,`cutomercode`,`narration`) 
            values ('%s','%s',%f,'%s','%s','%s','%s','%s',%f,'%s','%s','%s','%s','%s') ",  $JourNal,FormatDateForSQL($form['date']),$PERIODNO,$form['documentno'],53,
              $form['postingGroups'],$form['BankPostingGroup'],"", $form['totalamount'],0,$form['currencycode'],$form['BankExchange'],
              $form['VendorID'],(trim($form['VendorName'])));
          
         $SQLARRAY[]=SPRINTF("Insert into `Generalledger` (`journalno` ,`Docdate` ,`period`,`DocumentNo`,`DocumentType`
            ,`accountcode`,`balaccountcode` ,`VATaccountcode` ,`amount`,`VATamount` ,`currencycode` ,`ExchangeRate`,`cutomercode`,`narration`) 
            values ('%s','%s',%f,'%s','%s','%s','%s','%s',%f,'%s','%s','%s','%s','%s') ",  $JourNal,FormatDateForSQL($form['date']),$PERIODNO,$form['documentno'],53,
              $form['postingGroups'],$_SESSION['WithholdingTaxGlAccount'],"",$form['WithholdingTX'],0,$form['currencycode'],$form['BankExchange'],
              $form['VendorID'],(trim($form['VendorName'])));
          
             foreach ($form['topay'] as $invjournal => $valuetopay) {
                           
                  $SQLARRAY[]= SPRINTF("insert into `PaymentsAllocation`
                  (`itemcode`,`date`,`invoiceno`,`journalno`,`doctype`,`receiptno`,`amount`,`receiptjournal`) 
                   (Select `Accountno`,`date`,`Documentno`,`JournalNo`,`Documenttype`,'%s',%s,'%s' 
                   from `SupplierStatement` where `JournalNo`='%s') ", $form['documentno'],$valuetopay, $JourNal,$invjournal);
              }
              
              
              foreach ($form['wtax'] as $invjournal => $valuetopay) {
                           
                  $SQLARRAY[]= SPRINTF("insert into `PaymentsAllocation`
                  (`itemcode`,`date`,`invoiceno`,`journalno`,`doctype`,`receiptno`,`amount`,`receiptjournal`) 
                   (Select `Accountno`,`date`,`Documentno`,`JournalNo`,`Documenttype`,'%s',%s,'%s' 
                   from `SupplierStatement` where `JournalNo`='%s') ", $form['documentno'],$valuetopay, $JourNal,$invjournal);
             }
              
              
        }
        
     }
      
      return $SQLARRAY;
  }
  
}
  


Class GetBankAccounts {
    var $BankObject = '';
    var $BankArray = array();
    
    function __construct() {
        Global $db;
        $this->BankObject='<tr><td>Select Bank:</td><td><Select name="Bank_Code" required="required" onchange="ReloadForm(custreseipts.update)"><option></option>';
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
