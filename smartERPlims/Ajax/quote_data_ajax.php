<?php
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

function quoteLog($message) {
    @file_put_contents(__DIR__ . '/../quotes/quote_ajax_errors.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function ajaxDB_query($sql) {
    global $db, $action;
    $result = DB_query($sql, $db, '', '', false, false);
    if ($result === false) {
        quoteLog('query failed: action=' . $action . ' errno=' . $db->errno . ' error=' . $db->error
            . ' | ' . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 1500));
    }
    return $result;
}

$req = json_decode(file_get_contents('php://input'), true);
$req = is_array($req) ? $req + $_REQUEST : $_REQUEST;
$action = $req['action'] ?? '';

function quoteFail($message) {
    quoteLog('replied error: ' . $message);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function quoteStockDetails($itemcode) {
    global $db;

    $empty = ['barcode' => '', 'itemcode' => '', 'stockname' => '', 'si' => '', 'vat' => 0, 'units' => '', 'sellingprice' => 0];
    $SQL = "SELECT stockmaster.barcode, stockmaster.itemcode, stockmaster.descrip,
                   unit.descrip AS si, vatcategory.vat, stockmaster.units, stockmaster.sellingprice
            FROM stockmaster
            LEFT JOIN unit ON stockmaster.units = unit.code
            LEFT JOIN inventorypostinggroup ON stockmaster.postinggroup = inventorypostinggroup.code
            LEFT JOIN vatcategory ON inventorypostinggroup.vatcategory = vatcategory.vatc
            WHERE stockmaster.itemcode='" . mysqli_real_escape_string($db, $itemcode) . "'";
    $ResultIndex = ajaxDB_query($SQL);
    if ($row = DB_fetch_row($ResultIndex)) {
        return [
            'barcode' => $row[0],
            'itemcode' => trim($row[1]),
            'stockname' => $row[2],
            'si' => $row[3],
            'vat' => (int)($row[4] ?? 0),
            'units' => trim(($row[5] ?? 'PCS') !== '' ? $row[5] : 'PCS'),
            'sellingprice' => (float)($row[6] ?? 0)
        ];
    }
    return $empty;
}

function quoteTatFor($stockCode) {
    global $db;

    if (empty($stockCode)) {
        return 0;
    }
    $SQL = sprintf("SELECT tat FROM PriceList WHERE stockcode='%s' LIMIT 1", mysqli_real_escape_string($db, $stockCode));
    $ResultIndex = ajaxDB_query($SQL);
    if ($row = DB_fetch_row($ResultIndex)) {
        return (int)($row[0] ?? 0);
    }
    return 0;
}

function SelectTestPriceListToUse($stockid, $quantity = 1, $customerid = '') {
    global $db;

    $price = 0;
    $Dprice = 0;
    if (mb_strlen($customerid) > 1) {
        $SQL = sprintf("SELECT price FROM PriceList WHERE stockcode='%s' AND approved=1 AND quantity=%f AND customerCode='%s'",
            mysqli_real_escape_string($db, $stockid), $quantity, mysqli_real_escape_string($db, $customerid));
        $ResultIndex = ajaxDB_query($SQL);
        if ($row = DB_fetch_row($ResultIndex)) {
            $price = $row[0];
        }
    }
    $SQL = sprintf("SELECT price FROM PriceList WHERE stockcode='%s' AND quantity=%f AND (customerCode IS NULL OR customerCode='')",
        mysqli_real_escape_string($db, $stockid), $quantity);
    $ResultIndex = ajaxDB_query($SQL);
    if ($row = DB_fetch_row($ResultIndex)) {
        $Dprice = $row[0];
    }
    return $price > 0 ? (float)$price : (float)$Dprice;
}

function quotePriceFor($code, $customerid = '') {
    $price = (float)SelectTestPriceListToUse($code, 1, $customerid);
    if ($price <= 0) {
        $details = quoteStockDetails($code);
        if ($details['sellingprice'] > 0) {
            $price = $details['sellingprice'];
        }
    }
    return $price;
}

function quoteCustomerFlags($customerid) {
    global $db;

    $flags = ['vatinclusive' => false, 'istaxed' => false];
    if (empty($customerid)) {
        return $flags;
    }
    $SQL = "SELECT debtors.curr_cod, debtors.customer, postinggroups.VATinclusive, postinggroups.IsTaxed
            FROM debtors LEFT JOIN postinggroups ON code = customerposting
            WHERE debtors.itemcode='" . mysqli_real_escape_string($db, $customerid) . "' LIMIT 1";
    $ResultIndex = ajaxDB_query($SQL);
    if ($row = DB_fetch_row($ResultIndex)) {
        $flags['vatinclusive'] = ($row[2] == 1 || strtolower((string)$row[2]) === 'true');
        $flags['istaxed'] = ($row[3] == 1 || strtolower((string)$row[3]) === 'true');
        $flags['currency'] = trim((string)($row[0] ?? ''));
        $flags['name'] = trim((string)($row[1] ?? ''));
    }
    return $flags;
}

function quotePeriodFor($DATE) {
    global $db;

    if ($DATE !== null && $DATE !== '') {
        $escaped = mysqli_real_escape_string($db, $DATE);
        $ResultIndex = ajaxDB_query("SELECT periodno FROM periods WHERE perioddate <= '$escaped' AND lastdate_in_period >= '$escaped' LIMIT 1");
        if ($row = DB_fetch_row($ResultIndex)) {
            return (int)$row[0];
        }
    }
    $ResultIndex = ajaxDB_query("SELECT IFNULL(MAX(periodno),0) FROM periods");
    $row = DB_fetch_row($ResultIndex);
    return $row ? (int)$row[0] : 0;
}

function quoteUnitDescrip($unitCode) {
    global $db;

    if ($unitCode === null || trim((string)$unitCode) === '') {
        return '';
    }
    $ResultIndex = ajaxDB_query("SELECT descrip FROM unit WHERE code='" . mysqli_real_escape_string($db, $unitCode) . "' LIMIT 1");
    if ($row = DB_fetch_row($ResultIndex)) {
        return trim((string)$row[0]);
    }
    return trim((string)$unitCode);
}

// Sanitize a group pricing-mode map (cat -> auto|standard|pertest).
function quoteCleanModes($modes) {
    $clean = [];
    if (is_array($modes)) {
        foreach ($modes as $k => $v) {
            $k = trim((string)$k);
            if ($k === '' || strlen($k) > 20) {
                continue;
            }
            if ($v === 'standard' || $v === 'pertest' || $v === 'auto') {
                $clean[$k] = $v;
            }
        }
    }
    return $clean;
}

// Shared line-amount math (base - line disc, VAT in/ex, header disc).
// Returns [net, vat, gross]. Single source of truth for reprice + save.
function quoteLineAmounts($price, $qty, $ppu, $lineDisc, $vatrate, $VATinclusive, $headerDisc) {
    $qty = (float)$qty;
    if ($qty <= 0) {
        return [0, 0, 0];
    }
    $price = (float)$price;
    $ppu = (float)$ppu;
    if ($ppu <= 0) {
        $ppu = 1;
    }
    $lineDisc = (float)$lineDisc;
    $vatrate = (float)$vatrate;
    $base = $price * $qty * $ppu;
    $taxable = $base - ($base * ($lineDisc / 100));
    if ($VATinclusive) {
        $net = $taxable / (($vatrate + 100) / 100);
        $vat = $taxable - $net;
        $gross = round($taxable * 100) / 100;
    } else {
        $vat = $taxable * ($vatrate / 100);
        $gross = round(($taxable + $vat) * 100) / 100;
        $net = $taxable;
    }
    $headerDisc = (float)$headerDisc;
    if ($headerDisc > 0) {
        $factor = (100 - $headerDisc) / 100;
        $net *= $factor;
        $vat *= $factor;
        $gross *= $factor;
    }
    return [$net, $vat, $gross];
}

function quoteIsTsBundle($code) {
    return preg_match('/^TS\d{4}$/', trim((string)$code)) === 1;
}

function quoteActiveTestsFor($cat, $customerid) {
    global $db;
    $tests = [];
    $catEsc = mysqli_real_escape_string($db, $cat);
    $ResultIndex = ajaxDB_query("SELECT dt.itemcode, dt.discount_percent, sm.descrip, sm.units
                                 FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode
                                 WHERE dt.categoryid='$catEsc'
                                   AND dt.is_active = 1 AND (sm.inactive = 0 OR sm.inactive IS NULL)
                                 ORDER BY sm.descrip");
    if ($ResultIndex) {
        while ($row = DB_fetch_array($ResultIndex)) {
            $tcode = trim($row['itemcode']);
            $td = quoteStockDetails($tcode);
            $tests[] = [
                'code' => $tcode,
                'name' => $row['descrip'],
                'units' => $td['units'],
                'vat' => $td['vat'],
                'price' => quotePriceFor($tcode, $customerid),
                'tat' => quoteTatFor($tcode),
                'disc' => (float)($row['discount_percent'] ?? 0)
            ];
        }
    }
    return $tests;
}

// Shared group-pricing rule (80/20 auto + forced standard/pertest).
// $lines: list of ['code','qty','group','price','disc'] (price/disc = current grid values).
// Returns [index => ['price','disc']] overrides for rows the rule reprices;
// rows it leaves alone keep their sent values (manual edits survive).
function quoteGroupRule($lines, $modes, $customerid) {
    $modes = quoteCleanModes($modes);
    $inCart = [];
    foreach ($lines as $l) {
        $c = trim((string)($l['code'] ?? ''));
        if ($c !== '' && (float)($l['qty'] ?? 0) > 0) {
            $inCart[$c] = true;
        }
    }
    $claimed = [];
    foreach (array_keys($inCart) as $c) {
        if (quoteIsTsBundle($c)) {
            $claimed[$c] = true;
        }
    }
    $groups = [];
    foreach ($lines as $l) {
        $g = trim((string)($l['group'] ?? ''));
        if ($g !== '') {
            $groups[$g] = true;
        }
    }
    $activeByCat = [];
    foreach (array_keys($groups) as $g) {
        if (isset($claimed[$g])) {
            continue;
        }
        $act = quoteActiveTestsFor($g, $customerid);
        $activeByCat[$g] = $act;
        foreach ($act as $t) {
            if (isset($inCart[$t['code']])) {
                $claimed[$g] = true;
                break;
            }
        }
    }
    $byCode = [];
    foreach ($lines as $i => $l) {
        $byCode[trim((string)($l['code'] ?? ''))] = $i;
    }
    $overrides = [];
    foreach (array_keys($claimed) as $cat) {
        if (quoteIsTsBundle($cat)) {
            $bundlePrice = quotePriceFor($cat, $customerid);
            $active = quoteActiveTestsFor($cat, $customerid);
        } else {
            $bundlePrice = 0;
            $active = $activeByCat[$cat] ?? quoteActiveTestsFor($cat, $customerid);
        }
        if (empty($active)) {
            continue;
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
            if (isset($byCode[$t['code']])) {
                if ((float)($lines[$byCode[$t['code']]]['disc'] ?? 0) !== 100.0) {
                    $testDisc100 = false;
                    break;
                }
            }
        }
        $savedStandard = ($included === 0 && $total > 0 && $testDisc100);
        $mode = $modes[$cat] ?? 'auto';
        if ($mode === 'standard') {
            $useStandard = $bundleInCart;
        } elseif ($mode === 'pertest') {
            $useStandard = false;
        } else {
            $useStandard = $bundleInCart && $bundlePrice > 0 &&
                ($included > 0 && ($included / $total) >= 0.8 ? true : $savedStandard);
        }
        if ($bundleInCart && isset($byCode[$cat])) {
            $wantDisc = $useStandard ? 0 : 100;
            if ((float)($lines[$byCode[$cat]]['disc'] ?? 0) !== (float)$wantDisc) {
                $overrides[$byCode[$cat]] = array_merge($overrides[$byCode[$cat]] ?? [], ['disc' => $wantDisc]);
            }
        }
        foreach ($active as $t) {
            if (!isset($byCode[$t['code']])) {
                continue;
            }
            $i = $byCode[$t['code']];
            $wantDisc = $useStandard ? 100 : $t['disc'];
            $o = [];
            if ((float)($lines[$i]['price'] ?? 0) !== (float)$t['price']) {
                $o['price'] = $t['price'];
            }
            if ((float)($lines[$i]['disc'] ?? 0) !== (float)$wantDisc) {
                $o['disc'] = $wantDisc;
            }
            if (!empty($o)) {
                $overrides[$i] = array_merge($overrides[$i] ?? [], $o);
            }
        }
    }
    return $overrides;
}

switch ($action) {

    case 'docno':
        echo json_encode(['status' => 'ok', 'data' => GetTempNextNo(54)]);
        break;

    case 'customer':
        $customerid = trim($req['customerid'] ?? '');
        $flags = quoteCustomerFlags($customerid);
        if ($customerid !== '') {
            $_SESSION['SelectedCustomer'] = $customerid;
        }
        echo json_encode(array_merge(['status' => 'ok'], $flags));
        break;

    case 'meta':
        $ts = [];
        $ResultIndex = ajaxDB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid LIKE 'TS%' ORDER BY categorydescription");
        while ($row = DB_fetch_array($ResultIndex)) {
            $ts[] = ['value' => trim($row['categoryid']), 'label' => ($row['categorydescription'] ?? trim($row['categoryid']))];
        }
        $other = [];
        $ResultIndex = ajaxDB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid NOT LIKE 'TS%' ORDER BY categorydescription");
        while ($row = DB_fetch_array($ResultIndex)) {
            $other[] = ['value' => trim($row['categoryid']), 'label' => ($row['categorydescription'] ?? trim($row['categoryid']))];
        }
        $units = [];
        $ResultIndex = ajaxDB_query("SELECT code, descrip FROM unit ORDER BY descrip");
        while ($row = DB_fetch_array($ResultIndex)) {
            $units[] = ['value' => trim($row['code']), 'label' => $row['descrip']];
        }
        $salesreps = [];
        $ResultIndex = ajaxDB_query("SELECT code, salesman FROM salesrepsinfo WHERE inactive IS NULL OR inactive = 0 ORDER BY salesman");
        while ($row = DB_fetch_array($ResultIndex)) {
            $salesreps[] = ['value' => trim($row['code']), 'label' => $row['salesman']];
        }
        $stock = [];
        $ResultIndex = ajaxDB_query("SELECT itemcode, barcode, descrip, units FROM stockmaster WHERE (inactive = 0 OR inactive IS NULL) AND isstock_1 = 1 ORDER BY descrip");
        while ($row = DB_fetch_array($ResultIndex)) {
            $stock[] = [
                'itemcode' => trim($row['itemcode']),
                'barcode' => trim($row['barcode']),
                'description' => $row['descrip'],
                'units' => trim(($row['units'] ?? 'PCS') !== '' ? $row['units'] : 'PCS')
            ];
        }
        echo json_encode(['status' => 'ok', 'categories' => $ts, 'othercategories' => $other, 'units' => $units, 'salesreps' => $salesreps, 'stock' => $stock]);
        break;

    case 'item':
        $itemcode = trim($req['itemcode'] ?? '');
        $customerid = trim($req['customerid'] ?? '');
        if ($itemcode === '') {
            quoteFail('No item code provided.');
        }
        $sd = quoteStockDetails($itemcode);
        if ($sd['itemcode'] === '') {
            quoteFail('Stock item not found.');
        }
        echo json_encode([
            'status' => 'ok',
            'code' => $sd['itemcode'],
            'name' => $sd['stockname'],
            'barcode' => $sd['barcode'],
            'units' => $sd['units'],
            'vat' => $sd['vat'],
            'price' => (float)SelectTestPriceListToUse($itemcode, 1, $customerid),
            'sellingprice' => $sd['sellingprice'],
            'tat' => quoteTatFor($sd['itemcode'])
        ]);
        break;

    case 'search':
        $q = trim($req['q'] ?? '');
        $results = [];
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $ResultIndex = ajaxDB_query("SELECT categoryid, categorydescription FROM stockcategory
                                         WHERE categoryid LIKE 'TS%'
                                           AND (categoryid LIKE '%$qEsc%' OR categorydescription LIKE '%$qEsc%')
                                         ORDER BY categorydescription LIMIT 10");
            while ($row = DB_fetch_array($ResultIndex)) {
                $code = trim($row['categoryid']);
                $results[] = [
                    'value' => $code,
                    'label' => ($row['categorydescription'] ?? $code),
                    'name' => ($row['categorydescription'] ?? $code)
                ];
            }
        }
        echo json_encode(['status' => 'ok', 'results' => $results]);
        break;

    case 'search_quotes':
        $q = trim($req['q'] ?? '');
        $results = [];
        $where = "documenttype='54'";
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $where .= " AND (documentno LIKE '%$qEsc%' OR customername LIKE '%$qEsc%' OR customercode LIKE '%$qEsc%')";
        }
        $ResultIndex = ajaxDB_query("SELECT documentno, customername, docdate FROM SalesHeader
                                     WHERE $where ORDER BY docdate DESC LIMIT 15");
        if ($ResultIndex) {
            while ($row = DB_fetch_array($ResultIndex)) {
                $docno = trim($row['documentno']);
                $cust = trim((string)($row['customername'] ?? ''));
                $results[] = [
                    'value' => $docno,
                    'label' => $docno . ($cust !== '' ? ' - ' . $cust : '')
                ];
            }
        }
        echo json_encode(['status' => 'ok', 'results' => $results]);
        break;

    case 'standard':
        $cat = trim($req['cat'] ?? '');
        $customerid = trim($req['customerid'] ?? '');
        if ($cat === '' || !preg_match('/^TS\d{4}$/', $cat)) {
            quoteFail('Select a sample standard first.');
        }
        $catEsc = mysqli_real_escape_string($db, $cat);
        $bundle = [
            'code' => $cat,
            'name' => $cat,
            'units' => 'PCS',
            'vat' => 0,
            'price' => quotePriceFor($cat, $customerid),
            'tat' => quoteTatFor($cat),
            'disc' => 0
        ];
        $ResultIndex = ajaxDB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid='$catEsc' LIMIT 1");
        if ($row = DB_fetch_array($ResultIndex)) {
            $bundle['name'] = ($row['categorydescription'] ?? $cat);
        }
        $bd = quoteStockDetails($cat);
        if ($bd['itemcode'] !== '') {
            $bundle['units'] = $bd['units'];
            $bundle['vat'] = $bd['vat'];
        }
        $tests = [];
        $ResultIndex = ajaxDB_query("SELECT dt.itemcode, dt.discount_percent, sm.descrip, sm.units
                                     FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode
                                     WHERE dt.categoryid='$catEsc'
                                       AND dt.is_active = 1 AND (sm.inactive = 0 OR sm.inactive IS NULL)
                                     ORDER BY sm.descrip");
        while ($row = DB_fetch_array($ResultIndex)) {
            $tcode = trim($row['itemcode']);
            $td = quoteStockDetails($tcode);
            $tests[] = [
                'code' => $tcode,
                'name' => $row['descrip'],
                'units' => $td['units'],
                'vat' => $td['vat'],
                'price' => quotePriceFor($tcode, $customerid),
                'tat' => quoteTatFor($tcode),
                'disc' => (float)($row['discount_percent'] ?? 0)
            ];
        }
        if ((float)$bundle['price'] <= 0 && count($tests) > 0) {
            $sum = 0;
            foreach ($tests as $t) {
                $sum += (float)$t['price'];
            }
            if ($sum > 0) {
                $bundle['price'] = round($sum, 2);
                $bundle['price_fallback'] = true;
            }
        }
        // Price the rows server-side (qty 1 each) so the grid paints final
        // amounts in one pass with no client recompute.
        $stdFlags = quoteCustomerFlags($customerid);
        $stdVATinclusive = $stdFlags['vatinclusive'] ? 1 : 0;
        $stdIsTaxed = $stdFlags['istaxed'];
        $stdHeaderDisc = (float)($req['DiscountPercent'] ?? 0);
        $stdPriceOne = function ($item) use ($stdIsTaxed, $stdVATinclusive, $stdHeaderDisc) {
            $vatrate = $stdIsTaxed ? (int)($item['vat'] ?? 0) : 0;
            list($net, $vat, $gross) = quoteLineAmounts($item['price'] ?? 0, 1, 1, $item['disc'] ?? 0, $vatrate, $stdVATinclusive, $stdHeaderDisc);
            $item['net'] = round($net * 100) / 100;
            $item['vatAmt'] = round($vat * 100) / 100;
            $item['gross'] = round($gross * 100) / 100;
            return $item;
        };
        $bundle = $stdPriceOne($bundle);
        foreach ($tests as $k => $t) {
            $tests[$k] = $stdPriceOne($t);
        }
        echo json_encode(['status' => 'ok', 'cat' => $cat, 'bundle' => $bundle, 'tests' => $tests]);
        break;

    case 'load_quote':
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            quoteFail('No quote number provided.');
        }
        $docnoEsc = mysqli_real_escape_string($db, $docno);
        $hdr = null;
        $ResultIndex = ajaxDB_query("SELECT documentno, docdate, customercode, customername, currencycode, salespersoncode,
                                            QtyDiscount, locationcode, externaldocumentno AS Bank_Code2, paymentterms, picture, pricingmode
                                     FROM SalesHeader WHERE documenttype=54 AND documentno='$docnoEsc' LIMIT 1");
        if ($ResultIndex === false) {
            // Pre-migration fallback: pricingmode column not yet added (see sql/migration_add_pricingmode_column.sql).
            $ResultIndex = ajaxDB_query("SELECT documentno, docdate, customercode, customername, currencycode, salespersoncode,
                                                QtyDiscount, locationcode, externaldocumentno AS Bank_Code2, paymentterms, picture
                                         FROM SalesHeader WHERE documenttype=54 AND documentno='$docnoEsc' LIMIT 1");
        }
        if ($ResultIndex && ($row = DB_fetch_array($ResultIndex))) {
            $hdr = [
                'documentno' => $row['documentno'],
                'date' => ConvertSQLDate($row['docdate']),
                'customercode' => $row['customercode'],
                'customername' => $row['customername'],
                'currencycode' => $row['currencycode'],
                'salespersoncode' => $row['salespersoncode'] ?? '',
                'locationcode' => $row['locationcode'] ?? '',
                'bank2' => $row['Bank_Code2'] ?? '',
                'paymentterms' => $row['paymentterms'] ?? '',
                'picture' => $row['picture'] ?? '',
                'pricingmode' => $row['pricingmode'] ?? '',
                'DiscountPercent' => (float)($row['QtyDiscount'] ?? 0)
            ];
        }
        if (!$hdr) {
            quoteFail('Quote not found: ' . $docno);
        }
        $lines = [];
        $ResultIndex = ajaxDB_query("SELECT code, description, unitofmeasure, Quantity, UnitPrice, vatrate, inclusive,
                                            LineDiscountPercent, TAT, PartPerUnit
                                     FROM SalesLine WHERE documenttype=54 AND documentno='$docnoEsc'
                                       AND code IS NOT NULL AND code != ''");
        // Price the saved lines server-side so the grid paints final amounts
        // in one pass with no client recompute.
        $ldFlags = quoteCustomerFlags($hdr['customercode'] ?? '');
        $ldVATinclusive = $ldFlags['vatinclusive'] ? 1 : 0;
        $ldHeaderDisc = (float)($hdr['DiscountPercent'] ?? 0);
        while ($row = DB_fetch_array($ResultIndex)) {
            $lQty = (float)$row['Quantity'];
            $lPrice = (float)$row['UnitPrice'];
            $lPpu = (float)($row['PartPerUnit'] ?? 1);
            $lDisc = (float)($row['LineDiscountPercent'] ?? 0);
            $lVat = (float)($row['vatrate'] ?? 0);
            list($lNet, $lVatAmt, $lGross) = quoteLineAmounts($lPrice, $lQty, $lPpu, $lDisc, $lVat, $ldVATinclusive, $ldHeaderDisc);
            $lines[] = [
                'code' => trim($row['code']),
                'description' => $row['description'],
                'unitofmeasure' => $row['unitofmeasure'] ?? '',
                'quantity' => $lQty,
                'price' => $lPrice,
                'vatrate' => $lVat,
                'discountpercent' => $lDisc,
                'tat' => $row['TAT'] ?? 0,
                'partsperunit' => $lPpu,
                'net' => round($lNet * 100) / 100,
                'vatAmt' => round($lVatAmt * 100) / 100,
                'gross' => round($lGross * 100) / 100
            ];
        }
        echo json_encode(['status' => 'ok', 'header' => $hdr, 'lines' => $lines]);
        break;

    case 'customers':
        $q = trim($req['q'] ?? '');
        $results = [];
        if ($q !== '') {
            $qEsc = mysqli_real_escape_string($db, $q);
            $ResultIndex = ajaxDB_query("SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
                                         FROM debtors
                                         WHERE itemcode LIKE '%$qEsc%' OR customer LIKE '%$qEsc%'
                                         ORDER BY customer LIMIT 25");
        } else {
            $ResultIndex = ajaxDB_query("SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
                                         FROM debtors ORDER BY customer LIMIT 25");
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

    case 'new':
        unset($_SESSION['sales_orders']);
        unset($_SESSION['sales_orders_category']);
        echo json_encode([
            'status' => 'ok',
            'data' => GetTempNextNo(54),
            'currency' => ($_SESSION['CompanyRecord']['currencydefault'] ?? 'KES')
        ]);
        break;

    case 'reprice':
        // Server-side pricing: the single source of truth for grid amounts.
        // scope=full  -> apply group rules (toggle/qty/customer: may reset price/disc to base).
        // scope=amounts -> preserve sent price/disc, recompute amounts only (manual price edits).
        $customerid = trim($req['customerid'] ?? '');
        $headerDisc = (float)($req['DiscountPercent'] ?? 0);
        $scope = trim((string)($req['scope'] ?? 'full'));
        if ($scope !== 'amounts') {
            $scope = 'full';
        }
        $pmRaw = $req['pricingmode'] ?? '{}';
        $modes = quoteCleanModes(is_array($pmRaw) ? $pmRaw : (json_decode((string)$pmRaw, true) ?? []));
        $in = (array)($req['lines'] ?? []);
        $lines = [];
        $present = [];
        foreach ($in as $l) {
            if (!is_array($l)) {
                continue;
            }
            $code = trim((string)($l['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $idx = count($lines);
            $lines[] = [
                'code' => $code,
                'qty' => (float)($l['qty'] ?? 0),
                'group' => trim((string)($l['group'] ?? '')),
                'price' => (float)($l['price'] ?? 0),
                'disc' => (float)($l['disc'] ?? 0),
                'ppu' => (float)($l['ppu'] ?? 1)
            ];
            $present[] = [
                'id' => $l['id'] ?? $idx,
                'stdGroup' => trim((string)($l['group'] ?? '')),
                'stdName' => (string)($l['gname'] ?? ''),
                'isBundle' => !empty($l['bundle']),
                'code' => $code,
                'name' => (string)($l['name'] ?? $code),
                'units' => trim((string)($l['units'] ?? 'PCS')) !== '' ? trim((string)($l['units'] ?? 'PCS')) : 'PCS',
                'ppu' => (float)($l['ppu'] ?? 1) > 0 ? (float)($l['ppu'] ?? 1) : 1,
                'vat' => (float)($l['vat'] ?? 0),
                'tat' => $l['tat'] ?? 0
            ];
        }
        if (empty($lines)) {
            quoteFail('No lines to price.');
        }
        $flags = quoteCustomerFlags($customerid);
        $VATinclusive = $flags['vatinclusive'] ? 1 : 0;
        $IsTaxed = $flags['istaxed'];
        $modeMap = $modes;
        $overrides = ($scope === 'full') ? quoteGroupRule($lines, $modes, $customerid) : [];
        $rows = [];
        $totalNet = 0;
        $totalVat = 0;
        $totalGross = 0;
        foreach ($lines as $i => $l) {
            if (isset($overrides[$i]['price'])) {
                $l['price'] = (float)$overrides[$i]['price'];
            }
            if (isset($overrides[$i]['disc'])) {
                $l['disc'] = (float)$overrides[$i]['disc'];
            }
            $vatrate = $IsTaxed ? (int)quoteStockDetails($l['code'])['vat'] : 0;
            list($net, $vat, $gross) = quoteLineAmounts($l['price'], $l['qty'], $l['ppu'], $l['disc'], $vatrate, $VATinclusive, $headerDisc);
            $totalNet += $net;
            $totalVat += $vat;
            $totalGross += $gross;
            $p = $present[$i];
            $rows[] = [
                'id' => $p['id'],
                'stdGroup' => $p['stdGroup'],
                'stdName' => $p['stdName'] !== '' ? $p['stdName'] : $p['stdGroup'],
                'isBundle' => $p['isBundle'],
                'pricingMode' => $modeMap[$p['stdGroup']] ?? 'auto',
                'code' => $p['code'],
                'label' => $p['code'] . ' - ' . $p['name'],
                'name' => $p['name'],
                'units' => $p['units'],
                'ppu' => $p['ppu'],
                'quantity' => $l['qty'],
                'price' => $l['price'],
                'disc' => $l['disc'],
                'vat' => $vatrate,
                'tat' => $p['tat'],
                'net' => round($net * 100) / 100,
                'vatAmt' => round($vat * 100) / 100,
                'gross' => round($gross * 100) / 100,
                'rowseq' => ''
            ];
        }
        echo json_encode([
            'status' => 'ok',
            'rows' => $rows,
            'totals' => [
                'net' => round($totalNet * 100) / 100,
                'vat' => round($totalVat * 100) / 100,
                'gross' => round($totalGross * 100) / 100
            ]
        ]);
        break;

    case 'save':
        $docno = trim($req['documentno'] ?? '');
        $customerid = trim($req['CustomerID'] ?? '');
        if ($docno === '') {
            quoteFail('No document number. Reload the quotation page to get one.');
        }
        if ($customerid === '') {
            quoteFail('Select a customer before saving.');
        }
        $docnoEsc = mysqli_real_escape_string($db, $docno);
        $customerEsc = mysqli_real_escape_string($db, $customerid);

        $flags = quoteCustomerFlags($customerid);
        $postinggroup = '';
        $cur = $flags['currency'] ?? '';
        $ResultIndex = ajaxDB_query("SELECT customerposting FROM debtors WHERE itemcode='$customerEsc' LIMIT 1");
        if ($deRow = DB_fetch_row($ResultIndex)) {
            $postinggroup = trim((string)$deRow[0]);
        } elseif ($cur === '') {
            quoteFail('Customer not found: ' . $customerid);
        }

        $quoteCurrency = trim((string)($req['currencycode'] ?? ''));
        if ($quoteCurrency === '') {
            $quoteCurrency = ($cur !== '' ? $cur : ($_SESSION['CompanyRecord']['currencydefault'] ?? 'KES'));
        }

        $VATinclusive = $flags['vatinclusive'] ? 1 : 0;
        $IsTaxed = $flags['istaxed'];

        $date = trim($req['date'] ?? '');
        $DATE = $date !== '' ? FormatDateForSQL($date) : null;
        if ($DATE === null) {
            quoteFail('Invalid quotation date.');
        }
        $PeriodNo = quotePeriodFor($DATE);

        $customerName = trim((string)($req['CustomerName'] ?? ''));
        if ($customerName === '') {
            $customerName = ($flags['name'] ?? '');
        }
        $salesman = trim((string)($req['salespersoncode'] ?? ''));
        $Bank_Code = trim((string)($req['Bank_Code'] ?? ''));
        $Bank_Code2 = trim((string)($req['Bank_Code2'] ?? ''));
        $quoteBank2 = (($Bank_Code2 !== '') && ($Bank_Code2 === $Bank_Code)) ? '' : $Bank_Code2;

        // A blank bank must never block saving: use the first account for the
        // customer's currency, else the quote currency, else leave locationcode NULL.
        if ($Bank_Code === '') {
            $bankCurrency = ($cur !== '' ? $cur : $quoteCurrency);
            $ResultIndex = ajaxDB_query("SELECT accountcode FROM BankAccounts WHERE currency='" . mysqli_real_escape_string($db, $bankCurrency) . "' ORDER BY accountcode LIMIT 1");
            if ($bankRow = DB_fetch_row($ResultIndex)) {
                $Bank_Code = trim((string)$bankRow[0]);
            }
        }

        $paymentterms = trim((string)($req['paymentterms'] ?? ''));
        if ($paymentterms === '') {
            $paymentterms = trim((string)($req['terms'] ?? ''));
        }
        $qtyDiscount = (float)($req['DiscountPercent'] ?? 0);

        // Per-group manual pricing toggle (JSON cat -> auto|standard|pertest). Sanitized; '{}' = all Auto.
        $pmRawSave = $req['pricingmode'] ?? '{}';
        $pmCleanSave = quoteCleanModes(is_array($pmRawSave) ? $pmRawSave : (json_decode(trim((string)$pmRawSave), true) ?? []));
        $pricingmodeJson = empty($pmCleanSave) ? '{}' : json_encode($pmCleanSave);
        $pmEsc = mysqli_real_escape_string($db, $pricingmodeJson);

        $imagesArray2 = '[]';
        $selectedImages = (string)($req['selectedImages'] ?? '');
        if ($selectedImages !== '') {
            $j = json_decode(html_entity_decode($selectedImages, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (json_last_error() === JSON_ERROR_NONE && $j !== null) {
                $imagesArray2 = json_encode($j);
            }
        }

        $ResultIndex = ajaxDB_query("SELECT documentno FROM SalesHeader WHERE documenttype='54' AND documentno='$docnoEsc' LIMIT 1");
        $isEdit = (DB_fetch_row($ResultIndex) !== null);

        if (!$isEdit && preg_match('/(\d+)$/', $docno, $m)) {
            ajaxDB_query("UPDATE systypes_1 SET typeno=" . (int)$m[1] . " WHERE typeid='54'");
        }

        $db->begin_transaction();

        if ($isEdit) {
            ajaxDB_query("UPDATE SalesHeader SET released=0 WHERE documenttype='54' AND documentno='$docnoEsc'");
            ajaxDB_query("DELETE FROM SalesLine WHERE documenttype='54' AND documentno='$docnoEsc'");
            ajaxDB_query("DELETE FROM SalesHeader WHERE documenttype='54' AND documentno='$docnoEsc'");
        }

        $SQL = "INSERT INTO SalesHeader
                (documenttype, documentno, docdate, customercode, customername, externaldocumentno,
                 locationcode, postinggroup, currencycode, salespersoncode, status, userid, period,
                 vatinclusive, picture, QtyDiscount, pricingmode)
                VALUES
                ('54', '$docnoEsc', '" . mysqli_real_escape_string($db, $DATE) . "',
                 '$customerEsc',
                 '" . mysqli_real_escape_string($db, $customerName) . "',
                 '" . mysqli_real_escape_string($db, $quoteBank2) . "',
                 " . ($Bank_Code !== '' ? "'" . mysqli_real_escape_string($db, $Bank_Code) . "'" : 'NULL') . ",
                 '" . mysqli_real_escape_string($db, $postinggroup) . "',
                 '" . mysqli_real_escape_string($db, $quoteCurrency) . "',
                 '" . mysqli_real_escape_string($db, $salesman) . "',
                 '1', '" . mysqli_real_escape_string($db, ($_SESSION['UserID'] ?? '')) . "', '" . (int)$PeriodNo . "',
                 '" . (int)$VATinclusive . "',
                 '" . mysqli_real_escape_string($db, $imagesArray2) . "',
                 '" . (float)$qtyDiscount . "',
                 '$pmEsc')";
        ajaxDB_query($SQL);
        $hdrErrno = $db->errno;
        $hdrError = $db->error;
        if ($hdrErrno == 1054 && stripos($hdrError, 'pricingmode') !== false) {
            // Pre-migration fallback: pricingmode column not yet added (see sql/migration_add_pricingmode_column.sql).
            quoteLog('pricingmode column missing, saving without it. Run sql/migration_add_pricingmode_column.sql');
            $SQL = "INSERT INTO SalesHeader
                    (documenttype, documentno, docdate, customercode, customername, externaldocumentno,
                     locationcode, postinggroup, currencycode, salespersoncode, status, userid, period,
                     vatinclusive, picture, QtyDiscount)
                    VALUES
                    ('54', '$docnoEsc', '" . mysqli_real_escape_string($db, $DATE) . "',
                     '$customerEsc',
                     '" . mysqli_real_escape_string($db, $customerName) . "',
                     '" . mysqli_real_escape_string($db, $quoteBank2) . "',
                     " . ($Bank_Code !== '' ? "'" . mysqli_real_escape_string($db, $Bank_Code) . "'" : 'NULL') . ",
                     '" . mysqli_real_escape_string($db, $postinggroup) . "',
                     '" . mysqli_real_escape_string($db, $quoteCurrency) . "',
                     '" . mysqli_real_escape_string($db, $salesman) . "',
                     '1', '" . mysqli_real_escape_string($db, ($_SESSION['UserID'] ?? '')) . "', '" . (int)$PeriodNo . "',
                     '" . (int)$VATinclusive . "',
                     '" . mysqli_real_escape_string($db, $imagesArray2) . "',
                     '" . (float)$qtyDiscount . "')";
            ajaxDB_query($SQL);
            $hdrErrno = $db->errno;
            $hdrError = $db->error;
        }
        $hdrCheck = ajaxDB_query("SELECT documentno FROM SalesHeader WHERE documenttype='54' AND documentno='$docnoEsc' LIMIT 1");
        if (DB_fetch_row($hdrCheck) === null) {
            quoteLog('save header failed: errno=' . $hdrErrno . ' error=' . $hdrError . ' docno=' . $docno . ' customer=' . $customerid
                . ' bank=' . ($Bank_Code !== '' ? $Bank_Code : 'NULL') . ' period=' . $PeriodNo . ' date=' . $DATE
                . ' | ' . preg_replace('/\s+/', ' ', $SQL));
            $db->rollback();
            quoteFail('Failed to save the quote header (errno ' . $hdrErrno . '): ' . $hdrError);
        }

        $codes = (array)($req['itemcode[]'] ?? []);
        $names = (array)($req['stockname[]'] ?? []);
        $unitcodes = (array)($req['units[]'] ?? []);
        $ppus = (array)($req['partsperunit[]'] ?? []);
        $qtys = (array)($req['quantity[]'] ?? []);
        $prices = (array)($req['pricevalue[]'] ?? []);
        $discs = (array)($req['LineDiscountPercent[]'] ?? []);
        $tats = (array)($req['tat[]'] ?? []);
        $stdgroups = (array)($req['stdgroup[]'] ?? []);

        foreach ($codes as $i => $code) {
            $code = trim((string)$code);
            $qty = (float)($qtys[$i] ?? 0);
            if ($code === '' || $qty <= 0) {
                continue;
            }
            $ppu = (float)($ppus[$i] ?? 1);
            if ($ppu <= 0) {
                $ppu = 1;
            }
            $price = (float)($prices[$i] ?? 0);
            $lineDisc = (float)($discs[$i] ?? 0);
            $tatVal = (isset($tats[$i]) && trim((string)$tats[$i]) !== '') ? (int)$tats[$i] : null;
            $unit = trim((string)($unitcodes[$i] ?? 'PCS'));
            if ($unit === '') {
                $unit = 'PCS';
            }
            $descri = trim((string)($names[$i] ?? $code));
            if ($descri === '') {
                $descri = $code;
            }

            // stdGroup is the exact TS#### standard category that the
            // quotation grid used for this line.
            $stdGroup = trim((string)($stdgroups[$i] ?? ''));
            if ($stdGroup === '' && preg_match('/^TS\\d{4}$/', $code)) {
                $stdGroup = $code;
            }
            $stdGroupEsc = mysqli_real_escape_string($db, $stdGroup);

            $vatrate = $IsTaxed ? (int)quoteStockDetails($code)['vat'] : 0;

            // Shared amount math (single source of truth with reprice).
            list($net, $vat, $gross) = quoteLineAmounts($price, $qty, $ppu, $lineDisc, $vatrate, $VATinclusive, $qtyDiscount);

            $SQL = "INSERT INTO SalesLine
                    (documenttype, docdate, documentno, code, description, unitofmeasure, Quantity,
                     UnitPrice, vatamount, invoiceamount, vatrate, inclusive, containerprice,
                     containersunits, totalchargedcontainers, containercode, Partperunit, TAT, LineDiscountPercent, category)
                    VALUES
                    ('54', '" . mysqli_real_escape_string($db, $DATE) . "', '$docnoEsc',
                     '" . mysqli_real_escape_string($db, $code) . "', '" . mysqli_real_escape_string($db, $descri) . "',
                     '" . mysqli_real_escape_string($db, quoteUnitDescrip($unit)) . "',
                     $qty, $price, $vat, $gross, $vatrate, '" . (int)$VATinclusive . "',
                     0, 0, 0, '0', $ppu, " . ($tatVal !== null ? (string)$tatVal : 'NULL') . ", $lineDisc,
                     '" . $stdGroupEsc . "')";
            $lineRes = ajaxDB_query($SQL);
            if ($lineRes === false) {
                $lineErrno = $db->errno;
                $lineError = $db->error;
                quoteLog('save line failed: docno=' . $docno . ' code=' . $code . ' errno=' . $lineErrno . ' error=' . $lineError);
                $db->rollback();
                quoteFail('Failed to save a quote line: ' . $code . ' (errno ' . $lineErrno . '): ' . $lineError);
            }
        }

        ajaxDB_query("UPDATE SalesHeader SET released = 1 WHERE documentno='$docnoEsc' AND documenttype='54'");
        $finRes = ajaxDB_query("UPDATE SalesLine SET completed = 1 WHERE documentno='$docnoEsc' AND documenttype='54'");
        if ($finRes === false) {
            $finErrno = $db->errno;
            $finError = $db->error;
            quoteLog('finalize failed: docno=' . $docno . ' errno=' . $finErrno . ' error=' . $finError);
            $db->rollback();
            quoteFail('Failed to finalize the quote (errno ' . $finErrno . '): ' . $finError);
        }
        $db->commit();

        if ($paymentterms !== '') {
            @file_put_contents(__DIR__ . '/../quotes/' . $docno . '.terms', strip_tags($paymentterms) . "\r\n\r\n");
        }
        $_SESSION['CompleteDocument'] = $docno;
        echo json_encode([
            'status' => 'saved',
            'documentno' => $docno,
            'released' => true,
            'message' => 'Sales Quote :' . $docno . ' has been created',
            'print_url' => 'PDFPrintSalesQuote.php?No=' . rawurlencode($docno)
        ]);
        break;

    case 'confirm':
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            quoteFail('No quote number provided.');
        }
        $docnoEsc = mysqli_real_escape_string($db, $docno);
        ajaxDB_query("UPDATE SalesHeader SET released = 1 WHERE documentno='$docnoEsc'");
        ajaxDB_query("UPDATE SalesLine SET completed = 1 WHERE documentno='$docnoEsc'");
        $_SESSION['CompleteDocument'] = $docno;
        echo json_encode(['status' => 'ok', 'message' => 'Quote confirmed: ' . $docno]);
        break;

    case 'cancel':
        $docno = trim($req['documentno'] ?? '');
        if ($docno === '') {
            quoteFail('No quote number provided.');
        }
        $docnoEsc = mysqli_real_escape_string($db, $docno);
        ajaxDB_query("DELETE FROM SalesLine WHERE documentno='$docnoEsc' AND documenttype='15'");
        ajaxDB_query("DELETE FROM SalesHeader WHERE documentno='$docnoEsc' AND documenttype='15'");
        unset($_SESSION['CompleteDocument']);
        echo json_encode(['status' => 'ok', 'message' => 'Quote deleted: ' . $docno]);
        break;

    default:
        quoteFail('Unknown action: ' . $action);
        break;
}
