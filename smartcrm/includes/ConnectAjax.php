<?php
/* ConnectDB_mysql.inc â€” PHP 7.0+ / MySQL (mysqli) version
 * Replaces the original ODBC/MS-SQL connection and wrapper functions.
 */

define('LIKE', 'LIKE');

session_write_close(); // close any previous session before starting a new one
session_name('ErpWithCRM');
session_start();

include('../config.php');

global $db;

// ---------------------------------------------------------------------------
// Database connection â€” mysqli replaces odbc_connect
// ---------------------------------------------------------------------------
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);

if (!$db) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($db, 'utf8');

// ---------------------------------------------------------------------------
// DB wrapper functions  (drop-in replacements for the odbc_* originals)
// ---------------------------------------------------------------------------

/**
 * Execute a SQL statement and return the result.
 *
 * @param  string        $SQL
 * @param  mysqli        $Conn
 * @return mysqli_result|bool
 */
function DB_query($SQL, $Conn)
{
    $result = mysqli_query($Conn, $SQL);
    if ($result === false) {
        // Surface query errors during development; replace with proper logging in production.
        trigger_error('DB_query failed: ' . mysqli_error($Conn) . ' | SQL: ' . $SQL, E_USER_WARNING);
    }
    return $result;
}

/**
 * Fetch the next row as an associative array.
 *
 * @param  mysqli_result $ResultIndex
 * @return array|null
 */
function DB_fetch_array($ResultIndex)
{
    return mysqli_fetch_assoc($ResultIndex);
}

/**
 * Return the number of rows in a result set.
 *
 * @param  mysqli_result $ResultIndex
 * @return int
 */
function DB_num_rows($ResultIndex)
{
    return mysqli_num_rows($ResultIndex);
}

/**
 * Fetch the next row as a zero-indexed numeric array.
 * Mirrors the original odbc_fetch_row / odbc_result loop.
 *
 * @param  mysqli_result $ResultIndex
 * @return array
 */
function DB_fetch_row($ResultIndex)
{
    $row = mysqli_fetch_row($ResultIndex);
    return $row ? $row : array();
}

// ---------------------------------------------------------------------------
// Company record â€” loaded into session on every request
// ---------------------------------------------------------------------------
$sql = "SELECT
            c.coycode, c.coyname, c.PIN, c.vat,
            c.regoffice1, c.regoffice2, c.regoffice3,
            c.regoffice4, c.regoffice5, c.regoffice6,
            c.telephone, c.fax, c.email, c.currencydefault,
            cu.decimalplaces
        FROM companies c
        INNER JOIN currencies cu ON c.currencydefault = cu.currabrev
        WHERE c.coycode = 1";

$ReadCoyResult = DB_query($sql, $db);
if (DB_num_rows($ReadCoyResult) > 0) {
    $_SESSION['CompanyRecord'] = DB_fetch_array($ReadCoyResult);
}

// ---------------------------------------------------------------------------
// filtercustomer â€” search customers by name (used on credit-note screen)
// ---------------------------------------------------------------------------
if (isset($_GET['filtercustomer'])) {
    filtercustomer($_GET['offset'], $_GET['height'], $_GET['filtercustomer']);
} elseif (isset($_POST['filtercustomer'])) {
    filtercustomer($_POST['offset'], $_POST['height'], $_POST['filtercustomer']);
}

function filtercustomer($offset, $height, $value)
{
    global $db;

    $SearchString = '%' . mysqli_real_escape_string($db, str_replace(' ', '%', substr($value, 0, 2))) . '%';

    if (mb_strlen(trim($value)) > 0) {
        $ResultIndex = DB_query(
            "SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
             FROM debtors
             WHERE inactive = 0
               AND customer LIKE '$SearchString'
             ORDER BY customer",
            $db
        );
    } else {
        $ResultIndex = DB_query(
            "SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
             FROM debtors
             WHERE inactive = 0
             ORDER BY customer",
            $db
        );
    }

    $top  = $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="findcustomer" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search Customers:</b><div class="finder">'
            . '<table id="mycustomersTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr><td class="cell" onclick="filtercustomer(\'%s\',\'%s\')">%s</td></tr>',
            trim($row['itemcode']),
            trim($row['customer']),
            trim($row['customer'])
        );
    }

    $return .= '</table></div>'
             . '<input type="text" class="myInput" id="mycustomersInput" onkeyup="mycustomersFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="filtercustomer(\'\',\'\')" value="Cant find Account"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// vatrefresh â€” show sales orders for a customer (credit-note lookup)
// ---------------------------------------------------------------------------
if (isset($_GET['vatrefresh'])) {
    vatrefresh($_GET['vatrefresh']);
} elseif (isset($_POST['vatrefresh'])) {
    vatrefresh($_POST['vatrefresh']);
}

function vatrefresh($CustomerID)
{
    global $db;

    $CustomerID = mysqli_real_escape_string($db, $CustomerID);

    $SQL = "SELECT
                sh.documentno,
                sh.docdate,
                sh.oderdate,
                sh.duedate,
                sh.customercode,
                sh.customername,
                sh.currencycode,
                sh.salespersoncode,
                sh.status,
                sh.userid,
                SUM(sl.invoiceamount) AS OrderValue
            FROM SalesHeader sh
            JOIN SalesLine sl ON sh.documentno = sl.documentno
            WHERE sh.documenttype = '10'
              AND sh.customercode = '$CustomerID'
            GROUP BY
                sh.documentno, sh.docdate, sh.oderdate, sh.duedate,
                sh.customercode, sh.customername, sh.currencycode,
                sh.salespersoncode, sh.status, sh.userid
            ORDER BY sh.docdate DESC";

    $Result = DB_query($SQL, $db);

    $Echo = '<table class="table table-bordered" id="salesoderslist">'
          . '<tr>'
          . '<th>Action</th>'
          . '<th>Date</th>'
          . '<th>Customer<br/>ID</th>'
          . '<th>Customer<br/>Name</th>'
          . '<th>Sales Order<br/>Value</th>'
          . '<th>Currency</th>'
          . '<th>Authorisation<br/>Status</th>'
          . '<th>Created<br/>By</th>'
          . '</tr>';

    while ($row = DB_fetch_array($Result)) {
        $Echo .= '<tr>';
        $Echo .= sprintf('<td><input type="checkbox" name="ref[%s]" />%s</td>', $row['documentno'], $row['documentno']);
        $Echo .= sprintf('<td>%s</td>', is_null($row['docdate']) ? '' : $row['docdate']);
        $Echo .= sprintf('<td>%s</td>', $row['customercode']);
        $Echo .= sprintf('<td>%s</td>', $row['customername']);
        $Echo .= sprintf('<td>%s</td>', number_format($row['OrderValue'], 2));
        $Echo .= sprintf('<td>%s</td>', $row['currencycode']);
        $Echo .= sprintf('<td>%s</td>', $row['status'] == 2 ? 'Approved' : '');
        $Echo .= sprintf('<td>%s</td>', $row['userid']);
        $Echo .= '</tr>';
    }

    $Echo .= '</table>';
    $Echo .= '<div class="centre">'
           . '<input type="submit" name="confirm" value="' . _('Proceed') . '" '
           . 'onclick="return confirm(\'' . _('Are you sure you wish to create this Credit Note ?') . '\');" />'
           . '</div>';

    echo $Echo;
}

// ---------------------------------------------------------------------------
// getcustomers â€” full customer finder (select + populate form fields)
// ---------------------------------------------------------------------------
if (isset($_GET['Customerfind'])) {
    getcustomers($_GET['offset'], $_GET['height'], $_GET['Customerfind']);
} elseif (isset($_POST['Customerfind'])) {
    getcustomers($_POST['offset'], $_POST['height'], $_POST['Customerfind']);
}

function getcustomers($offset, $height, $value)
{
    global $db;

    $SearchString = '%' . mysqli_real_escape_string($db, str_replace(' ', '%', substr($value, 0, 2))) . '%';

    if (mb_strlen(trim($value)) > 0) {
        $ResultIndex = DB_query(
            "SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
             FROM debtors
             WHERE inactive = 0
               AND customer LIKE '$SearchString'
             ORDER BY customer",
            $db
        );
    } else {
        $ResultIndex = DB_query(
            "SELECT itemcode, customer, curr_cod, IFNULL(salesman,'') AS salesman
             FROM debtors
             WHERE inactive = 0
             ORDER BY customer",
            $db
        );
    }

    $top  = $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="findcustomer" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search Customers:</b><div class="finder">'
            . '<table id="mycustomersTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr><td class="cell" onclick="selectcustomer(\'%s\',\'%s\',\'%s\',\'%s\')">%s</td></tr>',
            trim($row['itemcode']),
            trim($row['customer']),
            trim($row['curr_cod']),
            trim($row['salesman']),
            trim($row['customer'])
        );
    }

    $return .= '</table></div>'
             . '<input type="text" class="myInput" id="mycustomersInput" onkeyup="mycustomersFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="selectcustomer(\'\',\'\',\'\',\'not\')" value="Cant Find Account"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// Chartfind â€” GL account lookup
// ---------------------------------------------------------------------------
if (isset($_GET['Chartfind'])) {
    Chartfind($_GET['offset'], $_GET['height'], $_GET['Chartfind']);
} elseif (isset($_POST['Chartfind'])) {
    Chartfind($_POST['offset'], $_POST['height'], $_POST['Chartfind']);
}

function Chartfind($offset, $height, $value)
{
    global $db;

    $AccountType = array(
        0 => 'Posting',
        1 => 'Heading',
        2 => 'Total',
        3 => 'Begin-Total',
        4 => 'End-Total',
    );

    $BalanceSheet = array(
        0 => 'Balance Sheet',
        1 => 'Profit and Loss',
    );

    if ($value == 'reload') {
        $java        = 'ReloadForm(Journal.update);';
        $ResultIndex = DB_query(
            "SELECT a.accno, a.accdesc, a.ReportCode, a.ReportStyle, a.balance_income
             FROM acct a
             JOIN GLpostinggroup g ON g.code = a.postinggroup
             ORDER BY a.ReportCode, a.accdesc",
            $db
        );
    } else {
        $java        = '';
        $ResultIndex = DB_query(
            "SELECT accno, accdesc, ReportCode, ReportStyle, balance_income
             FROM acct
             ORDER BY ReportCode, accdesc",
            $db
        );
    }

    $top  = $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="findSchart" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search for Account:</b><div class="finder">'
            . '<table id="myAccountTable" class="table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $accdesc = trim($row['accdesc']);
        if ($row['ReportStyle'] == 0) {
            $toclick = sprintf(
                '<td onclick="selectaccount(\'%s\',\'%s\');%s">%s</td>',
                trim($row['accno']), $accdesc, $java, $accdesc
            );
        } else {
            $toclick = sprintf(
                '<td onclick="SmartDialog.warning(\'This Account is not a posting account\', \'Warning\');">%s</td>',
                $accdesc
            );
        }
        $return .= sprintf(
            '<tr>%s<td>%s</td><td>%s</td></tr>',
            $toclick,
            $BalanceSheet[$row['balance_income']],
            $AccountType[$row['ReportStyle']]
        );
    }

    $return .= '</table></div>'
             . '<input type="text" tabindex="1" class="myInput" id="myAccountInput" onkeyup="myAccountFunction()" autofocus="autofocus" placeholder="Search for account..">'
             . '<input type="button" onclick="findSchart.setAttribute(\'style\',\'visibility:hidden;display:none\');" value="Cancel"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// GetVendors â€” supplier / creditor lookup
// ---------------------------------------------------------------------------
if (isset($_GET['Vendorfind'])) {
    GetVendors($_GET['offset'], $_GET['height'], $_GET['Vendorfind']);
} elseif (isset($_POST['Vendorfind'])) {
    GetVendors($_POST['offset'], $_POST['height'], $_POST['Vendorfind']);
}

function GetVendors($offset, $height, $value)
{
    global $db;

    $SearchString = '%' . mysqli_real_escape_string($db, str_replace(' ', '%', substr($value, 0, 2))) . '%';

    if (mb_strlen(trim($value)) > 0) {
        $ResultIndex = DB_query(
            "SELECT itemcode, customer, curr_cod
             FROM creditors
             WHERE inactive = 0
               AND customer LIKE '$SearchString'
             ORDER BY customer",
            $db
        );
    } else {
        $ResultIndex = DB_query(
            "SELECT itemcode, customer, curr_cod
             FROM creditors
             WHERE inactive = 0
             ORDER BY customer",
            $db
        );
    }

    $top  = $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="findVendor" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search Venders or Suppliers:</b><div class="finder">'
            . '<table id="myVendorTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr><td onclick="selectvendor(\'%s\',\'%s\',\'%s\')">%s</td></tr>',
            trim($row['itemcode']),
            trim($row['customer']),
            trim($row['curr_cod']),
            trim($row['customer'])
        );
    }

    $return .= '</table></div>'
             . '<input type="text" tabindex="1" class="myInput" id="myVendorInput" onkeyup="myVendorFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="selectvendor(\'\',\'\',\'\',\'not\')" value="Cancel"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// ShowStockUOM â€” return unit-of-measure options for a stock item
// ---------------------------------------------------------------------------
if (isset($_GET['stocktransferitemcode'])) {
    ShowStockUOM($_GET['stocktransferitemcode']);
} elseif (isset($_POST['stocktransferitemcode'])) {
    ShowStockUOM($_POST['stocktransferitemcode']);
}

function ShowStockUOM($stockcode)
{
    global $db;

    $stockcode = mysqli_real_escape_string($db, $stockcode);

    $Results = DB_query(
        "SELECT f.descrip AS fulqty, l.descrip AS loosqty
         FROM stockmaster
         LEFT JOIN unit f ON stockmaster.units = f.code
         LEFT JOIN unit l ON stockmaster.units = l.code
         WHERE itemcode = '$stockcode'",
        $db
    );

    $rows    = DB_fetch_row($Results);
    $UOMline = '<option value="fulqty">'  . $rows[0] . '</option>'
             . '<option value="loosqty">' . $rows[1] . '</option>';

    echo $UOMline;
}

// ---------------------------------------------------------------------------
// Jobfind â€” service / job item lookup
// ---------------------------------------------------------------------------
if (isset($_GET['Jobfind'])) {
    Jobfind($_GET['offset'], $_GET['height'], $_GET['Jobfind']);
} elseif (isset($_POST['Jobfind'])) {
    Jobfind($_POST['offset'], $_POST['height'], $_POST['Jobfind']);
}

function Jobfind($offset, $height, $value)
{
    global $db;

    $ResultIndex = DB_query(
        "SELECT itemcode, descrip
         FROM stockmaster
         WHERE inactive = 0 AND isstock_3 = 1
         ORDER BY descrip",
        $db
    );

    $top  = $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="findJob" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search for services offered:</b><div class="finder">'
            . '<table id="myStockTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr><td onclick="selectservice(\'%s\',\'%s\');CalculateForm(salesform.submit)">%s</td></tr>',
            trim($row['itemcode']),
            trim($row['descrip']),
            trim($row['descrip'])
        );
    }

    $return .= '</table></div>'
             . '<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="selectservice(\'\',\'\')" value="Cancel"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// Stockfind â€” inventory item lookup with selling price
// ---------------------------------------------------------------------------
if (isset($_GET['Stockfind'])) {
    Stockfind($_GET['offset'], $_GET['height'], $_GET['Stockfind']);
} elseif (isset($_POST['Stockfind'])) {
    Stockfind($_POST['offset'], $_POST['height'], $_POST['Stockfind']);
}

function Stockfind($offset, $height, $value)
{
    global $db;

    $top  = $offset['top'] - 250;
    $left = $offset['left'];

    $ResultIndex = DB_query(
        "SELECT sm.itemcode, sm.descrip, IFNULL(sm.sellingprice, 0) AS sellingprice,
                u.descrip AS UOM
         FROM stockmaster sm
         LEFT JOIN unit u ON sm.units = u.code
         WHERE sm.inactive = 0
         ORDER BY sm.descrip",
        $db
    );

    $return = '<div class="finderheader" id="findStock" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search for Inventory:</b><div class="finder">'
            . '<table id="myStockTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr>'
            . '<td onclick="selectInventory(\'%s\',\'%s\');CalculateForm(salesform.submit)">%s</td>'
            . '<td>%s</td>'
            . '<td class="number">%s</td>'
            . '</tr>',
            trim($row['itemcode']),
            trim($row['descrip']),
            trim($row['descrip']),
            trim($row['UOM']),
            $row['sellingprice']
        );
    }

    $return .= '</table></div>'
             . '<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="selectInventory(\'\',\'\')" value="Cancel"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// Assetfind â€” fixed-asset lookup
// ---------------------------------------------------------------------------
if (isset($_GET['Assetfind'])) {
    Assetfind($_GET['offset'], $_GET['height'], $_GET['Assetfind']);
} elseif (isset($_POST['Assetfind'])) {
    Assetfind($_POST['offset'], $_POST['height'], $_POST['Assetfind']);
}

function Assetfind($offset, $height, $value)
{
    global $db;

    $ResultIndex = DB_query(
        "SELECT assetid, description, longdescription
         FROM fixedassets
         ORDER BY description",
        $db
    );

    $top  = $height + $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="AssetStock" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search Assets and equipment:</b><div class="finder">'
            . '<table id="myAssetTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr><td onclick="selectFixedassets(\'%s\',\'%s\');CalculateForm(salesform.submit)"><label>%s</label></td></tr>',
            trim($row['assetid']),
            trim($row['description']),
            trim($row['description'])
        );
    }

    $return .= '</table></div>'
             . '<input type="text" class="myInput" id="myAssetInput" onkeyup="myAssetFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="selectFixedassets(\'\',\'\')" value="Cancel"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// EmployeeNamefind â€” production employee lookup
// ---------------------------------------------------------------------------
if (isset($_GET['EmployeeNamefind'])) {
    EmployeeNamefind($_GET['offset'], $_GET['height'], $_GET['EmployeeNamefind']);
} elseif (isset($_POST['EmployeeNamefind'])) {
    EmployeeNamefind($_POST['offset'], $_POST['height'], $_POST['EmployeeNamefind']);
}

function EmployeeNamefind($offset, $height, $value)
{
    global $db;

    $ResultIndex = DB_query(
        "SELECT code, salesman FROM productionEmployee",
        $db
    );

    $top  = $offset['top'];
    $left = $offset['left'];

    $return = '<div class="finderheader" id="EmployeeNamefind" style="top:' . $top . 'px; left:' . $left . 'px;">'
            . '<b>Search for employee:</b><div class="finder">'
            . '<table id="myEmployeeTable" class="table table-bordered">';

    while ($row = DB_fetch_array($ResultIndex)) {
        $return .= sprintf(
            '<tr><td onclick="selectemployee(\'%s\',\'%s\');CalculateForm(salesform.submit)">%s</td></tr>',
            trim($row['code']),
            trim($row['salesman']),
            trim($row['salesman'])
        );
    }

    $return .= '</table></div>'
             . '<input type="text" tabindex="1" class="myInput" id="myEmployeeInput" onkeyup="myEmployeeFunction()" autofocus="autofocus" placeholder="Search for names..">'
             . '<input type="button" onclick="selectemployee(\'\',\'\')" value="Cancel"/>'
             . '</div>';

    echo $return;
}

// ---------------------------------------------------------------------------
// Checkwhenpaid â€” run checkInvoiceWhenpaid for all CustomerStatement rows
// The original called a MS-SQL stored procedure; replaced with a MySQL CALL.
// ---------------------------------------------------------------------------
if (isset($_GET['Checkwhenpaid']) || isset($_POST['Checkwhenpaid'])) {
    Checkwhenpaid();
}

/**
 * Iterate every row in CustomerStatement and mark each invoice
 * with the date it was fully paid via checkInvoiceWhenpaid().
 */
function Checkwhenpaid()
{
    global $db;

    $ResultIndex = DB_query(
        "SELECT Accountno, JournalNo FROM CustomerStatement",
        $db
    );

    while ($rows = DB_fetch_array($ResultIndex)) {
        checkInvoiceWhenpaid($rows['Accountno'], $rows['JournalNo']);
    }

    echo 'Done';
}

/**
 * Mark an invoice as paid (sets CustomerStatement.Datewhenpaid) once
 * the sum of all receipt allocations covers the gross invoice amount.
 *
 * Logic ported from MS-SQL stored procedure dbo.CheckInvoiceWhenpaid:
 *
 *  1. Sum all ReceiptsAllocation.amount for this account + journal  â†’ $balance
 *  2. Sum all CustomerStatement.grossamount for this account + journal â†’ $grossAmount
 *  3. If ($grossAmount + $balance) <= 1  (i.e. fully settled within rounding):
 *       - Find the latest receipt date for this journal
 *       - Update CustomerStatement.Datewhenpaid to whichever is later:
 *           the invoice date (CustomerStatement.Date) or the receipt date
 *
 * @param  string $accountno   Debtor account code
 * @param  string $journalno   Journal / invoice reference
 * @return void
 */
function checkInvoiceWhenpaid($accountno, $journalno)
{
    global $db;

    $accountno = mysqli_real_escape_string($db, $accountno);
    $journalno = mysqli_real_escape_string($db, $journalno);

    // Step 1 â€” sum of receipt allocations for this invoice (negative values reduce balance)
    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(amount), 0) AS balance
         FROM ReceiptsAllocation
         WHERE itemcode  = '$accountno'
           AND journalno = '$journalno'",
        $db
    ));
    $balance = (float)$row['balance'];

    // Step 2 â€” gross invoice amount(s) on the statement
    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(grossamount), 0) AS grossamount
         FROM CustomerStatement
         WHERE Accountno = '$accountno'
           AND JournalNo = '$journalno'",
        $db
    ));
    $grossAmount = (float)$row['grossamount'];

    // Step 3 â€” only update when the invoice is fully settled (within Â£/$/1 rounding tolerance)
    if (($grossAmount + $balance) <= 1) {

        // Latest receipt date for this journal
        $row = DB_fetch_array(DB_query(
            "SELECT date AS datepaid
             FROM ReceiptsAllocation
             WHERE journalno = '$journalno'
               AND itemcode  = '$accountno'
             ORDER BY date DESC
             LIMIT 1",
            $db
        ));
        $datepaid = $row ? $row['datepaid'] : null;

        if ($datepaid !== null) {
            $datepaid = mysqli_real_escape_string($db, $datepaid);

            // Set Datewhenpaid = GREATEST(invoice date, receipt date)
            // mirrors the original CASE WHEN Date > @datepaid THEN Date ELSE @datepaid END
            DB_query(
                "UPDATE CustomerStatement
                 SET    Datewhenpaid = GREATEST(`Date`, '$datepaid')
                 WHERE  JournalNo = '$journalno'
                   AND  Accountno = '$accountno'",
                $db
            );
        }
    }
}
