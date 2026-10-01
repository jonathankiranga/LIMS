<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('SALES');
include('includes/header.inc');

function decodeHtmlEntities($string) {
    return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
 
  $POSclass = new laboratryPOS();
  
if(isset($_GET['new'])){
    $POSclass->neworder();
 }

if (isset($_POST['loadOrderNo']) && !empty($_POST['loadOrderNo'])) {
    $loadDocNo = mysqli_real_escape_string($db, $_POST['loadOrderNo']);
    $hdrSql = "SELECT docdate, customercode, customername, yourreference, currencycode, salespersoncode, QtyDiscount, locationcode
               FROM SalesHeader WHERE documenttype=1 AND documentno='$loadDocNo' LIMIT 1";
    $hdrResult = DB_query($hdrSql, $db);
    if ($hdrRow = DB_fetch_array($hdrResult)) {
        $_POST['documentno'] = $loadDocNo;
        $_POST['date'] = ConvertSQLDate($hdrRow['docdate']);
        $_POST['CustomerID'] = $hdrRow['customercode'];
        $_POST['CustomerName'] = $hdrRow['customername'];
        $_POST['reference'] = $hdrRow['yourreference'] ?? '';
        $_POST['currencycode'] = $hdrRow['currencycode'];
        $_POST['salespersoncode'] = $hdrRow['salespersoncode'] ?? '';
        $_POST['Bank_Code'] = $hdrRow['locationcode'] ?? '';
        $_POST['coa_documentno'] = '';

        $_SESSION['sales_orders'] = array();
        $lineSql = "SELECT code, description, Quantity, UnitPrice, LineDiscountPercent, TAT, sampleID
                    FROM SalesLine WHERE documenttype=1 AND documentno='$loadDocNo' AND code IS NOT NULL AND code != ''";
        $lineResult = DB_query($lineSql, $db);
        while ($line = DB_fetch_array($lineResult)) {
            $code = trim($line['code']);
            $hash = $POSclass->gethashcode($code, 0);
            $_SESSION['sales_orders'][$hash] = array(
                'line_no' => $hash,
                'itemcode' => $code,
                'barcode' => '',
                'stockname' => trim($line['description']),
                'sampleid' => $line['sampleID'] ?? '',
                'partsperunit' => 1,
                'averagestock' => 0,
                'quantity' => (float)$line['Quantity'],
                'pricevalue' => (float)$line['UnitPrice'],
                'tat' => $line['TAT'] ?? 0,
                'discountpercent' => (float)($line['LineDiscountPercent'] ?? 0)
            );
        }

        if (!empty($_SESSION['sales_orders'])) {
            $codes = array();
            foreach ($_SESSION['sales_orders'] as $item) {
                $codes[] = "'" . mysqli_real_escape_string($db, trim($item['itemcode'])) . "'";
            }
            $catQ = DB_query("SELECT DISTINCT category FROM stockmaster WHERE itemcode IN (" . implode(',', $codes) . ")", $db);
            $cats = array();
            while ($r = DB_fetch_array($catQ)) {
                if (!empty($r['category'])) $cats[] = $r['category'];
            }
            $cats = array_unique($cats);
            $_SESSION['sales_orders_category'] = (count($cats) === 1) ? reset($cats) : '';

            applyCategoryDiscounts();
        }
    } else {
        prnMsg(_('Order not found: ') . $loadDocNo, 'warn');
    }
}

if (isset($_POST['loadQuoteNo']) && !empty($_POST['loadQuoteNo'])) {
    $loadQuote = mysqli_real_escape_string($db, trim($_POST['loadQuoteNo']));
    $hdrSql = "SELECT docdate, customercode, customername, yourreference, currencycode, salespersoncode, QtyDiscount, locationcode
               FROM SalesHeader WHERE documenttype=54 AND documentno='$loadQuote' LIMIT 1";
    $hdrResult = DB_query($hdrSql, $db);
    if ($hdrRow = DB_fetch_array($hdrResult)) {
        $_POST['date'] = ConvertSQLDate($hdrRow['docdate']);
        $_POST['CustomerID'] = $hdrRow['customercode'];
        $_POST['CustomerName'] = $hdrRow['customername'];
        $_POST['reference'] = $hdrRow['yourreference'] ?? '';
        $_POST['currencycode'] = $hdrRow['currencycode'];
        $_POST['salespersoncode'] = $hdrRow['salespersoncode'] ?? '';
        $_POST['DiscountPercent'] = $hdrRow['QtyDiscount'] ?? '';
        $_POST['Bank_Code'] = $hdrRow['locationcode'] ?? '';
        $_POST['coa_documentno'] = '';
        $_POST['stockitemcode'] = '';

        $_SESSION['sales_orders'] = array();
        $lineSql = "SELECT code, description, Quantity, UnitPrice, LineDiscountPercent, TAT, Partperunit
                    FROM SalesLine WHERE documenttype=54 AND documentno='$loadQuote' AND code IS NOT NULL AND code != ''";
        $lineResult = DB_query($lineSql, $db);
        while ($line = DB_fetch_array($lineResult)) {
            $code = trim($line['code']);
            $hash = $POSclass->gethashcode($code, 0);
            $_SESSION['sales_orders'][$hash] = array(
                'line_no' => $hash,
                'itemcode' => $code,
                'barcode' => '',
                'stockname' => trim($line['description']),
                'sampleid' => '',
                'partsperunit' => (float)($line['Partperunit'] ?: 1),
                'averagestock' => 0,
                'quantity' => (float)$line['Quantity'],
                'pricevalue' => (float)$line['UnitPrice'],
                'tat' => $line['TAT'] ?? 0,
                'discountpercent' => (float)($line['LineDiscountPercent'] ?? 0)
            );
        }

        if (!empty($_SESSION['sales_orders'])) {
            $codes = array();
            foreach ($_SESSION['sales_orders'] as $item) {
                $codes[] = "'" . mysqli_real_escape_string($db, trim($item['itemcode'])) . "'";
            }
            $catQ = DB_query("SELECT DISTINCT category FROM stockmaster WHERE itemcode IN (" . implode(',', $codes) . ")", $db);
            $cats = array();
            while ($r = DB_fetch_array($catQ)) {
                if (!empty($r['category'])) $cats[] = $r['category'];
            }
            $cats = array_unique($cats);
            $_SESSION['sales_orders_category'] = (count($cats) === 1) ? reset($cats) : '';

            applyCategoryDiscounts();
        }
        prnMsg(_('Sales Order loaded from Quote: ') . $loadQuote, 'info');
    } else {
        prnMsg(_('Quote not found: ') . $loadQuote, 'warn');
    }
    unset($_POST['submit']);
}

if (isset($_POST['submit']) && ($_POST['submit'] === 'Load' || $_POST['submit'] === 'Recalc')) {
    unset($_POST['submit']);
}

if(!isset($_SESSION['units'])){
            $ResultIndex=DB_query("select code, descrip from unit",$db);
             while($row = DB_fetch_array($ResultIndex)){
                $code = trim($row['code']);
                $_SESSION['units'][$code]=$row;
            }
       }
if(isset($_POST['confirmOrder'])){
    DB_query("UPDATE `SalesHeader` SET `status`=1, `released`=1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    
    echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Sales Order') . '" alt="" />%s</a></p>',
        'PDFPrintSalesOrder.php',$_SESSION['CompleteDocument'], _('Print Sales Order ').$_SESSION['CompleteDocument']);
    
    echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);

    unset($_POST);
    unset($_SESSION['CompleteDocument']);
 }
if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmaster']) ;
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}
if(!isset($_POST['DiscountPercent'])){
    $_POST['DiscountPercent']='';
}
if(!isset($_POST['LineDiscountPercent'])){
    $_POST['LineDiscountPercent']='0';
}
    
 
$SQL = "SELECT itemcode,barcode, stockmaster.descrip  from stockmaster where (inactive=0 or inactive is null) and  isstock_1=1  
order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

   
 if(!isset($_SESSION['sampleid'])){
    $_SESSION['sampleid'] = [];
}


 if(!isset($_SESSION['Stores'])){
    $REsults=DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
    $x=0;
    while($row= DB_fetch_array($REsults)){
        $_SESSION['Stores'][$x]=$row;
        $x++;
    }
}   

 if(isset($_POST['remove'])){
   $POSclass->RemoveOrder($_POST['line_no']);
 }
 
 
 if(isset($_POST['submit'])){
     
if($_POST['submit']=='Save and Print Sales Order'){
    include('transactions/customerreadonly.inc');
    $POSclass->neworder();
} 


if($_POST['submit']=='Delete Order'){
    DeletePOS($_SESSION['CompleteDocument']);
    unset($_SESSION['CompleteDocument']);
}
     
  }elseif($_POST['submit']=='Re-Calculate' or !isset($_POST['submit'])){
  
applyCategoryDiscounts();

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>





<!-- Single column: header form, then the editable order grid (no entry window;
     lines are added/searched/edited directly in the grid below). -->
<div class="pos-layout">
  <div class="pos-left">
    <?php DisplayPOS($POSclass); ?>
  </div>
</div>



<?php
echo '</div><input type="hidden" id="selectedRef"  name="selectedRef" value="'.$_POST['selectedRef'].'">
<input type="hidden" id="selectedImages" name="selectedImages" value="'.$_POST['selectedImages'].'"/></form>';
 }

$labFaCss = __DIR__ . '/css/fontawesome6.4.0.all.min.css';
$labFaFix = __DIR__ . '/css/fontawesome-fix.css';
$labTabCss = __DIR__ . '/javascripts/dist/css/tabulator_bootstrap5.min.css';
if (is_file($labFaCss)) {
    echo '<link rel="stylesheet" href="css/fontawesome6.4.0.all.min.css?v=' . filemtime($labFaCss) . '" />';
}
if (is_file($labFaFix)) {
    echo '<link rel="stylesheet" href="css/fontawesome-fix.css?v=' . filemtime($labFaFix) . '" />';
}
if (is_file($labTabCss)) {
    echo '<link rel="stylesheet" href="javascripts/dist/css/tabulator_bootstrap5.min.css?v=' . filemtime($labTabCss) . '" />';
}
$labJsFile = __DIR__ . '/javascripts/laboratoryorder.js';
if (is_file($labJsFile)) {
    echo '<script src="javascripts/laboratoryorder.js?v=' . filemtime($labJsFile) . '" type="text/javascript"></script>';
} else {
    trigger_error('laboratoryorder.js missing in javascripts/ - upload it together with LaboratoryOder.php', E_USER_WARNING);
}

include('includes/footer.inc');

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(1);
if (!defined('GRIDFORM_CSS')) {
    define('GRIDFORM_CSS', 1);
    echo '<style>
    .gridform{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem 1rem;margin-bottom:0.5rem;}
    .gridform .form-group{display:flex;flex-direction:column;gap:0.25rem;}
    .gridform label{font-weight:500;font-size:0.8rem;margin-bottom:0;}
    .gridform input,.gridform select,.gridform button{max-width:100%;}
    .gridform .full-width{grid-column:span 2;}
    @media(max-width:768px){.gridform{grid-template-columns:1fr 1fr;}}
    @media(max-width:520px){.gridform{grid-template-columns:1fr;}.gridform .full-width{grid-column:span 1;}}
    .lab-addrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0.5rem 0;}
    .lab-addrow label{font-weight:600;font-size:0.8rem;white-space:nowrap;margin-bottom:0;}
    .lab-addrow input{flex:1;min-width:220px;}
    #labOrderGrid{border:1px solid #adb5bd;background:#fff;font-size:13px;max-width:100%;}
    #labOrderGrid .tabulator-tableholder{overflow-x:auto;max-width:100%;}
    #labOrderGrid .tabulator-table{max-width:100%;table-layout:fixed;width:100%;}
    #labOrderGrid .tabulator-row.tabulator-group{background:#edf2fb;font-weight:600;}
    #labOrderGrid .tabulator-row.tabulator-group:has(.lab-nogroup-marker){display:none;}
    #labOrderGrid .tabulator-row.tabulator-group .tabulator-cell{white-space:normal;overflow:hidden;max-width:100%;}
    #labOrderGrid .tabulator-row.lab-bundle{background:#edf2fb !important;font-weight:600;}
    #labOrderGrid .tabulator-cell input{width:100%;padding:2px 6px;border:2px solid #e1dfe8;border-radius:6px;font-size:13px;}
    #labOrderGrid .tabulator-cell input:focus{border-color:#2563eb;outline:none;box-shadow:none;}
    #labTotalsRow{display:flex;justify-content:flex-end;gap:18px;padding:6px 4px;font-weight:700;border:1px solid #adb5bd;border-top:none;background:#f8f9fa;}
    .statusbar{font-size:12px;color:#495057;background:#f1f3f5;border-left:3px solid #6c757d;padding:2px 8px;margin:4px 0;}
    .lab-dropdown{position:absolute;background:#fff;border:1px solid #6c757d;max-height:220px;overflow-y:auto;z-index:1000;box-shadow:0 2px 8px rgba(0,0,0,0.15);}
    .lab-dropdown div{padding:6px 10px;cursor:pointer;white-space:nowrap;font-size:13px;border-bottom:1px solid #e9ecef;}
    .lab-dropdown div:hover{background-color:#dbe9f4;}
    </style>';
}

echo '<div class="gridform">'
    . '<div class="form-group"><label for="date">' . _('Date') . '</label>'
    . '<input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" id="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' . $_POST['date'] . '" onchange="isDate(this, this.value, \'' . $_SESSION['DefaultDateFormat'] . '\')" /></div>'
    . '<div class="form-group"><label for="salesid">' . _('Document No') . '</label>'
    . '<input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'" id="salesid" size="5" required="required" /></div>'
    . '<div class="form-group"><label>' . _('Load Order') . '</label>'
    . '<div style="display:flex;gap:4px;"><input type="text" name="loadOrderNo" id="loadOrderNo" size="8" placeholder="Search order no / customer" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleLabOrderSearch(event)" onkeydown="return labLoadOnEnter(event,&#39;order&#39;)" /></div></div>'
    . '<div class="form-group"><label>' . _('Load Quote') . '</label>'
    . '<div style="display:flex;gap:4px;"><input type="text" name="loadQuoteNo" id="loadQuoteNo" size="8" placeholder="Search quote no / customer" style="width:50px;" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleLabQuoteSearch(event)" onkeydown="return labLoadOnEnter(event,&#39;quote&#39;)" /></div></div>'
    . '<div class="form-group"><label for="reference">' . _('LPO Reference') . '</label>'
    . '<input tabindex="5" type="text" name="reference" id="reference" value="' . $_POST['reference'] . '" size="5" /></div>'
    . '<div class="form-group"><label for="salespersoncode">' . _('SalesMan (For Sales commision)') . '</label>'
    . '<select tabindex="7" name="salespersoncode" id="salespersoncode"><option></option>';

$ResultIndex = DB_query("SELECT `code`,`salesman`,`commission`,`inactive` FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while ($row = DB_fetch_array($ResultIndex)) {
    $salesmancode = trim($row['code']);
    echo sprintf('<option value="%s" %s >%s</option>', $salesmancode, ($_POST['salespersoncode'] == $salesmancode ? 'selected="selected"' : ''), $row['salesman']);
}

echo '</select></div>'
    . '<div class="form-group"><label>' . _('Customer') . '</label>'
    . '<div style="display:flex;gap:4px;"><input type="hidden" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'" size="5" readonly="readonly" required="required" /></div>'
    . '<input type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'" size="20" required="required" placeholder="' . _('Search a customer name') . '" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleLabCustomerSearch(event)" onkeydown="return labCustomerEnter(event)" /></div>'
    . '<div class="form-group"><label>' . _('Certificate of Analysis') . '</label>'
    . '<input type="text" name="coa_documentno" id="coa_documentno" value="'.$_POST['coa_documentno'].'" size="10" readonly="readonly" /></div>'
    . '<div class="form-group"><label for="DiscountPercent">' . _('Discount (%)') . '</label>'
    . '<input type="text" class="number" name="DiscountPercent" id="DiscountPercent" value="'.htmlspecialchars($_POST['DiscountPercent'], ENT_QUOTES).'" size="5" /></div>'
    . '<div class="form-group"><label for="currencycode">' . _('Currency Code') . '</label>'
    . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly" /></div>'
    . '</div>';

echo '<div id="labmessages"></div>';

echo '<div class="lab-addrow"><label for="labItemSearch">'. _('Add item:').'</label>'
     . '<input type="text" id="labItemSearch" placeholder="' . _('Search item code or description, Enter adds first match') . '" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleLabItemSearch(event)" onkeydown="return labItemEnter(event)" /></div>';

echo '<div id="labOrderGrid"></div>';

echo '<div id="labTotalsRow"><span>' . _('Total Order') . '</span>'
     . '<span class="l-tnet">0.00</span>'
     . '<span class="l-tvat">0.00</span>'
     . '<span class="l-tgross">0.00</span></div>';

echo '<table>';
$SQLstment="SELECT accountcode,bankName,BranchName,currency FROM `BankAccounts` where currency='".$_POST['currencycode']."'";
        echo '<tr><td>Select Bank to Deposit (Proforma Invoince) :</td><td><Select name="Bank_Code"><option></option>';
                 
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                echo  '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        
        echo '</select></td></tr><tr><td colsapn="2">
 	<input type="submit" name="submit" value="'._('Save and Print Sales Order').'" /></td>
        </table>';  
        
}
 
Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from `Salesline` where documentno='".$DOC."' and `documenttype`='1'", $db);
    DB_query("delete from `SalesHeader` where documentno='".$DOC."' and `documenttype`='1'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

?>
