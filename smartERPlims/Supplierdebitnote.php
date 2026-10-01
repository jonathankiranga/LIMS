<?php

$Title = _('Create Supplier Debit Note');

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

include('includes/header.inc');   
include('purchases/stockbalance.inc');   

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Create Supplier Debit Note') .'" alt="" />' . ' ' . _('Create Supplier Debit Note') . '</p>';

if(isset($_GET['No'])){
    $_POST['documentno'] = $_GET['No'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    
}
    
if(isset($_POST['submit']) and $_POST['submit']=='Confirm Debit Note'){
    include('purchases/savesupplierdebitnote.inc');  
}

echo '<a href="'.htmlspecialchars('PurchaseOrderDBlist.php',ENT_QUOTES,'UTF-8').'">Purchase Orders</a>';
     
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
           ,stockledger.jobcard
       FROM stockledger join `PurchaseHeader`  
       on `PurchaseHeader`.`documentno`=`stockledger`.`invref`
       join PurchaseLine on `PurchaseHeader`.`documentno`=`PurchaseLine`.`documentno`
      where stockledger.jobcard='".$_POST['documentno']."' "
        . "and stockledger.doctyp='30' ";

$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
       
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
  
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="purchasesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<input type="hidden" name="LpoNo" value="'. $rowresults[1] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
. '<table class="table table-bordered"><caption>GRN Invoice Header Details</caption>';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Posting Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="posingdate" size="11" maxlength="10" required="required" value="' .$_POST['posingdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';

echo '<tr><td>DEBIT NOTE No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly" /></td>'
        . '<td>Delivery No/Supplier Invoice No</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" required="required" /></td></tr>';

echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
       . '<td>Supplier Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" readonly="readonly"/></td></tr>';
echo '<tr><td>Currency Code</td><td><input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</tr></table></td></tr><tr><td>';
  
$sql="SELECT `itemcode`,`customer`,
      `phone`,`email`,`city`,`country`,`curr_cod`,
      `supplierposting`,`VATinclusive`,`IsTaxed`
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
    ,`PurchaseLine`.`UnitPrice`
    ,`PurchaseLine`.`vatamount`
    ,`PurchaseLine`.`invoiceamount`
    ,`PurchaseLine`.`vatrate` 
    ,`PurchaseLine`.`inclusive`
    ,`PurchaseLine`.`PartPerUnit`
    ,stockmaster.`averagestock`
    ,`PurchaseLine`.`UOM`
FROM  PurchaseLine 
join `stockmaster` on `PurchaseLine`.`code`=`stockmaster`.`itemcode` 
join `stockledger` on `PurchaseLine`.`documentno`=`stockledger`.`jobcard` 
and stockledger.jobcard='".$_POST['documentno']."' and stockledger.doctyp='30' 
and stockledger.itemcode=PurchaseLine.code";


 $ResultIndex = DB_query($Slaqry,$db);
echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Parts</th>'
        . '<th class="number">VAT<br />RATE</th>'
        . '<th class="number">Qty<br />Returned</th>'
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
    $InfRowContainers = ContainerInfo($stkcode);
    $rate = $stocklist['vatrate'];
    $location = $_POST['location'][$itemcode];
    
    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
      
    $qty = $stocklist['Quantity'];
     
     if($_POST['UOM'][$itemcode]){
         $UOM = $_POST['UOM'][$itemcode];
     }else{
        $UOM = $stocklist['UOM'];
    }
       
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
         $salesprice = $stocklist['UnitPrice'];
    }
       
    if($stocklist['PartPerUnit']>1){
       $baseamount = (($salesprice * $stocklist['PartPerUnit']) * $qty );
     }else{
       $baseamount = ($salesprice * $qty);
     }
                
     
    if($VATinclusive==1){
        $netamount  = $baseamount / (($rate+100) * .01);
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
    
    echo '<tr><td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>';
         echo sprintf('<td>%s</td>',trim($stocklist['description']));
         echo sprintf('<td>%s</td>',$Controlbox[$UOM]);
         echo sprintf('<td>%s</td>',number_format($stocklist['partperunit'],0));
         echo sprintf('<td>%s</td>',number_format($rate,2));
     echo'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
         .'<td><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
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
	<input type="submit" name="submit" value="' . _('Confirm Debit Note') . '"
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