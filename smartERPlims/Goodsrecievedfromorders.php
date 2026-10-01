<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc'); 

$Title = _('Goods Recived Note');
include('includes/header.inc');   
include('purchases/stockbalance.inc');   

$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Goods Recived Note') .'" alt="" />' . ' ' . _('Goods Recived Note') . '</p>';

if(isset($_POST['submit']) and $_POST['submit']=='Receive Goods' and $_SESSION['Grossamounttotal']!=0){
    include('purchases/SaveGRN.inc');
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
     $_POST['manualdocumentno'] = GetTempNextNo(30);
    $_SESSION['DocumentPicking']=false;
}
    
echo '<p class="page_title_text"><a href="'.htmlspecialchars('PurchaseOrderList.php',ENT_QUOTES,'UTF-8').'">Go to Purchase orders list</a></p>';
    
    
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
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Purchase Order Header </caption>';

echo '<tr><td>Date</td><td class="number"><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Order No</td><td class="number"><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="10" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>GRN No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required" /></td>'
        . '<td>Suppliers Delivery No</td>'
        . '<td class="number"><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="10" required="required" autofocus="autofocus"/></td></tr>';

echo '<tr><td>Supplier ID</td><td class="number">'.$_POST['VendorID'].'<input  type="hidden" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"/></td>'
    . '<td>Supplier Name</td><td class="number">'.$_POST['VendorName'].'<input  type="hidden" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"/>';

echo '</td><td class="number">Currency Code</td><td class="number"><input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';


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
      ,`code`
      ,`description`
      ,`unitofreceivedIn`
      ,`Quantity` 
      ,`QuantityToReceive`
      ,`Quantity_toinvoice`
      ,`Qunatity_delivered`
      ,`UnitPrice`
      ,`vatamount`
      ,`invoiceamount`
      ,`completed`
      ,`printed`
      ,`vatrate`
      ,`inclusive`
      ,packzizegrn
      ,containercode
      ,`discount`
  FROM `PurchaseLine` 
 where `documentno`='".$_POST['documentno']."'";

 

$ResultIndex = DB_query($SQL, $db);
echo '<table  class="table table-bordered"><tr>'
        . '<td>Stock ID</td>'
        . '<td>Stock Description</td>'
        . '<td>Packed In</td>'
        . '<td>Store to Receive</td>'
        . '<td>Qty to<br />Ordered</td>'
        . '<td>Qty to<br />Recieve</td>'
        . '<td>Qty to<br />Expect</td>'
        . '<td>Parts</td>'
        . '<td>Invoice Price<br /> per part</td>'
        . '<td>Net Amount</td>'
        . '<td>VAT Amount</td>'
        . '<td>Gross Amount</td>'
        . '</tr>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode=trim($stocklist['entryno']);
    $stkcode=trim($stocklist['code']);
    $partperunit=$stocklist['packzizegrn'];
    $containercode=$stocklist['containercode'];
    $discount=(float) $stocklist['discount'];

    if($IsTaxed==true){
       $rate=(float) $stocklist['vatrate'];
    }else{
       $rate=0;
    }
     
    if(isset($_POST['ordered'][$itemcode]) and $_POST['ordered'][$itemcode]>0){
        $qtyorderd =(int) $_POST['ordered'][$itemcode];
    }else{
        $qtyorderd = $stocklist['QuantityToReceive'];
    }
    
     if(isset($_POST['Toreceive'][$itemcode])){
        $Toreceive =(int) $_POST['Toreceive'][$itemcode];
    }
    
   
     $qtytoreceive =($qtyorderd - ($stocklist['Qunatity_delivered'] + $Toreceive));
     
    if(isset($_POST['salesprice'][$itemcode]) and $_POST['salesprice'][$itemcode]>0){
        $salesprice =(float) $_POST['salesprice'][$itemcode];
    }else{
         $salesprice =(float) ($stocklist['UnitPrice']*$stocklist['Quantity']) / $stocklist['QuantityToReceive'];
    }
    
                
    if($discount>0){
      $discountAmount = ($salesprice * $Toreceive ) * round($discount/100,2);
    }
    
    if($partperunit>1){
         $baseamount = ($Toreceive * $salesprice * $partperunit)-$discountAmount ;
    }else{
         $baseamount = ($Toreceive * $salesprice) - $discountAmount;
    }
   
    
  if($VATinclusive == true){
        $netamount = $baseamount / (($rate+100)/100);
        $vatamount = $baseamount - $netamount ;
        $grossamount= $baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
    
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    
    $location = $_POST['location'][$itemcode];
    
    echo sprintf('<tr>'
         .'<td><input type="hidden" name="containercode['.$itemcode.']" value="'.$containercode.'"/>'.$stkcode.''
         .'<input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'"/>'
         .'<input type="hidden" name="partperunit['.$itemcode.']" value="'.$partperunit.'"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td class="number">'.$qtyorderd.'<input type="hidden"  name="ordered['.$itemcode.']" value="'.$qtyorderd.'"/></td>'
         .'<td><input type="text" class="integer" name="Toreceive['.$itemcode.']" value="'.$Toreceive.'" autofocus="autofocus" size="10"/></td>'
         .'<td class="number">'.$qtytoreceive.'<input type="hidden"  name="balance['.$itemcode.']" value="'.$qtytoreceive.'"/></td>'
         .'<td>%s</td>'
         .'<td class="number">'.$salesprice.'<input type="hidden"  name="salesprice['.$itemcode.']" value="'.$salesprice.'"/></td>'
         .'<td class="number">'.$netamount.'<input type="hidden"  name="netamount['.$itemcode.']" value="'.$netamount.'"/></td>'
         .'<td class="number">'.$vatamount.'<input type="hidden"  name="vatamount['.$itemcode.']" value="'.$vatamount.'"/></td>'
         .'<td class="number">'.$grossamount.'<input type="hidden"  name="grossamount['.$itemcode.']" value="'.$grossamount.'"/></td>'
         .'</tr>',trim($stocklist['description']), trim($stocklist['unitofreceivedIn']),  createstores($itemcode),
            $stocklist['partperunit']);
}
   
echo sprintf('<tr>'
              . '<td colspan="9">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', 
            number_format($runningnettotal,2),
            number_format($runningvattotal,2),
            number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;
echo sprintf('<tfoot><tr><td></td><td>%s</td><td>%s</td><td colspan="9"></td></tr></tfoot>', 
            '<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />',
            '<input type="submit" name="submit" value="' . _('Receive Goods') . '"  />');
echo '</table>';
echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');

?>