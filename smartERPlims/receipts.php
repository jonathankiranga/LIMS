<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Create Customer Receipts');
include('includes/header.inc');   
include('includes/chartbalancing.inc');

$TRBanks = new ShowBankAccounts();


if(isset($_GET['SelectedCustomer'])){
    $SelectedCustomer = $_GET['SelectedCustomer'];
}elseif(isset($_POST['CustomerID'])){
   $SelectedCustomer = $_POST['CustomerID'];
}elseif(isset($_POST['SelectedCustomer'])){
    $SelectedCustomer = $_POST['SelectedCustomer'];
}

if(isset($_GET['new'])){
    $_SESSION['locked'] = true;
    $_POST['documentno'] = GetTempNextNo(12);
     
    prnMsg('Receipt :'.$_POST['documentno'].' has been created');
}else{
    if(isset($_POST['CustomerID'])){
        $chart=new SaveReceipt();
        $chart->GetForm();

        if(isset($_POST['receipt'])){ 
            if($_SESSION['locked'] == true){
            
                $chart->JournalArray = array();
                $chart->Receipt_Journal = GetNextTransNo(0,$db);
                $chart->GetForm();
                $array = $chart->JournalArray;

                DB_Txn_Begin($db);
                foreach ($array as $value) {
                   DB_query($value, $db);
                }

                if(DB_error_no($db)>0){
                   DB_Txn_Rollback($db);
                }else{
                   DB_Txn_Commit($db);
                   
               echo '<script type="text/javascript">
                   $(document).ready(
                        function() {
                         $.post("includes/autoallocatevendorsAjax.php",{
                                 autoallocatedebtors: "'.trim($_POST['CustomerID']).'"
                               },function(data){
                                 SmartDialog.info(data, "Information");
                               });
                        }
                      )
                      </script>';     
               
                   unset($_POST);
                   $_SESSION['locked'] =false;
                }
            
            }else{
            prnMsg('You could have tried to save the page more than once','warn');
        }
        }
    }
}
    
$ResultIndex = DB_query('Select NOW() as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);

if(!isset($_POST['date'])){
    $_POST['date']= ConvertSQLDate($rowdate[0]);
}
  


echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Create Customer Receipts') .'" alt="" />' . ' ' . _('Create Customer Receipts') . '</p>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="custreseipts"><div class="container">';
echo '<div class="centre"><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<input type="hidden" name="SelectedCustomer" value="'.$SelectedCustomer.'"/>';
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new number click here</a>';

Echo '<table  class="table-bordered">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required" /></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/><input type="hidden" name="salespersoncode" id="salespersoncode" value=""/></td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';

echo $TRBanks->Get();

echo '<tr><td>Currency Code</td><td>'
   . '<input tabindex="6" type="text" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';
       
echo '</tr>';


$sql="SELECT `Date`
      ,`Documentno`
      ,`Documenttype`
      ,`Accountno`
      ,`Grossamount`
      ,`JournalNo`
      ,`Dimension_One`
      ,`Dimension_Two`
      ,IFNULL((SELECT sum(`amount`) FROM `ReceiptsAllocation` 
         where `itemcode`=`CustomerStatement`.`Accountno` 
          and `invoiceno`=`CustomerStatement`.`Documentno` 
          and `journalno`=`CustomerStatement`.`JournalNo`),0) as Pamount
      FROM `CustomerStatement` where `CustomerStatement`.`Accountno`='".$SelectedCustomer."'";


Echo '<tr><td colspan="4">'
        . '<table  class="table table-bordered"><tr>'
        . '<th>Date</th>'
        . '<th>Doc No</th>'
        . '<th class="number">AMOUNT</th>'
        . '<th class="number">Unpaid AMOUNT</th>'
        . '<th class="number">Pay</th>'
        . '</tr>';
 
      
$k=0; 
$TotalAmount=0;

$ResultIndex=DB_query($sql,$db);
while($row=DB_fetch_array($ResultIndex)){
    
    if(($row['Grossamount']+$row['Pamount'])>0){ 
        $amounttopay = ($row['Grossamount'] + $row['Pamount']) ;
                     
        if(isset($_POST['topay'][$row['JournalNo']])){
            $amount =(float) $_POST['topay'][$row['JournalNo']];
        }else{
            $amount=0;
        }
        
        $TotalAmount += $amount;
        
        $linerow = sprintf('<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number" value="%s" readonly="readonly"/></td>'
                . '<td><input type="text" class="number" value="%s" readonly="readonly"/></td>'
                . '<td><input type="number" class="number"  max="'.$amounttopay.'" min="0" value="'.$amount.'"  step="0.01" name="topay['.$row['JournalNo'].']"/></td></tr>',
                ConvertSQLDate($row['Date']),
                $row['Documentno'],
                number_format($row['Grossamount'],2),
                number_format($amounttopay,2));
        
        echo $linerow;
    } 
}

echo '</table></td></tr><tr><td colspan="3">Total Amount Posted :</td><td>'
. '<input type="number" class="number"  readonly="readonly" max="'.$TotalAmount.'" min="'.$TotalAmount.'" value="'.$TotalAmount.'" name="totalamount"/></td></tr>'
. '<tr><td><input type="submit" name="submit" value="Calculate"/><input type="submit" name="receipt" value="Receive Amount" '
. '  onclick="return confirm(\''._('Do you want to save this receipt ?').'\');" /></td></tr></table>';
echo '</div></form></div>';
 
include('includes/footer.inc');


