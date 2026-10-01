<?php
// Price list backend: paginated, standard-driven listing with inline price
// editing and inline row creation. Errors are NEVER silent: every failure is
// logged with context and returned as JSON {status:'error',...}.
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../quotes/php_errors.log');

session_write_close();
session_name('ErpWithCRM');
session_start();

include('../config.php');
header('Content-Type: application/json');
$database = trim((string)($_SESSION['DatabaseName'] ?? ''));
if ($database === '') {
    plLog('no database selected: $_SESSION[DatabaseName] is empty on action=' . ($_REQUEST['action'] ?? ''));
    echo json_encode(['status' => 'error', 'code' => 'NO_DATABASE', 'message' => 'No database selected - reload the page.']);
    exit;
}
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);
if (!$db) {
    echo json_encode(['status' => 'error', 'code' => 'DB_CONNECT', 'message' => 'Database connection failed']);
    exit;
}
mysqli_set_charset($db, 'utf8mb4');

$action = $_REQUEST['action'] ?? '';

function plLog($message) {
    @file_put_contents(__DIR__ . '/../quotes/price_list_ajax_errors.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function plDB($sql) {
    global $db, $action;
    $result = mysqli_query($db, $sql);
    if ($result === false) {
        plLog('query failed: action=' . $action . ' errno=' . mysqli_errno($db) . ' error=' . mysqli_error($db)
            . ' | ' . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 1500));
    }
    return $result;
}

function plFail($message, $code = '') {
    plLog('replied error' . ($code !== '' ? ' [' . $code . ']' : '') . ': ' . $message);
    echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
    exit;
}

function plOk($payload = array()) {
    echo json_encode($payload + array('status' => 'ok'));
    exit;
}

function plStr($v) {
    global $db;
    return "'" . mysqli_real_escape_string($db, (string)$v) . "'";
}

// Bare escape for values that are embedded inside a wider literal,
// e.g. LIKE '%...%' where plStr()'s own quotes would break the syntax.
function plRaw($v) {
    global $db;
    return mysqli_real_escape_string($db, (string)$v);
}

// Default price rows are keyed by quantity; new inline rows always use 1.
function plDefaultQuantity() {
    return 1;
}

// Map of stockcode => the default price row we edit (quantity 1 preferred).
function plPriceMap(array $codes) {
    $out = array();
    if (empty($codes)) {
        return $out;
    }
    $list = array();
    foreach ($codes as $c) {
        $list[] = plStr($c);
    }
    $sql = "SELECT id, stockcode, price, TAT, units_code, quantity FROM PriceList
            WHERE customerCode = '' AND stockcode IN (" . implode(',', $list) . ")
            ORDER BY stockcode, quantity";
    $res = plDB($sql);
    if ($res === false) {
        plFail('Could not read the price list. See the error log.', 'DB_PRICES');
    }
    while ($row = mysqli_fetch_assoc($res)) {
        $code = trim($row['stockcode']);
        $qty = (float)$row['quantity'];
        // First row wins unless a quantity 1 row shows up later.
        if (!isset($out[$code]) || (abs($qty - 1) < 0.00001 && abs((float)$out[$code]['quantity'] - 1) >= 0.00001)) {
            $out[$code] = array(
                'id' => (int)$row['id'],
                'price' => (float)$row['price'],
                'tat' => (int)$row['TAT'],
                'units_code' => $row['units_code'],
                'quantity' => $qty,
            );
        }
    }
    return $out;
}

function plUnitNames(array $codes) {
    $out = array();
    if (empty($codes)) {
        return $out;
    }
    $list = array();
    foreach ($codes as $c) {
        $list[] = plStr($c);
    }
    $res = plDB("SELECT code, descrip FROM unit WHERE code IN (" . implode(',', $list) . ")");
    if ($res === false) {
        return $out;
    }
    while ($row = mysqli_fetch_assoc($res)) {
        $out[trim($row['code'])] = $row['descrip'];
    }
    return $out;
}

function plMakeRow($stockcode, $descrip, $unitCode, $discountPercent, $isActive, $groupKey, $groupName, $isBundle, $priceMap, $unitMap) {
    $stockcode = trim($stockcode);
    $p = $priceMap[$stockcode] ?? null;
    $unitCode = trim((string)$unitCode);
    $unitName = $unitMap[$unitCode] ?? '';
    if ($unitName === '' && $unitCode !== '') {
        $unitName = $unitCode;
    }
    return array(
        'id' => $p ? $p['id'] : 0,
        'stockcode' => $stockcode,
        'descrip' => (string)$descrip,
        'price' => $p ? $p['price'] : null,
        'tat' => $p ? $p['tat'] : 0,
        'units_code' => $unitCode,
        'unit_name' => $unitName,
        'quantity' => $p ? (int)$p['quantity'] : plDefaultQuantity(),
        'discount_percent' => (float)$discountPercent,
        'is_active' => (int)$isActive,
        'groupkey' => $groupKey,
        'groupname' => $groupName,
        'is_bundle' => $isBundle ? 1 : 0,
        'is_priced' => $p ? 1 : 0,
    );
}

// One standard: its bundle row (when it exists in stockmaster) plus every
// active test mapped to it in discounttable.
function plStandardRows($cat, $catName) {
    $bundle = null;
    $bRes = plDB("SELECT itemcode, descrip, units FROM stockmaster WHERE itemcode = " . plStr($cat) . " LIMIT 1");
    if ($bRes === false) {
        plFail('Could not read the standard item. See the error log.', 'DB_BUNDLE');
    }
    if ($row = mysqli_fetch_assoc($bRes)) {
        $bundle = $row;
    }
    $mRes = plDB("SELECT dt.itemcode, dt.discount_percent, dt.is_active, sm.descrip, sm.units
                  FROM discounttable dt
                  JOIN stockmaster sm ON sm.itemcode = dt.itemcode
                  WHERE dt.categoryid = " . plStr($cat) . " AND dt.is_active = 1
                  ORDER BY sm.descrip");
    if ($mRes === false) {
        plFail('Could not read the tests mapped to this standard. See the error log.', 'DB_MEMBERS');
    }
    $members = array();
    while ($row = mysqli_fetch_assoc($mRes)) {
        $members[] = $row;
    }

    $codes = array();
    if ($bundle) {
        $codes[] = $bundle['itemcode'];
    }
    foreach ($members as $m) {
        $codes[] = $m['itemcode'];
    }
    $priceMap = plPriceMap($codes);
    $unitMap = plUnitNames($codes);

    $rows = array();
    if ($bundle) {
        $rows[] = plMakeRow($bundle['itemcode'], $bundle['descrip'], $bundle['units'], 0, 1, $cat, $catName, true, $priceMap, $unitMap);
    }
    foreach ($members as $m) {
        $rows[] = plMakeRow($m['itemcode'], $m['descrip'], $m['units'], $m['discount_percent'], $m['is_active'], $cat, $catName, false, $priceMap, $unitMap);
    }
    return $rows;
}

switch ($action) {

    case 'list_prices':
        $page = max(1, (int)($_REQUEST['page'] ?? 1));
        $pageSize = (int)($_REQUEST['page_size'] ?? 25);
        if ($pageSize < 5) {
            $pageSize = 5;
        }
        if ($pageSize > 200) {
            $pageSize = 200;
        }
        $group = trim((string)($_REQUEST['group'] ?? ''));
        if ($group !== '' && preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $group) !== 1) {
            plFail('Invalid filter value.', 'BAD_GROUP');
        }

        // Single standard selected: show all of its rows, no paging needed.
        if ($group !== '' && stripos($group, 'TS') === 0) {
            $cRes = plDB("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid = " . plStr($group) . " LIMIT 1");
            if ($cRes === false) {
                plFail('Could not read the standard. See the error log.', 'DB_CAT');
            }
            $cRow = mysqli_fetch_assoc($cRes);
            if (!$cRow) {
                plFail('Sample standard not found: ' . $group, 'CAT_NOT_FOUND');
            }
            $rows = plStandardRows($group, (string)($cRow['categorydescription'] ?? $group));
            plOk(array(
                'rows' => $rows,
                'total' => count($rows),
                'groups' => 1,
                'page' => 1,
                'pages' => 1,
                'page_size' => $pageSize,
                'scope' => 'standard',
            ));
        }

        // Category selected: paginate the stock items in that category.
        if ($group !== '') {
            $cRes = plDB("SELECT categoryid, categorydescription FROM stockcategory WHERE categoryid = " . plStr($group) . " LIMIT 1");
            if ($cRes === false) {
                plFail('Could not read the category. See the error log.', 'DB_CAT');
            }
            $cRow = mysqli_fetch_assoc($cRes);
            $catName = $cRow ? (string)($cRow['categorydescription'] ?? $group) : $group;
            $cDesc = plStr($catName);
            $countRes = plDB("SELECT COUNT(*) AS c FROM stockmaster
                              WHERE (category = " . plStr($group) . " OR category = " . $cDesc . ")
                                AND (inactive = 0 OR inactive IS NULL) AND isstock_1 = 1");
            if ($countRes === false) {
                plFail('Could not count the items in this category. See the error log.', 'DB_COUNT');
            }
            $crow = mysqli_fetch_assoc($countRes);
            $total = (int)($crow['c'] ?? 0);
            $pages = max(1, (int)ceil($total / $pageSize));
            if ($page > $pages) {
                $page = $pages;
            }
            $offset = ($page - 1) * $pageSize;
            $listRes = plDB("SELECT itemcode, descrip, units FROM stockmaster
                             WHERE (category = " . plStr($group) . " OR category = " . $cDesc . ")
                               AND (inactive = 0 OR inactive IS NULL) AND isstock_1 = 1
                             ORDER BY descrip LIMIT $pageSize OFFSET $offset");
            if ($listRes === false) {
                plFail('Could not read the items in this category. See the error log.', 'DB_CATEGORY_ITEMS');
            }
            $items = array();
            while ($row = mysqli_fetch_assoc($listRes)) {
                $items[] = $row;
            }
            $codes = array();
            foreach ($items as $it) {
                $codes[] = $it['itemcode'];
            }
            $priceMap = plPriceMap($codes);
            $unitMap = plUnitNames($codes);
            $rows = array();
            foreach ($items as $it) {
                $rows[] = plMakeRow($it['itemcode'], $it['descrip'], $it['units'], 0, 1, 'CAT:' . $group, $catName, false, $priceMap, $unitMap);
            }
            plOk(array(
                'rows' => $rows,
                'total' => $total,
                'groups' => 1,
                'page' => $page,
                'pages' => $pages,
                'page_size' => $pageSize,
                'scope' => 'category',
            ));
        }

        // Default: paginate BY STANDARD so a group is never split across pages.
        $countRes = plDB("SELECT COUNT(*) AS c FROM stockcategory WHERE categoryid LIKE 'TS%'");
        if ($countRes === false) {
            plFail('Could not count the sample standards. See the error log.', 'DB_COUNT');
        }
        $crow = mysqli_fetch_assoc($countRes);
        $total = (int)($crow['c'] ?? 0);
        $pages = max(1, (int)ceil($total / $pageSize));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $pageSize;
        $sRes = plDB("SELECT categoryid, categorydescription FROM stockcategory
                      WHERE categoryid LIKE 'TS%' ORDER BY categorydescription
                      LIMIT $pageSize OFFSET $offset");
        if ($sRes === false) {
            plFail('Could not read the sample standards. See the error log.', 'DB_STANDARDS');
        }
        $stds = array();
        while ($row = mysqli_fetch_assoc($sRes)) {
            $stds[] = $row;
        }
        $rows = array();
        foreach ($stds as $s) {
            $cat = trim($s['categoryid']);
            $name = (string)($s['categorydescription'] ?? $cat);
            foreach (plStandardRows($cat, $name) as $r) {
                $rows[] = $r;
            }
        }
        plOk(array(
            'rows' => $rows,
            'total' => $total,
            'groups' => count($stds),
            'page' => $page,
            'pages' => $pages,
            'page_size' => $pageSize,
            'scope' => 'standards',
        ));
        break;

    case 'save_price':
        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            plFail('Cannot edit a row that has no price yet - add it instead.', 'NOT_SAVED');
        }
        $cur = plDB("SELECT id, stockcode, units_code, quantity, price, TAT FROM PriceList WHERE id = $id LIMIT 1");
        if ($cur === false) {
            plFail('Could not read the price row. See the error log.', 'DB_READ');
        }
        if (!$row = mysqli_fetch_assoc($cur)) {
            plFail('Price row not found: ' . $id, 'NOT_FOUND');
        }
        $sets = array();
        if (isset($_REQUEST['price'])) {
            $price = (float)$_REQUEST['price'];
            if ($price < 0) {
                plFail('Price cannot be negative.', 'BAD_PRICE');
            }
            $sets[] = 'price = ' . $price;
        }
        if (isset($_REQUEST['tat'])) {
            $tat = (int)$_REQUEST['tat'];
            if ($tat < 0) {
                plFail('TAT cannot be negative.', 'BAD_TAT');
            }
            $sets[] = 'TAT = ' . $tat;
        }
        if (isset($_REQUEST['units_code'])) {
            $units = trim((string)$_REQUEST['units_code']);
            if ($units === '') {
                plFail('Unit is required.', 'BAD_UNIT');
            }
            // Note: the unit table only holds a handful of rows while
            // stockmaster.units legitimately contains values like 'PCS' and
            // 'mg/kg', so the unit is free text and is not validated against it.
            $sets[] = 'units_code = ' . plStr($units);
        }
        if (empty($sets)) {
            plFail('Nothing to save.', 'NO_CHANGES');
        }
        // quantity is intentionally left alone: it keys the default price row.
        $upd = plDB("UPDATE PriceList SET " . implode(', ', $sets) . " WHERE id = $id");
        if ($upd === false) {
            plFail('Could not save the price. See the error log.', 'DB_UPDATE');
        }
        $after = plDB("SELECT id, stockcode, price, TAT, units_code, quantity FROM PriceList WHERE id = $id LIMIT 1");
        $out = mysqli_fetch_assoc($after);
        plOk(array(
            'id' => $id,
            'stockcode' => trim($row['stockcode']),
            'price' => $out ? (float)$out['price'] : 0,
            'tat' => $out ? (int)$out['TAT'] : 0,
            'units_code' => $out ? $out['units_code'] : $row['units_code'],
            'quantity' => $out ? (int)$out['quantity'] : plDefaultQuantity(),
        ));
        break;

    case 'add_price':
        // Inline "add row": creates the default (customerCode '') price row for
        // a stock item, defaulting to quantity 1.
        $stockcode = trim((string)($_REQUEST['stockcode'] ?? ''));
        if ($stockcode === '') {
            plFail('Item code is required.', 'NO_STOCKCODE');
        }
        $price = (float)($_REQUEST['price'] ?? 0);
        if ($price < 0) {
            plFail('Price cannot be negative.', 'BAD_PRICE');
        }
        $tat = (int)($_REQUEST['tat'] ?? 0);
        if ($tat < 0) {
            plFail('TAT cannot be negative.', 'BAD_TAT');
        }
        $units = trim((string)($_REQUEST['units_code'] ?? ''));
        $sRes = plDB("SELECT itemcode, descrip, units FROM stockmaster WHERE itemcode = " . plStr($stockcode) . " LIMIT 1");
        if ($sRes === false) {
            plFail('Could not read the stock item. See the error log.', 'DB_STOCK');
        }
        if (!$stock = mysqli_fetch_assoc($sRes)) {
            plFail('Item not found in stockmaster: ' . $stockcode, 'STOCK_NOT_FOUND');
        }
        if ($units === '') {
            $units = trim((string)$stock['units']);
        }
        if ($units === '' || $units === '0') {
            $units = 'PCS';
        }
        $qty = plDefaultQuantity();
        $chk = plDB("SELECT id FROM PriceList WHERE customerCode = '' AND stockcode = " . plStr($stockcode)
            . " AND units_code = " . plStr($units) . " AND quantity = $qty LIMIT 1");
        if ($chk === false) {
            plFail('Could not check for an existing price. See the error log.', 'DB_CHECK');
        }
        if ($existing = mysqli_fetch_assoc($chk)) {
            $id = (int)$existing['id'];
            $upd = plDB("UPDATE PriceList SET price = $price, TAT = $tat WHERE id = $id");
            if ($upd === false) {
                plFail('Could not update the existing price. See the error log.', 'DB_UPDATE');
            }
            $created = false;
        } else {
            $ins = plDB("INSERT INTO PriceList (customerCode, stockcode, units_code, quantity, price, TAT, approved)
                         VALUES ('', " . plStr($stockcode) . ", " . plStr($units) . ", $qty, $price, $tat, 1)");
            if ($ins === false) {
                plFail('Could not insert the price. See the error log.', 'DB_INSERT');
            }
            $id = (int)mysqli_insert_id($db);
            $created = true;
        }
        // Report which standard this item belongs to so the UI can point at it.
        $dRes = plDB("SELECT dt.categoryid, sc.categorydescription FROM discounttable dt
                      LEFT JOIN stockcategory sc ON sc.categoryid = dt.categoryid
                      WHERE dt.itemcode = " . plStr($stockcode) . " AND dt.is_active = 1
                      ORDER BY dt.categoryid LIMIT 1");
        $groupKey = '';
        $groupName = '';
        if ($dRes !== false && $d = mysqli_fetch_assoc($dRes)) {
            $groupKey = trim((string)$d['categoryid']);
            $groupName = (string)($d['categorydescription'] ?? $groupKey);
        }
        plOk(array(
            'id' => $id,
            'created' => $created,
            'stockcode' => $stockcode,
            'descrip' => (string)$stock['descrip'],
            'price' => $price,
            'tat' => $tat,
            'units_code' => $units,
            'quantity' => $qty,
            'groupkey' => $groupKey,
            'groupname' => $groupName,
        ));
        break;

    case 'delete_price':
        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            plFail('Invalid price row id.', 'BAD_ID');
        }
        $cur = plDB("SELECT stockcode, units_code, quantity FROM PriceList WHERE id = $id LIMIT 1");
        if ($cur === false) {
            plFail('Could not read the price row. See the error log.', 'DB_READ');
        }
        if (!$row = mysqli_fetch_assoc($cur)) {
            plFail('Price row not found: ' . $id, 'NOT_FOUND');
        }
        $code = plStr($row['stockcode']);
        $units = plStr($row['units_code']);
        $qty = (float)$row['quantity'];
        // Mirrors PriceList::deleteMasterdata(): when no customer-specific rows
        // share this stockcode+unit+quantity, the other default rows go too.
        $chk = plDB("SELECT COUNT(*) AS c FROM PriceList
                     WHERE stockcode = $code AND units_code = $units AND quantity = $qty AND customerCode <> ''");
        if ($chk === false) {
            plFail('Could not check related prices. See the error log.', 'DB_CHECK');
        }
        $crow = mysqli_fetch_assoc($chk);
        $customerRows = (int)($crow['c'] ?? 0);
        if ($customerRows === 0) {
            $del = plDB("DELETE FROM PriceList WHERE stockcode = $code AND units_code = $units AND quantity = $qty");
        } else {
            $del = plDB("DELETE FROM PriceList WHERE id = $id");
        }
        if ($del === false) {
            plFail('Could not delete the price. See the error log.', 'DB_DELETE');
        }
        $affected = mysqli_affected_rows($db);
        plOk(array(
            'id' => $id,
            'stockcode' => trim($row['stockcode']),
            'deleted' => $affected,
            'customer_rows' => $customerRows,
            'message' => $customerRows > 0
                ? ('Deleted. ' . $customerRows . ' customer-specific price(s) left untouched.')
                : ('Deleted ' . $affected . ' price row(s) for ' . trim($row['stockcode']) . '.'),
        ));
        break;

    case 'search_stock':
        $q = trim((string)($_GET['q'] ?? ''));
        if (strlen($q) < 1) {
            echo json_encode(array());
            exit;
        }
        $esc = plRaw($q);
        $res = plDB("SELECT itemcode, descrip, units FROM stockmaster
                     WHERE (inactive = 0 OR inactive IS NULL) AND isstock_1 = 1
                       AND (itemcode LIKE '%$esc%' OR descrip LIKE '%$esc%')
                     ORDER BY descrip LIMIT 20");
        if ($res === false) {
            plFail('Stock search failed. See the error log.', 'DB_SEARCH');
        }
        $results = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $unit = trim((string)$row['units']);
            if ($unit === '' || $unit === '0') {
                $unit = 'PCS';
            }
            $results[] = array(
                'value' => $row['itemcode'],
                'label' => $row['descrip'] . ' (' . $row['itemcode'] . ')',
                'descrip' => $row['descrip'],
                'units' => $unit,
            );
        }
        echo json_encode($results);
        break;

    case 'get_price':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            plFail('Invalid ID', 'BAD_ID');
        }
        $res = plDB("SELECT id, stockcode, price, TAT, units_code, quantity FROM PriceList WHERE id = $id");
        if ($res === false) {
            plFail('Could not read the price. See the error log.', 'DB_READ');
        }
        if ($row = mysqli_fetch_assoc($res)) {
            plOk(array('data' => $row));
        }
        plFail('Price not found', 'NOT_FOUND');
        break;

    case 'get_discount':
        $itemcode = trim((string)($_GET['itemcode'] ?? ''));
        if ($itemcode === '') {
            plFail('Item code required', 'NO_STOCKCODE');
        }
        $res = plDB("SELECT dt.discount_percent, dt.is_active, dt.categoryid
                     FROM discounttable dt WHERE dt.itemcode = " . plStr($itemcode) . " LIMIT 1");
        if ($res === false) {
            plFail('Could not read the discount. See the error log.', 'DB_DISCOUNT');
        }
        if ($row = mysqli_fetch_assoc($res)) {
            plOk(array('data' => $row));
        }
        plOk(array('data' => array('discount_percent' => 0, 'is_active' => 1, 'categoryid' => '')));
        break;

    case 'save_discount':
        $itemcode = trim((string)($_POST['itemcode'] ?? ''));
        if ($itemcode === '') {
            plFail('Item code required', 'NO_STOCKCODE');
        }
        $discount = (float)($_POST['discount_percent'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);
        // Resolve the standard: an explicit categoryid, else the existing
        // mapping, else the stockmaster category when it is a real code.
        // (stockmaster has no categoryid column - that is why the old lookup
        // always failed.)
        $categoryid = trim((string)($_POST['categoryid'] ?? ''));
        if ($categoryid === '') {
            $ex = plDB("SELECT categoryid FROM discounttable WHERE itemcode = " . plStr($itemcode) . " LIMIT 1");
            if ($ex === false) {
                plFail('Could not read the existing mapping. See the error log.', 'DB_DISCOUNT');
            }
            if ($e = mysqli_fetch_assoc($ex)) {
                $categoryid = trim((string)$e['categoryid']);
            }
        }
        if ($categoryid === '') {
            $sc = plDB("SELECT category FROM stockmaster WHERE itemcode = " . plStr($itemcode) . " LIMIT 1");
            if ($sc === false) {
                plFail('Could not read the stock item. See the error log.', 'DB_STOCK');
            }
            $srow = $sc ? mysqli_fetch_assoc($sc) : null;
            $candidate = $srow ? trim((string)$srow['category']) : '';
            if ($candidate !== '') {
                $v = plDB("SELECT categoryid FROM stockcategory WHERE categoryid = " . plStr($candidate) . " LIMIT 1");
                if ($v === false) {
                    plFail('Could not verify the category. See the error log.', 'DB_CAT');
                }
                if (mysqli_fetch_assoc($v)) {
                    $categoryid = $candidate;
                }
            }
        }
        if ($categoryid === '') {
            plFail('No sample standard is mapped to ' . $itemcode . '. Choose the standard first.', 'NO_STANDARD');
        }
        $chk = plDB("SELECT id FROM discounttable WHERE categoryid = " . plStr($categoryid)
            . " AND itemcode = " . plStr($itemcode) . " LIMIT 1");
        if ($chk === false) {
            plFail('Could not check the mapping. See the error log.', 'DB_CHECK');
        }
        if ($existing = mysqli_fetch_assoc($chk)) {
            $id = (int)$existing['id'];
            $upd = plDB("UPDATE discounttable SET discount_percent = $discount, is_active = $isActive WHERE id = $id");
            if ($upd === false) {
                plFail('Could not update the discount. See the error log.', 'DB_UPDATE');
            }
        } else {
            $ins = plDB("INSERT INTO discounttable (categoryid, itemcode, discount_percent, is_active)
                         VALUES (" . plStr($categoryid) . ", " . plStr($itemcode) . ", $discount, $isActive)");
            if ($ins === false) {
                plFail('Could not insert the discount. See the error log.', 'DB_INSERT');
            }
            $id = (int)mysqli_insert_id($db);
        }
        plOk(array('id' => $id, 'categoryid' => $categoryid));
        break;

    default:
        plFail('Unknown action', 'BAD_ACTION');
}
