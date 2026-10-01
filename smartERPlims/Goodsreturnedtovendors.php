<?php
$Title = _('Debit Note');
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
include('includes/header.inc');  
include('includes/PostStockCost.inc');  
include('purchases/stockbalance.inc');   
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Debit Note') .'" alt="" />' . ' ' . _('Debit Note') . '</p>';
echo '<p class="page_title_text"><a href="'.htmlspecialchars('PurchaseOrderDBlist.php',ENT_QUOTES,'UTF-8').'">Go to Purchase orders list</a></p>';

if(isset($_POST['submit']) and $_POST['submit']=='Return Goods'){
    if($_SESSION['Grossamounttotal']==0){
      $_POST['submit']="Empty";
    }
}

if(isset($_POST['submit']) and $_POST['submit']=='Return Goods'){
    include('purchases/Savecreditnote.inc');  
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
       FROM `PurchaseHeader` 
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
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Purchase Order Header </caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Order No</td>'
        . '<td>'.$_POST['documentno'].'<input  type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
        . '<td>External Reference No</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" required="required" autofocus="autofocus"/></td></tr>';

echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '</td>'
        . '<td>Supplier Name</td>'
        . '<td>'.$_POST['VendorName'].'<input type="hidden" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  readonly="readonly"/></td>';

echo '<td>Currency Code</td><td>'.$_POST['currencycode'].'<input  type="hidden" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';


echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT `itemcode`,`customer`,`phone`,
    `email`,`city`,`country`,`curr_cod`,`supplierposting`,
    `VATinclusive`,`IsTaxed`  FROM `creditors` 
    join arpostinggroups on `arpostinggroups`.`code`=`creditors`.`supplierposting` 
    where itemcode='".$_POST['VendorID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[7];
$VATinclusive = $debtorsrow[8];
$IsTaxed= $debtorsrow[9];

      
$SQL="SELECT `entryno`
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
      ,PurchaseLine.partperunit
      ,stockmaster.averagestock
  FROM `PurchaseLine` 
  join `stockmaster`  on `PurchaseLine`.code=`stockmaster`.itemcode where `documentno`='".$_POST['documentno']."'";


$ResultIndex = DB_query($SQL, $db);
echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Kit Size</th>'
        . '<th class="number">Ave Cost<br /> per part</th>'
        . '<th class="number">Qty <br />Ordered</th>'
        . '<th class="number">Qty to<br />Return</th>'
        . '<th class="number">Qty <br />Balance</th>'
        . '<th class="number">Pick From Store</th>'
        . '<th class="number">Price<br /> per part</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    
    $itemcode=trim($stocklist['entryno']);
    $stkcode=trim($stocklist['code']);
    $UOM=trim($stocklist['unitofmeasure']);
    $rate=$stocklist['vatrate'];
    $partperunit=(float) $stocklist['partperunit'];
    
    if(isset($_POST['ordered'][$itemcode])){
        $qtyorderd = $_POST['ordered'][$itemcode];
    }else{
        $qtyorderd = $stocklist['Quantity'];
    }
    
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode];
    }else{
        $qty = $stocklist['Quantity_toinvoice'];
    }
   
     $qtytoreceive =($qtyorderd-($stocklist['Qunatity_delivered']+$qty));
     
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
         $salesprice = $stocklist['UnitPrice'];
    }
   
    if($partperunit==1){
         $baseamount = ($qty * $salesprice);
    }else{
         $baseamount = ($qty * $salesprice) * $partperunit;
    }
    
     
    $location= $_POST['location'][$itemcode];
    if(mb_strlen($location)==0){ 
        $location=$_SESSION['Stores'][0]['code']; 
    }
    
    if($VATinclusive==TRUE){
        $grossamount=$baseamount ;
        $vatamount = $baseamount * ($rate/(100+$rate));
        $netamount = $grossamount-$vatamount;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
    
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    
    
    echo sprintf('<tr>'
         .'<td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="6" readonly="readonly"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="integer" name="partperunit['.$itemcode.']" value="%s" readonly="readonly" size="10"/></td>'//
         .'<td>%s</td>'
         .'<td class="number">'.$qtyorderd.'<input type="hidden" class="integer" name="ordered['.$itemcode.']" value="'.$qtyorderd.'" readonly="readonly" size="10"/></td>'
         .'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" size="10"/></td>'
         .'<td class="number">'.$qtytoreceive.'<input type="hidden" class="integer" name="balance['.$itemcode.']" value="'.$qtytoreceive.'" readonly="readonly" size="10"/></td>'
         .'<td>%s</td>'
         .'<td class="number">'.$salesprice.'<input type="hidden" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="10"/></td>'
         .'<td class="number">'.$netamount.'<input type="hidden" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="10"/></td>'
         .'<td class="number">'.$vatamount.'<input type="hidden" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="10"/></td>'
         .'<td class="number">'.$grossamount.'<input type="hidden" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="10"/></td>'
         .'</tr>',trim($stocklist['description']),
                 trim($stocklist['unitofmeasure']),
                 $stocklist['partperunit'],
                 number_format($stocklist['averagestock'],2),
                 createstores($itemcode));
}
   
echo sprintf('<tfoot><tr><td>
        <input type="submit" name="submit" value="' . _('Re-Calculate') . '" /></td><td>
	<input type="submit" name="submit" value="' . _('Return Goods') . '"  /></td>'
              . '<td colspan="8">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
            number_format($runningnettotal,2),
            number_format($runningvattotal,2),
            number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';


echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');

?>