<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Sales Invoice');
include('includes/header.inc');   
include('transactions/stockbalance.inc');  
require_once('vendor/autoload.php');
include_once('includes/EtimsService.inc');
require_once('reports/BarCodeClass.inc');

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    $_SESSION['Qunatity_delivered']=0;
    $_POST['manualdocumentno']= GetTempNextNo(10);
}

if(isset($_POST['confirmed']) && $_POST['confirmed'] === '1'){
    prnMsg("Saving",'info');
    require_once('vendor/autoload.php');
    include_once('includes/EtimsService.inc');
    require_once('reports/BarCodeClass.inc');
    include('transactions/Saveinvoice.inc');  
}


if(isset($_POST['delete']) and $_POST['delete']=='Delete Document for ever'){
    if($_SESSION['Qunatity_delivered']==0){
     $sql= sprintf("delete from SalesLine where `documentno`='%s' "
             . " and `SalesLine`.`documenttype`='1' ",$_POST['documentno']);
     DB_query($sql, $db);

     $sql=sprintf("delete from SalesHeader where `documentno`='%s' "
             . " and `SalesHeader`.`documenttype`='1' ",$_POST['documentno']);
     DB_query($sql, $db);
   }else{
       prnMsg("Items exist",'warn');
   }
}    

$filter="SELECT 
            `documenttype`
           ,`documentno`
           ,`docdate`
           ,`oderdate`
           ,`duedate`
           ,`postingdate`
           ,`customercode`
           ,`customername`
           ,`yourreference`
           ,`externaldocumentno`
           ,`locationcode`
           ,`paymentterms`
           ,`postinggroup`
           ,`currencycode`
           ,`salespersoncode`
           ,`vatinclusive`
           ,shipping
           ,packagescharge
       FROM `SalesHeader` 
       where `documentno`='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
        // populate quotation number from SalesHeader.externaldocumentno if present
        if(!isset($_POST['quotationno'])){
            $_POST['quotationno'] = isset($rowresults[9]) ? $rowresults[9] : '';
        }
    if(!isset($_POST['date'])){
      $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    }
    
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
    
    if(!isset($_POST['datedue'])){
      $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    }
  
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
    $Headershipping =(float) $rowresults[16];
     $Headerpackaging =(float) $rowresults[17];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
    
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<input type="hidden" name="confirmed" id="confirmed" value="" />';
echo '</div><div class="container-fluid">'
        . '<table class="table table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="10" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Invoice No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required"/></td>'
        . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly" /></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive` FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
// Quotation number (optional) - will be persisted to SalesHeader.externaldocumentno when invoice is saved

echo '</table>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
$runningshipping=0;
    
$sqldebtors=DB_query("SELECT `itemcode` ,`creditlimit`,`customer`
      ,`phone` ,`email` ,`city` ,`country`,`curr_cod`,`customerposting`,`salesman`,`VATinclusive`,`IsTaxed`
       FROM `debtors` join postinggroups on code=`customerposting` where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];

      
$Slaqry ="SELECT `entryno`,`documenttype`,`docdate`,`documentno`,`locationcode`
    ,`stocktype`,`code`,`description`,`unitofmeasure`,`Quantity`
    ,`Quantity_toinvoice`,`Qunatity_delivered`,`UnitPrice`,`vatamount`,`invoiceamount`
    ,`completed`,`printed` ,`containerprice`,`containersunits` 
    ,`totalchargedcontainers`,`containercode`,`vatrate` ,`inclusive`,`partperunit` 
    ,`PriceInPricelist`, `LineDiscountPercent`, `SampleID`, `TAT`
  FROM `SalesLine` 
  where `documentno`='".$_POST['documentno']."'
    and `code` is not null and `code` != '' ";


 
 
 
  $ResultIndex = DB_query($Slaqry,$db);

echo '<label>Filter: <input type="text" id="paramFilter" onkeyup="filterTable()" placeholder="Search code, description or sample ID..." size="40"></label>';
echo '<table class="table-condensed table-responsive-small table-bordered" id="invoiceTable"><tr>'
    . '<th>BarCode</th>'
    . '<th>Description</th>'
    . '<th>Sample ID</th>'
    . '<th>No Units</th>'
    . '<th>Sales Price</th>'
    . '<th>Disc (%)</th>'
    . '<th>Net Amount</th>'
    . '<th>VAT Amount</th>'
    . '<th>Gross Amount</th>'
    . '<th>TAT</th></tr>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
$runningshipping = 0;


// Pre-fetch item categories for all rows
$itemCatMap = array();
$itemDiscountMap = array();
$ResultIndex2 = DB_query($Slaqry, $db);
while ($sl = DB_fetch_array($ResultIndex2)) {
    $sc = trim($sl['code']);
    $itemCatMap[$sc] = '';
    $itemDiscountMap[$sc] = (float)($sl['LineDiscountPercent'] ?? 0);
}
if (!empty($itemCatMap)) {
    $escCats = array_map(function($c) use ($db) { return "'" . mysqli_real_escape_string($db, $c) . "'"; }, array_keys($itemCatMap));
    $catRes = DB_query("SELECT itemcode, category FROM stockmaster WHERE itemcode IN (" . implode(',', $escCats) . ")", $db);
    while ($cr = DB_fetch_array($catRes)) {
        $itemCatMap[trim($cr['itemcode'])] = $cr['category'] ?? '';
    }
    // Pre-fetch discounttable discounts
    $discRes = DB_query("SELECT itemcode, discount_percent FROM discounttable WHERE itemcode IN (" . implode(',', $escCats) . ") AND is_active = 1", $db);
    while ($dr = DB_fetch_array($discRes)) {
        $itemDiscountMap[trim($dr['itemcode'])] = (float)$dr['discount_percent'];
    }
}

while($stocklist=DB_fetch_array($ResultIndex)){
    
    $emptycost=0; $totalemptycost=0; $cvatamount =0; $cnetamount=0; $cgrossamount=0; $emptyunits=0;$Shipping=0;
    $PriceInPricelist=0;
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $rowCategory = $itemCatMap[$stkcode] ?? '';
    $rowDiscFromTable = $itemDiscountMap[$stkcode] ?? 0;
    $containercode = trim($stocklist['container']);
    $InfRowContainers = ContainerInfo($stkcode);
    $rate = ($IsTaxed==0)?0: $stocklist['vatrate'];
   
    $location = $_POST['location'][$itemcode];
    $qty = $stocklist['Qunatity_delivered'];
    $PriceInPricelist = $stocklist['PriceInPricelist'];
    $unitofmeasure = $stocklist['unitofmeasure'];
    $salesprice = $stocklist['PriceInPricelist'];
    if(!$salesprice || $salesprice == 0){
         $salesprice  = SelectTestPriceListToUse($stkcode,$qty,$_POST['CustomerID']) ;
         $PriceInPricelist = SelectTestPriceListToUse($stkcode,$qty) ;
         $salesprice = ($salesprice==0)?$PriceInPricelist:$salesprice;
    }
    $_SESSION['Qunatity_delivered'] += $qty;
       
    if($stocklist['partperunit']>1){
            $baseamount = ($salesprice * $qty);
 
            if(isset($_POST['emptycost'][$itemcode])){
                
                 $emptycost = $_POST['emptycost'][$itemcode];
                 $totalemptycost = ($emptycost * $qty);             
                 $crate = $InfRowContainers[3];
                 $cnetamount = $totalemptycost;
                 
                 if($VATinclusive==true){
                    $cvatamount = $cnetamount * ($crate/100+$crate);
                    $cgrossamount= $cnetamount ;
                }else{
                    $cvatamount = $cnetamount * ($crate/100);
                    $cgrossamount= $cnetamount + $cvatamount;
                }
                
            }
     } else {
          $baseamount = ($salesprice * $qty);
    }
    // determine line discount percent: prefer stored order value, then POST override(s), then header
    $lineDiscountPercent = 0.0;
    if (isset($stocklist['LineDiscountPercent'])) {
        $lineDiscountPercent = (float)$stocklist['LineDiscountPercent'];
    }
    // allow overrides from the form (old capitalization or new lowercase name)
    if (isset($_POST['linediscountpercent']) && is_array($_POST['linediscountpercent']) && isset($_POST['linediscountpercent'][$itemcode])) {
        $lineDiscountPercent = (float)$_POST['linediscountpercent'][$itemcode];
    } elseif (isset($_POST['LineDiscountPercent']) && is_array($_POST['LineDiscountPercent']) && isset($_POST['LineDiscountPercent'][$itemcode])) {
        $lineDiscountPercent = (float)$_POST['LineDiscountPercent'][$itemcode];
    } elseif (isset($_POST['DiscountPercent']) && $_POST['DiscountPercent'] !== '') {
        $lineDiscountPercent = (float)$_POST['DiscountPercent'];
    }
    $discountResult = calculateLineDiscount($baseamount, $lineDiscountPercent);
    $discountAmount = $discountResult['discount_amount'];
    $baseamount = $discountResult['amount_after'];
    // determine sample id and tat to show in UI (prefer stored values)
    $sampleValue = '';
    if (isset($stocklist['SampleID'])) {
        $sampleValue = $stocklist['SampleID'];
    } elseif (isset($stocklist['sampleID'])) {
        $sampleValue = $stocklist['sampleID'];
    }
    $tatValue = isset($stocklist['TAT']) ? $stocklist['TAT'] : (isset($stocklist['tat']) ? $stocklist['tat'] : '');
        
    
   $Shipping =(float)(isset($_POST['Shipping'][$itemcode])?$_POST['Shipping'][$itemcode] :$PostShipping); 
   
    if($VATinclusive==true){
        $netamount = ($baseamount  * (1- ($rate/(100+$rate)))) ;
        $vatamount = ($baseamount  * ($rate/(100+$rate))) ;
        $grossamount = $baseamount + $Shipping+$Postpackaging   ;
    }else{
        $vatamount  = ($baseamount  * ($rate/100));
        $grossamount = $baseamount  + $vatamount + $Shipping+$Postpackaging ;
        $netamount = $baseamount;
    }
  
    
    $runningnettotal += ($netamount);
    $runningvattotal += ($vatamount);
    $runninggrosstotal += ($grossamount) ;
    $runningshipping += round($Shipping,1);
    
    // Invoice lines keep the order's own line discount (no category override on invoices).
    $appliedDiscount = $lineDiscountPercent;

    $rowsHtml[$itemcode] = '<tr data-itemcode="'.htmlspecialchars($stkcode,ENT_QUOTES).'" data-category="'.htmlspecialchars($rowCategory,ENT_QUOTES).'" data-discount="'.htmlspecialchars($rowDiscFromTable,ENT_QUOTES).'">'
         .'<td>'.$stkcode.'</td>'
         .'<td>'.trim($stocklist['description']).'</td>'
         .'<td><input type="text" name="sampleid['.$itemcode.']" value="'.htmlspecialchars($sampleValue,ENT_QUOTES).'" size="10" /></td>'
         .'<td class="number">'.$qty.'</td>'
         .'<td class="number">'.number_format($salesprice,2).'</td>'
         .'<td class="number"><input type="text" class="number linediscountinput" name="linediscountpercent['.$itemcode.']" size="6" value="'.htmlspecialchars(number_format($appliedDiscount,2),ENT_QUOTES).'" /></td>'
         .'<td class="number">'.number_format($netamount,2).'</td>'
         .'<td class="number">'.number_format($vatamount,2).'</td>'
         .'<td class="number">'.number_format($grossamount,2).'</td>'
         .'<td class="number"><input type="text" name="TAT['.$itemcode.']" size="4" value="'.htmlspecialchars($tatValue,ENT_QUOTES).'" /></td>'
         .'</tr>';
    $rowCat[$itemcode] = $rowCategory;

    $_SESSION['invoiceRows'][$itemcode] = [
        'entryno' => $itemcode,
        'code' => $stkcode,
        'description' => trim($stocklist['description']),
        'unitofmeasure' => $stocklist['unitofmeasure'],
        'qty' => $qty,
        'salesprice' => $salesprice,
        'PriceInPricelist' => $PriceInPricelist,
        'UnitPrice' => $stocklist['UnitPrice'],
        'vatrate' => $stocklist['vatrate'],
        'inclusive' => $stocklist['inclusive'],
        'locationcode' => $stocklist['locationcode'],
        'partperunit' => $stocklist['partperunit'],
        'containercode' => $containercode,
        'linepackage' => isset($Postpackaging) ? $Postpackaging : 0,
        'Shipping' => $Shipping,
        'netamount' => $netamount,
        'vatamount' => $vatamount,
        'grossamount' => $grossamount,
        'emptycost' => $emptycost,
        'totalemptycost' => $cgrossamount,
        'discountamount' => $discountAmount,
        'linediscountpercent' => $lineDiscountPercent,
        'sampleid' => $sampleValue,
        'TAT' => $tatValue,
    ];
}
   
if (!isset($rowsHtml)) {
    $rowsHtml = array();
    $rowCat = array();
}

if (!defined('STDGROUP_CSS')) {
    define('STDGROUP_CSS', 1);
    echo '<style>.std-group td{background:#edf2fb !important;font-weight:bold;}</style>';
}

$tsGroups = array();
$looseEntries = array();
foreach ($rowsHtml as $entryno => $h) {
    $cat = $rowCat[$entryno] ?? '';
    if ($cat !== '' && preg_match('/^TS\d{4}$/', $cat)) {
        $tsGroups[$cat][] = $entryno;
    } else {
        $looseEntries[] = $entryno;
    }
}
$grpNames = array();
if (!empty($tsGroups)) {
    $gIn = array();
    foreach (array_keys($tsGroups) as $c) {
        $gIn[] = "'" . mysqli_real_escape_string($db, $c) . "'";
    }
    $gRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid IN (" . implode(',', $gIn) . ")", $db);
    while ($g = DB_fetch_array($gRes)) {
        $grpNames[trim($g['categoryid'])] = $g['categorydescription'];
    }
}
$grpOrder = array_keys($tsGroups);
usort($grpOrder, function($a, $b) use ($grpNames) {
    return strcasecmp($grpNames[$a] ?? $a, $grpNames[$b] ?? $b);
});
foreach ($grpOrder as $gcat) {
    echo '<tr class="std-group"><td colspan="10">' . htmlspecialchars(($grpNames[$gcat] ?? $gcat) . ' [' . $gcat . ']', ENT_QUOTES) . '</td></tr>';
    foreach ($tsGroups[$gcat] as $en) {
        echo $rowsHtml[$en];
    }
}
foreach ($looseEntries as $en) {
    echo $rowsHtml[$en];
}

echo sprintf('<tfoot>'
        . '<td colspan="4"></td>'
        . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningshipping,2),
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

             $_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table></td></tr><tr><td>';


echo '<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" id="confirmBtn" value="' . _('Enter Delivery Details and Confirm Invoice') . '"
            onclick="if(confirm(\''._('Are you sure you wish to Close this Invoice ?').'\')){ document.getElementById(\'confirmed\').value=\'1\'; return true; } else { return false; }" />'
        . '<input type="submit" name="delete" value="' . _('Invalidate Invoice ') . '"
            onclick="return confirm(\''._('Are you sure you wish to Invalidate This Document ?').'\');" />';

echo '</td></tr></table></div></form>';

echo '<script>
function filterTable(){
    var input = document.getElementById("paramFilter");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("invoiceTable");
    var rows = table.getElementsByTagName("tr");
    for(var i=1; i<rows.length; i++){
        var cells = rows[i].getElementsByTagName("td");
        if(!cells.length){ continue; }
        var match = false;
        for(var c=0; c<Math.min(3, cells.length); c++){
            if(cells[c].textContent.toUpperCase().indexOf(filter) > -1){
                match = true; break;
            }
        }
        rows[i].style.display = match ? "" : "none";
    }
}
</script>';

include('includes/footer.inc');


function calculateLineDiscount($amount, $discountPercent){
    $discountPercent = floatval($discountPercent);
    if($discountPercent <= 0){
        return array('amount_after' => $amount, 'discount_amount' => 0.0);
    }
    $discount_amount = ($amount * $discountPercent) / 100.0;
    $amount_after = $amount - $discount_amount;
    return array('amount_after' => $amount_after, 'discount_amount' => $discount_amount);
}


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