<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Petty Cash');
include('includes/header.inc');
include('includes/budgetbalance.php');
include('includes/chartbalancing.inc');
include('includes/petteycash.inc');
 
   
if(isset($_GET['New'])){    
    $_SESSION['ThisIsAnewPC'] = date('U');
}

if(isset($_GET['journal'])){
    $sql=array();
    
    $sql[] ="Delete FROM `pettdoc` where `journal`='".$_GET['journal']."'";
    $sql[] ="Delete FROM `SupplierStatement` where `JournalNo`='".$_GET['journal']."'";
    $sql[] ="Delete FROM `creditorsledger` where `journal`='".$_GET['journal']."'";
    
    $sql[] ="Delete FROM `BankTransactions` where `journal`='".$_GET['journal']."'";
    $sql[] ="Delete FROM `Generalledger` where `journalno`='".$_GET['journal']."'";
    
    $sql[] ="Delete FROM `CustomerStatement` where `JournalNo`='".$_GET['journal']."'";
    $sql[] ="Delete FROM `debtorsledger` where `journal`='".$_GET['journal']."'";
    
    DB_Txn_Begin($db);
    foreach ( $sql as $sqlcmd) {
      DB_query($sqlcmd,$db);
    }

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    } else {
        DB_Txn_Commit($db);
    }
}

$TRBanks = new  ShowBankAccounts() ;
$ClsPettey = new  Petteycash();
$ClsPettey->Calculate();

if(!isset($_POST['VATamount'])){
    $_POST['VATamount'] =(float) $ClsPettey->VATamt;
}

if(!isset($_POST['VATGLaccount'])){
    $_POST['VATGLaccount'] =(float) $ClsPettey->VATGLaccount;
}

if($_POST['Grossamount']>0){ 
    if($_POST['amount']==0){
       $_POST['amount']=(float)($_POST['Grossamount'] - $_POST['VATamount']);
    }
}else{
    $_POST['Grossamount']=(float)($_POST['VATamount'] + $_POST['amount']);
}

  

if(isset($_POST['Save']) or isset($_POST['updateClose'])){
    


    if($_POST['submitnext']==$_SESSION['ThisIsAnewPC']){
        

        if(mb_strlen($_POST['comments'])>0){
            $PERIODNO = GetPeriod($_POST['date'], $db,TRUE);
            $journal  = GetNextTransNo(0,$db);
     
            $ClsPettey->SQLARRAY = array();
            $ClsPettey->Calculate();
  
            if($_POST['transtype']==0){
                $ClsPettey->POSTGL($journal);
            } else {
                if($_POST['accttype']==1){
                    $ClsPettey->POSTSupplier($journal);
                }elseif($_POST['accttype']==0){
                    $ClsPettey->POSTGL($journal);
                }elseif($_POST['accttype']==2){
                    $ClsPettey->POSTDeposit($journal);
                }elseif($_POST['accttype']==3){
                    $ClsPettey->POSTdebtor($journal);
                }
            }           
            DB_Txn_Begin($db);
           
            foreach ($ClsPettey->SQLARRAY as $sql) {
                 DB_query($sql,$db);
            }

            if(DB_error_no($db)>0){
                DB_Txn_Rollback($db);
            } else {
                DB_Txn_Commit($db);
                $_SESSION['ThisIsAnewPC']++;
                DB_query("Update `BankAccounts` set `lastChequeno`=`lastChequeno`+1 where `accountcode`='".$_POST['Bank_Code']."'", $db);
            
                if(isset($_POST['updateClose'])){
                     header('location:PetteyCash.php?New=1');
                } else {
                    $_POST['Grossamount'] = 0;
                    $_POST['VATamount'] = 0;
                    $_POST['amount'] = 0;
                    unset($_POST['comments']);
                }
               
         }
                        
        }else{
            prnMsg('You have not entered any narration','warn');
        }    
    }else{
       prnMsg('Please dont try to resubmit this page','warn');
    }
    
}

$_POST['shiftno'] = $ClsPettey->GetShiftno($_SESSION['UserID']);

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Petty Cash') .'" alt="" />' . ' ' . _('Petty Cash') . '</p>';

echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="Journal">'
   . '<div class="container-fluid">';

echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
 $Description = $TRBanks->GetBankDetails($_POST['Bank_Code']);  
 $_POST['documentno'] ='P'. str_repeat(0,4-strlen($Description['lastChequeno'])).$Description['lastChequeno'];
   
if(isset($_POST['submitnext'])){
    
    echo '<input type="hidden" name="submitnext" value="'.$_SESSION['ThisIsAnewPC'].'"/>';
    echo '<input type="hidden" name="transtype" value="'.$_POST['transtype'].'"/>';
    echo '<input type="hidden" name="Bank_Code" value="'.$_POST['Bank_Code'].'" />';
   
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);

    if(!isset($_POST['date'])){  $_POST['date']= ConvertSQLDate($rowdate[0]); }
    
    
    echo '<input type="hidden" name="currencycode" value="'.$Description['currency'].'" />';
    echo '<input type="hidden" name="BankPostingGL" value="'.$Description['PostingGroup'].'" />';
    
    if(isset($_POST['Bank_Code2'])){
        $arrayBank2 = $TRBanks->GetBankDetails($_POST['Bank_Code2']);     
        echo '<input type="hidden" name="BankfromPostingGL" value="'.$arrayBank2['PostingGroup'].'"/>';
        echo '<input type="hidden" name="currencycodeFrom" value="'.$arrayBank2['currency'].'"/>';
     }
     
    
    echo '<table class="table-bordered">';
    echo '<tr><td>Shift No</td><td><input type="text" name="shiftno" value="'.$_POST['shiftno'].'"  readonly="readonly"/></td>';
    echo '<td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
    echo '<tr><td>Doc No</td><td><input type="text" name="documentno" value="'.$_POST['documentno'].'" readonly="readonly"/></td>';
    echo '<td>Selected Float Account</td><td><input type="text" name="BankNAME" value="'.$Description['bankName'].'" readonly="readonly"/></td></tr>';
    echo '<tr><td colspan="4"><p class="good">User Account Balance<br/> '.GETUSERBALANCE($ClsPettey->GetShiftno($_SESSION['UserID'])).'</p></td></tr>';
   
    echo '<tr><td colspan="4"><p class="good">To include More Leger Accounts in the petty cash module ,<br/> Go to Ledger Setup And "ADD" a "Posting Group"</p></td></tr>';
    echo '<tr><td>Account Type</td><td><select name="accttype" required="required"  onchange="ReloadForm(Journal.update);">'; Accttype() ; echo '</select></td>';
                      
    if($_POST['transtype']==0){
         $SQLstment=sprintf("SELECT `accountcode`,`bankName`,BranchName,currency  FROM `BankAccounts` where `accountcode` <> '%s'",$_POST['Bank_Code']);
               
         echo '<td>Select Bank to withdraw from :</td><td><Select name="Bank_Code2" required="required">';
                 
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code2'])){
                echo  '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code2'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        
        echo '</select></td></tr>';
    
    }else {
        
      if($_POST['accttype']==1){
                echo '<td>Select Supplier :</td><td><select name="itemcode" onchange="ReloadForm(Journal.update);">';
                $ResultIndex=DB_query("select itemcode,customer from creditors where `inactive`=0 order by customer", $db);
                while($row=DB_fetch_array($ResultIndex)){
                    if(isset($_POST['itemcode'])){
                         echo '<option value="'.$row['itemcode'].'" '.((trim($_POST['itemcode'])==trim($row['itemcode']))?'selected="selected"':'').'>'.$row['customer'].'</option>';
                    }else{
                        echo '<option value="'.$row['itemcode'].'">'.$row['customer'].'</option>';
                    }
                }
                echo '</select></td></tr>';

            }  elseif($_POST['accttype']==0) {
                
            echo '<td>Select Ledger :</td><td><input type="hidden" name="acctno" id="accountcode"  value="'.$_POST['acctno'].'"/>'
                . '<input type="button" id="searchchart2" value="Find"/>'
                . '<input type="text" name="accountname" id="accountname" value="'.$_POST['accountname'].'"/>'
                . '</td></tr>';
             
             echo $_SESSION['SelectObject']['dimensionone'];
             echo $_SESSION['SelectObject']['dimensiontwo'];
       
         }elseif($_POST['accttype']==2){
             
                $SQLstment=sprintf("SELECT `accountcode`,`bankName`,BranchName,currency FROM `BankAccounts` where `accountcode` <> '%s'",$_POST['Bank_Code']);
                 echo '<td>Select Bank to Deposit To :</td>'
                . '<td><Select name="Bank_Code2" onchange="ReloadForm(Journal.update);">';

                $resultindex=DB_query($SQLstment, $db);
                while($row=DB_fetch_array($resultindex)){
                    if(Isset($_POST['Bank_Code2'])){
                        echo  '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code2'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
                     }else{
                        echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
                     }
                }
        
        echo '</select></td></tr>';
         }elseif($_POST['accttype']==3){
                echo '<td>Select Staff:</td><td><select name="itemcode" onchange="ReloadForm(Journal.update);">';
                $ResultIndex=DB_query("select itemcode,customer FROM `debtors` where `inactive`=0 order by customer", $db);
                while($row=DB_fetch_array($ResultIndex)){
                    if(isset($_POST['itemcode'])){
                         echo '<option value="'.$row['itemcode'].'" '.((trim($_POST['itemcode'])==trim($row['itemcode']))?'selected="selected"':'').'>'.$row['customer'].'</option>';
                    }else{
                        echo '<option value="'.$row['itemcode'].'">'.$row['customer'].'</option>';
                    }
                }
                echo '</select></td></tr>';

            }
        
    }
    
    echo '<tr><td colspan="4">';
    
    if($_POST['accttype']==1 or $_POST['accttype']==2 or $_POST['accttype']==3){$type='readonly="readonly"';}else{$type='';}
    
    echo '<table class="table-bordered alignleft"><tr><TH>AMOUNT(EXCL VAT)</th><th>VAT AMOUNT</th><th>GROSS AMOUNT</th></TR>';
    echo '<tr><td class="number"><input type="text" name="amount"  class="number" value="'.$_POST['amount'].'"  required="required" '.$type.'/></td>';
    echo '<td class="number"><input type="text" name="VATamount"  class="number" value="'.$_POST['VATamount'].'"  required="required" '.$type.'/></td>';
    echo '<td class="number"><input type="text" name="Grossamount"  class="number" value="'.$_POST['Grossamount'].'"  required="required"/></td></tr>';
    ECHO '</table></td></tr>';
    echo '<tr><td>Narration</td><td colspan="3"><input class="table" type="text" name="comments" size="50" value="'.$_POST['comments'].'" maxlenth="200" />'
       . '<input type="hidden" name="VATGLaccount" value="'.$_POST['VATGLaccount'].'"/></td></tr>';
    echo '<tr>'
        . '<td colspan="2"><input type="submit" name="update" value="Refresh"/></td>'
        . '<td><input type="submit" name="Save" value="Save & New" onclick="return confirm(\''._('Do you want to save this document ?').'\');" /></td>'
        . '<td><input type="submit" name="updateClose" value="Save & Exit" onclick="return confirm(\''._('Do you want to save and Quit ?').'\');" />';
    echo '</td></tr>';
    echo '</table>';
}

else{
 

if(isset($_SESSION['UserStockLocation'])){
    $_POST['Bank_Code']=$_SESSION['UserStockLocation'];
}

echo '<input type="hidden" name="amount" value="0"/>';
echo '<table class="table-bordered">';
echo '<tr><td>Shift No</td><td><input type="text" name="shiftno" value="'.$_POST['shiftno'].'" readonly="readonly"/></td></tr>';
$TRBanks->Get() ;
echo '<tr><td>Transction Type</td><td><select name="transtype" required="required">'; Transtype(); echo '</select></td>'
. '</tr>';
echo '<tr><td colspan="3">';
echo '<input type="submit" name="submitnext" value="Go NEXT"/>';
echo '</td></tr>';
echo '</table>';

}

echo '<br/><div>'
. '<table class="table-striped table-bordered"><thead><tr><th class="ascending">DATE</th><th>Payee</th>'
        . '<th>Narration</th><th class="number">Amount</th>'
        . '<th class="number">Balance</th></tr></thead>';

$sqlbal = "Select sum((`moneyin`-`moneyout`)+balance)  FROM `pettdoc`"
        . " where `userid`='".$_SESSION['UserID']."' and `shiftno`<'".$_POST['shiftno']."'";
$ResultIndex=DB_query($sqlbal,$db);
$rowk = DB_fetch_row($ResultIndex);
$balance = $rowk[0];

$sql="SELECT 
       `date`
      ,`userid`
      ,IFNULL(`moneyin`,0) as moneyin
      ,IFNULL(`moneyout`,0) as moneyout
      ,IFNULL(`balance`,0) as balance
      ,case transtype when 0 
      then (select customer from creditors where itemcode=`pettdoc`.account) 
      else (select accdesc from acct where accno=`pettdoc`.account) 
      end as Account
      ,`expensedetails`
      ,`petteycashno`
      ,`journal`
      ,`shiftno`
      ,`transtype`
  FROM `pettdoc` 
  where `userid`='".$_SESSION['UserID']."' "
 . " and `shiftno`='".$_POST['shiftno']."'  "
 . " order by `shiftno`,`date`,`journal` asc";

$ResultIndex=DB_query($sql,$db);

echo sprintf('<tr><td>Balance</td><td></td><td></td><td></td><td class="number">%s</td></tr>',number_format($balance,2));

while($rows = DB_fetch_array($ResultIndex)){
    $amount   = $rows['moneyin']-$rows['moneyout'];
    $balance += $amount;
    $anchor='<a href="PetteyCash.php?journal='.$rows['journal'].'"  onclick="return confirm(\''._('Do you want to Delete this line ?').'\');" >%s</a>';
   
    echo sprintf('<tr><td>'.$anchor.'</td><td>%s</td><td>%s</td><td class="number">%s</td><td class="number">%s</td></tr>',
    ConvertSQLDate($rows['date']),$rows['Account'],$rows['expensedetails'],number_format(abs($amount),2),number_format($balance,2));
}

echo '</table>';
echo '<div class="centre"><a class="bad" href="PDFimprestReport.php">Print The Petty Cash Report</a></div>'
. '</div>';

echo '</div></form>';

include('includes/footer.inc');

function Transtype(){
    $array=array();
    $array[0]='Receive Float';
    $array[1]='Issue Float';
    
    foreach ($array as $key => $value) {
        if($_POST['transtype']==$key){
            echo '<option selected="selected" value="'.$key.'">'.$value.'</option>';
        }else{
            echo '<option  value="'.$key.'">'.$value.'</option>';
        }
    }
    
}

function Accttype(){
    $array=array();
    
    $array[0]='Select GL Account';
    $array[1]='Select A Supplier';
    $array[2]='Deposit to Bank';
    $array[3]='Imprest(To Account for later)';
    
    foreach ($array as $key => $value) {
        if($_POST['accttype']==$key){
            echo '<option selected="selected" value="'.$key.'">'.$value.'</option>';
        }else{
            echo '<option  value="'.$key.'">'.$value.'</option>';
        }
    }
    
}

function GETUSERBALANCE($shiftno){
    global $db;
    
  $sqlbal = "Select sum((`moneyin`-`moneyout`)+balance)  FROM `pettdoc`"
        . " where `userid`='".$_SESSION['UserID']."' and `shiftno`<'".$shiftno."'";
        $ResultIndex=DB_query($sqlbal,$db);
        $rowk = DB_fetch_row($ResultIndex);
        $balance = $rowk[0];
//Select sum((`moneyin`-`moneyout`)+balance) FROM `pettdoc` where `userid`='admin' and `shiftno`<'88'
       
  return number_format($balance);
}

?>