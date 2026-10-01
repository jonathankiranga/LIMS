<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Fixed Asset Picking Note');
include('includes/header.inc');   
include('purchases/stockbalance.inc');   

$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Fixed Asset Picking Note') .'" alt="" />' . ' ' . _('Fixed Asset Picking Note') . '</p>';

if(isset($_POST['submit']) and $_POST['submit']=='Collect Goods'){
    if($_SESSION['Grossamounttotal']==0){
      $_POST['submit']="Empty";
    }
}

if(isset($_POST['submit']) and $_POST['submit']=='Collect Goods'){
   include('purchases/Savehireinvoice.inc');  
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPicking']=false;
}
    
    
$filter="SELECT 
            `documenttype`
           ,`documentno`
           ,`docdate`
           ,`oderdate`
           ,`duedate`
           ,`postingdate`
           ,`vendorcode`
           ,`vendorname`
           ,`yourreference`
           ,`externaldocumentno`
           ,`locationcode`
           ,`paymentterms`
           ,`postinggroup`
           ,`currencycode`
           ,`vatinclusive`
       FROM `AssetsHeader` 
       where `documentno`='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
  
    $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['VendorID'] = $rowresults[6];
    $_POST['VendorName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Equipment for Hire Order Header </caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Salesoderdate" size="11" maxlength="10"  readonly="readonly" value="' .$_POST['Salesoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10" readonly="readonly"  value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Order No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
        . '<td>External Reference No</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" required="required" autofocus="autofocus"/></td></tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '</td>'
    . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';


echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    


$sqldebtors=DB_query("SELECT `itemcode` ,`creditlimit`,`customer`
      ,`phone` ,`email` ,`city` ,`country`,`curr_cod`,`customerposting`,`salesman`,`VATinclusive`,`islocal`
       FROM `debtors` join postinggroups on code=`customerposting`
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed= $debtorsrow[11];



$SQL="SELECT 
       `entryno`
      ,`documenttype`
      ,`docdate`
      ,`documentno`
      ,`locationcode`
      ,`stocktype`
      ,`code`
      ,`description`
      ,`unitofmeasure`
      ,`Quantity`
      ,`Quantity_toinvoice`
      ,`Qunatity_delivered`
      ,`UnitPrice`
      ,`vatamount`
      ,`invoiceamount`
      ,`completed`
      ,`printed`
      ,`vatrate`
      ,`inclusive`
      ,UOM
  FROM `FixedAssetsLine`   where `documentno`='".$_POST['documentno']."'";


$ResultIndex = DB_query($SQL, $db);
echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Asset ID</th>'
        . '<th class="number">Asset Description</th>'
        . '<th class="number">Qty to<br />Ordered</th>'
        . '<th class="number">Qty to<br />Pick</th>'
        . '<th class="number">Qty to<br />Remaining</th>'
        . '<th class="number">Price per unit</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
   $itemcode=trim($stocklist['entryno']);
    $stkcode=trim($stocklist['code']);
    $UOM=trim($stocklist['UOM']);

if($IsTaxed==true){
	$rate= $stocklist['vatrate'];
}else{
	$rate=0;
}
     
    if(isset($_POST['ordered'][$itemcode])){
        $qtyorderd = $_POST['ordered'][$itemcode];
    }else{
        $qtyorderd = $stocklist['Quantity'];
    }
    
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode];
    }else{
        $qty = ($stocklist['Quantity'] - $stocklist['Qunatity_delivered']);
    }
    
     $qtytoreceive =$qtyorderd- ($stocklist['Qunatity_delivered'] + $qty);
     
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
         $salesprice = $stocklist['UnitPrice'];
    }
    
    if($UOM=='fulqty'){
         $baseamount = ($qty * $salesprice) ;
    }else{
         $baseamount = ($qty * $salesprice);
    }
    
    $location= $_POST['location'][$itemcode];
    if(mb_strlen($location)==0){ $location=$_SESSION['Stores'][0]['code']; }
    
  if($VATinclusive == true){
        $netamount =$baseamount * (1 - ($rate/(100+$rate)));
        $vatamount = $netamount * ($rate/(100+$rate));
        $grossamount=$baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
    
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    
    
    echo sprintf('<tr>'
         .'<td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="integer" name="ordered['.$itemcode.']" value="'.$qtyorderd.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" size="5"/></td>'
         .'<td><input type="text" class="integer" name="balance['.$itemcode.']" value="'.$qtytoreceive.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
         .'<td><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
         .'<td></td>'
         .'</tr>',trim($stocklist['description']));
}
   
echo sprintf('<tfoot><tr>'
              . '<td colspan="6">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>',  number_format($runningnettotal,2),
            number_format($runningvattotal,2),  
        number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';
echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Collect Goods') . '"  />
</div>';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');

?>