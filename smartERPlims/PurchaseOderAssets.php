<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('purchases/stockbalance.inc');
include('purchases/puchasescart.inc');


$POSclass = new FixedAssets();

$Title = _('Purcahse Fixed Assets');
include('includes/header.inc');   
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">Click here to Create New Fixed Assets Request Number Refence</a>';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Purcahse Fixed Assets') .'" alt="" />' . ' ' . _('Purcahse Fixed Assets') . '</p>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="salesform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

if($_POST['submit']=="Enter Delivery Details and Confirm Order"){
    if($_SESSION['Grossamounttotal']==0){
      $_POST['submit']="Empty";
    }
}
    
if(isset($_POST['confirm'])){
    DB_query("UPDATE `AssetsHeader` SET `released` = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    DB_query("UPDATE `FixedAssetsLine` SET `completed` = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been saved.'
         . ' Click Here to Print'.'<a href="PurchaseAssetsList.php?No='.$_SESSION['CompleteDocument'].'">Print Purchase order '.$_SESSION['CompleteDocument'].'</a>');
    unset($_POST);
}

if(isset($_POST['Delete'])){
    DB_query("Delete from `FixedAssetsLine` where documentno='".$_SESSION['CompleteDocument']."' and `documenttype`='18'", $db);
    DB_query("delete from `AssetsHeader` where documentno='".$_SESSION['CompleteDocument']."' and `documenttype`='18'", $db);
    prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been Deleted.');
    unset($_POST);
}

if($_POST['submit']=='Add New Line'){
     $POSclass->Nextorder();
}

if($_POST['submit']=="Enter Delivery Details and Confirm Order"){
        include('purchases/Assetsreadonly.inc');
} else {
    
    if(isset($_GET['new'])){
         $_SESSION['locked'] = true;
         if(($_SESSION['ManualNumber']==0)){
         $_POST['documentno'] = GetNextTransNo(18,$db);
         }
         prnMsg('Purchase order :'.$_POST['documentno'].' has been created');
         $POSclass->neworder();
    }
    
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);

    if(!isset($_POST['date'])){
        $_POST['date']= ConvertSQLDate($rowdate[0]);
    }

$ResultIndex = DB_query('Select NOW()+1 as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);

if(!isset($_POST['datedue'])){
    $_POST['datedue']= ConvertSQLDate($rowdate[0]);
}

echo '<table class="table-striped table-bordered"><tr><td valign="top">'
 . '<table class="table1"><caption>Purchase Order Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Purchaseoderdate" size="11" maxlength="10"   value="' .$_POST['Purchaseoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10"   value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required"/></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td>'
        . '<td>Supplier Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td></tr>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];

echo '<tr><td>Find Asset</td>'
    . '<td colspan="3"><input type="hidden" id="stockitemcode" size="5" name="stockitemcode" value="'.$_POST['stockitemcode'].'" />'
    . '<input type="text" id="stockname" size="30" name="stockname"  value="'.$_POST['stockname'].'" />'
    . '<input type="button" id="searchfixedassets" value="Search"/></td></tr>';

echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
echo '<table class="table-striped table-bordered"><thead><tr>'
        . '<th>Asset ID</th>'
        . '<th>Asset Description</th>'
        . '<th class="number">Quantity to Order</th>'
        . '<th class="number">Cost</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$itemcode = $_POST['stockitemcode'];
$POSclass->Getitems($_POST['stockitemcode'],$_POST['quantity'][$itemcode],$_POST['salesprice'][$itemcode]);

echo '</table>';
echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
        <code>' . _('To add line , just select another asset category') . '</code>
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Order') . '"  />
</div>';


echo '</td></tr></table>';

}
echo '</div></form>' ;

include('includes/footer.inc');
 
?>