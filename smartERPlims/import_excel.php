<?php
/**
 * Simple CSV import helper for Customers, Suppliers, Stocks, and Fixed Assets.
 * - Provides downloadable CSV templates
 * - Accepts CSV uploads and inserts/updates rows
 *
 * NOTE: This is intentionally lightweight: it expects CSV files with column
 * headers matching the template column names. It uses existing trigger
 * functions (Triger_debtors, Triger_creditors, Triger_stockmaster) when
 * generating new item codes.
 */
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Import from Excel (CSV)');

$type = isset($_REQUEST['type']) ? trim(strtolower($_REQUEST['type'])) : '';

$templates = [
    'customer'   => ['itemcode','contact','creditlimit','customer','krapin','phone','company','altcontact','email','city','country','inactive','postcode','curr_cod','customerposting','salesman'],
    'supplier'   => ['itemcode','contact','vatregno','customer','middlen','phone','company','altcontact','email','city','country','inactive','postcode','curr_cod','supplierposting','firstn'],
    'stock'      => ['itemcode','barcode','descrip','postinggroup','sellingprice','reorderlevel','eoq','category','units','inactive','container','isstock_1','isstock_2','isstock_3','isstock_4','isstock_5','isstock_6','production','isstock'],
    'fixedasset' => ['assetid','description','longdescription','assetcategoryid','assetlocation','depntype','depnrate','barcode','serialno'],
    'assetregister' => ['assetid','equipment_name','location','equipment_code','quantity','manufacturer','date_purchased','serial_no','model_no','status','cost','remarks'],
];

// If no type specified, show selection links
if ($type == '' || !isset($templates[$type])) {
    include('includes/header.inc');
    echo '<h2>' . _('Import Data from CSV') . '</h2>';
    echo '<p>' . _('Choose the dataset to import and download the matching template.') . '</p>';
    echo '<ul>';
    foreach (array_keys($templates) as $t) {
        echo '<li><a href="import_excel.php?type=' . htmlspecialchars($t) . '">' . htmlspecialchars(ucfirst($t)) . ' ' . _('Import') . '</a> &mdash; <a href="import_excel.php?type=' . htmlspecialchars($t) . '&action=template">' . _('Download template') . '</a></li>';
    }
    echo '</ul>';
    include('includes/footer.inc');
    exit;
}

// Download template as CSV — pre-filled with all existing records
if (isset($_GET['action']) && $_GET['action'] == 'template') {
    $headers = $templates[$type];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_' . $type . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);

    switch ($type) {
        case 'customer':
            $rs = DB_query("SELECT itemcode,fax,creditlimit,customer,middlen,phone,company,altcontact,email,city,country,inactive,postcode,curr_cod,customerposting,salesman FROM debtors", $db);
            while ($r = DB_fetch_array($rs)) {
                fputcsv($out, [
                    $r['itemcode'], $r['fax'], $r['creditlimit'], $r['customer'],
                    $r['middlen'], $r['phone'], $r['company'], $r['altcontact'],
                    $r['email'], $r['city'], $r['country'], $r['inactive'],
                    $r['postcode'], $r['curr_cod'], $r['customerposting'], $r['salesman']
                ]);
            }
            break;

        case 'supplier':
            $rs = DB_query("SELECT itemcode,contact,vatregno,customer,middlen,phone,company,altcontact,email,city,country,inactive,postcode,curr_cod,supplierposting,firstn FROM creditors", $db);
            while ($r = DB_fetch_array($rs)) {
                fputcsv($out, [
                    $r['itemcode'], $r['contact'], $r['vatregno'], $r['customer'],
                    $r['middlen'], $r['phone'], $r['company'], $r['altcontact'],
                    $r['email'], $r['city'], $r['country'], $r['inactive'],
                    $r['postcode'], $r['curr_cod'], $r['supplierposting'], $r['firstn']
                ]);
            }
            break;

        case 'stock':
            $rs = DB_query("SELECT itemcode,barcode,descrip,postinggroup,sellingprice,reorderlevel,eoq,category,units,inactive,container,isstock_1,isstock_2,isstock_3,isstock_4,isstock_5,isstock_6,production,isstock FROM stockmaster", $db);
            while ($r = DB_fetch_array($rs)) {
                fputcsv($out, [
                    $r['itemcode'], $r['barcode'], $r['descrip'], $r['postinggroup'],
                    $r['sellingprice'], $r['reorderlevel'], $r['eoq'], $r['category'],
                    $r['units'], $r['inactive'], $r['container'],
                    $r['isstock_1'], $r['isstock_2'], $r['isstock_3'], $r['isstock_4'],
                    $r['isstock_5'], $r['isstock_6'], $r['production'], $r['isstock']
                ]);
            }
            break;

        case 'fixedasset':
            $rs = DB_query("SELECT assetid,description,longdescription,assetcategoryid,assetlocation,depntype,depnrate,barcode,serialno FROM fixedassets", $db);
            while ($r = DB_fetch_array($rs)) {
                fputcsv($out, [
                    $r['assetid'], $r['description'], $r['longdescription'],
                    $r['assetcategoryid'], $r['assetlocation'], $r['depntype'],
                    $r['depnrate'], $r['barcode'], $r['serialno']
                ]);
            }
            break;

        case 'assetregister':
            $rs = DB_query("SELECT
                                fa.assetid,
                                fa.description,
                                COALESCE(fal.locationdescription,'') AS locationdesc,
                                COALESCE(fa.equipment_code,'') AS equipment_code,
                                fa.quantity,
                                COALESCE(fa.manufacturer,'') AS manufacturer,
                                fa.datepurchased,
                                COALESCE(fa.serialno,'') AS serialno,
                                COALESCE(fa.modelno,'') AS modelno,
                                COALESCE(fa.status,'') AS status,
                                fa.cost,
                                COALESCE(fa.remarks,'') AS remarks
                            FROM fixedassets fa
                            LEFT JOIN fixedassetlocations fal ON fal.locationid = fa.assetlocation", $db);
            while ($r = DB_fetch_array($rs)) {
                $dateVal = $r['datepurchased'] ? date($_SESSION['DefaultDateFormat'], strtotime($r['datepurchased'])) : '';
                fputcsv($out, [
                    $r['assetid'],
                    $r['description'],
                    $r['locationdesc'],
                    $r['equipment_code'],
                    $r['quantity'],
                    $r['manufacturer'],
                    $dateVal,
                    $r['serialno'],
                    $r['modelno'],
                    $r['status'],
                    $r['cost'],
                    $r['remarks']
                ]);
            }
            break;
    }

    fclose($out);
    exit;
}

// Show upload form and handle uploads
include('includes/header.inc');
echo '<h2>' . _('Import') . ' ' . htmlspecialchars(ucfirst($type)) . '</h2>';
echo '<p>' . _('Download the template first, fill in your rows in Excel and save as CSV, then upload here.') . '</p>';
echo '<p><a class="btn btn-info" href="import_excel.php?type=' . htmlspecialchars($type) . '&action=template">' . _('Download CSV template') . '</a></p>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['importfile']) && is_uploaded_file($_FILES['importfile']['tmp_name'])) {
    $tmp = $_FILES['importfile']['tmp_name'];
    $fh  = fopen($tmp, 'r');
    if (!$fh) {
        prnMsg(_('Could not open uploaded file'), 'error');
    } else {
        $rawHeader = fgetcsv($fh);
        if (!$rawHeader) {
            prnMsg(_('Uploaded file appears empty or not a valid CSV'), 'error');
        } else {
            $headers = array_map('trim', array_map('strtolower', $rawHeader));
            $allowed = array_map('strtolower', $templates[$type]);
            // basic validation: at least one of the template columns must be present
            $found = array_intersect($headers, $allowed);
            if (count($found) == 0) {
                prnMsg(_('CSV header does not match expected template columns'), 'error');
            } else {
                $lineNo     = 1;
                $totalRows = 0;
                $success   = 0;
                $errors    = [];
                while (($row = fgetcsv($fh)) !== false) {
                    $lineNo++;
                    // skip empty rows
                    if (count(array_filter($row)) == 0) continue;
                    $totalRows++;
                    $assoc = [];
                    foreach ($headers as $i => $h) {
                        $assoc[$h] = isset($row[$i]) ? clean_str($row[$i]) : '';
                    }
                    switch ($type) {
                        case 'customer':   $res = import_customer_row($assoc);   break;
                        case 'supplier':   $res = import_supplier_row($assoc);   break;
                        case 'stock':      $res = import_stock_row($assoc);      break;
                        case 'fixedasset':    $res = import_fixedasset_row($assoc);    break;
                        case 'assetregister': $res = import_assetregister_row($assoc); break;
                        default:              $res = ['success' => false, 'error' => _('Unsupported type')];
                    }
                    if ($res['success']) {
                        $success++;
                    } else {
                        $errors[] = 'Line ' . $lineNo . ': ' . $res['error'];
                    }
                }
                echo '<div class="container">';
                echo '<p>' . sprintf(_('CSV rows: %d | Imported: %d | Errors: %d'), $totalRows, $success, count($errors)) . '</p>';
                if (count($errors) > 0) {
                    echo '<div class="panel panel-default"><ul>';
                    foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
                    echo '</ul></div>';
                }
                echo '</div>';
            }
        }
        fclose($fh);
    }
}

// Upload form
echo '<form method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="type" value="' . htmlspecialchars($type) . '" />';
echo '<div class="form-group"><label>' . _('CSV file') . '</label><input type="file" name="importfile" accept=".csv" required="required" /></div>';
echo '<div class="form-group"><input type="submit" value="' . _('Upload and Import') . '" class="btn btn-primary" /></div>';
echo '</form>';

include('includes/footer.inc');

/** Import helper implementations **/

function import_customer_row($assoc) {
    global $db;

    /*
     * $cols uses the CSV header names (matching $templates['customer']).
     * $map translates any CSV header name that differs from the real DB
     * column name.  Only entries that differ need to appear here.
     *
     * CSV header  =>  DB column (debtors table)
     *   krapin    =>  middlen
     */
    $map  = [
        'krapin' => 'middlen','contact' => 'fax',
    ];

    $cols = ['itemcode','contact','creditlimit','customer','krapin','phone','company','altcontact','email','city','country','inactive','postcode','curr_cod','customerposting','salesman'];

    $customerName = trim($assoc['customer'] ?? '');
    if ($customerName === '') return ['success' => false, 'error' => _('Missing customer name')];

    $itemcode = trim($assoc['itemcode'] ?? '');
    if ($itemcode != '') {
        $check = DB_query("SELECT itemcode FROM debtors WHERE itemcode='" . $db->real_escape_string($itemcode) . "'", $db);
        if (DB_num_rows($check) > 0) {
            // Record exists — UPDATE
            $sets = [];
            foreach ($cols as $c) {
                if ($c == 'itemcode') continue;
                if (!isset($assoc[$c])) continue;
                $dbcol  = $map[$c] ?? $c;   // resolve to real DB column name
                $sets[] = "`$dbcol`='" . $db->real_escape_string($assoc[$c]) . "'";
            }
            if (!empty($sets)) {
                $sql = "UPDATE debtors SET " . implode(',', $sets) . " WHERE itemcode='" . $db->real_escape_string($itemcode) . "'";
                DB_query($sql, $db);
            }
            return ['success' => true];
        }
    }

    // No itemcode supplied — generate one then INSERT
    if ($itemcode == '') {
        $itemcode = Triger_debtors($customerName);
    }

    $columns = [];
    $values  = [];
    foreach ($cols as $c) {
        $dbcol = $map[$c] ?? $c;   // resolve to real DB column name
        if ($c == 'itemcode') {
            $columns[] = 'itemcode';
            $values[]  = "'" . $db->real_escape_string($itemcode) . "'";
            continue;
        }
        $columns[] = "`$dbcol`";
        $values[]  = "'" . $db->real_escape_string($assoc[$c] ?? '') . "'";
    }
    $sql = "INSERT INTO debtors (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
    DB_query($sql, $db);
    return ['success' => true];
}

function import_supplier_row($assoc) {
    global $db;

    // No CSV-to-DB name differences for suppliers — $map is empty
    $map  = [];

    $cols = ['itemcode','contact','vatregno','customer','middlen','phone','company','altcontact','email','city','country','inactive','postcode','curr_cod','supplierposting','firstn'];

    $supplierName = trim($assoc['customer'] ?? '');
    if ($supplierName === '') return ['success' => false, 'error' => _('Missing supplier name')];

    $itemcode = trim($assoc['itemcode'] ?? '');
    if ($itemcode != '') {
        $check = DB_query("SELECT itemcode FROM creditors WHERE itemcode='" . $db->real_escape_string($itemcode) . "'", $db);
        if (DB_num_rows($check) > 0) {
            // Record exists — UPDATE
            $sets = [];
            foreach ($cols as $c) {
                if ($c == 'itemcode') continue;
                if (!isset($assoc[$c])) continue;
                $dbcol  = $map[$c] ?? $c;
                $sets[] = "`$dbcol`='" . $db->real_escape_string($assoc[$c]) . "'";
            }
            if (!empty($sets)) {
                $sql = "UPDATE creditors SET " . implode(',', $sets) . " WHERE itemcode='" . $db->real_escape_string($itemcode) . "'";
                DB_query($sql, $db);
            }
            return ['success' => true];
        }
    }

    if ($itemcode == '') {
        $itemcode = Triger_creditors($supplierName);
    }

    $columns = [];
    $values  = [];
    foreach ($cols as $c) {
        $dbcol = $map[$c] ?? $c;
        if ($c == 'itemcode') {
            $columns[] = 'itemcode';
            $values[]  = "'" . $db->real_escape_string($itemcode) . "'";
            continue;
        }
        $columns[] = "`$dbcol`";
        $values[]  = "'" . $db->real_escape_string($assoc[$c] ?? '') . "'";
    }
    $sql = "INSERT INTO creditors (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
    DB_query($sql, $db);
    return ['success' => true];
}

function import_stock_row($assoc) {
    global $db;

    // No CSV-to-DB name differences for stock — $map is empty
    $map  = [];

    $cols = ['itemcode','barcode','descrip','postinggroup','sellingprice','reorderlevel','eoq','category','units','inactive','container','isstock_1','isstock_2','isstock_3','isstock_4','isstock_5','isstock_6','production','isstock'];

    $descrip = trim($assoc['descrip'] ?? '');
    if ($descrip === '') return ['success' => false, 'error' => _('Missing stock description')];

    $itemcode = trim($assoc['itemcode'] ?? '');
    if ($itemcode != '') {
        $check = DB_query("SELECT itemcode FROM stockmaster WHERE itemcode='" . $db->real_escape_string($itemcode) . "'", $db);
        if (DB_num_rows($check) > 0) {
            // Record exists — UPDATE
            $sets = [];
            foreach ($cols as $c) {
                if ($c == 'itemcode') continue;
                if (!isset($assoc[$c])) continue;
                $dbcol  = $map[$c] ?? $c;
                $sets[] = "`$dbcol`='" . $db->real_escape_string($assoc[$c]) . "'";
            }
            if (!empty($sets)) {
                $sql = "UPDATE stockmaster SET " . implode(',', $sets) . " WHERE itemcode='" . $db->real_escape_string($itemcode) . "'";
                DB_query($sql, $db);
            }
            return ['success' => true];
        }
    }

    if ($itemcode == '') {
        $itemcode = Triger_stockmaster($descrip);
    }

    $columns = [];
    $values  = [];
    foreach ($cols as $c) {
        $dbcol = $map[$c] ?? $c;
        if ($c == 'itemcode') {
            $columns[] = 'itemcode';
            $values[]  = "'" . $db->real_escape_string($itemcode) . "'";
            continue;
        }
        $columns[] = "`$dbcol`";
        $values[]  = "'" . $db->real_escape_string($assoc[$c] ?? '') . "'";
    }
    $sql = "INSERT INTO stockmaster (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
    DB_query($sql, $db);
    return ['success' => true];
}

function import_fixedasset_row($assoc) {
    global $db;

    // No CSV-to-DB name differences for fixed assets — $map is empty
    $map  = [];

    $cols = ['assetid','description','longdescription','assetcategoryid','assetlocation','depntype','depnrate','barcode','serialno'];

    $desc = trim($assoc['description'] ?? '');
    if ($desc === '') return ['success' => false, 'error' => _('Missing asset description')];

    $assetid = trim($assoc['assetid'] ?? '');
    if ($assetid != '') {
        $check = DB_query("SELECT assetid FROM fixedassets WHERE assetid='" . $db->real_escape_string($assetid) . "'", $db);
        if (DB_num_rows($check) > 0) {
            // Record exists — UPDATE
            $sets = [];
            foreach ($cols as $c) {
                if ($c == 'assetid') continue;
                if (!isset($assoc[$c])) continue;
                $dbcol  = $map[$c] ?? $c;
                $sets[] = "`$dbcol`='" . $db->real_escape_string($assoc[$c]) . "'";
            }
            if (!empty($sets)) {
                $sql = "UPDATE fixedassets SET " . implode(',', $sets) . " WHERE assetid='" . $db->real_escape_string($assetid) . "'";
                DB_query($sql, $db);
            }
            return ['success' => true];
        }
    }

    // Fixed assets have no auto-generate trigger — insert without assetid
    $columns = [];
    $values  = [];
    foreach ($cols as $c) {
        if ($c == 'assetid') continue;
        $dbcol     = $map[$c] ?? $c;
        $columns[] = "`$dbcol`";
        $values[]  = "'" . $db->real_escape_string($assoc[$c] ?? '') . "'";
    }
    $sql = "INSERT INTO fixedassets (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
    DB_query($sql, $db);
    return ['success' => true];
}

/**
 * Clean string values: strip non-breaking spaces (\xA0), zero-width chars, and trim.
 */
function clean_str($val) {
    $enc = mb_detect_encoding($val, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
    if ($enc && $enc !== 'UTF-8') {
        $val = mb_convert_encoding($val, 'UTF-8', $enc);
    }
    // Strip control chars except \t \r \n
    $val = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $val);
    return trim($val);
}

/**
 * Resolve a location description (text) to a fixedassetlocations.locationid.
 * Creates a new location record if none matches.
 */
function resolve_asset_location($locText) {
    global $db;
    $locText = trim($locText);
    if ($locText === '') return '';

    $locTextSafe = $db->real_escape_string($locText);
    $res = DB_query("SELECT locationid FROM fixedassetlocations WHERE locationdescription='" . $locTextSafe . "'", $db);
    if (DB_num_rows($res) > 0) {
        $row = DB_fetch_array($res);
        return $row['locationid'];
    }

    // Create new location – generate ID from first 4 chars of description + 2 digits
    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($locText, 0, 4)));
    if (strlen($prefix) < 2) $prefix = 'LOC';
    $prefix = substr($prefix, 0, 4);
    $nextRes = DB_query("SELECT CONCAT('" . $prefix . "', LPAD(IFNULL(MAX(CAST(SUBSTRING(locationid,5) AS UNSIGNED))+1,1),2,'0')) AS newid FROM fixedassetlocations WHERE locationid LIKE '" . $prefix . "%'", $db);
    $nextRow = DB_fetch_array($nextRes);
    $newId = $nextRow['newid'];

    DB_query("INSERT INTO fixedassetlocations (locationid, locationdescription) VALUES ('" . $newId . "','" . $locTextSafe . "')", $db);
    return $newId;
}

/**
 * Get or create a default asset category for register imports.
 */
function get_default_asset_category() {
    global $db;
    $res = DB_query("SELECT categoryid FROM fixedassetcategories LIMIT 1", $db);
    if (DB_num_rows($res) > 0) {
        $row = DB_fetch_array($res);
        return $row['categoryid'];
    }
    // No categories exist – create a default one (note: GL accounts must exist)
    DB_query("INSERT INTO fixedassetcategories (categoryid, categorydescription, costact, depnact, disposalact, accumdepnact)
              VALUES ('EQUIP', 'Equipment', '0', '0', '0', '0')", $db);
    return 'EQUIP';
}

function import_assetregister_row($assoc) {
    global $db;

    $desc = clean_str($assoc['equipment_name'] ?? '');
    if ($desc === '') {
        $desc = _('Unnamed Equipment');
    }
    $desc = mb_substr($desc, 0, 50);

    $locationId = resolve_asset_location(clean_str($assoc['location'] ?? ''));

    $quantity = 1;
    if (!empty($assoc['quantity'])) {
        $quantity = (int)$assoc['quantity'];
        if ($quantity < 1) $quantity = 1;
    }

    $cost = 0;
    if (isset($assoc['cost']) && $assoc['cost'] !== '') {
        $cost = str_replace(',', '', $assoc['cost']);
        if (!is_numeric($cost)) $cost = 0;
        $cost = (float)$cost;
    }

    $equipmentCode = clean_str($assoc['equipment_code'] ?? '');
    if ($equipmentCode != '') {
        $equipmentCode = preg_replace('/[^A-Za-z0-9 _-]/', '', $equipmentCode);
    }

    $manufacturer = clean_str($assoc['manufacturer'] ?? '');
    if ($manufacturer != '') {
        $manufacturer = mb_substr($manufacturer, 0, 100);
    }

    $serialNo = clean_str($assoc['serial_no'] ?? '');
    $serialNo = mb_substr($serialNo, 0, 30);

    $modelNo = clean_str($assoc['model_no'] ?? '');
    $modelNo = mb_substr($modelNo, 0, 50);

    $status = clean_str($assoc['status'] ?? '');

    $remarks = clean_str($assoc['remarks'] ?? '');

    $datePurchased = '';
    $dp = clean_str($assoc['date_purchased'] ?? '');
    if ($dp != '') {
        $dpFormatted = FormatDateForSQL($dp);
        if ($dpFormatted !== '') {
            $datePurchased = $dpFormatted;
        }
    }

    $defaultCat = get_default_asset_category();

    // Check if assetid was provided → UPDATE that specific record
    $assetId = trim($assoc['assetid'] ?? '');
    if ($assetId !== '') {
        $check = DB_query("SELECT assetid FROM fixedassets WHERE assetid='" . $db->real_escape_string($assetId) . "'", $db);
        if (DB_num_rows($check) > 0) {
            $sets = [];
            $sets[] = "description='" . $db->real_escape_string($desc) . "'";
            $sets[] = "quantity=" . $quantity;
            $sets[] = "cost=" . $cost;
            if ($equipmentCode != '')  $sets[] = "equipment_code='" . $db->real_escape_string($equipmentCode) . "'";
            if ($locationId != '')     $sets[] = "assetlocation='" . $db->real_escape_string($locationId) . "'";
            if ($manufacturer != '')   $sets[] = "manufacturer='" . $db->real_escape_string($manufacturer) . "'";
            if ($serialNo != '')       $sets[] = "serialno='" . $db->real_escape_string($serialNo) . "'";
            if ($modelNo != '')        $sets[] = "modelno='" . $db->real_escape_string($modelNo) . "'";
            if ($status != '')         $sets[] = "status='" . $db->real_escape_string($status) . "'";
            if ($remarks != '')        $sets[] = "remarks='" . $db->real_escape_string($remarks) . "'";
            if ($datePurchased != '')   $sets[] = "datepurchased='" . $db->real_escape_string($datePurchased) . "'";
            $sql = "UPDATE fixedassets SET " . implode(',', $sets) . " WHERE assetid='" . $db->real_escape_string($assetId) . "'";
            DB_query($sql, $db);
            if (DB_error_no($db) > 0) {
                return ['success' => false, 'error' => _('DB error updating asset #') . $assetId];
            }
            return ['success' => true];
        }
    }

    // No assetid (or assetid not found) → always INSERT as new item
    $columns = ['description','assetcategoryid','depntype','depnrate','serialno'];
    $values = ["'" . $db->real_escape_string($desc) . "'","'" . $db->real_escape_string($defaultCat) . "'",'1','0',"'" . $db->real_escape_string($serialNo) . "'"];

    $columns[] = 'quantity'; $values[] = $quantity;

    if ($equipmentCode != '') {
        $columns[] = 'equipment_code';
        $values[] = "'" . $db->real_escape_string($equipmentCode) . "'";
    }
    if ($locationId != '') {
        $columns[] = 'assetlocation';
        $values[] = "'" . $db->real_escape_string($locationId) . "'";
    }
    if ($manufacturer != '') {
        $columns[] = 'manufacturer';
        $values[] = "'" . $db->real_escape_string($manufacturer) . "'";
    }
    if ($datePurchased != '') {
        $columns[] = 'datepurchased';
        $values[] = "'" . $db->real_escape_string($datePurchased) . "'";
    }
    if ($modelNo != '') {
        $columns[] = 'modelno';
        $values[] = "'" . $db->real_escape_string($modelNo) . "'";
    }
    if ($status != '') {
        $columns[] = 'status';
        $values[] = "'" . $db->real_escape_string($status) . "'";
    }
    $columns[] = 'cost'; $values[] = $cost;
    if ($remarks != '') {
        $columns[] = 'remarks';
        $values[] = "'" . $db->real_escape_string($remarks) . "'";
    }

    $sql = "INSERT INTO fixedassets (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
    DB_query($sql, $db);
    if (DB_error_no($db) > 0) {
        return ['success' => false, 'error' => _('DB error inserting asset') . ' ' . $equipmentCode];
    }
    return ['success' => true];
}