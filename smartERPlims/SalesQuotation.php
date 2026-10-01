<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');
$Title = _('SALES QUOTE');
include('includes/header.inc');

function decodeHtmlEntities($string) {
    return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

if (!isset($_SESSION['units'])) {
    $ResultIndex = DB_query("select code, descrip from unit", $db);
    while ($row = DB_fetch_array($ResultIndex)) {
        $code = trim($row['code']);
        $_SESSION['units'][$code] = $row;
    }
}

$quoteCurrency = ($_POST['currencycode'] ?? '') ?: (isset($_SESSION['CompanyRecord']['currencydefault']) ? $_SESSION['CompanyRecord']['currencydefault'] : 'KES');

// Persist the last-picked customer across page loads (set by the AJAX customer action).
$selectedCustomerID = trim((string)( $_POST['CustomerID']?? ''));
$selectedCustomerName = '';
if ($selectedCustomerID !== '') {
    $selRes = DB_query("SELECT customer FROM debtors WHERE itemcode='" . mysqli_real_escape_string($db, $selectedCustomerID) . "' LIMIT 1", $db);
    if ($selRow = DB_fetch_row($selRes)) {
        $selectedCustomerName = (string)$selRow[0];
    }
}

$POSclass = new QUOTES();
$PriceList = new PriceList();

if (isset($_GET['new'])) {
    $POSclass->neworder();
}

if (!isset($_POST['paymentterms'])) {
    $_POST['paymentterms'] = $_SESSION['paymentterms'] ?? '';
}
if (!isset($_POST['date'])) {
    $_POST['date'] = ConvertSQLDate(date('Y-m-d'));
}

// Replicate the per-line amount math used by QUOTES::showtable so the
// saved lines carry the same net/vat/gross figures the grid displays.
function saveQuoteComputeLines() {
    global $db;
    global $POSclass;
    if (empty($_SESSION['sales_orders'])) {
        return;
    }
    $results = DB_query("SELECT debtors.customerposting, postinggroups.VATinclusive, postinggroups.IsTaxed
                          FROM debtors join postinggroups on code=customerposting
                          WHERE debtors.itemcode='" . mysqli_real_escape_string($db, ($_POST['CustomerID'] ?? '')) . "' LIMIT 1", $db);
    $debtorsrow = DB_fetch_row($results);
    if ($debtorsrow) {
        $VATinclusive = $debtorsrow[1];
        $IsTaxed = $debtorsrow[2];
    } else {
        $VATinclusive = false;
        $IsTaxed = false;
    }
    $headerDiscountPercent = (float)($_POST['DiscountPercent'] ?? 0);

    foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
        $stocklist = $POSclass->GetStockdetails(trim($rowvalue['itemcode']));
        $rate = ($IsTaxed == false) ? 0 : ($stocklist['vat'] ?? 0);
        $package = (float) $rowvalue['partsperunit'];
        $salesprice = (float) $rowvalue['pricevalue'];
        $PriceInPricelist = SelectTestPriceListToUse(trim($rowvalue['itemcode']), $package, ($_POST['CustomerID'] ?? ''));
        $salesprice = ($salesprice == '') ? (float) $PriceInPricelist : $salesprice;
        $qty = (float) $rowvalue['quantity'];
        $baseamount = ($salesprice * $qty * $package);
        $lineDiscountPercent = (float) ($rowvalue['discountpercent'] ?? 0);
        $lineDiscountAmount = $baseamount * ($lineDiscountPercent / 100);
        $taxableAmount = $baseamount - $lineDiscountAmount;

        if ($VATinclusive == true) {
            $netamount = $taxableAmount / (($rate + 100) / 100);
            $vatamount = $taxableAmount - $netamount;
            $grossamount = $taxableAmount;
        } else {
            $vatamount = $taxableAmount * ($rate / 100);
            $grossamount = $taxableAmount + $vatamount;
            $netamount = $taxableAmount;
        }
        if ($headerDiscountPercent > 0) {
            $factor = (100 - $headerDiscountPercent) / 100;
            $netamount *= $factor;
            $vatamount *= $factor;
            $grossamount *= $factor;
        }

        $_SESSION['sales_orders'][$lineno] = array_replace_recursive($rowvalue, array(
            'stockname' => $stocklist['stockname'] ?? $rowvalue['stockname'],
            'vatrate' => $rate,
            'salesprice' => $salesprice,
            'netamount' => $netamount,
            'vatamount' => $vatamount,
            'grossamount' => $grossamount
        ));
    }
    $GLOBALS['VATinclusive'] = $VATinclusive;
}

// Persist a quote: header + lines (both doc type 54). Mirrors Quotereadonly.inc.
function saveQuoteDocument() {
    global $db;
$sqldebtors = DB_query("SELECT itemcode, creditlimit, customer, phone, email, city, country, curr_cod,
                               customerposting, salesman, VATinclusive
                        FROM debtors LEFT JOIN postinggroups ON code = customerposting
                        WHERE itemcode='" . mysqli_real_escape_string($db, ($_POST['CustomerID'] ?? '')) . "'", $db);
    $debtorsrow = DB_fetch_row($sqldebtors);
    if (!$debtorsrow) {
        return array('status' => 'error', 'message' => _('Customer not found.'));
    }
    $customerposting = $debtorsrow[8];
    $VATinclusive = $debtorsrow[10] ?? 0;

    if (($_SESSION['ManualNumber'] ?? 0) == 0) {
        $_POST['documentno'] = GetNextTransNo(54, $db);
    }
    $_SESSION['CompleteDocument'] = $_POST['documentno'];

    $transstart = DB_Txn_Begin($db);
    $PeriodNo = GetPeriod($_POST['date'], $db, true);
    $DATE = FormatDateForSQL($_POST['date']);

    $selectedImages = $_POST['selectedImages'] ?? '';
    $decodedString = decodeHtmlEntities($selectedImages);
    $imagesArray = json_decode($decodedString);
    $imagesArray2 = json_encode($imagesArray);

    $quoteBank2 = (($_POST['Bank_Code2'] ?? '') !== '' && ($_POST['Bank_Code2'] ?? '') === ($_POST['Bank_Code'] ?? '')) ? '' : ($_POST['Bank_Code2'] ?? '');

    // Per-group manual pricing toggle (JSON cat -> auto|standard|pertest). '{}' = all Auto.
    // Legacy form path has no toggle UI, so this is only honoured if posted (e.g. AJAX parity); otherwise Auto.
    $pricingmodeJson = '{}';
    $pmRawLegacy = trim((string)($_POST['pricingmode'] ?? ''));
    if ($pmRawLegacy !== '') {
        $pmDecLegacy = json_decode($pmRawLegacy, true);
        if (is_array($pmDecLegacy)) {
            $pmCleanLegacy = array();
            foreach ($pmDecLegacy as $pmK => $pmV) {
                $pmK = trim((string)$pmK);
                if ($pmK === '' || strlen($pmK) > 20) {
                    continue;
                }
                if ($pmV === 'standard' || $pmV === 'pertest' || $pmV === 'auto') {
                    $pmCleanLegacy[$pmK] = $pmV;
                }
            }
            if (!empty($pmCleanLegacy)) {
                $pricingmodeJson = json_encode($pmCleanLegacy);
            }
        }
    }

    $legacyHeaderNoMode = "INSERT INTO SalesHeader
                   (documenttype, documentno, docdate, customercode, customername, externaldocumentno,
                    locationcode, postinggroup, currencycode, salespersoncode, status, userid, period,
                    vatinclusive, paymentterms, picture, QtyDiscount)
                 VALUES
                   ('54', '" . mysqli_real_escape_string($db, $_POST['documentno']) . "', '" . $DATE . "',
                    '" . mysqli_real_escape_string($db, $_POST['CustomerID'] ?? '') . "',
                    '" . mysqli_real_escape_string($db, $_POST['CustomerName'] ?? '') . "',
                    '" . mysqli_real_escape_string($db, $quoteBank2) . "',
                '" . mysqli_real_escape_string($db, $_POST['Bank_Code'] ?? '') . "',
                '" . $customerposting . "',
                '" . mysqli_real_escape_string($db, $quoteCurrency) . "',
                '" . mysqli_real_escape_string($db, $_POST['salespersoncode'] ?? '') . "',
                '1', '" . mysqli_real_escape_string($db, $_SESSION['UserID'] ?? '') . "', '" . $PeriodNo . "',
                '" . $VATinclusive . "', '" . mysqli_real_escape_string($db, (($_POST['paymentterms'] ?? '') !== '' ? $_POST['paymentterms'] : ($_POST['terms'] ?? ''))) . "',
                '" . mysqli_real_escape_string($db, $imagesArray2 ?: '[]') . "',
                '" . (int)($_POST['DiscountPercent'] ?? 0) . "')";
                $sql = array();
                $sql[] = "INSERT INTO SalesHeader
                   (documenttype, documentno, docdate, customercode, customername, externaldocumentno,
                    locationcode, postinggroup, currencycode, salespersoncode, status, userid, period,
                    vatinclusive, paymentterms, picture, QtyDiscount, pricingmode)
                 VALUES
                   ('54', '" . mysqli_real_escape_string($db, $_POST['documentno']) . "', '" . $DATE . "',
                    '" . mysqli_real_escape_string($db, $_POST['CustomerID'] ?? '') . "',
                    '" . mysqli_real_escape_string($db, $_POST['CustomerName'] ?? '') . "',
                    '" . mysqli_real_escape_string($db, $quoteBank2) . "',
                '" . mysqli_real_escape_string($db, $_POST['Bank_Code'] ?? '') . "',
                '" . $customerposting . "',
                '" . mysqli_real_escape_string($db, $quoteCurrency) . "',
                '" . mysqli_real_escape_string($db, $_POST['salespersoncode'] ?? '') . "',
                '1', '" . mysqli_real_escape_string($db, $_SESSION['UserID'] ?? '') . "', '" . $PeriodNo . "',
                '" . $VATinclusive . "', '" . mysqli_real_escape_string($db, (($_POST['paymentterms'] ?? '') !== '' ? $_POST['paymentterms'] : ($_POST['terms'] ?? ''))) . "',
                '" . mysqli_real_escape_string($db, $imagesArray2 ?: '[]') . "',
                '" . (int)($_POST['DiscountPercent'] ?? 0) . "',
                '" . mysqli_real_escape_string($db, $pricingmodeJson) . "')";
                $legacyHeaderFallback = $legacyHeaderNoMode;

    foreach ($_SESSION['sales_orders'] as $lineno => $rows) {
        if (isset($rows['grossamount']) && $rows['grossamount'] > 0) {
            $UnitCode = trim($rows['uom']);
            $stockcode = mysqli_real_escape_string($db, trim($rows['itemcode']));
            $Partperunit = (float) $rows['partsperunit'];
            $Inpacks = mysqli_real_escape_string($db, $_SESSION['units'][$UnitCode]['descrip'] ?? $UnitCode);
            $qty = (float) $rows['quantity'];
            $stockname = mysqli_real_escape_string($db, $rows['stockname']);
            $vatrate = $rows['vatrate'];
            $salesprice = $rows['salesprice'];
            $grossamount = $rows['grossamount'];
            $vatamount = $rows['vatamount'];
            $tat = $rows['tat'];
            $lineDiscountPercent = (float) ($rows['discountpercent'] ?? 0);
            $docnoEsc = mysqli_real_escape_string($db, $_POST['documentno']);

            $sql[] = sprintf("INSERT INTO SalesLine
                   (documenttype, docdate, documentno, code, description, unitofmeasure, Quantity,
                    UnitPrice, vatamount, invoiceamount, vatrate, inclusive, containerprice,
                    containersunits, totalchargedcontainers, containercode, Partperunit, TAT, LineDiscountPercent)
                 VALUES
                   ('%s','%s','%s','%s','%s','%s','%s',%f,%f,%f,'%f','%s',%f,%f,%f,'%s','%s',%s,%f)"
                , "54"
                , $DATE
                , $docnoEsc
                , $stockcode
                , $stockname
                , $Inpacks
                , $qty
                , $salesprice
                , $vatamount
                , $grossamount
                , $vatrate
                , $VATinclusive
                , 0, 0, 0, 0
                , $Partperunit
                , $tat ? (int) $tat : 'NULL'
                , $lineDiscountPercent);
        }
    }

    foreach ($sql as $value) {
        $ResultIndex = DB_query($value, $db);
    }

    if (DB_error_no($db) > 0 && stripos((string)DB_error_msg($db), 'pricingmode') !== false) {
        // Pre-migration fallback: pricingmode column not yet added (see sql/migration_add_pricingmode_column.sql).
        DB_Txn_Rollback($db);
        $transstart = DB_Txn_Begin($db);
        $sql[0] = $legacyHeaderFallback;
        foreach ($sql as $value) {
            $ResultIndex = DB_query($value, $db);
        }
    }

    if (DB_error_no($db) > 0) {
        DB_Txn_Rollback($db);
        return array('status' => 'error', 'message' => _('Failed to save sales Quote :') . $_POST['documentno']);
    }
    DB_Txn_Commit($db);

    $filename = 'quotes/' . trim($_POST['documentno']) . '.terms';
    @file_put_contents($filename, strip_tags($_POST['paymentterms'] ?? '') . "\r\n\r\n", FILE_APPEND);

    return array('status' => 'saved', 'documentno' => $_POST['documentno'], 'message' => _('Sales Quote :') . $_POST['documentno'] . _(' has been created'));
}

if (isset($_POST['submit']) && $_POST['submit'] === 'Save Quote') {
    $_SESSION['sales_orders'] = array();
    if (isset($_POST['itemcode']) && is_array($_POST['itemcode'])) {
        $n = count($_POST['itemcode']);
        for ($i = 0; $i < $n; $i++) {
            $code = trim($_POST['itemcode'][$i] ?? '');
            if ($code === '') {
                continue;
            }
            if ((float) ($_POST['quantity'][$i] ?? 0) <= 0) {
                continue;
            }
            if (isset($_SESSION['sales_orders'][$code])) {
                continue;
            }
            $_SESSION['sales_orders'][$code] = array(
                'line_no' => $code,
                'itemcode' => $code,
                'barcode' => $code,
                'stockname' => ($_POST['stockname'][$i] ?? $code),
                'uom' => trim(($_POST['units'][$i] ?? 'PCS') !== '' ? $_POST['units'][$i] : 'PCS'),
                'pricevalue' => (float) ($_POST['pricevalue'][$i] ?? 0),
                'partsperunit' => (float) ($_POST['partsperunit'][$i] ?? 1),
                'quantity' => (float) ($_POST['quantity'][$i] ?? 1),
                'tat' => ($_POST['tat'][$i] ?? 0),
                'discountpercent' => (float) ($_POST['LineDiscountPercent'][$i] ?? 0),
                'sampleid' => ''
            );
        }
    }
    applyStandardPricing();
    saveQuoteComputeLines();
    $POSclass->SaveFooter();
    $saveResult = saveQuoteDocument();
    if ($saveResult['status'] === 'saved') {
        $savedDocNo = $saveResult['documentno'];
        $pdfUrl = 'PDFPrintSalesQuote.php?No=' . urlencode($savedDocNo);
        echo '<div class="card mt-3"><div class="card-body">';
        echo '<h5 class="card-title">' . $saveResult['message'] . '</h5>';
        echo '<p><a id="' . htmlspecialchars($savedDocNo, ENT_QUOTES) . '" href="' . htmlspecialchars($pdfUrl, ENT_QUOTES) . '" target="_blank">'
            . '<img src="' . $RootPath . '/css/' . $Theme . '/images/pdf.png" title="' . _('Print Sales Quote') . '" alt="" /> '
            . _('Print Sales Quote ') . htmlspecialchars($savedDocNo, ENT_QUOTES) . '</a>'
            . ' &nbsp; '
            . '<a href="' . htmlspecialchars($pdfUrl . '&emailto=1', ENT_QUOTES) . '" target="_blank">'
            . '<img src="' . $RootPath . '/css/' . $Theme . '/images/email.png" title="' . _('Email Sales Quote') . '" alt="" /> '
            . _('Email Sales Quote ') . htmlspecialchars($savedDocNo, ENT_QUOTES) . '</a></p>';
        echo '<script type="text/javascript">setTimeout(function(){ window.location.replace(\'' . $CurrentPath . '?new=1\'); }, 2500);</script>';
        echo '</div></div>';
    } else {
        prnMsg($saveResult['message'], 'warn');
    }
    unset($_POST);
    $POSclass->neworder();
} else {
    if (!isset($_POST['submit'])) {
        $POSclass->neworder();
    }

    $SalesManOptions = '';
    $SalesManRes = DB_query("SELECT code, salesman, commission, inactive FROM salesrepsinfo WHERE inactive IS NULL OR inactive = 0", $db);
    while ($SalesManRow = DB_fetch_array($SalesManRes)) {
        $SalesManCode = trim($SalesManRow['code']);
        $SalesManOptions .= sprintf('<option value="%s"%s>%s</option>',
            htmlspecialchars($SalesManCode, ENT_QUOTES),
            ((string)($_POST['salespersoncode'] ?? '') === $SalesManCode ? ' selected="selected"' : ''),
            htmlspecialchars($SalesManRow['salesman'] ?? $SalesManCode, ENT_QUOTES));
    }

    $CatOpts = '<option value="">' . _('-- None (mixed) --') . '</option>';
    $CatRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription", $db);
    $selCat = $_SESSION['sales_orders_category'] ?? '';
    while ($CatRow = DB_fetch_array($CatRes)) {
        $CatOpts .= sprintf('<option value="%s"%s>%s</option>',
            htmlspecialchars($CatRow['categoryid'], ENT_QUOTES),
            ($CatRow['categoryid'] === $selCat ? ' selected="selected"' : ''),
            htmlspecialchars($CatRow['categorydescription'] ?? $CatRow['categoryid'], ENT_QUOTES));
    }

    $savedBank1 = $_POST['Bank_Code'] ?? '';
    $savedBank2 = $_POST['Bank_Code2'] ?? '';

    $CurrentPath = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
    ?>
<style>
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .form-group label {
        font-weight: 500;
        font-size: 0.875rem;
    }
    .form-group input,
    .form-group select {
        width: 100%;
    }
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        margin-top: 0.5rem;
    }
    /* Workspace-style (scoped sq-) tokens mirrored from blockchain/workspace.php */
    #quoteform.sq-app { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f2f8; padding: 10px 12px; border-radius: 10px; display: flex; flex-direction: column; gap: 10px; }
    #quoteform.sq-app *, #quoteform.sq-app *::before, #quoteform.sq-app *::after { box-sizing: border-box; }
    #quoteform .sq-card { background: #fff; border-radius: 10px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: visible; }
    #quoteform .sq-load-bar { border-left: 4px solid #2563eb; }
    #quoteform .sq-grid { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
    #quoteform .sq-field { display: flex; flex-direction: column; min-width: 0; }
    #quoteform .sq-field > label { display: block; font-size: 12px; font-weight: 600; color: #2f2b3a; margin-bottom: 4px; }
    #quoteform .sq-field input.form-control,
    #quoteform .sq-field select.form-control,
    #quoteform .sq-field textarea.form-control { border: 2px solid #e1dfe8; border-radius: 6px; font-size: 13px; }
    #quoteform .sq-field input.form-control:focus,
    #quoteform .sq-field select.form-control:focus,
    #quoteform .sq-field textarea.form-control:focus { border-color: #2563eb; outline: none; box-shadow: none; }
    #quoteform .sq-grow { flex: 1 1 280px; min-width: 220px; }
    #quoteform .sq-inline { display: flex; gap: 6px; }
    #quoteform .sq-inline > input { flex: 1 1 auto; }
    #quoteform .sq-table-wrap { overflow-x: auto; }
    #quoteform .sq-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e1dfe8; }
    #quoteform .statusbar { font-size: 12px; color: #2f2b3a; background: #f5f2f8; border-left: 3px solid #2563eb; padding: 2px 8px; margin: 4px 0; }
    @media (max-width: 900px) {
        #quoteform .sq-grid { align-items: stretch; }
        #quoteform .sq-field { flex: 1 1 100%; }
    }
    /* Tabulator (Bootstrap5 theme) scoped to the quote card */
    #quoteform #quoteTabulator { font-size: 13px; max-width: 100%; }
    #quoteform #quoteTabulator .tabulator-table { max-width: 100%; }
    #quoteform #quoteTabulator .tabulator-row.tabulator-group .tabulator-cell { white-space: normal; overflow: hidden; max-width: 100%; }
    #quoteform #quoteTabulator .tabulator-row.tabulator-group { background: #edf2fb; font-weight: 600; }
    #quoteform #quoteTabulator .tabulator-row.q-bundle { background: #edf2fb !important; font-weight: 600; }
    #quoteform #quoteTabulator .tabulator-cell input { width: 100%; padding: 2px 6px; border: 2px solid #e1dfe8; border-radius: 6px; font-size: 13px; }
    #quoteform #quoteTabulator .tabulator-cell input:focus { border-color: #2563eb; outline: none; }
    #quoteform .sq-pending { display: flex; gap: 6px; align-items: center; background: #fff; border: 1px dashed #adb5bd; border-radius: 8px; padding: 6px 8px; }
    .dropdown {
        position: absolute; background: white; border: 1px solid #adb5bd;
        max-height: 200px; overflow-y: auto; z-index: 1000; border-radius: 4px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .dropdown div { padding: 8px; cursor: pointer; white-space: nowrap; }
    .dropdown div:hover { background-color: #e9ecef; }
    #entryPanel .card-header {
        background-color: #002855; color: #ffffff; font-weight: 700; font-size: 0.95rem;
        border-top-left-radius: 6px; border-top-right-radius: 6px;
    }
    #entryPanel .form-group label { font-size: 0.8rem; font-weight: 600; color: #495057; }
</style>
       <div id="quotemessages"></div>

<div class="card">
    <div class="card-header">
    </div>
    <div class="card-body">
        <form id="quoteform" class="sq-app" autocomplete="off" onsubmit="return false;">
            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
            <input type="hidden" id="savedBank1" value="<?php echo htmlspecialchars($savedBank1, ENT_QUOTES); ?>" />
            <input type="hidden" id="savedBank2" value="<?php echo htmlspecialchars($savedBank2, ENT_QUOTES); ?>" />
            <input type="hidden" id="selectedRef" name="selectedRef" value="" />
            <input type="hidden" id="selectedImages" name="selectedImages" value="" />

            <div id="salesPhotosModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <span class="accept">&checkmark;</span>
                    <div id="salesPhotosContainer">
                        <div id="imageContainer"></div>
                    </div>
                </div>
            </div>

            <div class="sq-card sq-load-bar">
                    <div class="sq-grid">
                        <div class="sq-field"  style="min-width:90px;">
                            <label for="loadQuoteNo"><?php echo _('Load Quote'); ?></label>
                            <div class="sq-inline">
                                <input type="text" name="loadQuoteNo" id="loadQuoteNo" size="10" placeholder="<?php echo _('Search quote no / customer'); ?>" class="form-control form-control-sm" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleLoadQuoteInput(event)" />
                                <button type="button" id="loadQuoteBtn" class="btn btn-info btn-sm"><?php echo _('Load'); ?></button>
                            </div>
                            <div id="loadQuoteMsg"></div>
                        </div>
                    </div>
            </div>
            <div class="sq-card">
                    <div class="sq-grid" style="margin-bottom:8px;">
                        <div class="sq-field" style="min-width:96px;">
                            <label><?php echo _('Date'); ?></label>
                            <input tabindex="1" type="text" class="date form-control form-control-sm" alt="<?php echo $_SESSION['DefaultDateFormat']; ?>" data-datefmt="<?php echo $_SESSION['DefaultDateFormat']; ?>" name="date" id="date" size="11" maxlength="10" value="<?php echo $_POST['date']; ?>" onchange="isDate(this, this.value, '<?php echo $_SESSION['DefaultDateFormat']; ?>')" />
                        </div>
                        <div class="sq-field">
                            <label><?php echo _('Document No'); ?></label>
                            <div class="input-group input-group-sm">
                                <input tabindex="4" type="text" name="documentno" id="salesid" class="form-control form-control-sm" value="<?php echo htmlspecialchars($_POST['documentno'] ?? '', ENT_QUOTES); ?>" readonly="readonly" style="width:80px;"/>
                            </div>
                        </div>
                    </div>
                    <div class="sq-grid" style="margin-bottom:8px;">
                        <div class="sq-field" style="min-width:90px;">
                            <label for="CustomerName"><?php echo _('Customer Name:'); ?></label>
                            <input type="text" name="CustomerName" id="CustomerName" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedCustomerName !== '' ? $selectedCustomerName : ($_POST['CustomerName'] ?? ''), ENT_QUOTES); ?>" placeholder="<?php echo _('Search a customer name'); ?>" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleCustomerNameInput(event)" />
                            <input type="hidden" name="CustomerID" id="CustomerID" value="<?php echo htmlspecialchars($selectedCustomerID !== '' ? $selectedCustomerID : ($_POST['CustomerID'] ?? ''), ENT_QUOTES); ?>" />
                        </div>
                        
                    </div>
                    <div class="sq-grid" style="margin-bottom:8px;">
                        <div class="sq-field" style="min-width:180px;">
                            <label><?php echo _('Sales REP Account'); ?></label>
                            <select name="salespersoncode" id="salespersoncode" class="form-control form-control-sm"><option></option><?php echo $SalesManOptions; ?></select>
                        </div>
                        <div class="sq-field" style="min-width:90px;">
                            <label><?php echo _('CREDIT TERMS'); ?></label>
                            <input tabindex="5" type="text" name="terms" id="terms" class="form-control form-control-sm" value="<?php echo htmlspecialchars($_POST['terms'] ?? '', ENT_QUOTES); ?>" size="10" />
                        </div>
                        <div class="sq-field" style="min-width:64px;">
                            <label><?php echo _('Currency Code'); ?></label>
                            <input tabindex="6" type="text" id="currencycode" size="6" name="currencycode" class="form-control form-control-sm" value="<?php echo htmlspecialchars($quoteCurrency, ENT_QUOTES); ?>" readonly="readonly" onchange="loadBanks()" />
                        </div>
                    </div>
                    <div class="sq-grid">
                        <div class="sq-field" style="min-width:90px;">
                            <label><?php echo _('Select Bank to Deposit :'); ?></label>
                            <select name="Bank_Code" id="Bank_Code" class="form-control form-control-sm"><option></option></select>
                        </div>
                        <div class="sq-field" style="min-width:90px;">
                            <label><?php echo _('Select option 2 Bank to Deposit :'); ?></label>
                            <select name="Bank_Code2" id="Bank_Code2" class="form-control form-control-sm"><option></option></select>
                        </div>
                    </div>
            </div>

            <div class="sq-card">
                    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:8px;">
                    <button type="button" id="addStandardBtn" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-plus"></i> <?php echo _('Add Sample Standard'); ?>
                    </button>
                    </div>
                    <div id="pendingStandards" style="display:flex; flex-direction:column; gap:8px; margin-bottom:8px;"></div>
                    <div id="quoteTableContainer" class="sq-table-wrap">
                        <div id="quoteTabulator"></div>
                        <div id="totalsRow" style="display:flex; justify-content:flex-end; gap:18px; padding:8px 4px; font-weight:700;">
                            <span><?php echo _('Total Order'); ?></span>
                            <span class="q-tnet">0.00</span>
                            <span class="q-tvat">0.00</span>
                            <span class="q-tgross">0.00</span>
                        </div>
                    </div>

                    <div class="sq-field mb-3" style="margin-top:12px;">
                        <label for="paymentterms"><?php echo _('Terms Footer'); ?></label>
                        <textarea name="paymentterms" id="ParameterName" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['paymentterms'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>

                    <div class="action-buttons sq-footer">
                        <button type="button" id="newQuoteBtn" class="btn btn-outline-secondary btn-sm"><i class="fa fa-file"></i> <?php echo _('New Quote'); ?></button>
                        <button type="button" id="recalcBtn" class="btn btn-outline-secondary btn-sm"><i class="fa fa-calculator"></i> <?php echo _('Re-Calculate'); ?></button>
                        <button type="button" id="saveQuoteBtn" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo _('SAVE QUOTE'); ?></button>
                    </div>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('recalcBtn').addEventListener('click', function (e) {
        e.preventDefault();
        quoteRecalc();
        quoteMsg('Recalculated.');
    });
    var qform = document.getElementById('quoteform');
    qform.addEventListener('submit', function (e) {
        var hasLines = (window.quoteTable && window.quoteTable.getData) ? window.quoteTable.getData().length > 0 : false;
        if (!hasLines) {
            if (!window.confirm('No lines on this quote. Save anyway?')) { e.preventDefault(); return false; }
        }
    });
</script>
<?php
}

$sqFaCss = __DIR__ . '/css/fontawesome6.4.0.all.min.css';
$sqFaFix = __DIR__ . '/css/fontawesome-fix.css';
if (is_file($sqFaCss)) {
    echo '<link rel="stylesheet" href="css/fontawesome6.4.0.all.min.css?v=' . filemtime($sqFaCss) . '" />';
}
if (is_file($sqFaFix)) {
    echo '<link rel="stylesheet" href="css/fontawesome-fix.css?v=' . filemtime($sqFaFix) . '" />';
}
$sqTabCss = __DIR__ . '/javascripts/dist/css/tabulator_bootstrap5.min.css';
$sqTabJs = __DIR__ . '/javascripts/dist/js/tabulator.min.js';
if (is_file($sqTabCss)) {
    echo '<link rel="stylesheet" href="javascripts/dist/css/tabulator_bootstrap5.min.css?v=' . filemtime($sqTabCss) . '" />';
}
if (is_file($sqTabJs)) {
    echo '<script src="javascripts/dist/js/tabulator.min.js?v=' . filemtime($sqTabJs) . '" type="text/javascript"></script>';
}
$sqJsFile = __DIR__ . '/javascripts/salesquotation.js';
if (is_file($sqJsFile)) {
    echo '<script src="javascripts/salesquotation.js?v=' . filemtime($sqJsFile) . '" type="text/javascript"></script>';
} else {
    trigger_error('salesquotation.js missing in javascripts/ - upload it together with SalesQuotation.php', E_USER_WARNING);
}

include('includes/footer.inc');

// 80/20 standard pricing: mirror of the original SalesQuotation function so the
// save path (and any QUOTES::showtable render) resolves bundle vs per-test fees.
function applyStandardPricing() {
    global $db;
    if (empty($_SESSION['sales_orders'])) {
        return;
    }

    $cartCodes = array();
    foreach ($_SESSION['sales_orders'] as $row) {
        $cartCodes[trim($row['itemcode'])] = true;
    }
    $cartCodes = array_filter($cartCodes);
    if (empty($cartCodes)) {
        return;
    }

    $isTsBundle = function($c) {
        return preg_match('/^TS\d{4}$/', $c) === 1;
    };

    $claimed = array();
    foreach (array_keys($cartCodes) as $c) {
        if ($isTsBundle($c)) {
            $claimed[$c] = true;
        }
    }

    $inList = array();
    foreach (array_keys($cartCodes) as $c) {
        $inList[] = "'" . mysqli_real_escape_string($db, $c) . "'";
    }
    $inList = implode(',', $inList);

    $catRes = DB_query("SELECT DISTINCT dt.categoryid FROM discounttable dt WHERE dt.itemcode IN (" . $inList . ") AND dt.is_active = 1", $db);
    while ($catRow = DB_fetch_array($catRes)) {
        $cat = trim($catRow['categoryid']);
        if ($isTsBundle($cat)) {
            $claimed[$cat] = true;
        }
    }

    if (empty($claimed)) {
        return;
    }

    foreach (array_keys($claimed) as $cat) {
        $catEsc = mysqli_real_escape_string($db, $cat);
        $active = array();
        $actRes = DB_query("SELECT dt.itemcode, dt.discount_percent FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode WHERE dt.categoryid = '" . $catEsc . "' AND dt.is_active = 1 AND (sm.inactive = 0 OR sm.inactive IS NULL)", $db);
        while ($actRow = DB_fetch_array($actRes)) {
            $active[trim($actRow['itemcode'])] = (float)$actRow['discount_percent'];
        }
        if (empty($active)) {
            continue;
        }

        $bundleInCart = isset($_SESSION['sales_orders'][$cat]);
        $bundlePrice = (float) SelectTestPriceListToUse($cat, 1, ($_POST['CustomerID'] ?? ''));
        $included = 0;
        foreach (array_keys($active) as $at) {
            if (isset($_SESSION['sales_orders'][$at])) {
                $included++;
            }
        }
        $total = count($active);
        $useStandard = ($bundleInCart && $bundlePrice > 0 && $included > 0 && ($included / $total) >= 0.8);

        if ($bundleInCart) {
            $_SESSION['sales_orders'][$cat]['discountpercent'] = $useStandard ? 0 : 100;
        }

        foreach ($active as $at => $disc) {
            if (isset($_SESSION['sales_orders'][$at])) {
                $_SESSION['sales_orders'][$at]['pricevalue'] = (float) SelectTestPriceListToUse($at, 1, ($_POST['CustomerID'] ?? ''));
                $_SESSION['sales_orders'][$at]['discountpercent'] = $useStandard ? 100 : $disc;
            }
        }
    }
}