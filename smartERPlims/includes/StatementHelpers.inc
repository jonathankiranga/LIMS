<?php
$ResultIndex = DB_query("SELECT `periodno` FROM `FinancialPeriods`", $db);
$row = DB_fetch_row($ResultIndex);

if (empty($row) || empty($row[0])) {
    echo '<script type="text/javascript">SmartDialog.warning("Set up your Financial Periods", "Warning");</script>';
} else {
    $_POST['Financial_Periods'] = $row[0];
}
    
function db_customerstatements($accountno, $yearindex) {
    global $db;

    $accountno = DB_escape_string($accountno);
    $yearindex = (int)$yearindex;

    // 1. Get period date range
    $dateResult = DB_query(
        "SELECT MIN(start_date), MAX(end_date)
         FROM FinancialPeriods
         WHERE periodno=" . $yearindex,
        $db
    );
    $dateRow  = DB_fetch_row($dateResult);
    $dateFrom = $dateRow[0] ?? '';
    $dateTo   = $dateRow[1] ?? '';

    // 2. Get opening balance ONCE
    $balResult = DB_query(
        "SELECT IFNULL(SUM(Grossamount), 0)
         FROM CustomerStatement
         WHERE Accountno='" . $accountno . "'
           AND Date < '" . $dateFrom . "'",
        $db
    );
    $balRow  = DB_fetch_row($balResult);
    $openBal = $balRow[0] ?? 0;

    // 3. Get transactions with pamount
    $sql = "SELECT
                cs.Date AS date,
                cs.Documentno,
                cs.Accountno,
                cs.JournalNo,
                cs.Grossamount,
                (cs.Grossamount - IFNULL((
                    SELECT SUM(ra.amount)
                    FROM ReceiptsAllocation ra
                    WHERE ra.itemcode  = cs.Accountno
                      AND ra.date      = cs.Date
                      AND ra.invoiceno = cs.Documentno
                      AND ra.journalno = cs.JournalNo
                ), 0)) AS pamount,
                (SELECT s.typename FROM systypes_1 s
                 WHERE s.typeid = cs.Documenttype LIMIT 1) AS doctypes
            FROM CustomerStatement cs
            WHERE cs.Accountno='" . $accountno . "'
              AND cs.Date BETWEEN '" . $dateFrom . "' AND CURDATE()
            ORDER BY cs.Date";

    return [
        'openbal'      => $openBal,
        'datefrom'     => $dateFrom,
        'transactions' => DB_query($sql, $db),
    ];
}

function db_ageingCustomers_row($account){
    global $db;

    $account = DB_escape_string($account);
    $sql = "SELECT
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS current_bucket,
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days30,
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days60,
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days90,
             IFNULL(SUM(CASE
                 WHEN cs.Date < DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS over90days
         FROM CustomerStatement cs
         WHERE cs.Accountno='" . $account . "'
           AND cs.Grossamount > 0";

    $result = DB_query($sql, $db);
    $row = DB_fetch_row($result);
    return array(
        isset($row[0]) ? (float)$row[0] : 0,
        isset($row[1]) ? (float)$row[1] : 0,
        isset($row[2]) ? (float)$row[2] : 0,
        isset($row[3]) ? (float)$row[3] : 0,
        isset($row[4]) ? (float)$row[4] : 0
    );
}

function db_vendorstatements($accountno, $yearindex) {
    global $db;

    $accountno = DB_escape_string($accountno);
    $yearindex = (int)$yearindex;

    // 1. Get period date range
    $dateResult = DB_query(
        "SELECT MIN(start_date), MAX(end_date)
         FROM FinancialPeriods
         WHERE periodno=" . $yearindex,
        $db
    );
    $dateRow  = DB_fetch_row($dateResult);
    $dateFrom = $dateRow[0] ?? '';
    $dateTo   = $dateRow[1] ?? '';

    // 2. Get opening balance ONCE
    $balResult = DB_query(
        "SELECT IFNULL(SUM(Grossamount), 0)
         FROM SupplierStatement
         WHERE Accountno='" . $accountno . "'
           AND Date < '" . $dateFrom . "'",
        $db
    );
    $balRow  = DB_fetch_row($balResult);
    $openBal = $balRow[0] ?? 0;

    // 3. Get transactions with pamount (outstanding per invoice)
    $sql = "SELECT
                ss.Date AS date,
                ss.Documentno,
                ss.Accountno,
                ss.JournalNo,
                ss.Grossamount,
                (ss.Grossamount - IFNULL((
                    SELECT SUM(pa.amount)
                    FROM PaymentsAllocation pa
                    WHERE pa.itemcode  = ss.Accountno
                      AND pa.date      = ss.Date
                      AND pa.invoiceno = ss.Documentno
                      AND pa.journalno = ss.JournalNo
                ), 0)) AS pamount,
                (SELECT s.typename FROM systypes_1 s
                 WHERE s.typeid = ss.Documenttype LIMIT 1) AS doctypes
            FROM SupplierStatement ss
            WHERE ss.Accountno='" . $accountno . "'
              AND ss.Date BETWEEN '" . $dateFrom . "' AND CURDATE()
            ORDER BY ss.Date";

    return [
        'openbal'      => $openBal,
        'datefrom'     => $dateFrom,
        'transactions' => DB_query($sql, $db),
    ];
}

function db_ageingSuppliers_row($account){
    global $db;

    $account = DB_escape_string($account);
    $sql = "SELECT
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS current_bucket,
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days30,
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days60,
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                                  AND DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days90,
             IFNULL(SUM(CASE
                 WHEN ss.Date < DATE_SUB(CURDATE(), INTERVAL 120 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS over90days
         FROM SupplierStatement ss
         WHERE ss.Accountno='" . $account . "'
           AND ss.Grossamount < 0";

    $result = DB_query($sql, $db);
    $row = DB_fetch_row($result);
    return array(
        isset($row[0]) ? (float)$row[0] : 0,
        isset($row[1]) ? (float)$row[1] : 0,
        isset($row[2]) ? (float)$row[2] : 0,
        isset($row[3]) ? (float)$row[3] : 0,
        isset($row[4]) ? (float)$row[4] : 0
    );
}

function db_autoallocatevendors($accountno){
    global $db;

    $accountno = DB_escape_string(trim($accountno));

    $receiptsSQL = "SELECT
            (ss.Grossamount - IFNULL((
                SELECT SUM(pa.amount)
                FROM PaymentsAllocation pa
                WHERE pa.receiptjournal = ss.JournalNo
                  AND pa.itemcode = '" . $accountno . "'
            ),0)) AS AMOUNT2,
            ss.JournalNo,
            ss.Documentno
        FROM SupplierStatement ss
        WHERE ss.Accountno = '" . $accountno . "'
          AND (ss.Grossamount - IFNULL((
                SELECT SUM(pa.amount)
                FROM PaymentsAllocation pa
                WHERE pa.receiptjournal = ss.JournalNo
                  AND pa.itemcode = '" . $accountno . "'
          ),0)) > 0
        ORDER BY ss.Date ASC";

    $receiptsResult = DB_query($receiptsSQL, $db);
    while($receiptRow = DB_fetch_array($receiptsResult)){
        $amountReceipt  = (float)$receiptRow['AMOUNT2'];
        $journalReceipt = DB_escape_string($receiptRow['JournalNo']);
        $receiptNo      = DB_escape_string($receiptRow['Documentno']);

        $invoicesSQL = "SELECT
                (ss.Grossamount + IFNULL((
                    SELECT SUM(pa.amount)
                    FROM PaymentsAllocation pa
                    WHERE pa.journalno = ss.JournalNo
                      AND pa.itemcode = '" . $accountno . "'
                ),0)) AS AMOUNT2,
                ss.JournalNo
            FROM SupplierStatement ss
            WHERE ss.Accountno = '" . $accountno . "'
              AND (ss.Grossamount + IFNULL((
                    SELECT SUM(pa.amount)
                    FROM PaymentsAllocation pa
                    WHERE pa.journalno = ss.JournalNo
                      AND pa.itemcode = '" . $accountno . "'
              ),0)) < 0
            ORDER BY ss.Date ASC";

        $invoicesResult = DB_query($invoicesSQL, $db);
        while($amountReceipt > 0 && ($invoiceRow = DB_fetch_array($invoicesResult))){
            $amountInvoice = (float)$invoiceRow['AMOUNT2'];
            $journalNoInv  = DB_escape_string($invoiceRow['JournalNo']);

            if(($amountReceipt + $amountInvoice) > 0){
                DB_query("INSERT INTO PaymentsAllocation
                    (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                    SELECT Accountno, Date, Documentno, JournalNo, Documenttype,
                           '" . $receiptNo . "', (Grossamount * -1), '" . $journalReceipt . "'
                    FROM SupplierStatement
                    WHERE JournalNo = '" . $journalNoInv . "'", $db);

                $amountReceipt = $amountReceipt + $amountInvoice;
            } else {
                DB_query("INSERT INTO PaymentsAllocation
                    (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                    SELECT Accountno, Date, Documentno, JournalNo, Documenttype,
                           '" . $receiptNo . "', " . $amountReceipt . ", '" . $journalReceipt . "'
                    FROM SupplierStatement
                    WHERE JournalNo = '" . $journalNoInv . "'", $db);

                $amountReceipt = $amountReceipt + $amountInvoice;
            }
        }
    }
}

function db_ageingCustomersReport_row($account, $periodno){
    global $db;

    $account = DB_escape_string($account);
    $periodno = (int)$periodno;

    $cutoffResult = DB_query("SELECT MAX(end_date) AS cutoff_date
                              FROM FinancialPeriods
                              WHERE periodno='" . $periodno . "'", $db);
    $cutoffRow = DB_fetch_row($cutoffResult);
    $cutoff = (!empty($cutoffRow[0])) ? $cutoffRow[0] : date('Y-m-d');

    $sql = "SELECT
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 30 DAY) AND '" . $cutoff . "'
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS current_bucket,
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 60 DAY)
                                  AND DATE_SUB('" . $cutoff . "', INTERVAL 30 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days30,
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 90 DAY)
                                  AND DATE_SUB('" . $cutoff . "', INTERVAL 60 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days60,
             IFNULL(SUM(CASE
                 WHEN cs.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 120 DAY)
                                  AND DATE_SUB('" . $cutoff . "', INTERVAL 90 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS days90,
             IFNULL(SUM(CASE
                 WHEN cs.Date < DATE_SUB('" . $cutoff . "', INTERVAL 120 DAY)
                 THEN cs.Grossamount + IFNULL(
                     (SELECT SUM(ra.amount) FROM ReceiptsAllocation ra
                      WHERE ra.itemcode  = cs.Accountno
                        AND ra.invoiceno = cs.Documentno
                        AND ra.journalno = cs.JournalNo), 0)
                 ELSE 0 END), 0) AS over90days
         FROM CustomerStatement cs
         WHERE cs.Accountno='" . $account . "'
           AND cs.Grossamount > 0";

    $result = DB_query($sql, $db);
    $row = DB_fetch_row($result);
    return array(
        isset($row[0]) ? (float)$row[0] : 0,
        isset($row[1]) ? (float)$row[1] : 0,
        isset($row[2]) ? (float)$row[2] : 0,
        isset($row[3]) ? (float)$row[3] : 0,
        isset($row[4]) ? (float)$row[4] : 0
    );
}

function db_ageingSuppliersReport_row($account, $periodno){
    global $db;

    $account = DB_escape_string($account);
    $periodno = (int)$periodno;

    $cutoffResult = DB_query("SELECT MAX(end_date) AS cutoff_date
                              FROM FinancialPeriods
                              WHERE periodno='" . $periodno . "'", $db);
    $cutoffRow = DB_fetch_row($cutoffResult);
    $cutoff = (!empty($cutoffRow[0])) ? $cutoffRow[0] : date('Y-m-d');

    $sql = "SELECT
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 30 DAY) AND '" . $cutoff . "'
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS current_bucket,
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 60 DAY)
                                  AND DATE_SUB('" . $cutoff . "', INTERVAL 30 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days30,
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 90 DAY)
                                  AND DATE_SUB('" . $cutoff . "', INTERVAL 60 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days60,
             IFNULL(SUM(CASE
                 WHEN ss.Date BETWEEN DATE_SUB('" . $cutoff . "', INTERVAL 120 DAY)
                                  AND DATE_SUB('" . $cutoff . "', INTERVAL 90 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS days90,
             IFNULL(SUM(CASE
                 WHEN ss.Date < DATE_SUB('" . $cutoff . "', INTERVAL 120 DAY)
                 THEN ss.Grossamount + IFNULL(
                     (SELECT SUM(pa.amount) FROM PaymentsAllocation pa
                      WHERE pa.itemcode  = ss.Accountno
                        AND pa.invoiceno = ss.Documentno
                        AND pa.journalno = ss.JournalNo), 0)
                 ELSE 0 END), 0) AS over90days
         FROM SupplierStatement ss
         WHERE ss.Accountno='" . $account . "'
           AND ss.Grossamount < 0";

    $result = DB_query($sql, $db);
    $row = DB_fetch_row($result);
    return array(
        isset($row[0]) ? (float)$row[0] : 0,
        isset($row[1]) ? (float)$row[1] : 0,
        isset($row[2]) ? (float)$row[2] : 0,
        isset($row[3]) ? (float)$row[3] : 0,
        isset($row[4]) ? (float)$row[4] : 0
    );
}

?>
