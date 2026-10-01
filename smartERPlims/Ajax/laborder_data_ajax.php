<?php
// Laboratory order grid backend: session-cart CRUD + autocomplete sources.
// Errors are NEVER silent: every failure is logged with context and returned
// as JSON {status:'error',...} so the UI can show what happened.
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../quotes/php_errors.log');

session_write_close();
session_name('ErpWithCRM');
session_start();

$PathPrefix = '../';
include('../config.php');
include('../includes/ConnectDB_mysqli.inc');
include('../includes/LanguageSetup.php');
include('../includes/SQL_CommonFunctions.inc');
include('../includes/DateFunctions.inc');

header('Content-Type: application/json');

function labLog($message) {
    @file_put_contents(__DIR__ . '/../quotes/laborder_ajax_errors.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function labDB($sql) {
    global $db, $action;
    $result = DB_query($sql, $db, '', '', false, false);
    if ($result === false) {
        labLog('query failed: action=' . $action . ' errno=' . $db->errno . ' error=' . $db->error
            . ' | ' . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 1500));
    }
    return $result;
}

$req = json_decode(file_get_contents('php://input'), true);
$req = is_array($req) ? $req + $_REQUEST : $_REQUEST;
$action = $req['action'] ?? '';

function labFail($message, $code = '') {
    labLog('replied error' . ($code !== '' ? ' [' . $code . ']' : '') . ': ' . $message);
    echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
    exit;
}

function labSessionCheck() {
    if (empty($_SESSION['UserID'])) {
        labFail('Session expired - reload the page and log in again.', 'NO_SESSION');
    }
    if (!isset($_SESSION['sales_orders']) || !is_array($_SESSION['sales_orders'])) {
        $_SESSION['sales_orders'] = array();
    }
}

// Same key scheme as laboratryPOS::gethashcode($itemcode, 0) (CryptPass sha1 per config).
function labHash($itemcode) {
    $itemcode = trim((string)$itemcode);
    if ($itemcode === '') {
        return '';
    }
    return sha1($itemcode . '0');
}

function labStockDetails($itemcode) {
    global $db;

    $empty = ['barcode' => '', 'itemcode' => '', 'stockname' => '', 'si' => '', 'vat' => 0, 'units' => 'PCS', 'sellingprice' => 0, 'partperunit' => 1, 'averagestock' => 0];
    $itemEsc = mysqli_real_escape_string($db, trim($itemcode));
    $SQL = "SELECT stockmaster.barcode, stockmaster.itemcode, stockmaster.descrip,
                   unit.descrip AS si, vatcategory.vat, stockmaster.units, stockmaster.sellingprice,
                   stockmaster.partperunit, stockmaster.averagestock
            FROM stockmaster
            LEFT JOIN unit ON stockmaster.units = unit.code
            LEFT JOIN inventorypostinggroup ON stockmaster.postinggroup = inventorypostinggroup.code
            LEFT JOIN vatcategory ON inventorypostinggroup.vatcategory = vatcategory.vatc
            WHERE stockmaster.itemcode='$itemEsc'";
    $ResultIndex = labDB($SQL);
    if ($ResultIndex && ($row = DB_fetch_row($ResultIndex))) {
        $si = trim((string)($row[3] ?? ''));
        $unitCode = trim((string)($row[5] ?? ''));
        return [
            'barcode' => $row[0],
            'itemcode' => trim($row[1]),
            'stockname' => $row[2],
            'si' => $si,
            'vat' => (int)($row[4] ?? 0),
            'units' => $si !== '' ? $si : ($unitCode !== '' ? $unitCode : 'PCS'),
            'sellingprice' => (float)($row[6] ?? 0),
            'partperunit' => (float)($row[7] ?? 0) > 0 ? (float)($row[7] ?? 0) : 1,
            'averagestock' => (float)($row[8] ?? 0)
        ];
    }
    return $empty;
}

function labTatFor($stockCode) {
    global $db;

    if (empty($stockCode)) {
        return 0;
    }
    $ResultIndex = labDB(sprintf("SELECT tat FROM PriceList WHERE stockcode='%s' LIMIT 1", mysqli_real_escape_string($db, $stockCode)));
    if ($ResultIndex && ($row = DB_fetch_row($ResultIndex))) {
        return (int)($row[0] ?? 0);
    }
    return 0;
}

function labPriceFor($stockid, $quantity = 1, $customerid = '') {
    global $db;

    $price = 0;
    $Dprice = 0;
    if (mb_strlen($customerid) > 1) {
        $ResultIndex = labDB(sprintf("SELECT price FROM PriceList WHERE stockcode='%s' AND approved=1 AND quantity=%f AND customerCode='%s'",
            mysqli_real_escape_string($db, $stockid), $quantity, mysqli_real_escape_string($db, $customerid)));
        if ($ResultIndex && ($row = DB_fetch_row($ResultIndex))) {
            $price = $row[0];
        }
    }
    $ResultIndex = labDB(sprintf("SELECT price FROM PriceList WHERE stockcode='%s' AND quantity=%f AND (customerCode IS NULL OR customerCode='')",
        mysqli_real_escape_string($db, $stockid), $quantity));
    if ($ResultIndex && ($row = DB_fetch_row($ResultIndex))) {
        $Dprice = $row[0];
    }
    return $price > 0 ? (float)$price : (float)$Dprice;
}

function labCustomerFlags($customerid) {
    global $db;

    $flags = ['customerposting' => '', 'vatinclusive' => false, 'istaxed' => false, 'currency' => ''];
    if (empty($customerid)) {
        return $flags;
    }
    $ResultIndex = labDB("SELECT customerposting, VATinclusive, IsTaxed, curr_cod
                          FROM debtors LEFT JOIN postinggroups ON code = customerposting
                          WHERE debtors.itemcode='" . mysqli_real_escape_string($db, $customerid) . "' LIMIT 1");
    if ($ResultIndex && ($row = DB_fetch_row($ResultIndex))) {
        $flags['customerposting'] = trim((string)($row[0] ?? ''));
        $flags['vatinclusive'] = ($row[1] == 1 || strtolower((string)($row[1] ?? '')) === 'true');
        $flags['istaxed'] = ($row[2] == 1 || strtolower((string)($row[2] ?? '')) === 'true');
        $flags['currency'] = trim((string)($row[3] ?? ''));
    }
    return $flags;
}

// 80/20 standard pricing (same rule as the sales quotation): for each claimed
// sample standard, bundle-in-cart + >=80% of its tests included -> bundle
// charged (disc 0), tests free (disc 100); otherwise bundle free, tests at
// list discount. Manual/saved-disc edge preserved. Writes into session rows.
function labBasePrice($code, $customerid) {
    $price = labPriceFor($code, 1, $customerid);
    if ($price <= 0) {
        $sd = labStockDetails($code);
        if ($sd['sellingprice'] > 0) {
            $price = $sd['sellingprice'];
        }
    }
    return $price;
}

function labActiveTests($cat, $customerid) {
    global $db;
    $tests = [];
    $catEsc = mysqli_real_escape_string($db, $cat);
    $ResultIndex = labDB("SELECT dt.itemcode, dt.discount_percent, sm.descrip
                          FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode
                          WHERE dt.categoryid='$catEsc' AND dt.is_active = 1
                          AND (sm.inactive = 0 OR sm.inactive IS NULL) ORDER BY sm.descrip");
    if ($ResultIndex === false) {
        labFail('Could not load standard tests. See the error log.', 'DB_STD_TESTS');
    }
    while ($row = DB_fetch_array($ResultIndex)) {
        $tcode = trim($row['itemcode']);
        $tests[] = [
            'code' => $tcode,
            'name' => $row['descrip'],
            'price' => labBasePrice($tcode, $customerid),
            'disc' => (float)($row['discount_percent'] ?? 0)
        ];
    }
    return $tests;
}

function labModeClean($mode) {
    $mode = strtolower(trim((string)$mode));
    if ($mode === 'standard' || $mode === 'pertest') {
        return $mode;
    }
    return 'auto';
}

// Per-standard pricing override, mirroring the Sales Quotation control:
// auto = the 80/20 rule decides, standard = bundle price, pertest = test prices.
function labModeFor($cat) {
    $cat = trim((string)$cat);
    if ($cat === '') {
        return 'auto';
    }
    $modes = $_SESSION['lab_order_modes'] ?? array();
    return labModeClean($modes[$cat] ?? 'auto');
}

function labApplyStandardPricing($customerid = '') {
    global $db;
    if (empty($_SESSION['sales_orders'])) {
        return;
    }
    $inCart = [];
    $groups = [];
    foreach ($_SESSION['sales_orders'] as $row) {
        $c = trim($row['itemcode'] ?? '');
        if ($c !== '' && (float)($row['quantity'] ?? 0) > 0) {
            $inCart[$c] = true;
        }
        $g = trim($row['stdGroup'] ?? '');
        if ($g !== '') {
            $groups[$g] = true;
        }
    }
    // A standard is "claimed" once one of its test lines is already in the
    // cart, so we never stack a second bundle on top of the same tests.
    $claimed = [];
    $activeByCat = [];
    foreach (array_keys($groups) as $g) {
        if (isset($claimed[$g])) {
            continue;
        }
        $act = labActiveTests($g, $customerid);
        $activeByCat[$g] = $act;
        foreach ($act as $t) {
            if (isset($inCart[$t['code']])) {
                $claimed[$g] = true;
                break;
            }
        }
    }
    $byCode = [];
    foreach ($_SESSION['sales_orders'] as $key => $row) {
        $byCode[trim($row['itemcode'] ?? '')] = $key;
    }
    foreach (array_keys($claimed) as $cat) {
        $active = $activeByCat[$cat] ?? labActiveTests($cat, $customerid);
        if (empty($active)) {
            continue;
        }
        // Same effective bundle price as add_standard: list price, or the sum
        // of the tests when the bundle itself carries no price. Using the raw
        // base price here would read 0, mark the bundle fully discounted and
        // wipe out the fallback.
        $bundlePrice = labBasePrice($cat, $customerid);
        if ($bundlePrice <= 0) {
            $sum = 0;
            foreach ($active as $t) {
                $sum += (float)($t['price'] ?? 0);
            }
            $bundlePrice = round($sum, 2);
        }
        $bundleInCart = isset($inCart[$cat]);
        $included = 0;
        foreach ($active as $t) {
            if (isset($inCart[$t['code']])) {
                $included++;
            }
        }
        $total = count($active);
        $testDisc100 = true;
        foreach ($active as $t) {
            if (isset($byCode[$t['code']]) && (float)($_SESSION['sales_orders'][$byCode[$t['code']]]['discountpercent'] ?? 0) !== 100.0) {
                $testDisc100 = false;
                break;
            }
        }
        $savedStandard = ($included === 0 && $total > 0 && $testDisc100);
        // auto keeps the 80/20 rule; the group control can force either side.
        $mode = labModeFor($cat);
        if ($mode === 'standard') {
            $useStandard = true;
        } elseif ($mode === 'pertest') {
            $useStandard = false;
        } else {
            $useStandard = $bundleInCart && $bundlePrice > 0 &&
                ($included > 0 && ($included / $total) >= 0.8 ? true : $savedStandard);
        }
        if ($bundleInCart && isset($byCode[$cat])) {
            $_SESSION['sales_orders'][$byCode[$cat]]['discountpercent'] = $useStandard ? 0 : 100;
        }
        foreach ($active as $t) {
            if (!isset($byCode[$t['code']])) {
                continue;
            }
            $k = $byCode[$t['code']];
            $_SESSION['sales_orders'][$k]['pricevalue'] = $t['price'];
            $_SESSION['sales_orders'][$k]['discountpercent'] = $useStandard ? 100 : $t['disc'];
        }
    }
}

// Amount math mirroring laboratryPOS::showtable(): user price wins, else
// customer pricelist, else default pricelist; base = price x qty; VAT in/ex;
// header discount factor. Writes computed figures back into the session rows
// (the save reads them) and returns grid rows + totals.
function labCartList($customerid, $headerDisc) {
    $flags = labCustomerFlags($customerid);
    $headerDisc = (float)$headerDisc;
    $rows = array();
    $totalNet = 0;
    $totalVat = 0;
    $totalGross = 0;
    foreach ($_SESSION['sales_orders'] as $key => $rowvalue) {
        $itemcode = trim($rowvalue['itemcode']);
        $stock = labStockDetails($itemcode);
        if ($stock['itemcode'] === '') {
            labLog('cart row with unknown itemcode skipped: ' . $itemcode);
            continue;
        }
        $rate = $flags['istaxed'] ? (int)$stock['vat'] : 0;
        $package = (float)($rowvalue['partsperunit'] ?? 0) > 0 ? (float)$rowvalue['partsperunit'] : 1;
        $userPrice = (float)($rowvalue['pricevalue'] ?? 0);
        if ($userPrice == 0) {
            $listPrice = labPriceFor($itemcode, $package, $customerid);
            $salesprice = $listPrice > 0 ? $listPrice : labPriceFor($itemcode, $package);
        } else {
            $salesprice = $userPrice;
        }
        $qty = (float)($rowvalue['quantity'] ?? 0);
        $baseamount = $salesprice * $qty;
        $lineDisc = (float)($rowvalue['discountpercent'] ?? 0);
        $taxable = $baseamount - ($baseamount * ($lineDisc / 100));
        if ($flags['vatinclusive']) {
            $netamount = $taxable / (($rate + 100) / 100);
            $vatamount = $taxable - $netamount;
            $grossamount = round($taxable, 2);
        } else {
            $vatamount = $taxable * ($rate / 100);
            $grossamount = round($taxable + $vatamount, 2);
            $netamount = $taxable;
        }
        if ($headerDisc > 0) {
            $factor = (100 - $headerDisc) / 100;
            $netamount *= $factor;
            $vatamount *= $factor;
            $grossamount *= $factor;
        }
        $totalNet += $netamount;
        $totalVat += $vatamount;
        $totalGross += $grossamount;
        $_SESSION['sales_orders'][$key]['salesprice'] = $salesprice;
        $_SESSION['sales_orders'][$key]['netamount'] = $netamount;
        $_SESSION['sales_orders'][$key]['vatamount'] = $vatamount;
        $_SESSION['sales_orders'][$key]['grossamount'] = $grossamount;
        $rows[] = [
            'id' => $key,
            'code' => $itemcode,
            'label' => $stock['stockname'],
            'name' => $stock['stockname'],
            'stdGroup' => trim((string)($rowvalue['stdGroup'] ?? '')),
            'isBundle' => !empty($rowvalue['isBundle']),
            'stdName' => trim((string)($rowvalue['stdName'] ?? '')),
            'pricingMode' => labModeFor($rowvalue['stdGroup'] ?? ''),
            'sampleid' => (string)($rowvalue['sampleid'] ?? ''),
            'quantity' => $qty,
            'units' => $stock['si'] !== '' ? $stock['si'] : $stock['units'],
            'price' => $salesprice,
            'disc' => $lineDisc,
            'tat' => (int)($rowvalue['tat'] ?? 0),
            'net' => round($netamount * 100) / 100,
            'vatAmt' => round($vatamount * 100) / 100,
            'gross' => round($grossamount * 100) / 100
        ];
    }
    return [
        'rows' => $rows,
        'totals' => [
            'net' => round($totalNet * 100) / 100,
            'vat' => round($totalVat * 100) / 100,
            'gross' => round($totalGross * 100) / 100
        ]
    ];
}

function labCartResponse($customerid, $headerDisc, $warnings = []) {
    $list = labCartList($customerid, $headerDisc);
    $resp = ['status' => 'ok', 'rows' => $list['rows'], 'totals' => $list['totals']];
    if (!empty($warnings)) {
        $resp['warnings'] = array_values($warnings);
    }
    echo json_encode($resp);
    exit;
}

function labEsc($v) {
    global $db;
    return mysqli_real_escape_string($db, (string)($v ?? ''));
}

function labSqlVal($v) {
    global $db;
    if ($v === null || $v === '') {
        return "NULL";
    }
    if (is_numeric($v)) {
        return $v;
    }
    return "'" . mysqli_real_escape_string($db, $v) . "'";
}

// Closed-period guard mirroring GetPeriod(..., UseProhibit=true), but loud
// (JSON error) instead of die(). Fails when the date is missing/invalid too.
function labPeriodGuard($datestr) {
    global $db;
    if (!Is_date($datestr)) {
        labFail('Invalid order date. Use the date picker format.', 'BAD_DATE');
    }
    $testdate = FormatDateForSQL($datestr);
    $ResultIndex = labDB("SELECT DATEDIFF('$testdate', PeriodRollover) FROM companies");
    if ($ResultIndex === false) {
        labFail('Could not verify the accounting period. See the error log.', 'DB_PERIOD');
    }
    $row = DB_fetch_row($ResultIndex);
    if ($row && (int)$row[0] <= 0 && RequestForAuthorisation() == 0) {
        labFail('You are not authorised to post into a closed period (' . $testdate . ').', 'CLOSED_PERIOD');
    }
    return $testdate;
}

function labBanks($currency) {
    global $db;
    $banks = [];
    if (trim((string)$currency) === '') {
        return $banks;
    }
    $ResultIndex = labDB("SELECT accountcode, bankName, BranchName, currency FROM BankAccounts
                          WHERE currency='" . mysqli_real_escape_string($db, trim((string)$currency)) . "' ORDER BY bankName, BranchName");
    if ($ResultIndex === false) {
        labFail('Could not load bank accounts. See the error log.', 'DB_BANKS');
    }
    while ($row = DB_fetch_array($ResultIndex)) {
        $banks[] = [
            'accountcode' => trim($row['accountcode']),
            'bankName' => $row['bankName'],
            'BranchName' => $row['BranchName'],
            'currency' => $row['currency']
        ];
    }
    return $banks;
}

// Fill the session cart from saved lines, mirroring the page load handlers
// (order type 1 keeps its docno; quote type 54 starts a fresh number).
function labFillCart($docno, $type) {
    global $db;
    $docnoEsc = mysqli_real_escape_string($db, trim($docno));
    // Pre-migration fallback: pricingmode column not yet added
    // (see sql/migration_add_pricingmode_column.sql).
    $pmCol = labDB("SHOW COLUMNS FROM SalesHeader LIKE 'pricingmode'");
    $pmSelect = ($pmCol !== false && DB_num_rows($pmCol) > 0) ? ', pricingmode' : '';
    if ($type === 1) {
        $hdrSql = "SELECT documentno, docdate, customercode, customername, yourreference, currencycode,
                          salespersoncode, QtyDiscount, locationcode" . $pmSelect . "
                   FROM SalesHeader WHERE documenttype=1 AND documentno='$docnoEsc' LIMIT 1";
        $lineSql = "SELECT code, description, Quantity, UnitPrice, LineDiscountPercent, TAT, sampleID, PartPerUnit
                    FROM SalesLine WHERE documenttype=1 AND documentno='$docnoEsc' AND code IS NOT NULL AND code != ''";
    } else {
        $hdrSql = "SELECT documentno, docdate, customercode, customername, yourreference, currencycode,
                          salespersoncode, QtyDiscount, locationcode" . $pmSelect . "
                   FROM SalesHeader WHERE documenttype=54 AND documentno='$docnoEsc' LIMIT 1";
        $lineSql = "SELECT code, description, Quantity, UnitPrice, LineDiscountPercent, TAT, PartPerUnit
                    FROM SalesLine WHERE documenttype=54 AND documentno='$docnoEsc' AND code IS NOT NULL AND code != ''";
    }
    $hdrResult = labDB($hdrSql);
    if ($hdrResult === false) {
        labFail('Could not load the ' . ($type === 1 ? 'order' : 'quote') . ' header.', 'DB_LOAD_HDR');
    }
    $hdrRow = DB_fetch_array($hdrResult);
    if (!$hdrRow) {
        labFail(($type === 1 ? 'Order not found: ' : 'Quote not found: ') . trim($docno), 'NOT_FOUND');
    }
    $header = [
        'documentno' => $type === 1 ? trim($hdrRow['documentno']) : '',
        'date' => ConvertSQLDate($hdrRow['docdate']),
        'customercode' => $hdrRow['customercode'],
        'customername' => $hdrRow['customername'],
        'reference' => $hdrRow['yourreference'] ?? '',
        'currencycode' => $hdrRow['currencycode'],
        'salespersoncode' => $hdrRow['salespersoncode'] ?? '',
        'DiscountPercent' => $hdrRow['QtyDiscount'] ?? '',
        'Bank_Code' => $hdrRow['locationcode'] ?? '',
        'pricingmode' => $hdrRow['pricingmode'] ?? ''
    ];
    $lineResult = labDB($lineSql);
    if ($lineResult === false) {
        labFail('Could not load the lines.', 'DB_LOAD_LINES');
    }
    $_SESSION['sales_orders'] = array();
    // Restore the saved per-standard pricing overrides so the group controls
    // come back showing Standard/Test instead of resetting to Auto 80/20.
    $_SESSION['lab_order_modes'] = array();
    $pmRaw = trim((string)($header['pricingmode'] ?? ''));
    if ($pmRaw !== '') {
        $pmDecoded = json_decode($pmRaw, true);
        if (is_array($pmDecoded)) {
            foreach ($pmDecoded as $pmCat => $pmMode) {
                $pmCat = trim((string)$pmCat);
                $pmMode = labModeClean($pmMode);
                if ($pmCat !== '' && $pmMode !== 'auto') {
                    $_SESSION['lab_order_modes'][$pmCat] = $pmMode;
                }
            }
        }
    }
    // Bundle heuristic (same as quotation load): a TS-code line opens a group,
    // following lines attach to it until the next bundle row.
    $loadCat = '';
    $loadName = '';
    while ($line = DB_fetch_array($lineResult)) {
        $code = trim($line['code']);
        if (preg_match('/^TS\d{4}$/', $code) === 1) {
            $loadCat = $code;
            $loadName = trim($line['description']) !== '' ? trim($line['description']) : $code;
        } elseif ($loadCat === '' && $loadName === '') {
            $loadName = 'Loaded lines';
        }
        $hash = sha1($code . '0');
        $_SESSION['sales_orders'][$hash] = array(
            'line_no' => $hash,
            'itemcode' => $code,
            'barcode' => '',
            'stockname' => trim($line['description']),
            'sampleid' => trim((string)($line['sampleID'] ?? '')),
            'partsperunit' => (float)($line['PartPerUnit'] ?? 0) > 0 ? (float)($line['PartPerUnit']) : 1,
            'averagestock' => 0,
            'quantity' => (float)$line['Quantity'],
            'pricevalue' => (float)$line['UnitPrice'],
            'tat' => $line['TAT'] ?? 0,
            'discountpercent' => (float)($line['LineDiscountPercent'] ?? 0),
            'stdGroup' => $loadCat,
            'isBundle' => ($code !== '' && $code === $loadCat),
            'stdName' => $loadName !== '' ? $loadName : 'Loaded lines'
        );
    }
    // Intentionally no repricing here: a load restores saved values faithfully
    // (same as quotation load); amounts compute on read.
    return $header;
}

switch ($action) {

    case 'search_orders':
        labSessionCheck();
        $q = trim($req['q'] ?? '');
        $results = [];
        $where = "documenttype=1";
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $where .= " AND (documentno LIKE '%$qEsc%' OR customername LIKE '%$qEsc%' OR customercode LIKE '%$qEsc%')";
        }
        $ResultIndex = labDB("SELECT documentno, customername, docdate FROM SalesHeader
                              WHERE $where ORDER BY docdate DESC LIMIT 15");
        if ($ResultIndex === false) {
            labFail('Order search failed. See the error log for details.', 'DB_SEARCH_ORDERS');
        }
        while ($row = DB_fetch_array($ResultIndex)) {
            $docno = trim($row['documentno']);
            $cust = trim((string)($row['customername'] ?? ''));
            $results[] = ['value' => $docno, 'label' => $docno . ($cust !== '' ? ' - ' . $cust : '')];
        }
        echo json_encode(['status' => 'ok', 'results' => $results]);
        break;

    case 'search_quotes':
        labSessionCheck();
        $q = trim($req['q'] ?? '');
        $results = [];
        $where = "documenttype=54";
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $where .= " AND (documentno LIKE '%$qEsc%' OR customername LIKE '%$qEsc%' OR customercode LIKE '%$qEsc%')";
        }
        $ResultIndex = labDB("SELECT documentno, customername, docdate FROM SalesHeader
                              WHERE $where ORDER BY docdate DESC LIMIT 15");
        if ($ResultIndex === false) {
            labFail('Quote search failed. See the error log for details.', 'DB_SEARCH_QUOTES');
        }
        while ($row = DB_fetch_array($ResultIndex)) {
            $docno = trim($row['documentno']);
            $cust = trim((string)($row['customername'] ?? ''));
            $results[] = ['value' => $docno, 'label' => $docno . ($cust !== '' ? ' - ' . $cust : '')];
        }
        echo json_encode(['status' => 'ok', 'results' => $results]);
        break;

    case 'search_items':
        labSessionCheck();
        $q = trim($req['q'] ?? '');
        $results = [];
        $where = "(inactive = 0 OR inactive IS NULL) AND isstock_1 = 1";
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $where .= " AND (itemcode LIKE '%$qEsc%' OR descrip LIKE '%$qEsc%' OR barcode LIKE '%$qEsc%')";
        }
        $ResultIndex = labDB("SELECT itemcode, barcode, descrip FROM stockmaster
                              WHERE $where ORDER BY descrip LIMIT 25");
        if ($ResultIndex === false) {
            labFail('Item search failed. See the error log for details.', 'DB_SEARCH_ITEMS');
        }
        while ($row = DB_fetch_array($ResultIndex)) {
            $code = trim($row['itemcode']);
            $results[] = ['value' => $code, 'label' => ($row['descrip'] ?? $code), 'type' => 'item'];
        }
        // Sample standards live in the same box: picking one expands its tests.
        $stdWhere = "categoryid LIKE 'TS%'";
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $stdWhere .= " AND (categoryid LIKE '%$qEsc%' OR categorydescription LIKE '%$qEsc%')";
        }
        $stdResult = labDB("SELECT categoryid, categorydescription FROM stockcategory
                            WHERE $stdWhere ORDER BY categorydescription LIMIT 10");
        if ($stdResult === false) {
            labFail('Standard search failed. See the error log for details.', 'DB_SEARCH_STANDARDS');
        }
        while ($row = DB_fetch_array($stdResult)) {
            $code = trim($row['categoryid']);
            $results[] = ['value' => $code, 'label' => ($row['categorydescription'] ?? $code), 'type' => 'standard'];
        }
        echo json_encode(['status' => 'ok', 'results' => $results]);
        break;

    case 'add_standard':
        labSessionCheck();
        $cat = trim($req['cat'] ?? '');
        $customerid = trim($req['CustomerID'] ?? '');
        if ($cat === '' || preg_match('/^TS\d{4}$/', $cat) !== 1) {
            labFail('Select a sample standard first.', 'NO_STANDARD');
        }
        foreach ($_SESSION['sales_orders'] as $row) {
            if (trim($row['stdGroup'] ?? '') === $cat || trim($row['itemcode'] ?? '') === $cat) {
                labFail('Standard ' . $cat . ' is already in this order.', 'DUPLICATE_STANDARD');
            }
        }
        $catEsc = mysqli_real_escape_string($db, $cat);
        $bundleName = $cat;
        $catResult = labDB("SELECT categorydescription FROM stockcategory WHERE categoryid='$catEsc' LIMIT 1");
        if ($catResult === false) {
            labFail('Could not read the standard.', 'DB_STANDARD');
        }
        if ($catRow = DB_fetch_array($catResult)) {
            $bundleName = ($catRow['categorydescription'] ?? $cat);
        }
        $bd = labStockDetails($cat);
        $active = labActiveTests($cat, $customerid);
        // Quotation parity: the TS bundle row is ALWAYS added, even when the
        // standard has no mapped tests (the tests simply cannot follow).
        $bundleBase = labBasePrice($cat, $customerid);
        if ($bundleBase <= 0 && !empty($active)) {
            $sum = 0;
            foreach ($active as $t) {
                $sum += (float)$t['price'];
            }
            if ($sum > 0) {
                $bundleBase = round($sum, 2);
            }
        }
        $bundleId = labHash($cat);
        $_SESSION['sales_orders'][$bundleId] = array(
            'line_no' => $bundleId,
            'itemcode' => $cat,
            'barcode' => $bd['barcode'],
            'stockname' => $bundleName,
            'sampleid' => '',
            'partsperunit' => 1,
            'averagestock' => $bd['averagestock'] ?? 0,
            'quantity' => 1,
            'pricevalue' => $bundleBase,
            'tat' => labTatFor($cat),
            'discountpercent' => 0,
            'stdGroup' => $cat,
            'isBundle' => true,
            'stdName' => $bundleName
        );
        foreach ($active as $t) {
            $tid = labHash($t['code']);
            if (isset($_SESSION['sales_orders'][$tid])) {
                continue;
            }
            $td = labStockDetails($t['code']);
            $_SESSION['sales_orders'][$tid] = array(
                'line_no' => $tid,
                'itemcode' => $t['code'],
                'barcode' => $td['barcode'],
                'stockname' => $t['name'],
                'sampleid' => '',
                'partsperunit' => 1,
                'averagestock' => $td['averagestock'] ?? 0,
                'quantity' => 1,
                'pricevalue' => 0,
                'tat' => labTatFor($t['code']),
                'discountpercent' => 0,
                'stdGroup' => $cat,
                'isBundle' => false,
                'stdName' => $bundleName
            );
        }
        labApplyStandardPricing($customerid);
        $addWarnings = [];
        if (empty($active)) {
            $addWarnings[] = 'Standard ' . $cat . ' has no mapped tests in the discount table - bundle row added alone.';
        }
        labCartResponse($customerid, $req['DiscountPercent'] ?? 0, $addWarnings);
        break;

    case 'set_pricing_mode':
        labSessionCheck();
        $customerid = trim($req['CustomerID'] ?? '');
        $cat = trim($req['cat'] ?? $req['group'] ?? '');
        $mode = labModeClean($req['mode'] ?? 'auto');
        if ($cat === '') {
            labFail('No group specified.', 'NO_GROUP');
        }
        $found = false;
        foreach ($_SESSION['sales_orders'] as $row) {
            if (trim($row['stdGroup'] ?? '') === $cat) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            labFail('Group not found in this order: ' . $cat, 'GROUP_NOT_FOUND');
        }
        if (!isset($_SESSION['lab_order_modes']) || !is_array($_SESSION['lab_order_modes'])) {
            $_SESSION['lab_order_modes'] = array();
        }
        $_SESSION['lab_order_modes'][$cat] = $mode;
        labApplyStandardPricing($customerid);
        labCartResponse($customerid, $req['DiscountPercent'] ?? 0);
        break;

    case 'remove_group':
        labSessionCheck();
        $cat = trim($req['cat'] ?? $req['group'] ?? '');
        if ($cat === '') {
            labFail('No group specified.', 'NO_GROUP');
        }
        $removed = 0;
        foreach ($_SESSION['sales_orders'] as $key => $row) {
            if (trim($row['stdGroup'] ?? '') === $cat || trim($row['itemcode'] ?? '') === $cat) {
                unset($_SESSION['sales_orders'][$key]);
                $removed++;
            }
        }
        if ($removed === 0) {
            labFail('Group not found: ' . $cat, 'GROUP_NOT_FOUND');
        }
        unset($_SESSION['lab_order_modes'][$cat]);
        labCartResponse(trim($req['CustomerID'] ?? ''), $req['DiscountPercent'] ?? 0);
        break;

    case 'customers':
        labSessionCheck();
        $q = trim($req['q'] ?? '');
        $results = [];
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $ResultIndex = labDB("SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
                                  FROM debtors
                                  WHERE itemcode LIKE '%$qEsc%' OR customer LIKE '%$qEsc%'
                                  ORDER BY customer LIMIT 25");
        } else {
            $ResultIndex = labDB("SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
                                  FROM debtors ORDER BY customer LIMIT 25");
        }
        if ($ResultIndex === false) {
            labFail('Customer search failed. See the error log for details.', 'DB_SEARCH_CUSTOMERS');
        }
        while ($row = DB_fetch_array($ResultIndex)) {
            $code = trim($row['itemcode']);
            $results[] = [
                'value' => $code,
                'label' => ($row['customer'] ?? $code),
                'currency' => trim((string)($row['curr_cod'] ?? '')),
                'salesman' => trim((string)($row['salesman'] ?? ''))
            ];
        }
        echo json_encode(['status' => 'ok', 'results' => $results]);
        break;

    case 'cart_list':
        labSessionCheck();
        labCartResponse(trim($req['CustomerID'] ?? ''), $req['DiscountPercent'] ?? 0);
        break;

    case 'cart_add':
        labSessionCheck();
        $itemcode = trim($req['itemcode'] ?? '');
        if ($itemcode === '') {
            labFail('No item code provided.', 'NO_ITEM');
        }
        $stock = labStockDetails($itemcode);
        if ($stock['itemcode'] === '') {
            labFail('Stock item not found: ' . $itemcode, 'ITEM_NOT_FOUND');
        }
        $qty = (float)($req['quantity'] ?? 1);
        if ($qty <= 0) {
            labFail('Quantity must be greater than zero.', 'BAD_QTY');
        }
        $id = labHash($itemcode);
        $customerid = trim($req['CustomerID'] ?? '');
        $_SESSION['sales_orders'][$id] = array(
            'line_no' => $id,
            'itemcode' => $stock['itemcode'],
            'barcode' => $stock['barcode'],
            'stockname' => $stock['stockname'],
            'sampleid' => trim((string)($req['sampleid'] ?? '')),
            'partsperunit' => 1,
            'averagestock' => $stock['averagestock'] ?? 0,
            'quantity' => $qty,
            'pricevalue' => (float)($req['price'] ?? 0),
            'tat' => (int)($req['tat'] ?? labTatFor($itemcode)),
            'discountpercent' => (float)($req['disc'] ?? 0),
            'stdGroup' => '',
            'isBundle' => false,
            'stdName' => ''
        );
        labApplyStandardPricing($customerid);
        labCartResponse($customerid, $req['DiscountPercent'] ?? 0);
        break;

    case 'cart_update':
        labSessionCheck();
        $id = trim((string)($req['id'] ?? ''));
        if ($id === '' || !isset($_SESSION['sales_orders'][$id])) {
            labFail('Cart line not found. Reload the page and try again.', 'LINE_NOT_FOUND');
        }
        $field = trim((string)($req['field'] ?? ''));
        $value = $req['value'] ?? null;
        switch ($field) {
            case 'quantity':
                $qty = (float)$value;
                if ($qty <= 0) {
                    labFail('Quantity must be greater than zero. Use delete to remove the line.', 'BAD_QTY');
                }
                $_SESSION['sales_orders'][$id]['quantity'] = $qty;
                labApplyStandardPricing(trim($req['CustomerID'] ?? ''));
                break;
            case 'price':
                $_SESSION['sales_orders'][$id]['pricevalue'] = (float)$value;
                break;
            case 'disc':
                $disc = (float)$value;
                if ($disc < 0 || $disc > 100) {
                    labFail('Discount must be between 0 and 100.', 'BAD_DISC');
                }
                $_SESSION['sales_orders'][$id]['discountpercent'] = $disc;
                break;
            case 'tat':
                $_SESSION['sales_orders'][$id]['tat'] = (int)$value;
                break;
            case 'sampleid':
                $_SESSION['sales_orders'][$id]['sampleid'] = trim((string)$value);
                break;
            default:
                labFail('Unknown cart field: ' . $field, 'BAD_FIELD');
        }
        labCartResponse(trim($req['CustomerID'] ?? ''), $req['DiscountPercent'] ?? 0);
        break;

    case 'cart_delete':
        labSessionCheck();
        $id = trim((string)($req['id'] ?? ''));
        if ($id === '' || !isset($_SESSION['sales_orders'][$id])) {
            labFail('Cart line not found. It may already be deleted.', 'LINE_NOT_FOUND');
        }
        unset($_SESSION['sales_orders'][$id]);
        // Deleting a bundle must release its tests back to their own prices.
        labApplyStandardPricing(trim($req['CustomerID'] ?? ''));
        labCartResponse(trim($req['CustomerID'] ?? ''), $req['DiscountPercent'] ?? 0);
        break;

    case 'new':
        labSessionCheck();
        $_SESSION['sales_orders'] = array();
        $_SESSION['lab_order_modes'] = array();
        $_SESSION['sales_orders_category'] = '';
        echo json_encode(['status' => 'ok', 'message' => 'Cart cleared.']);
        break;

    case 'docno':
        labSessionCheck();
        echo json_encode(['status' => 'ok', 'data' => GetTempNextNo(1)]);
        break;

    case 'customer':
        labSessionCheck();
        $customerid = trim($req['customerid'] ?? '');
        $flags = labCustomerFlags($customerid);
        if ($customerid !== '' && $flags['customerposting'] === '' && $flags['currency'] === '') {
            labFail('Customer not found: ' . $customerid, 'CUSTOMER_NOT_FOUND');
        }
        echo json_encode([
            'status' => 'ok',
            'vatinclusive' => $flags['vatinclusive'],
            'istaxed' => $flags['istaxed'],
            'currency' => $flags['currency'],
            'banks' => labBanks($flags['currency'])
        ]);
        break;

    case 'load_order':
        labSessionCheck();
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            labFail('No order number provided.', 'NO_DOCNO');
        }
        $header = labFillCart($docno, 1);
        $list = labCartList($header['customercode'], $header['DiscountPercent']);
        echo json_encode([
            'status' => 'ok',
            'message' => 'Order loaded: ' . $header['documentno'],
            'header' => $header,
            'banks' => labBanks($header['currencycode']),
            'rows' => $list['rows'],
            'totals' => $list['totals']
        ]);
        break;

    case 'load_quote':
        labSessionCheck();
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            labFail('No quote number provided.', 'NO_DOCNO');
        }
        $header = labFillCart($docno, 54);
        $list = labCartList($header['customercode'], $header['DiscountPercent']);
        echo json_encode([
            'status' => 'ok',
            'message' => 'Sales Order loaded from Quote: ' . trim($docno),
            'header' => $header,
            'banks' => labBanks($header['currencycode']),
            'rows' => $list['rows'],
            'totals' => $list['totals']
        ]);
        break;

    case 'save':
        labSessionCheck();
        $customerid = trim($req['CustomerID'] ?? '');
        if ($customerid === '') {
            labFail('Select a customer before saving.', 'NO_CUSTOMER');
        }
        if (empty($_SESSION['sales_orders'])) {
            labFail('No lines on this order. Add items before saving.', 'NO_LINES');
        }
        $datestr = trim($req['date'] ?? '');
        $testdate = labPeriodGuard($datestr);
        $flags = labCustomerFlags($customerid);
        if ($flags['customerposting'] === '') {
            labFail('Customer not found: ' . $customerid, 'CUSTOMER_NOT_FOUND');
        }
        $customerposting = $flags['customerposting'];
        $VATinclusive = $flags['vatinclusive'] ? 1 : 0;
        if (($_SESSION['ManualNumber'] ?? 0) == 0) {
            $documentno = GetNextTransNo(1, $db);
        } else {
            $documentno = trim($req['documentno'] ?? '');
            if ($documentno === '') {
                labFail('No document number. Reload the page to get one.', 'NO_DOCNO');
            }
        }
        $_SESSION['CompleteDocument'] = $documentno;
        $PeriodNo = GetPeriod($datestr, $db, false);
        $DATE = FormatDateForSQL($datestr);
        $quoteBank = trim((string)($req['Bank_Code'] ?? ''));
        $headerDiscSave = (float)($req['DiscountPercent'] ?? 0);

        // Apply the 80/20 rule, then recompute amounts over the session cart
        // with the submitted header values so the save can never store
        // stale figures.
        labApplyStandardPricing($customerid);
        labCartList($customerid, $headerDiscSave);

        $selectedImages = (string)($req['selectedImages'] ?? '');
        $imagesArray2 = json_encode($selectedImages !== '' ? json_decode(html_entity_decode($selectedImages, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : null);

        // Per-standard pricing overrides are session state; persist them with
        // the header so a reload restores the same Standard/Test choices.
        $modesSave = array();
        foreach ($_SESSION['lab_order_modes'] ?? array() as $mCat => $mMode) {
            $mCat = trim((string)$mCat);
            $mMode = labModeClean($mMode);
            if ($mCat !== '' && $mMode !== 'auto') {
                $modesSave[$mCat] = $mMode;
            }
        }
        $pmEsc = mysqli_real_escape_string($db, empty($modesSave) ? '{}' : json_encode($modesSave));

        $transstart = DB_Txn_Begin($db);
        $sql = array();
        $hdrArgs = array(
            mysqli_real_escape_string($db, $documentno),
            $DATE, $DATE, $DATE,
            mysqli_real_escape_string($db, $customerid),
            mysqli_real_escape_string($db, trim((string)($req['CustomerName'] ?? ''))),
            mysqli_real_escape_string($db, trim((string)($req['reference'] ?? ''))),
            mysqli_real_escape_string($db, trim((string)($req['coa_documentno'] ?? ''))),
            mysqli_real_escape_string($db, $customerposting),
            mysqli_real_escape_string($db, trim((string)($req['currencycode'] ?? ''))),
            mysqli_real_escape_string($db, trim((string)($req['salespersoncode'] ?? ''))),
            mysqli_real_escape_string($db, ($_SESSION['UserID'] ?? '')),
            $PeriodNo,
            $VATinclusive,
            mysqli_real_escape_string($db, $quoteBank),
            mysqli_real_escape_string($db, $imagesArray2 ?: '[]'),
            (float)($req['DiscountPercent'] ?? 0),
        );
        $colRes = labDB("SHOW COLUMNS FROM SalesHeader LIKE 'pricingmode'");
        $hasPricingMode = ($colRes !== false && DB_num_rows($colRes) > 0);
        if ($hasPricingMode) {
            $sql[] = sprintf(
                "INSERT INTO SalesHeader (
                    documenttype, documentno, docdate, oderdate, duedate,
                    customercode, customername, yourreference, coa_documentno,
                    postinggroup, currencycode, salespersoncode, status,
                    userid, period, vatinclusive, locationcode, shipping,
                    packagescharge, picture, QtyDiscount, pricingmode
                ) VALUES (
                    1, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', 1, '%s', '%s', '%s', '%s', 0, 0, '%s', %f, '%s'
                )",
                $hdrArgs[0], $hdrArgs[1], $hdrArgs[2], $hdrArgs[3], $hdrArgs[4],
                $hdrArgs[5], $hdrArgs[6], $hdrArgs[7], $hdrArgs[8], $hdrArgs[9],
                $hdrArgs[10], $hdrArgs[11], $hdrArgs[12], $hdrArgs[13], $hdrArgs[14],
                $hdrArgs[15], $pmEsc
            );
        } else {
            // Pre-migration fallback: column not yet added.
            labLog('SalesHeader.pricingmode column missing - saving without per-standard pricing modes. Run sql/migration_add_pricingmode_column.sql');
            $sql[] = sprintf(
                "INSERT INTO SalesHeader (
                    documenttype, documentno, docdate, oderdate, duedate,
                    customercode, customername, yourreference, coa_documentno,
                    postinggroup, currencycode, salespersoncode, status,
                    userid, period, vatinclusive, locationcode, shipping,
                    packagescharge, picture, QtyDiscount
                ) VALUES (
                    1, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', 1, '%s', '%s', '%s', '%s', 0, 0, '%s', %f
                )",
                $hdrArgs[0], $hdrArgs[1], $hdrArgs[2], $hdrArgs[3], $hdrArgs[4],
                $hdrArgs[5], $hdrArgs[6], $hdrArgs[7], $hdrArgs[8], $hdrArgs[9],
                $hdrArgs[10], $hdrArgs[11], $hdrArgs[12], $hdrArgs[13], $hdrArgs[14],
                $hdrArgs[15]
            );
        }

        $skipped = array();
        $skippedZero = 0;
        foreach ($_SESSION['sales_orders'] as $line_No => $rows) {
            // Persist every line with a quantity, even at zero amount: under
            // 80/20 the free lines (bundle or tests) are part of the order.
            // (Invoices keep their own zero-amount skip.)
            if (trim($rows['itemcode'] ?? '') === '' || (float)($rows['quantity'] ?? 0) <= 0) {
                $skippedZero++;
                continue;
            }
            $stockcode = trim($rows['itemcode']);
            $qty = (float)$rows['quantity'];
            $stockSql = "SELECT sm.barcode, sm.itemcode, sm.descrip AS stockname, sm.partperunit,
                                vc.vat, sm.isstock_3
                         FROM stockmaster sm
                         LEFT JOIN inventorypostinggroup ipg ON sm.postinggroup = ipg.code
                         LEFT JOIN vatcategory vc ON ipg.vatcategory = vc.vatc
                         WHERE sm.itemcode = '" . mysqli_real_escape_string($db, $stockcode) . "'";
            $ResultIndex = labDB($stockSql);
            if ($ResultIndex === false) {
                DB_Txn_Rollback($db);
                labFail('Could not read stock item ' . $stockcode . '. See the error log.', 'DB_STOCK');
            }
            $stkmaster = DB_fetch_row($ResultIndex);
            if (!$stkmaster) {
                $skipped[] = $stockcode;
                continue;
            }
            $stockname = $stkmaster[2];
            $vatrate = (float)$stkmaster[4];
            $gotoinvoice = ($stkmaster[5] == 1) ? 1 : 0;
            $PartPerUnit = $stkmaster[3];
            $salesprice = (float)($rows['salesprice'] ?? 0);
            $grossamount = (float)($rows['grossamount'] ?? 0);
            $vatamount = (float)($rows['vatamount'] ?? 0);
            $tat = $rows['tat'] ?? null;
            $lineDiscountPercent = (float)($rows['discountpercent'] ?? 0);
            $PriceInPricelist = (float)($rows['PriceInPricelist'] ?? 0);
            $sampleid = $rows['sampleid'] ?? null;
            $columns = [
                'documenttype', 'docdate', 'documentno', 'code', 'description', 'sampleID',
                'Quantity', 'UnitPrice', 'vatamount', 'invoiceamount', 'vatrate', 'inclusive',
                'containerprice', 'containersunits', 'totalchargedcontainers', 'containercode',
                'PartPerUnit', 'TAT', 'LineDiscountPercent', 'PriceInPricelist'
            ];
            $values = [
                1, labSqlVal($DATE), labSqlVal($documentno), labSqlVal($stockcode),
                labSqlVal($stockname), labSqlVal($sampleid ?? ''), labSqlVal($qty),
                labSqlVal($salesprice), labSqlVal($vatamount), labSqlVal($grossamount),
                labSqlVal($vatrate), labSqlVal($VATinclusive), labSqlVal(0),
                labSqlVal(0), labSqlVal(0), labSqlVal(''),
                labSqlVal($PartPerUnit), ($tat !== null && trim((string)$tat) !== '' ? (int)$tat : "NULL"),
                labSqlVal($lineDiscountPercent), labSqlVal($PriceInPricelist)
            ];
            if ($gotoinvoice === 1) {
                $columns[] = 'Qunatity_delivered';
                $values[] = labSqlVal($qty);
            }
            $sql[] = "INSERT INTO SalesLine (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ")";
        }

        $stmtIdx = 0;
        $stmtCount = count($sql);
        foreach ($sql as $value) {
            $stmtIdx++;
            $ResultIndex = labDB($value);
            if ($ResultIndex === false || DB_error_no($db) > 0) {
                break;
            }
        }
        if (DB_error_no($db) > 0) {
            $errNo = DB_error_no($db);
            $errMsg = function_exists('DB_error_msg') ? DB_error_msg($db) : $db->error;
            DB_Txn_Rollback($db);
            labLog('save failed: docno=' . $documentno . ' stmt=' . $stmtIdx . '/' . $stmtCount . ' errno=' . $errNo . ' error=' . $errMsg);
            labFail('Failed to save sales order ' . $documentno . ' (statement ' . $stmtIdx . ' of ' . $stmtCount . ', DB ' . $errNo . '): ' . $errMsg, 'DB_SAVE');
        }
        DB_Txn_Commit($db);
        $_SESSION['sales_orders'] = array();
        $_SESSION['sales_orders_category'] = '';
        $resp = [
            'status' => 'saved',
            'documentno' => $documentno,
            'message' => 'Sales order :' . $documentno . ' has been created',
            'print_url' => 'PDFPrintSalesOrder.php?No=' . rawurlencode($documentno)
        ];
        if (!empty($skipped)) {
            $resp['warnings'] = ['Skipped unknown items (not saved): ' . implode(', ', $skipped)];
        }
        if ($skippedZero > 0) {
            $resp['warnings'][] = $skippedZero . ' line(s) with no quantity skipped (not saved).';
        }
        echo json_encode($resp);
        break;

    case 'confirm':
        labSessionCheck();
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            labFail('No order number provided.', 'NO_DOCNO');
        }
        $docnoEsc = mysqli_real_escape_string($db, $docno);
        $ResultIndex = labDB("UPDATE SalesHeader SET status=1, released=1
                              WHERE documenttype=1 AND documentno='$docnoEsc'");
        if ($ResultIndex === false) {
            labFail('Could not confirm the order. See the error log.', 'DB_CONFIRM');
        }
        if ($db->affected_rows <= 0) {
            labFail('Order not found (it may have been deleted): ' . $docno, 'NOT_FOUND');
        }
        unset($_SESSION['CompleteDocument']);
        echo json_encode([
            'status' => 'ok',
            'documentno' => $docno,
            'message' => 'Sales Order ' . $docno . ' confirmed',
            'print_url' => 'PDFPrintSalesOrder.php?No=' . rawurlencode($docno)
        ]);
        break;

    case 'delete_order':
        labSessionCheck();
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            labFail('No order number provided.', 'NO_DOCNO');
        }
        $docnoEsc = mysqli_real_escape_string($db, $docno);
        $chk = labDB("SELECT released FROM SalesHeader WHERE documenttype=1 AND documentno='$docnoEsc' LIMIT 1");
        if ($chk === false) {
            labFail('Could not check the order status. See the error log.', 'DB_DELETE_CHECK');
        }
        $chkRow = DB_fetch_row($chk);
        if (!$chkRow) {
            labFail('Order not found (it may have been deleted): ' . $docno, 'NOT_FOUND');
        }
        if ((int)($chkRow[0] ?? 0) === 1) {
            labFail('Order ' . $docno . ' is already released and cannot be deleted. Raise a credit note instead.', 'RELEASED');
        }
        $transstart = DB_Txn_Begin($db);
        $r1 = labDB("DELETE FROM SalesLine WHERE documentno='$docnoEsc' AND documenttype=1");
        $r2 = labDB("DELETE FROM SalesHeader WHERE documentno='$docnoEsc' AND documenttype=1");
        if ($r1 === false || $r2 === false || DB_error_no($db) > 0) {
            $errNo = DB_error_no($db);
            $errMsg = function_exists('DB_error_msg') ? DB_error_msg($db) : $db->error;
            DB_Txn_Rollback($db);
            labLog('delete failed: docno=' . $docno . ' errno=' . $errNo . ' error=' . $errMsg);
            labFail('Failed to delete order ' . $docno . ' (DB ' . $errNo . '): ' . $errMsg, 'DB_DELETE');
        }
        DB_Txn_Commit($db);
        unset($_SESSION['CompleteDocument']);
        echo json_encode(['status' => 'ok', 'message' => 'Order :' . $docno . ' has been Deleted.']);
        break;

    default:
        labFail('Unknown action: ' . $action, 'BAD_ACTION');
        break;
}
