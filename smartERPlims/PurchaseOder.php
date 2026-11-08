<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('purchases/puchasescart.inc');
include('transactions/stockbalance.inc');   

$Title = _('PURCHASES');
include('includes/header.inc');
 
$POSclass = new PurchaseOrder();

if (!isset($_SESSION['units']) || !is_array($_SESSION['units'])) {
    $_SESSION['units'] = array();
    $ResultIndex = DB_query("SELECT code, descrip FROM unit", $db);
    while ($row = DB_fetch_array($ResultIndex)) {
        $code = trim($row['code']);
        $_SESSION['units'][$code] = $row;
    }
}

$rfqPrefillError = '';
if (isset($_GET['rfq_quote_id']) && ctype_digit((string)$_GET['rfq_quote_id'])) {
    $rfqQuoteId = (int)$_GET['rfq_quote_id'];
    $quoteResult = DB_query("SELECT q.id,q.rfq_id,q.supplier_code,q.quote_reference,q.tax_inclusive,h.rfq_no,h.currencycode,
            h.po_documentno,c.customer
        FROM rfq_quotes q
        JOIN rfq_header h ON h.id=q.rfq_id
        JOIN creditors c ON c.itemcode=q.supplier_code
        WHERE q.id=" . $rfqQuoteId . " AND h.awarded_quote_id=q.id
          AND h.status='awarded' AND h.po_documentno IS NULL", $db);
    if (DB_num_rows($quoteResult) !== 1) {
        $rfqPrefillError = 'This RFQ quotation has not been awarded, or a Purchase Order has already been created from it.';
    } else {
        $rfqQuote = DB_fetch_array($quoteResult);
        $quoteLinesResult = DB_query("SELECT rl.id,rl.itemcode,rl.description,rl.quantity,rl.requested_pack,
                ql.unit_price,ql.tax_rate,sm.itemcode AS stock_itemcode,sm.barcode,sm.descrip AS stock_description,
                sm.units,sm.subunits,sm.partperunit,ipg.vatcategory
            FROM rfq_quote_lines ql
            JOIN rfq_lines rl ON rl.id=ql.rfq_line_id
            LEFT JOIN stockmaster sm ON sm.itemcode=rl.itemcode AND (sm.inactive=0 OR sm.inactive IS NULL)
            LEFT JOIN inventorypostinggroup ipg ON ipg.code=sm.postinggroup
            WHERE ql.quote_id=" . $rfqQuoteId . " ORDER BY rl.line_no", $db);
        $prefillLines = array();
        $seenRfqItems = array();
        $lineError = '';
        while ($quoteLine = DB_fetch_array($quoteLinesResult)) {
            if (empty($quoteLine['stock_itemcode'])) {
                $lineError = 'Every RFQ line must be linked to an active inventory item before it can be converted to a Purchase Order. Missing mapping: ' . $quoteLine['description'];
                break;
            }
            $itemCodeKey = trim($quoteLine['stock_itemcode']);
            if (isset($seenRfqItems[$itemCodeKey])) {
                $lineError = 'The same inventory item appears on more than one RFQ line. Consolidate those lines before generating a Purchase Order so quantities and quoted prices are not overwritten.';
                break;
            }
            $seenRfqItems[$itemCodeKey] = true;
            $requestedPack = trim((string)$quoteLine['requested_pack']);
            if ($requestedPack === '') {
                $lineError = 'The buyer-defined packaging is missing for ' . $quoteLine['description'] . '. Recreate or correct the RFQ before creating the PO.';
                break;
            }
            $fullUnit = trim((string)$quoteLine['units']);
            $looseUnit = trim((string)$quoteLine['subunits']);
            $partsPerFullUnit = (float)$quoteLine['partperunit'];
            if ($partsPerFullUnit <= 0) {
                $partsPerFullUnit = 1;
            }
            if ($fullUnit === '' || !isset($_SESSION['units'][$fullUnit])) {
                $lineError = 'The full-unit inventory mapping is missing for ' . $quoteLine['description'] . '. Correct the inventory item before creating the PO.';
                break;
            }
            if ($partsPerFullUnit > 1 && ($looseUnit === '' || !isset($_SESSION['units'][$looseUnit]))) {
                $lineError = 'The loose-unit inventory mapping is missing for ' . $quoteLine['description'] . '. Correct the inventory item before creating the PO.';
                break;
            }
            $quoteLine['rfq_parts_per_full_unit'] = $partsPerFullUnit;
            $quoteLine['rfq_full_unit'] = $fullUnit;
            $quoteLine['rfq_loose_unit'] = ($partsPerFullUnit > 1) ? $looseUnit : $fullUnit;
            $prefillLines[] = $quoteLine;
        }
        $expectedLinesResult = DB_query("SELECT COUNT(*) AS line_count FROM rfq_lines WHERE rfq_id=" . (int)$rfqQuote['rfq_id'], $db);
        $expectedLines = DB_fetch_array($expectedLinesResult);
        if ($lineError === '' && count($prefillLines) !== (int)$expectedLines['line_count']) {
            $lineError = 'The selected quotation does not contain a complete set of RFQ product prices.';
        }
        if ($lineError !== '') {
            $rfqPrefillError = $lineError;
        } else {
            $_SESSION['Purchase_orders'] = array();
            foreach ($prefillLines as $quoteLine) {
                $itemCode = trim($quoteLine['stock_itemcode']);
                $_SESSION['Purchase_orders'][$itemCode] = array(
                    'line_no' => $itemCode,
                    'itemcode' => $itemCode,
                    'barcode' => $quoteLine['barcode'],
                    'stockname' => $quoteLine['stock_description'],
                    'uom' => $quoteLine['rfq_full_unit'],
                    'uomreceived' => $quoteLine['rfq_loose_unit'],
                    'partsperunit' => $quoteLine['rfq_parts_per_full_unit'],
                    'packzizegrn' => $quoteLine['rfq_parts_per_full_unit'],
                    'price' => (float)$quoteLine['unit_price'] / $quoteLine['rfq_parts_per_full_unit'],
                    'quantity' => (float)$quoteLine['quantity'],
                    'ReceivedQuantity' => (float)$quoteLine['quantity'],
                    'discount' => 0,
                    'vatcategory' => $quoteLine['vatcategory'],
                    'vatrate' => (float)$quoteLine['tax_rate'],
                    'rfq_tax_rate' => (float)$quoteLine['tax_rate']
                );
            }
            // Leave the add-item controls empty; the PurchaseOrder cart already contains RFQ lines.
            $_POST['stockitemcode'] = '';
            $_POST['qty'] = '';
            $_POST['units'] = '';
            $_POST['packzize'] = '';
            $_POST['cost'] = '';
            $_POST['vatcategory'] = '';
            $_POST['discount'] = '';
            $_POST['recqty'] = '';
            $_POST['unitsreceived'] = '';
            $_POST['packzizegrn'] = '';
            $todayResult = DB_query("SELECT NOW() AS today", $db);
            $todayRow = DB_fetch_array($todayResult);
            $_POST['date'] = ConvertSQLDate($todayRow['today']);
            $_POST['Purchaseoderdate'] = $_POST['date'];
            $_POST['datedue'] = '';
            $_POST['VendorID'] = $rfqQuote['supplier_code'];
            $_POST['VendorName'] = $rfqQuote['customer'];
            $_POST['currencycode'] = $rfqQuote['currencycode'];
            $_POST['reference'] = $rfqQuote['rfq_no'] . ($rfqQuote['quote_reference'] !== '' ? ' / ' . $rfqQuote['quote_reference'] : '');
            $_POST['FREIGHT'] = '0';
            $_POST['stockitemcode'] = '';
            $_SESSION['RFQTaxInclusive'] = (int)$rfqQuote['tax_inclusive'];
            $_SESSION['RFQQuoteID'] = $rfqQuoteId;
            $_SESSION['RFQHeaderID'] = (int)$rfqQuote['rfq_id'];
            $_SESSION['RFQNo'] = $rfqQuote['rfq_no'];
        }
    }
}
if ($rfqPrefillError !== '') {
    echo '<div class="alert alert-warning" style="margin:15px">' . htmlspecialchars($rfqPrefillError, ENT_QUOTES, 'UTF-8') . '</div>';
}
 
 if(isset($_GET['new']) && !isset($_GET['rfq_quote_id'])){
     $POSclass->neworder();
     unset($_SESSION['RFQQuoteID'], $_SESSION['RFQHeaderID'], $_SESSION['RFQNo'], $_SESSION['RFQTaxInclusive']);
 }
 
If($_SESSION['SINGLEUSER']=='Singleuser'){
    if(isset($_POST['Delete'])){
        DB_query("Delete from `PurchaseLine` where documentno='".$_SESSION['CompleteDocument']."' and `documenttype`='18'", $db);
        DB_query("delete from `PurchaseHeader` where documentno='".$_SESSION['CompleteDocument']."' and `documenttype`='18'", $db);
        if (isset($_SESSION['RFQQuoteID'], $_SESSION['RFQHeaderID'])) {
            DB_query("UPDATE rfq_header SET po_documentno=NULL WHERE id=" . (int)$_SESSION['RFQHeaderID'] .
                " AND awarded_quote_id=" . (int)$_SESSION['RFQQuoteID'] . " AND po_documentno='" .
                DB_escape_string($_SESSION['CompleteDocument']) . "'", $db);
        }
        unset($_SESSION['RFQQuoteID'], $_SESSION['RFQHeaderID'], $_SESSION['RFQNo'], $_SESSION['RFQTaxInclusive']);
        prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been Deleted.');
        unset($_POST);
    }

    if(isset($_POST['confirm'])){
        DB_query("UPDATE `PurchaseHeader` SET `released` = 1 where documenttype=18 AND documentno='".$_SESSION['CompleteDocument']."'", $db);
        DB_query("UPDATE `PurchaseLine` SET `completed` = 1 where documenttype=18 AND documentno='".$_SESSION['CompleteDocument']."'", $db);
        unset($_SESSION['RFQQuoteID'], $_SESSION['RFQHeaderID'], $_SESSION['RFQNo'], $_SESSION['RFQTaxInclusive']);
     
      echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Sales Order') . '" alt="" />%s</a></p>',
        'PDFPrintPurchaseOrder.php',$_SESSION['CompleteDocument'], _('Print Sales Order ').$_SESSION['CompleteDocument']);
    
      echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);  
        
        unset($_POST);
    }
}

 
 if(!isset($_POST['date'])){ 
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
   
}
    
  $_SESSION['stockmaster']=array();
    $SQL = "SELECT 
           `stockmaster`.`descrip`,
           `stockmaster`.`itemcode`,
           `vatcategory`.`vat` as CVAT ,
           `stockmaster`.`units`
     FROM `stockmaster` 
     left join `inventorypostinggroup` on `stockmaster`.`postinggroup`=`inventorypostinggroup`.`code`
     left join `vatcategory` on `inventorypostinggroup`.`vatcategory`=`vatcategory`.`vatc`
       where isstock_2=1 and (`inactive`=0 or `inactive` is null)
       group by descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

   
 if(!isset($_SESSION['Stores'])){
    $REsults=DB_query('SELECT '
            . '`code`,'
            . '`Storename` '
            . 'FROM `Stores`', $db);
    $x=0;
    while($row= DB_fetch_array($REsults)){
        $_SESSION['Stores'][$x]=$row;
        $x++;
    }
}   

 if(isset($_POST['remove'])){
   $POSclass->RemoveOrder($_POST['stockitemcode']);
 }
 
 if(isset($_POST['submit'])){
     
     if($_POST['submit']=='Save Purchase Order'){
         include('purchases/vendorreadonly.inc');
         $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Delete Order'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }elseif($_POST['submit']=='Re-Calculate' or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<I>Some inventory items are purchaed in different Units/Quanities than we invoice<br/> e.g. liters but the system requires in kgs. <br/>'
    . 'Enter the purchased quantity for Invoicing and Received quantity for inventory </I>' ;
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div class="centre"><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
  
?>
<div class="pos-layout">
  <!-- Left column: POS display -->
  <div class="pos-left">
    <?php DisplayPOS($POSclass); ?>
  </div>

  <!-- Right column: Entry + Stock list -->
  <div class="pos-right">
    <div class="pos-entry">
      <?php Entry(); ?>
    </div>
    <div class="pos-entry">
      <?php getstocklist(); ?>
    </div>
  </div>

</div>

<?php 

echo '</div></form>';

 }
 
 
include('includes/footer.inc');


function getstocklist(){
    
    $return= '<label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
                <table id="myStockTable" class="table-bordered"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
        
    foreach ($_SESSION['stockmaster'] as $key => $row){
       $container = $_SESSION['units'][trim($row['units'])]['descrip'];
         
    $return .= sprintf('<tr onclick="posInventoryvat(\'%s\',\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['CVAT']),trim($row['descrip']),trim($container),
            trim($row['itemcode']),trim($row['descrip'])) ;
   }
   $return .= '</table>';
            
   echo $return;
}

Function Entry(){
      Global $db;
    echo  '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
     . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
     . '<tr><td>Barcode</td><td><input type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
     . '<tr><td>Item Description</td><td><input type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
     . '<tr><td>Purchasing Units of Measure </td><td><select id="packid" name="units">' ;
                
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
               
    echo '</select></td></tr>'
     . '<tr><td>Purchasable Quantity </td><td><input type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'"/></td></tr>'
     . '<tr><td>Each Purchased Unit Contains</td><td><input type="text" maxlength="6" size="10" id="packzize" name="packzize" required="required" value="'.$_POST['packzize'].'"/></td></tr>'
     . '<tr><td>Cost Per Purchased Quantity</td><td><input type="text" maxlength="20" size="10" id="cost" name="cost" required="required" value="'.$_POST['cost'].'"/></td></tr>'
     . '<tr><td>Receiving Units of Measure </td><td><select id="receivedid" name="unitsreceived">';
              
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['unitsreceived']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
               
    echo '</select></td></tr>'
     . '<tr><td>Stores Quantity To Be Received</td><td><input type="text" maxlength="6" size="10" id="recqty" name="recqty" required="required" value="'.$_POST['recqty'].'"/></td></tr>'
     . '<tr><td>Each Received Unit Contains</td><td><input type="text" maxlength="6" size="10" id="packzizegrn" name="packzizegrn" required="required" value="'.$_POST['packzizegrn'].'"/></td></tr>'
     . '<tr><td>Discount %</td><td><input type="text" maxlength="2" size="10" id="discount" name="discount" value="'.$_POST['discount'].'"/></td></tr>'
     . '<tr><td>VAT Category</td><td><select id="vatcategory" name="vatcategory">';
             
     $result=DB_query("Select vatc,vat from vatcategory",$db);
    while ($myrow = DB_fetch_array($result)) {
        if($_POST['vatcategory']== trim($myrow['vatc'])){
                echo '<option selected="selected" value="'.  trim($myrow['vatc']) .'">' . $myrow['vatc'] .'-'. $myrow['vat'] . '</option>';
        } else {
                echo '<option value="'.  trim($myrow['vatc']).'">' . $myrow['vatc'] .'-'. $myrow['vat'] . '</option>';
        }
    } //end while loop
              
    echo '</select></td></tr>'
    . '<tr><td colspan="2"><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td></tr>'
    . '<tr><td colspan="2"><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
    . '</table>';
       
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(18);
echo '<table class="table-bordered table-condensed">';
echo '<tr><td>Date</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of Order</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Purchaseoderdate" size="11" maxlength="10"   value="' .$_POST['Purchaseoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10"   value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Document No</td>'
   . '<td><input type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required"/></td>'
   . '<td>Reference</td>'
   . '<td><input type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Supplier ID</td>'
        . '<td><input type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td>'
        . '<td>Supplier Name</td>'
        . '<td colspan="3"><input type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
   . '<input type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</tr>';
echo '</table>';


    echo  '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>BarCode</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>Quantity Purchased <br/>(Cloumn 1)<br/>Unit Descrip</label></th>'
        . '<th><label>For Cloumn 1<br/>Each Unit Contains </label></th>'
        . '<th><label>Purchased<br/>Cost Price<br/>For Cloumn 1</label></th>'
        . '<th><label>Equivalent<br/> Quantity To be <br/>Received in</label></th>'
        . '<th><label>Real Cost Price</label></th>'
        . '<th><label>Discount Rate</label></th>'
        . '<th><label>Net Amount</label></th>'
        . '<th><label>VAT Amount</label></th>'
        . '<th><label>Gross Amount</label></th>'
        . '</tr>'
        . '</thead>';
   
     
   $POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units']
        ,$_POST['packzize'],$_POST['cost'],$_POST['vatcategory'],$_POST['discount'],$_POST['recqty'],$_POST['unitsreceived'],$_POST['packzizegrn']);
        
  echo '</table><table><tr><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Purchase Order').'" /></td>
        </table>';  
}
 
Function DeletePOS($DOC){
    global $db;
  
    DB_query("Delete from `PurchaseLine` where documentno='".$DOC."' and `documenttype`='18'", $db);
    DB_query("delete from `PurchaseHeader` where documentno='".$DOC."' and `documenttype`='18'", $db);
    
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

 