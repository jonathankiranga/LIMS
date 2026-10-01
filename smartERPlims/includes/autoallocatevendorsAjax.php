<?php
/**
 * autoallocate_mysql.php — PHP 7.0+ / MySQL (mysqli)
 *
 * Faithful port of the following MS-SQL stored procedures and UDFs:
 *   dbo.autoallocatedebtors       → autoallocatedebtors()
 *   dbo.autoallocatevendors       → autoallocatevendors()
 *   dbo.AutoAllocateALLcustomers  → AutoAllocateALLcustomers()
 *   dbo.AutoAllocateALLsuppliers  → AutoAllocateALLsuppliers()
 *   dbo.CheckInvoiceWhenpaid      → checkInvoiceWhenpaid()
 *   dbo.ageingCustomers           → ageingCustomers()
 *   dbo.ageingSuppliers           → ageingSuppliers()
 *   dbo.ReceiptAllocations UDF    → ReceiptAllocations()
 *   dbo.PaymentsAllocations UDF   → PaymentsAllocations()
 *   dbo.InvoiceAllocations UDF    → InvoiceAllocations()   (implied by debtors proc)
 *   dbo.BillAllocations UDF       → BillAllocations()      (implied by vendors proc)
 *
 * ReceiptsAllocation columns:
 *   itemcode, date, invoiceno, journalno (= invoice journal),
 *   doctype, receiptno, amount, receiptjournal (= receipt journal)
 *
 * PaymentsAllocation columns:
 *   itemcode, date, invoiceno, journalno (= invoice journal),
 *   doctype, receiptno, amount, receiptjournal (= payment journal)
 */

define('LIKE', 'LIKE');

session_write_close();
session_name('ErpWithCRM');
session_start();

include('../config.php');

global $db, $database;

// ---------------------------------------------------------------------------
// Connection
// ---------------------------------------------------------------------------
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);

if (!$db) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($db, 'utf8');

// ---------------------------------------------------------------------------
// DB wrapper functions
// ---------------------------------------------------------------------------

function DB_query($SQL, $Conn)
{
    $result = mysqli_query($Conn, $SQL);
    if ($result === false) {
        trigger_error('DB_query failed: ' . mysqli_error($Conn) . ' | SQL: ' . $SQL, E_USER_WARNING);
    }
    return $result;
}

function DB_fetch_array($ResultIndex)
{
    return mysqli_fetch_assoc($ResultIndex);
}

function DB_num_rows($ResultIndex)
{
    return mysqli_num_rows($ResultIndex);
}

function DB_fetch_row($ResultIndex)
{
    $row = mysqli_fetch_row($ResultIndex);
    return $row ? $row : array();
}

// ---------------------------------------------------------------------------
// Company record
// ---------------------------------------------------------------------------
$sql = "SELECT coycode, coyname, PIN, vat,
               regoffice1, regoffice2, regoffice3, regoffice4, regoffice5, regoffice6,
               telephone, fax, email, currencydefault, currencies.decimalplaces
        FROM   companies
        INNER JOIN currencies ON companies.currencydefault = currencies.currabrev
        WHERE  coycode = 1";

$ReadCoyResult = DB_query($sql, $db);
if (DB_num_rows($ReadCoyResult) > 0) {
    $_SESSION['CompanyRecord'] = DB_fetch_array($ReadCoyResult);
}

// ---------------------------------------------------------------------------
// Request routing
// ---------------------------------------------------------------------------

if (isset($_GET['autoallocatevendors'])) {
    autoallocatevendors($_GET['autoallocatevendors']);
} elseif (isset($_POST['autoallocatevendors'])) {
    autoallocatevendors($_POST['autoallocatevendors']);
}

if (isset($_GET['autoallocatedebtors'])) {
    autoallocatedebtors($_GET['autoallocatedebtors']);
} elseif (isset($_POST['autoallocatedebtors'])) {
    autoallocatedebtors($_POST['autoallocatedebtors']);
}

if (isset($_GET['autoallocateall']) || isset($_POST['autoallocateall'])) {
    AutoAllocateALLcustomers();
    AutoAllocateALLsuppliers();
    echo 'All customers and suppliers have been allocated.';
}

// ===========================================================================
// UDF REPLACEMENTS
// These four functions replace the MS-SQL scalar UDFs used inside the
// allocation cursors to calculate already-allocated amounts.
// ===========================================================================

/**
 * dbo.ReceiptAllocations(@journal, @Accountno)
 * SUM of ReceiptsAllocation.amount WHERE receiptjournal = $journal
 * Used in the OUTER cursor of autoallocatedebtors to find receipts
 * that still have unallocated credit remaining.
 *
 * @param  string $journal    receiptjournal column value (the receipt's own journal)
 * @param  string $accountno  itemcode (debtor account)
 * @return float
 */
function ReceiptAllocations($journal, $accountno)
{
    global $db;
    $journal   = mysqli_real_escape_string($db, $journal);
    $accountno = mysqli_real_escape_string($db, $accountno);

    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(amount), 0) AS amount
         FROM ReceiptsAllocation
         WHERE receiptjournal = '$journal'
           AND itemcode       = '$accountno'",
        $db
    ));
    return (float)$row['amount'];
}

/**
 * dbo.InvoiceAllocations(@journal, @Accountno)  [implied by autoallocatedebtors]
 * SUM of ReceiptsAllocation.amount WHERE journalno = $journal
 * Used in the INNER cursor of autoallocatedebtors to find invoices
 * that still have an outstanding balance.
 *
 * @param  string $journal    journalno column value (the invoice's own journal)
 * @param  string $accountno  itemcode (debtor account)
 * @return float
 */
function InvoiceAllocations($journal, $accountno)
{
    global $db;
    $journal   = mysqli_real_escape_string($db, $journal);
    $accountno = mysqli_real_escape_string($db, $accountno);

    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(amount), 0) AS amount
         FROM ReceiptsAllocation
         WHERE journalno = '$journal'
           AND itemcode  = '$accountno'",
        $db
    ));
    return (float)$row['amount'];
}

/**
 * dbo.PaymentsAllocations(@journal, @Accountno)
 * SUM of PaymentsAllocation.amount WHERE receiptjournal = $journal
 * Used in the OUTER cursor of autoallocatevendors to find payments
 * that still have unallocated credit remaining.
 *
 * @param  string $journal    receiptjournal column value (the payment's own journal)
 * @param  string $accountno  itemcode (creditor account)
 * @return float
 */
function PaymentsAllocations($journal, $accountno)
{
    global $db;
    $journal   = mysqli_real_escape_string($db, $journal);
    $accountno = mysqli_real_escape_string($db, $accountno);

    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(amount), 0) AS amount
         FROM PaymentsAllocation
         WHERE receiptjournal = '$journal'
           AND itemcode       = '$accountno'",
        $db
    ));
    return (float)$row['amount'];
}

/**
 * dbo.BillAllocations(@journal, @Accountno)  [implied by autoallocatevendors]
 * SUM of PaymentsAllocation.amount WHERE journalno = $journal
 * Used in the INNER cursor of autoallocatevendors to find bills
 * that still have an outstanding balance.
 *
 * @param  string $journal    journalno column value (the bill's own journal)
 * @param  string $accountno  itemcode (creditor account)
 * @return float
 */
function BillAllocations($journal, $accountno)
{
    global $db;
    $journal   = mysqli_real_escape_string($db, $journal);
    $accountno = mysqli_real_escape_string($db, $accountno);

    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(amount), 0) AS amount
         FROM PaymentsAllocation
         WHERE journalno = '$journal'
           AND itemcode  = '$accountno'",
        $db
    ));
    return (float)$row['amount'];
}

// ===========================================================================
// autoallocatedebtors
//
// Ports dbo.autoallocatedebtors exactly.
//
// Outer loop — receipts where net (Grossamount - ReceiptAllocations) < 0
//   These are customer receipts/credits with remaining unallocated balance.
//   Grossamount is negative for receipts; ReceiptAllocations reduces it further.
//
// Inner loop — invoices where net (Grossamount + InvoiceAllocations) > 0
//   These are open invoices not yet fully cleared.
//   Loop condition: @amountreceipt < 0 (stop when receipt is exhausted)
//
// Two branches per invoice:
//   if (amountreceipt + amountinvoice) <= 0
//       → receipt covers full invoice: insert amount = amountinvoice * -1
//         amountreceipt = amountreceipt + amountinvoice (reduce remaining credit)
//   else
//       → invoice absorbs all remaining receipt: insert amount = amountreceipt
//         amountreceipt = 0 (receipt exhausted)
//
//   After EVERY inner iteration: call checkInvoiceWhenpaid()
// ===========================================================================

/**
 * @param string $accountno  Debtor account code
 */
function autoallocatedebtors($accountno)
{
    global $db;

    echo 'Debtors payment now being allocated';

    $accountno = mysqli_real_escape_string($db, trim($accountno));

    // ---------------------------------------------------------------------------
    // Outer cursor — receipts with remaining unallocated credit
    // Mirrors: SELECT (Grossamount - ISNULL(dbo.ReceiptAllocations(JournalNo, @accountno), 0))
    //          WHERE ... < 0 ORDER BY Date ASC
    // ---------------------------------------------------------------------------
    $receiptsResult = DB_query(
        "SELECT
             (cs.Grossamount - IFNULL(
                 (SELECT SUM(ra.amount)
                  FROM ReceiptsAllocation ra
                  WHERE ra.receiptjournal = cs.JournalNo
                    AND ra.itemcode       = '$accountno'), 0)
             ) AS AMOUNT2,
             cs.JournalNo,
             cs.Documentno,
             cs.Date
         FROM CustomerStatement cs
         WHERE cs.Accountno = '$accountno'
           AND (cs.Grossamount - IFNULL(
                    (SELECT SUM(ra.amount)
                     FROM ReceiptsAllocation ra
                     WHERE ra.receiptjournal = cs.JournalNo
                       AND ra.itemcode       = '$accountno'), 0)
               ) < 0
         ORDER BY cs.Date ASC",
        $db
    );

    while ($receiptRow = DB_fetch_array($receiptsResult)) {

        $amountReceipt  = (float)$receiptRow['AMOUNT2'];
        $journalReceipt = mysqli_real_escape_string($db, $receiptRow['JournalNo']);
        $receiptNo      = mysqli_real_escape_string($db, $receiptRow['Documentno']);
        $receiptDate    = mysqli_real_escape_string($db, $receiptRow['Date']);

        // -----------------------------------------------------------------------
        // Inner cursor — open invoices for this account
        // Mirrors: SELECT (Grossamount + ISNULL(dbo.InvoiceAllocations(JournalNo, @accountno), 0))
        //          WHERE ... > 0 ORDER BY Date ASC
        // Loop while amountreceipt < 0
        // -----------------------------------------------------------------------
        $invoicesResult = DB_query(
            "SELECT
                 (cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount)
                      FROM ReceiptsAllocation ra
                      WHERE ra.journalno = cs.JournalNo
                        AND ra.itemcode  = '$accountno'), 0)
                 ) AS AMOUNT2,
                 cs.JournalNo,
                 cs.Grossamount
             FROM CustomerStatement cs
             WHERE cs.Accountno = '$accountno'
               AND (cs.Grossamount + IFNULL(
                        (SELECT SUM(ra.amount)
                         FROM ReceiptsAllocation ra
                         WHERE ra.journalno = cs.JournalNo
                           AND ra.itemcode  = '$accountno'), 0)
                   ) > 0
             ORDER BY cs.Date ASC",
            $db
        );

        while ($amountReceipt < 0 && ($invoiceRow = DB_fetch_array($invoicesResult))) {

            $amountInvoice = (float)$invoiceRow['AMOUNT2'];
            $journalNoInv  = mysqli_real_escape_string($db, $invoiceRow['JournalNo']);

            if (($amountReceipt + $amountInvoice) <= 0) {

                // Receipt covers the full invoice
                // amount = amountInvoice * -1  (clears the invoice balance)
                DB_query(
                    "INSERT INTO ReceiptsAllocation
                         (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                     SELECT
                         Accountno, '$receiptDate', Documentno, JournalNo, Documenttype,
                         '$receiptNo',
                         (Grossamount * -1),
                         '$journalReceipt'
                     FROM CustomerStatement
                     WHERE JournalNo = '$journalNoInv'",
                    $db
                );

                $amountReceipt = $amountReceipt + $amountInvoice;

            } else {

                // Receipt is exhausted before covering the full invoice
                // amount = amountReceipt (use whatever is left)
                DB_query(
                    "INSERT INTO ReceiptsAllocation
                         (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                     SELECT
                         Accountno, '$receiptDate', Documentno, JournalNo, Documenttype,
                         '$receiptNo',
                         $amountReceipt,
                         '$journalReceipt'
                     FROM CustomerStatement
                     WHERE JournalNo = '$journalNoInv'",
                    $db
                );

                $amountReceipt = 0;
            }

            // Check and stamp Datewhenpaid after every inner iteration
            checkInvoiceWhenpaid($accountno, $invoiceRow['JournalNo']);
        }

        mysqli_free_result($invoicesResult);
    }

    mysqli_free_result($receiptsResult);
}

// ===========================================================================
// autoallocatevendors
//
// Ports dbo.autoallocatevendors exactly.
//
// Outer loop — payments where net (Grossamount - PaymentsAllocations) > 0
//   Supplier payments have POSITIVE Grossamount.
//
// Inner loop — bills where net (Grossamount + BillAllocations) < 0
//   Supplier invoices/bills have NEGATIVE Grossamount.
//   Loop condition: @amountreceipt > 0
//
// Two branches per bill:
//   if (amountreceipt + amountinvoice) > 0
//       → payment covers full bill: insert amount = amountinvoice * -1
//         amountreceipt = amountreceipt + amountinvoice
//   else
//       → bill absorbs all remaining payment: insert amount = amountreceipt
//         amountreceipt = amountreceipt + amountinvoice (→ <= 0, ends loop)
// ===========================================================================

/**
 * @param string $accountno  Creditor account code
 */
function autoallocatevendors($accountno)
{
    global $db;

    echo 'Vendors payment now being allocated';

    $accountno = mysqli_real_escape_string($db, trim($accountno));

    // ---------------------------------------------------------------------------
    // Outer cursor — payments with remaining unallocated balance
    // Mirrors: SELECT (Grossamount - ISNULL(dbo.PaymentsAllocations(JournalNo, @accountno), 0))
    //          WHERE ... > 0 ORDER BY Date ASC
    // ---------------------------------------------------------------------------
    $receiptsResult = DB_query(
        "SELECT
             (ss.Grossamount - IFNULL(
                 (SELECT SUM(pa.amount)
                  FROM PaymentsAllocation pa
                  WHERE pa.receiptjournal = ss.JournalNo
                    AND pa.itemcode       = '$accountno'), 0)
             ) AS AMOUNT2,
             ss.JournalNo,
             ss.Documentno
         FROM SupplierStatement ss
         WHERE ss.Accountno = '$accountno'
           AND (ss.Grossamount - IFNULL(
                    (SELECT SUM(pa.amount)
                     FROM PaymentsAllocation pa
                     WHERE pa.receiptjournal = ss.JournalNo
                       AND pa.itemcode       = '$accountno'), 0)
               ) > 0
         ORDER BY ss.Date ASC",
        $db
    );

    while ($receiptRow = DB_fetch_array($receiptsResult)) {

        $amountReceipt  = (float)$receiptRow['AMOUNT2'];
        $journalReceipt = mysqli_real_escape_string($db, $receiptRow['JournalNo']);
        $receiptNo      = mysqli_real_escape_string($db, $receiptRow['Documentno']);

        // -----------------------------------------------------------------------
        // Inner cursor — open bills for this account
        // Mirrors: SELECT (Grossamount + ISNULL(dbo.BillAllocations(JournalNo, @accountno), 0))
        //          WHERE ... < 0 ORDER BY Date ASC
        // Loop while amountreceipt > 0
        // -----------------------------------------------------------------------
        $invoicesResult = DB_query(
            "SELECT
                 (ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount)
                      FROM PaymentsAllocation pa
                      WHERE pa.journalno = ss.JournalNo
                        AND pa.itemcode  = '$accountno'), 0)
                 ) AS AMOUNT2,
                 ss.JournalNo
             FROM SupplierStatement ss
             WHERE ss.Accountno = '$accountno'
               AND (ss.Grossamount + IFNULL(
                        (SELECT SUM(pa.amount)
                         FROM PaymentsAllocation pa
                         WHERE pa.journalno = ss.JournalNo
                           AND pa.itemcode  = '$accountno'), 0)
                   ) < 0
             ORDER BY ss.Date ASC",
            $db
        );

        while ($amountReceipt > 0 && ($invoiceRow = DB_fetch_array($invoicesResult))) {

            $amountInvoice = (float)$invoiceRow['AMOUNT2'];
            $journalNoInv  = mysqli_real_escape_string($db, $invoiceRow['JournalNo']);

            if (($amountReceipt + $amountInvoice) > 0) {

                // Payment covers the full bill
                // amount = amountInvoice * -1
                DB_query(
                    "INSERT INTO PaymentsAllocation
                         (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                     SELECT
                         Accountno, Date, Documentno, JournalNo, Documenttype,
                         '$receiptNo',
                         (Grossamount * -1),
                         '$journalReceipt'
                     FROM SupplierStatement
                     WHERE JournalNo = '$journalNoInv'",
                    $db
                );

                $amountReceipt = $amountReceipt + $amountInvoice;

            } else {

                // Bill absorbs all remaining payment
                // amount = amountReceipt
                DB_query(
                    "INSERT INTO PaymentsAllocation
                         (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                     SELECT
                         Accountno, Date, Documentno, JournalNo, Documenttype,
                         '$receiptNo',
                         $amountReceipt,
                         '$journalReceipt'
                     FROM SupplierStatement
                     WHERE JournalNo = '$journalNoInv'",
                    $db
                );

                $amountReceipt = $amountReceipt + $amountInvoice; // becomes <= 0, ends loop
            }
        }

        mysqli_free_result($invoicesResult);
    }

    mysqli_free_result($receiptsResult);
}

// ===========================================================================
// AutoAllocateALLcustomers / AutoAllocateALLsuppliers
//
// Ports dbo.AutoAllocateALLcustomers and dbo.AutoAllocateALLsuppliers.
// Both were cursor loops over debtors/creditors calling the single-account
// allocation function. Replaced with PHP while loops.
//
// Note: original had commented-out DELETE lines — omitted here intentionally.
// ===========================================================================

/**
 * Run autoallocatedebtors for every debtor account.
 */
function AutoAllocateALLcustomers()
{
    global $db;
    $result = DB_query("SELECT itemcode FROM debtors ORDER BY itemcode", $db);
    while ($row = DB_fetch_array($result)) {
        autoallocatedebtors($row['itemcode']);
    }
    mysqli_free_result($result);
}

/**
 * Run autoallocatevendors for every creditor account.
 */
function AutoAllocateALLsuppliers()
{
    global $db;
    $result = DB_query("SELECT itemcode FROM creditors ORDER BY itemcode", $db);
    while ($row = DB_fetch_array($result)) {
        autoallocatevendors($row['itemcode']);
    }
    mysqli_free_result($result);
}

// ===========================================================================
// checkInvoiceWhenpaid
//
// Ports dbo.CheckInvoiceWhenpaid exactly.
//
// Sums ReceiptsAllocation.amount WHERE journalno = $journalno (invoice journal).
// If grossamount + balance <= 1 (fully settled within rounding tolerance):
//   Find latest receipt date → update CustomerStatement.Datewhenpaid
//   to GREATEST(invoice Date, receipt date).
// ===========================================================================

/**
 * @param string $accountno
 * @param string $journalno  The INVOICE journal number
 */
function checkInvoiceWhenpaid($accountno, $journalno)
{
    global $db;

    $accountno = mysqli_real_escape_string($db, $accountno);
    $journalno = mysqli_real_escape_string($db, $journalno);

    // Sum allocations posted against this invoice (journalno = invoice journal)
    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(amount), 0) AS balance
         FROM ReceiptsAllocation
         WHERE itemcode  = '$accountno'
           AND journalno = '$journalno'",
        $db
    ));
    $balance = (float)$row['balance'];

    $row = DB_fetch_array(DB_query(
        "SELECT IFNULL(SUM(grossamount), 0) AS grossamount
         FROM CustomerStatement
         WHERE Accountno = '$accountno'
           AND JournalNo = '$journalno'",
        $db
    ));
    $grossAmount = (float)$row['grossamount'];

    if (($grossAmount + $balance) <= 1) {

        // Latest receipt date for this invoice
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

            // GREATEST() mirrors: CASE WHEN Date > @datepaid THEN Date ELSE @datepaid END
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

// ===========================================================================
// ageingCustomers
//
// Ports dbo.ageingCustomers exactly.
//
// Five aging buckets based on invoice date vs today:
//   Current  : 0–30 days   (BETWEEN DATE_SUB(NOW(),INTERVAL 30 DAY) AND NOW())
//   30 days  : 31–60 days
//   60 days  : 61–90 days
//   90 days  : 91–120 days
//   Over 120 : > 120 days
//
// Each bucket sums: Grossamount + IFNULL(InvoiceAllocations, 0)
// for invoices (Grossamount > 0) for the given account.
//
// The five cursor loops in the original are replaced with a single
// conditional-aggregation query — same result, one DB round-trip.
//
// Returns associative array:
//   ['current', '30days', '60days', '90days', 'over90days']
// ===========================================================================

/**
 * @param  string $account  Debtor account code
 * @return array
 */
function ageingCustomers($account)
{
    global $db;

    $account = mysqli_real_escape_string($db, $account);

    // One query replaces five cursor loops.
    // DATE_SUB(CURDATE(), INTERVAL N DAY) mirrors MSSQL getdate()-N.
    $result = DB_fetch_array(DB_query(
        "SELECT
             -- Current: 0-30 days
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS current_bucket,

             -- 30-60 days
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days30,

             -- 60-90 days
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days60,

             -- 90-120 days
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days90,

             -- Over 120 days
             IFNULL(SUM(CASE
                 WHEN cs.Date < DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS over90days

         FROM CustomerStatement cs
         WHERE cs.Accountno   = '$account'
           AND cs.Grossamount > 0",
        $db
    ));

    return array(
        'current'    => round((float)$result['current_bucket'], 2),
        '30days'     => round((float)$result['days30'],         2),
        '60days'     => round((float)$result['days60'],         2),
        '90days'     => round((float)$result['days90'],         2),
        'over90days' => round((float)$result['over90days'],     2),
    );
}

// ===========================================================================
// ageingSuppliers
//
// Ports dbo.ageingSuppliers exactly.
//
// Same five buckets as ageingCustomers but:
//   - Uses SupplierStatement and PaymentsAllocation
//   - Grossamount < 0 (bills are negative on the supplier side)
//
// Returns associative array:
//   ['current', '30days', '60days', '90days', 'over90days']
// ===========================================================================

/**
 * @param  string $account  Creditor account code
 * @return array
 */
function ageingSuppliers($account)
{
    global $db;

    $account = mysqli_real_escape_string($db, $account);

    $result = DB_fetch_array(DB_query(
        "SELECT
             -- Current: 0-30 days
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS current_bucket,

             -- 30-60 days
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days30,

             -- 60-90 days
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days60,

             -- 90-120 days
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days90,

             -- Over 120 days
             IFNULL(SUM(CASE
                 WHEN ss.Date < DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS over90days

         FROM SupplierStatement ss
         WHERE ss.Accountno   = '$account'
           AND ss.Grossamount < 0",
        $db
    ));

    return array(
        'current'    => round((float)$result['current_bucket'], 2),
        '30days'     => round((float)$result['days30'],         2),
        '60days'     => round((float)$result['days60'],         2),
        '90days'     => round((float)$result['days90'],         2),
        'over90days' => round((float)$result['over90days'],     2),
    );
}