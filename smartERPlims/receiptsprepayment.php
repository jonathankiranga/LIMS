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
        $chart = new SavePrepaymentReceipt();
        $chart->GetForm();

        if(isset($_POST['receipt']) and ($_SESSION['locked'] == true)){
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
                $_SESSION['locked'] = false;
            }
       }else{
           prnMsg('You could have attempted to submit this page more than once','warn');
       }
    }
}
    
$ResultIndex = DB_query('Select NOW() as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);

if(!isset($_POST['date'])){
    $_POST['date']= ConvertSQLDate($rowdate[0]);
}
  


echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Create Customer Receipts') .'" alt="" />' . ' ' . _('Create Customer Receipts') . '</p>';
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new number click here</a>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="custreseipts"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<input type="hidden" name="SelectedCustomer" value="'.$SelectedCustomer.'"/>';

Echo '<table   class="table table-bordered">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No'
        . '<input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required" /></td>'
        . '<td>Your Reference'
        . '<input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
echo '<tr><td>Customer ID</td>'
        . '<td>'
        . '<input type="button" id="searchcustomer" value="Search Customer"/><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" /><input type="hidden" name="salespersoncode" id="salespersoncode" value=""/></td>'
        . '<td>Customer Name</td>'
        . '<td><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';

echo $TRBanks->Get();

echo '<tr><td>Currency Code</td><td>'
   . '<input tabindex="6" type="text" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/>';
echo 'Total Amount Posted :</td><td><input type="text" class="number"    value="'.$_POST['totalamount'].'" name="totalamount"/></td>'
     . '<td><input type="submit" name="receipt" value="Receive Amount" '
     . '  onclick="return confirm(\''._('Do you want to save this receipt ?').'\');" /></td></tr></table>';
echo '</div></form>';
 
include('includes/footer.inc');


?>
