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
    $_POST['manualdocumentno']= GetTempNextNo(10);
}

if(isset($_POST['delete']) and $_POST['delete']=='Delete Document for ever'){
    /* An order with any delivered quantity must not be deleted. */
    $delivCheck = DB_fetch_row(DB_query("SELECT COUNT(*) FROM SalesLine WHERE documentno='".$db->real_escape_string($_POST['documentno'])."' AND documenttype=1 AND IFNULL(Qunatity_delivered,0)>0", $db));
    if((int)$delivCheck[0] == 0){
     $sql= sprintf("delete from SalesLine where `documentno`='%s' "
             . " and `SalesLine`.`documenttype`='1' ",$_POST['documentno']);
     DB_query($sql, $db);

     $sql=sprintf("delete from SalesHeader where `documentno`='%s' "
             . " and `SalesHeader`.`documenttype`='1' ",$_POST['documentno']);
     DB_query($sql, $db);
   }else{
       prnMsg(_('Items on this order have been delivered, so it cannot be deleted.'),'warn');
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

/* Customer and tax settings drive both the line pricing and the verification
   of the frozen payload, so resolve them before anything is saved. */
$customerposting = '';
$VATinclusive = 0;
$IsTaxed = 1;

$sqldebtors=DB_query("SELECT `itemcode` ,`creditlimit`,`customer`
      ,`phone` ,`email` ,`city` ,`country`,`curr_cod`,`customerposting`,`salesman`,`VATinclusive`,`IsTaxed`
       FROM `debtors` join postinggroups on code=`customerposting` where itemcode='".$db->real_escape_string($_POST['CustomerID'])."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
if ($debtorsrow) {
    $customerposting = $debtorsrow[8];
    $VATinclusive = (int)(bool)$debtorsrow[10];
    $IsTaxed = (int)$debtorsrow[11];
}

$invoiceLines = null;

if(isset($_POST['confirmed']) && $_POST['confirmed'] === '1'){
    /* Re-derive the lines from the payload that was rendered, never from the
       current database state, so a delivery or repricing booked after the
       page was loaded cannot change what gets invoiced. */
    $invoiceLines = invoiceRebuildFrozenLines($db, $VATinclusive);

    if ($invoiceLines === false) {
        prnMsg(_('This invoice could not be verified. The form contents were altered or the page was tampered with. Reload the order and try again.'),'error');
    } else {
        prnMsg("Saving",'info');
        require_once('vendor/autoload.php');
        include_once('includes/EtimsService.inc');
        require_once('reports/BarCodeClass.inc');
        include('transactions/Saveinvoice.inc');
    }
}

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

/* Only lines with a delivered quantity can be invoiced. Pricing comes from the
   sales quotation (UnitPrice) and nothing else. */
$computed = computeInvoiceLines($db, $_POST['documentno'], $VATinclusive, $IsTaxed, $_POST);
$invoiceRows = $computed['lines'];

/* Advisory notices float over the grid rather than sitting in the document
   flow, so the table is not pushed out of position. */
if (!empty($computed['errors'])) {
    invoiceFloatNotice($computed['errors'], 'warn');
}

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

/* Categories drive the TS group headings below. */
$itemCatMap = array();
if (!empty($invoiceRows)) {
    $escCats = array_map(function($c) use ($db) { return "'" . mysqli_real_escape_string($db, $c) . "'"; }, array_unique(array_column($invoiceRows, 'code')));
    $catRes = DB_query("SELECT itemcode, category FROM stockmaster WHERE itemcode IN (" . implode(',', $escCats) . ")", $db);
    while ($cr = DB_fetch_array($catRes)) {
        $itemCatMap[trim($cr['itemcode'])] = $cr['category'] ?? '';
    }
}

$rowsHtml = array();
$rowCat = array();

foreach ($invoiceRows as $entryno => $row) {
    $stkcode = $row['code'];
    $rowCategory = $itemCatMap[$stkcode] ?? '';
    $rowDiscFromTable = 0;

    $runningnettotal += $row['netamount'];
    $runningvattotal += $row['vatamount'];
    $runninggrosstotal += $row['grossamount'];
    $runningshipping += round($row['Shipping'], 1);

/* Invoice lines keep the order's own line discount, no category override. */
    $appliedDiscount = $row['linediscountpercent'];

    /* A line with no quotation price gets an editable price box so it can be
       priced inline. Lines that already carry the quotation price stay
       read-only, so the agreed price cannot be altered by accident. */
    if ($row['priceMissing']) {
        $priceCell = '<input type="text" class="number priceinput" name="salesprice['.$entryno.']" size="8" value="" placeholder="0.00" />';
        $rowClass  = ' class="needprice"';
    } else {
        $priceCell = '<input type="text" class="number priceinput" name="salesprice['.$entryno.']" size="8" value="'.htmlspecialchars(number_format($row['salesprice'],2),ENT_QUOTES).'" readonly="readonly" />';
        $rowClass  = '';
    }

    $rowsHtml[$entryno] = '<tr'.$rowClass.' data-itemcode="'.htmlspecialchars($stkcode,ENT_QUOTES).'" data-category="'.htmlspecialchars($rowCategory,ENT_QUOTES).'" data-discount="'.htmlspecialchars($rowDiscFromTable,ENT_QUOTES).'">'
         .'<td>'.$stkcode.'</td>'
         .'<td>'.htmlspecialchars($row['description'],ENT_QUOTES).'</td>'
         .'<td><input type="text" name="sampleid['.$entryno.']" value="'.htmlspecialchars($row['sampleid'],ENT_QUOTES).'" size="10" /></td>'
         .'<td class="number">'.$row['qty'].'</td>'
         .'<td class="number">'.$priceCell.'</td>'
         .'<td class="number"><input type="text" class="number linediscountinput" name="linediscountpercent['.$entryno.']" size="6" value="'.htmlspecialchars(number_format($appliedDiscount,2),ENT_QUOTES).'" /></td>'
         .'<td class="number">'.number_format($row['netamount'],2).'</td>'
         .'<td class="number">'.number_format($row['vatamount'],2).'</td>'
         .'<td class="number">'.number_format($row['grossamount'],2).'</td>'
         .'<td class="number"><input type="text" name="TAT['.$entryno.']" size="4" value="'.htmlspecialchars($row['TAT'],ENT_QUOTES).'" /></td>'
         .'</tr>';
    $rowCat[$entryno] = $rowCategory;
}

/* Freeze the computed lines into a signed payload. The signature is verified on
   save, so these values are what gets invoiced regardless of later changes. */
echo '<input type="hidden" name="invoice_freeze" value="'.htmlspecialchars(base64_encode(json_encode($invoiceRows)),ENT_QUOTES).'" />';
echo '<input type="hidden" name="invoice_freeze_sig" value="'.invoiceFreezeSign($invoiceRows).'" />';


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


/* ------------------------------------------------------------------------
 * Floating notice
 *
 * Sits above the page instead of in the document flow, so it cannot shift
 * the grid or the totals out of alignment. Dismissable, and collapses to a
 * single line when there is only one message.
 * ---------------------------------------------------------------------- */
function invoiceFloatNotice($messages, $type = 'warn')
{
    $messages = array_values(array_unique((array)$messages));
    if (empty($messages)) {
        return;
    }

    $esc = function ($s) {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };

    if (count($messages) === 1) {
        $body = '<p class="fnv-msg">' . $esc($messages[0]) . '</p>';
    } else {
        $items = '';
        foreach ($messages as $m) {
            $items .= '<li>' . $esc($m) . '</li>';
        }
        $body = '<p class="fnv-count">' . count($messages) . ' items need attention:</p>'
              . '<ul class="fnv-list">' . $items . '</ul>';
    }

    echo '<div id="invoiceFloatNotice" class="fnv fnv-' . $esc($type) . '" role="alert">'
       . '<button type="button" class="fnv-close" title="Dismiss" '
       . 'onclick="document.getElementById(\'invoiceFloatNotice\').style.display=\'none\'">&times;</button>'
       . $body
       . '</div>';

    echo '<style>
    #invoiceFloatNotice.fnv{
        position:fixed; top:12px; right:12px; z-index:99999;
        max-width:420px; padding:12px 34px 12px 14px;
        background:#fffbe6; color:#5a3e00;
        border:1px solid #d9b64a; border-left:4px solid #d9b64a;
        border-radius:6px; box-shadow:0 4px 14px rgba(0,0,0,.18);
        font-size:13px; line-height:1.45; text-align:left;
    }
    #invoiceFloatNotice.fnv-error{
        background:#fdeaea; color:#7a1414; border-color:#c94a4a; border-left-color:#c94a4a;
    }
    #invoiceFloatNotice.fnv-info{
        background:#eaf3fd; color:#12456f; border-color:#4a90c9; border-left-color:#4a90c9;
    }
    #invoiceFloatNotice.fnv .fnv-msg{ margin:0; }
    #invoiceFloatNotice.fnv .fnv-count{ margin:0 0 6px; font-weight:bold; }
    #invoiceFloatNotice.fnv .fnv-list{ margin:0; padding-left:18px; }
    #invoiceFloatNotice.fnv .fnv-list li{ margin:2px 0; }
    #invoiceFloatNotice.fnv .fnv-close{
        position:absolute; top:6px; right:8px;
        background:none; border:0; font-size:20px; line-height:1;
        color:inherit; opacity:.6; cursor:pointer; padding:0 4px;
    }
    #invoiceFloatNotice.fnv .fnv-close:hover{ opacity:1; }
    tr.needprice td{ background:#fff6d5; }
    tr.needprice td.priceinput{ font-weight:bold; }
    </style>';
}


function calculateLineDiscount($amount, $discountPercent){
    $discountPercent = floatval($discountPercent);
    if($discountPercent <= 0){
        return array('amount_after' => $amount, 'discount_amount' => 0.0);
    }
    $discount_amount = ($amount * $discountPercent) / 100.0;
    $amount_after = $amount - $discount_amount;
    return array('amount_after' => $amount_after, 'discount_amount' => $discount_amount);
}


/* ------------------------------------------------------------------------
 * Invoice line freezing
 *
 * Line values are computed on page load and frozen into a signed hidden
 * payload. On save the payload is verified, so a price list edit, a repricing
 * or a delivery booked after the page was rendered cannot change what gets
 * invoiced. The signature also prevents a browser-side edit of the hidden
 * fields.
 * ---------------------------------------------------------------------- */

/** Server-side signing key. Never emitted into the page markup. */
function invoiceFreezeKey(){
    global $InvoiceFreezeSecret;

    if (!empty($InvoiceFreezeSecret)) {
        return (string)$InvoiceFreezeSecret;
    }
    if (empty($_SESSION['InvoiceFreezeKey'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['InvoiceFreezeKey'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['InvoiceFreezeKey'] = sha1(uniqid((string)mt_rand(), true));
        }
    }
    return $_SESSION['InvoiceFreezeKey'];
}

/** Fields carried in the frozen payload, in a fixed order. */
function invoiceFrozenFields(){
    return array(
        'entryno', 'code', 'description', 'unitofmeasure', 'qty',
        'salesprice', 'PriceInPricelist', 'vatrate', 'inclusive',
        'locationcode', 'partperunit', 'containercode',
        'Shipping', 'linepackage', 'totalemptycost', 'totalchargedcontainers',
        /* Governs whether a posted price may override the frozen one, so it
           must itself be signed or the flag can be forged. */
        'priceMissing',
    );
}

/** Canonical string form of the frozen set, used as the HMAC message. */
function invoiceFreezeCanonical($rows){
    $fields = invoiceFrozenFields();
    $lines = array();

    foreach ($rows as $entryno => $row) {
        $parts = array((string)$entryno);
        foreach ($fields as $field) {
            $parts[] = isset($row[$field]) ? (string)$row[$field] : '';
        }
        $lines[] = implode('|', $parts);
    }
    return implode("\n", $lines);
}

function invoiceFreezeSign($rows){
    return hash_hmac('sha256', invoiceFreezeCanonical($rows), invoiceFreezeKey());
}

/**
 * Net / VAT / gross for one line.
 *
 * The base (salesprice * qty) is frozen; the line discount is applied to it
 * here, so a discount typed into the form is honoured even when the user
 * confirms without pressing Re-Calculate.
 */
function invoiceLineAmounts($salesprice, $qty, $discountpercent, $rate, $VATinclusive, $Shipping, $linepackage){
    $baseamount = ((float)$salesprice) * ((float)$qty);
    $disc = calculateLineDiscount($baseamount, $discountpercent);
    $after = $disc['amount_after'];
    $Shipping = (float)$Shipping;
    $linepackage = (float)$linepackage;
    $rate = (float)$rate;

    if ($VATinclusive) {
        $netamount = $after * (1 - ($rate / (100 + $rate)));
        $vatamount = $after * ($rate / (100 + $rate));
        $grossamount = $netamount + $vatamount + $Shipping + $linepackage;
    } else {
        $vatamount = $after * ($rate / 100);
        $grossamount = $after + $vatamount + $Shipping + $linepackage;
        $netamount = $after;
    }

    return array(
        'baseamount'    => $baseamount,
        'discountamount' => $disc['discount_amount'],
        'netamount'     => $netamount,
        'vatamount'     => $vatamount,
        'grossamount'   => $grossamount,
    );
}

/**
 * Load the order lines that may be invoiced and compute their amounts.
 *
 * Lines with no delivered quantity are excluded in SQL so the grid, the
 * totals and the save all work from one set. Price is the agreed quotation
 * price (UnitPrice) and nothing else.
 *
 * Returns array('lines' => rows keyed by entryno, 'errors' => array of strings).
 */
function computeInvoiceLines($db, $documentno, $VATinclusive, $IsTaxed, $post = array()){
    $query = "SELECT `entryno`,`documenttype`,`docdate`,`documentno`,`locationcode`
        ,`stocktype`,`code`,`description`,`unitofmeasure`
        ,`Qunatity_delivered`,`UnitPrice`
        ,`vatrate`,`inclusive`,`partperunit`,`containercode`
        ,`totalchargedcontainers`,`PriceInPricelist`,`LineDiscountPercent`,`SampleID`,`TAT`
      FROM `SalesLine`
      WHERE `documentno`='" . $db->real_escape_string($documentno) . "'
        AND `code` IS NOT NULL AND `code` != ''
        AND IFNULL(`Qunatity_delivered`,0) > 0
      ORDER BY `entryno`";

    $ResultIndex = DB_query($query, $db);

    $lines = array();
    $errors = array();

    while ($stocklist = DB_fetch_array($ResultIndex)) {
        $entryno = (string)$stocklist['entryno'];
        $stkcode = trim($stocklist['code']);

        /* Quotation price, unconditionally. No price list lookup. */
        $salesprice = (float)$stocklist['UnitPrice'];

        /* A line with no quotation price is still invoiced: it is shown with
           an empty editable price so the user can supply one. It is flagged so
           the notice can point at it, not hidden away. */
        $priceMissing = ($salesprice <= 0);

        if ($priceMissing && isset($post['salesprice'][$entryno]) && $post['salesprice'][$entryno] !== '') {
            $salesprice = (float)$post['salesprice'][$entryno];
            $priceMissing = ($salesprice <= 0);
        }

        if ($priceMissing) {
            $errors[] = sprintf(
                _('Item %1$s (%2$s) has no price on the sales quotation. Enter a price to include it on the invoice.'),
                $stkcode,
                trim($stocklist['description'])
            );
        }

        $qty = (float)$stocklist['Qunatity_delivered'];
        $rate = ((int)$IsTaxed == 0) ? 0 : (float)$stocklist['vatrate'];
        $containercode = trim($stocklist['containercode']);

        $Shipping = 0;
        if (isset($post['Shipping'][$entryno])) {
            $Shipping = (float)$post['Shipping'][$entryno];
        }
        $linepackage = 0;

        /* Discount: order line value, then a form override. */
        $lineDiscountPercent = (float)($stocklist['LineDiscountPercent'] ?? 0);
        if (isset($post['linediscountpercent'][$entryno])) {
            $lineDiscountPercent = (float)$post['linediscountpercent'][$entryno];
        } elseif (isset($post['LineDiscountPercent'][$entryno])) {
            $lineDiscountPercent = (float)$post['LineDiscountPercent'][$entryno];
        } elseif (isset($post['DiscountPercent']) && $post['DiscountPercent'] !== '') {
            $lineDiscountPercent = (float)$post['DiscountPercent'];
        }

        $amounts = invoiceLineAmounts(
            $salesprice, $qty, $lineDiscountPercent, $rate, $VATinclusive, $Shipping, $linepackage
        );

        $lines[$entryno] = array(
            'entryno'        => $entryno,
            'code'           => $stkcode,
            'description'    => trim($stocklist['description']),
            'unitofmeasure'  => $stocklist['unitofmeasure'],
            'qty'            => $qty,
            'salesprice'     => $salesprice,
            'PriceInPricelist' => (float)($stocklist['PriceInPricelist'] ?? 0),
            'vatrate'        => $rate,
            'inclusive'      => (int)((bool)$VATinclusive),
            'locationcode'   => $stocklist['locationcode'],
            'partperunit'    => (float)($stocklist['partperunit'] ?? 0),
            'containercode'  => $containercode,
            'Shipping'       => $Shipping,
            'linepackage'    => $linepackage,
            'totalemptycost' => 0,
            'totalchargedcontainers' => (float)($stocklist['totalchargedcontainers'] ?? 0),
            'linediscountpercent' => $lineDiscountPercent,
            'priceMissing'   => $priceMissing,
            'sampleid'       => isset($stocklist['SampleID']) ? $stocklist['SampleID'] : '',
            'TAT'            => isset($stocklist['TAT']) ? $stocklist['TAT'] : '',
            'netamount'      => $amounts['netamount'],
            'vatamount'      => $amounts['vatamount'],
            'grossamount'    => $amounts['grossamount'],
            'baseamount'     => $amounts['baseamount'],
            'discountamount' => $amounts['discountamount'],
        );
    }

    return array('lines' => $lines, 'errors' => $errors);
}

/**
 * Decode and verify the frozen payload posted with the confirmation.
 *
 * Returns the verified rows with totals recomputed from the frozen base and
 * the posted discount, or false when the payload is missing or has been
 * tampered with.
 */
function invoiceRebuildFrozenLines($db, $VATinclusive){
    if (!isset($_POST['invoice_freeze']) || !isset($_POST['invoice_freeze_sig'])) {
        return false;
    }

    $raw = base64_decode($_POST['invoice_freeze'], true);
    if ($raw === false) {
        return false;
    }
    $rows = json_decode($raw, true);
    if (!is_array($rows) || empty($rows)) {
        return false;
    }

    /* Verify against the values as received, before any recalculation. */
    if (!hash_equals(invoiceFreezeSign($rows), (string)$_POST['invoice_freeze_sig'])) {
        return false;
    }

    $verified = array();
    foreach ($rows as $entryno => $row) {
        if (!is_array($row)) {
            return false;
        }
        foreach (invoiceFrozenFields() as $field) {
            if (!isset($row[$field])) {
                return false;
            }
        }

        /* Discount, sample ID and TAT are user-editable on the form. */
        $discount = $row['linediscountpercent'];
        if (isset($_POST['linediscountpercent'][$entryno])) {
            $discount = (float)$_POST['linediscountpercent'][$entryno];
        }

        /* Price is editable only where the quotation had none. Where the
           quotation set a price, the signed frozen value stands and a posted
           value cannot override it. */
        if (!empty($row['priceMissing']) && isset($_POST['salesprice'][$entryno])
            && $_POST['salesprice'][$entryno] !== '') {
            $row['salesprice'] = (float)$_POST['salesprice'][$entryno];
            $row['priceMissing'] = ($row['salesprice'] <= 0);
        }
        $sampleid = $row['sampleid'];
        if (isset($_POST['sampleid'][$entryno]) && $_POST['sampleid'][$entryno] !== '') {
            $sampleid = $_POST['sampleid'][$entryno];
        }
        $tat = $row['TAT'];
        if (isset($_POST['TAT'][$entryno]) && $_POST['TAT'][$entryno] !== '') {
            $tat = $_POST['TAT'][$entryno];
        }

        /* Tax basis comes from the signed payload, not from the debtor as it
           stands now. If the debtor's flag changed since the invoice was
           rendered, refuse rather than silently reprice the confirmed lines. */
        $frozenInclusive = (int)((bool)$row['inclusive']);
        if ((int)((bool)$VATinclusive) !== $frozenInclusive) {
            return false;
        }

        $amounts = invoiceLineAmounts(
            (float)$row['salesprice'], (float)$row['qty'], $discount,
            (float)$row['vatrate'], $frozenInclusive, $row['Shipping'], $row['linepackage']
        );

        $row['linediscountpercent'] = (float)$discount;
        $row['sampleid'] = $sampleid;
        $row['TAT'] = $tat;
        $row['qty'] = (float)$row['qty'];
        $row['salesprice'] = (float)$row['salesprice'];
        $row['vatrate'] = (float)$row['vatrate'];
        $row['inclusive'] = $frozenInclusive;
        $row['netamount'] = $amounts['netamount'];
        $row['vatamount'] = $amounts['vatamount'];
        $row['grossamount'] = $amounts['grossamount'];
        $row['baseamount'] = $amounts['baseamount'];
        $row['discountamount'] = $amounts['discountamount'];

        $verified[$entryno] = $row;
    }

    return $verified;
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
