<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Create Supplier Invoice');
include('includes/header.inc');   
include('purchases/stockbalance.inc');

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Create Supplier Invoice') .'" alt="" />' . ' ' . _('Create Supplier Invoice') . '</p>';

if(isset($_GET['No'])){
    $_POST['documentno'] = $_GET['No'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    $_POST['manualdocumentno']= GetTempNextNo(20);
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
}
    
if(isset($_POST['submit']) and $_POST['submit']=='Enter Delivered Details and Confirm Invoice'){
    include('purchases/savesupplierinvoice.inc');  
}

     
$filter="SELECT 
            `PurchaseHeader`.`documenttype`
           ,`PurchaseHeader`.`documentno`
           ,`PurchaseHeader`.`docdate`
           ,`PurchaseHeader`.`oderdate`
           ,`PurchaseHeader`.`duedate`
           ,`PurchaseHeader`.`postingdate`
           ,`PurchaseHeader`.`vendorcode`
           ,`PurchaseHeader`.`vendorname`
           ,`PurchaseHeader`.`yourreference`
           ,`PurchaseHeader`.`externaldocumentno`
           ,`PurchaseHeader`.`locationcode`
           ,`PurchaseHeader`.`paymentterms`
           ,`PurchaseHeader`.`postinggroup`
           ,`PurchaseHeader`.`currencycode`
           ,`PurchaseHeader`.`vatinclusive`
       FROM `PurchaseHeader`
       join PurchaseLine on `PurchaseHeader`.`documentno`=`PurchaseLine`.`documentno`
       where `PurchaseHeader`.`documentno`='".$_POST['documentno']."' and `PurchaseHeader`.`documenttype`='18' ";

    $ResultIndex= DB_query($filter, $db);
    $rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    $_POST['posingdate'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
  
echo '<div class="centre"><a href="'.htmlspecialchars('GoodsReceivedNote.php',ENT_QUOTES,'UTF-8').'">Go To Goods Received Note</a></div>';

echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="purchasesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<input type="hidden" name="LpoNo" value="'. $rowresults[1] .'" />';
echo '<table class="table-bordered" cellspacing="4"><tr><td valign="top">';
echo '<table class="table table-bordered"><caption>GRN Invoice Header Details</caption>';
echo '<tr><td>GRN Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Posting Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="posingdate" size="11" maxlength="10" required="required" value="' .$_POST['posingdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>GRN No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="10" readonly="readonly" />'.$_POST['documentno'].'</td>'
        . '<td>Invoice No</td>'
        . '<td><input tabindex="4" type="hidden" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required" />'.$_POST['manualdocumentno'].'</td>'
        . '<td>Delivery No/Supplier Invoice No</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" required="required" /></td></tr>';
echo '<tr><td><input tabindex="4" type="hidden" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . 'Supplier Name</td>'
        . '<td><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" readonly="readonly"/></td>';
echo '<td>Currency Code</td><td><input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td></tr>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</tr></table></td></tr><tr><td>';
  
$sql="SELECT 
       `itemcode`,`customer`,
      `phone`,`email`,
      `city`,`country`,
      `curr_cod`,`supplierposting`,
      `VATinclusive`,`IsTaxed`
       FROM `creditors` join arpostinggroups on code=`supplierposting` 
       where itemcode='".$_POST['CustomerID']."'";

$sqldebtors = DB_query($sql, $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[7];
$VATinclusive = $debtorsrow[8];
$IsTaxed = $debtorsrow[9];

$Slaqry ="SELECT 
     `PurchaseLine`.`entryno`
    ,`PurchaseLine`.`documenttype`
    ,`PurchaseLine`.`docdate`
    ,`PurchaseLine`.`documentno`
    ,`PurchaseLine`.`locationcode`
    ,`PurchaseLine`.`stocktype`
    ,`PurchaseLine`.`code` 
    ,`PurchaseLine`.`description`
    ,`PurchaseLine`.`unitofmeasure`
    ,`PurchaseLine`.`Quantity`
    ,`PurchaseLine`.`Quantity_toinvoice`
    ,`PurchaseLine`.`Qunatity_delivered` 
    ,`PurchaseLine`.`PriceToReceive` 
    ,`PurchaseLine`.`vatamount`
    ,`PurchaseLine`.`invoiceamount`
    ,`PurchaseLine`.`vatrate`  
    ,`PurchaseLine`.`inclusive`
    ,`PurchaseLine`.`partperunit`
    ,stockmaster.`averagestock`
    ,`PurchaseLine`.`UOM`
    ,`PurchaseLine`.`discount`
FROM  PurchaseLine 
join stockmaster on `PurchaseLine`.`code`=stockmaster.itemcode  
Where `PurchaseLine`.`documentno`='".$_POST['documentno']."' and `PurchaseLine`.`documenttype`='18' 
 group by
     `PurchaseLine`.`entryno`
    ,`PurchaseLine`.`documenttype`
    ,`PurchaseLine`.`docdate`
    ,`PurchaseLine`.`documentno`
    ,`PurchaseLine`.`locationcode`
    ,`PurchaseLine`.`stocktype`
    ,`PurchaseLine`.`code` 
    ,`PurchaseLine`.`description`
    ,`PurchaseLine`.`unitofmeasure` 
    ,`PurchaseLine`.`PriceToReceive` 
    ,`PurchaseLine`.`vatrate`  
    ,`PurchaseLine`.`inclusive`
    ,`PurchaseLine`.`partperunit`
    ,stockmaster.`averagestock`
    ,`PurchaseLine`.`UOM`
    ,`PurchaseLine`.`Quantity`
    ,`PurchaseLine`.`Quantity_toinvoice`
    ,`PurchaseLine`.`Qunatity_delivered`
    ,`PurchaseLine`.`vatamount`
    ,`PurchaseLine`.`invoiceamount`
    ,`PurchaseLine`.`discount` ";


 $ResultIndex = DB_query($Slaqry,$db);
echo '<table  class="table-bordered table"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Parts</th>'
        . '<th class="number">Ave Cost<br /> per part</th>'
        . '<th class="number">Qty to<br />Invoice</th>'
        . '<th class="number">Purchase Cost<br /> per part</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $containercode = trim($stocklist['container']);
    $rate = (($IsTaxed==1)?$stocklist['vatrate']:0);
    
    $InfRowContainers = ContainerInfo($stkcode);
    $location = $_POST['location'][$itemcode];
    
    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
      
    $qty = $stocklist['Qunatity_delivered'];
    $partperunit = $stocklist['partperunit'];
     if($_POST['UOM'][$itemcode]){
         $UOM=$_POST['UOM'][$itemcode];
     }else{
        $UOM = $stocklist['UOM'];
    }
       
   // `PriceToReceive` 
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
        $salesprice = $stocklist['PriceToReceive']-$stocklist['discount'];
    }
       
      $baseamount = ($salesprice * $qty * $partperunit);
     
                
    if($VATinclusive==1){
        $netamount  = $baseamount / ( ($rate+100)/100);
        $vatamount  = $baseamount - $netamount ;
        $grossamount= $baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
         
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    $Controlbox = GetUnits($stkcode);
    
    echo '<tr><td><input type="hidden" name="id['.$stocklist['entryno'].']" value="'.$stocklist['entryno'].'"/>'
    . '<input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>';
         echo sprintf('<td>%s</td>',trim($stocklist['description']));
         echo sprintf('<td>%s</td>',$Controlbox[$UOM]);
         echo sprintf('<td>%s</td>',number_format($stocklist['partperunit'],0));
         echo sprintf('<td>%s</td>',number_format($stocklist['averagestock'],2));
     echo'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" readonly="readonly" size="8"/></td>'
         .'<td><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="8"/></td>'
         .'<td><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="10"/></td>'
         .'<td><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="10"/></td>'
         .'<td><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="10"/></td>'
         .'</tr>';
}
   
echo sprintf('<tfoot><tr>'
        . '<td colspan="6"></td>'
              . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';


echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivered Details and Confirm Invoice') . '"
            onclick="return confirm(\''._('Have you selected the correct GRN. Once saved it cannot be changed ?').'\');" />
</div>';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');


function ContainerInfo($itemcode){
    global $db;
    
    $ResultIndex = DB_query("SELECT 
        `stockmaster`.`container`,
        `c`.`itemcode` ,
        `c`.`descrip` as `packname`,
        IFNULL(`cv`.`vat`,0) as CVAT
  FROM `stockmaster` 
  left join `stockmaster` c on `stockmaster`.`container`=`c`.`itemcode`
  left join `inventorypostinggroup` ci on `c`.`postinggroup`=`ci`.`code`
  left join `vatcategory` cv on `ci`.`vatcategory`=`cv`.`vatc`
  where `stockmaster`.`itemcode`='".$itemcode."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   return $stkmaster;
}
?>