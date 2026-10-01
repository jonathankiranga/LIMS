<?php


$_SESSION['PostRow'] = array();
$_SESSION['records'] = array();

// ---------------------------------------------------------------------------
// DB helper not present in original wrapper — added for completeness
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// ExchangeRates class — unchanged logic, only DB calls updated via wrappers
// ---------------------------------------------------------------------------

class ExchangeRates
{
    var $FunctionalCurrency;
    var $FromRate;
    var $Torate;

    function __construct()
    {
        $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
    }

    /**
     * Return the exchange rate from $fromcurrency to the functional currency.
     *
     * @param  string $fromcurrency
     * @return float
     */
    function get($fromcurrency)
    {
        $this->FromRate = $this->ExtractRate($fromcurrency);
        $this->Torate   = $this->ExtractRate($this->FunctionalCurrency);
        return ($this->Torate != 0) ? ($this->FromRate / $this->Torate) : 0;
    }

    /**
     * @param  string $currbrieve
     * @return float
     */
    function ExtractRate($currbrieve)
    {
        global $db;
        $currbrieve = mysqli_real_escape_string($db, $currbrieve);
        $result     = DB_query("SELECT rate FROM currencies WHERE currabrev = '$currbrieve'", $db);
        $row        = DB_fetch_row($result);
        return isset($row[0]) ? (float)$row[0] : 0;
    }
}

// ---------------------------------------------------------------------------
// AverageCost — return most-recent purchase cost for an item
// ---------------------------------------------------------------------------

/**
 * Return the latest unit cost for a stock item, converted to functional currency.
 * If UOM is 'fulqty', the per-unit cost is divided by partperunit.
 *
 * @param  string $itemcode
 * @return float
 */
function AverageCost($itemcode)
{
    global $db;
    $itemcode = mysqli_real_escape_string($db, trim($itemcode));

    $SQL = "SELECT
                (PurchaseLine.UnitPrice * currencies.rate) AS UnitPrice,
                PurchaseHeader.documentno,
                PurchaseHeader.docdate,
                PurchaseHeader.currencycode,
                PurchaseLine.code,
                PurchaseLine.UOM,
                stockmaster.partperunit
            FROM PurchaseHeader
            JOIN PurchaseLine  ON PurchaseLine.documentno  = PurchaseHeader.documentno
            JOIN currencies    ON currencies.currabrev     = PurchaseHeader.currencycode
            JOIN stockmaster   ON PurchaseLine.code        = stockmaster.itemcode
            WHERE PurchaseLine.code = '$itemcode'
            ORDER BY PurchaseHeader.docdate DESC
            LIMIT 1";

    $result = DB_query($SQL, $db);
    $rows   = DB_fetch_row($result);

    if (!$rows) {
        return 0;
    }

    // index 5 = UOM, index 0 = UnitPrice, index 6 = partperunit
    return ($rows[5] === 'fulqty') ? ($rows[0] / $rows[6]) : $rows[0];
}

// ---------------------------------------------------------------------------
// FindJournal
// ---------------------------------------------------------------------------

/**
 * Return the journal number linked to a stock ledger invref.
 *
 * @param  string $invref
 * @return string|null
 */
function FindJournal($invref)
{
    global $db;
    $invref = mysqli_real_escape_string($db, trim($invref));
    $result = DB_query("SELECT journal FROM stockledger WHERE invref = '$invref'", $db);
    $rows   = DB_fetch_row($result);
    return isset($rows[0]) ? $rows[0] : null;
}

// ---------------------------------------------------------------------------
// GetStockRegister — set-based UPDATE (replaces cursor loop)
// ---------------------------------------------------------------------------

/**
 * Update stockmaster.averagestock with the cost from the earliest open
 * StockRegister row (StockBalance > 0) for each item.
 *
 * Replaces the MS-SQL cursor that iterated every stockmaster row.
 */
function GetStockRegister()
{
    global $db;

    // One set-based UPDATE replaces the per-row cursor.
    // Subquery finds the minimum rowid with a positive StockBalance per item.
    $SQL = "UPDATE stockmaster sm
            INNER JOIN (
                SELECT sr.itemcode, sr.cost
                FROM   StockRegister sr
                INNER JOIN (
                    SELECT itemcode, MIN(rowid) AS min_rowid
                    FROM   StockRegister
                    WHERE  StockBalance > 0
                    GROUP  BY itemcode
                ) first_row ON first_row.itemcode  = sr.itemcode
                           AND first_row.min_rowid = sr.rowid
                WHERE  sr.cost > 0
            ) AS sr_data ON sr_data.itemcode = sm.itemcode
            SET sm.averagestock = sr_data.cost";

    DB_query($SQL, $db);
}

// ---------------------------------------------------------------------------
// PostStockRegister — GRN receipt into StockRegister + Generalledger
// ---------------------------------------------------------------------------

/**
 * Record a goods-received quantity into StockRegister and post the
 * corresponding GL entry.
 *
 * @param float  $Quantity
 * @param int    $key       PurchaseLine.entryno
 * @param string $journal
 * @param string $PeriodNo
 */
function PostStockRegister($Quantity, $key, $journal, $PeriodNo)
{
    global $db;

    $key      = (int)$key;
    $Quantity = (float)$Quantity;
    $journal  = mysqli_real_escape_string($db, $journal);
    $PeriodNo = mysqli_real_escape_string($db, $PeriodNo);

    $SQL = "SELECT
                PurchaseHeader.documentno,
                PurchaseHeader.docdate,
                PurchaseHeader.currencycode,
                PurchaseLine.code,
                (PurchaseLine.UnitPrice * currencies.rate)     AS UnitPrice,
                PurchaseLine.UOM,
                stockmaster.partperunit,
                (PurchaseLine.invoiceamount * currencies.rate) AS invoiceamount,
                (PurchaseHeader.freight * currencies.rate)     AS freight,
                PurchaseLine.Quantity,
                (SELECT SUM(PL.Quantity * SM.partperunit)
                 FROM   PurchaseLine PL
                 JOIN   stockmaster  SM ON PL.code = SM.itemcode
                 WHERE  PL.documentno IN (
                     SELECT documentno FROM PurchaseLine PLi
                     JOIN   stockmaster smi ON PLi.code = smi.itemcode
                     WHERE  PLi.entryno = $key
                 )
                 AND SM.isstock <> 3) AS Totunits
            FROM PurchaseHeader
            JOIN PurchaseLine ON PurchaseLine.documentno = PurchaseHeader.documentno
            JOIN currencies   ON currencies.currabrev    = PurchaseHeader.currencycode
            JOIN stockmaster  ON PurchaseLine.code       = stockmaster.itemcode
            WHERE stockmaster.isstock <> 3
              AND PurchaseLine.entryno = $key";

    $RESULTS       = DB_query($SQL, $db);
    $rows          = DB_fetch_row($RESULTS);
    DB_free_result($RESULTS);

    if (!$rows || mb_strlen(trim($rows[3])) === 0) {
        return;
    }

    $GRN            = mysqli_real_escape_string($db, trim($rows[0]));
    $Price          = (float)$rows[4];
    $itemcode       = mysqli_real_escape_string($db, trim($rows[3]));
    $UOM            = trim($rows[5]);
    $AMOUNT         = (float)$rows[7];
    $freight        = is_null($rows[8]) ? 0 : (float)$rows[8];
    $Unitstodevide  = (float)$rows[10];

    // Calculate loose units and per-unit price
    $looseQty = ($UOM === 'fulqty')
        ? $Quantity * (float)$rows[6]   // partperunit fetched below
        : $Quantity;

    // Fetch partperunit separately to mirror the original @partperunit variable
    $ppuRow     = DB_fetch_row(DB_query(
        "SELECT partperunit FROM stockmaster WHERE itemcode = '$itemcode'", $db
    ));
    $partperunit = isset($ppuRow[0]) ? (float)$ppuRow[0] : 1;

    $looseQty = ($UOM === 'fulqty') ? ($Quantity * $partperunit) : $Quantity;
    $unitPrice = ($UOM === 'fulqty') ? ($Price / $partperunit)   : $Price;

    // Apply freight surcharge
    if ($freight > 0 && $Unitstodevide > 0) {
        $freightPerUnit = $freight / $Unitstodevide;
        $unitPrice      = $unitPrice + $freightPerUnit;
    }

    // Insert into StockRegister
    DB_query(
        "INSERT INTO StockRegister (itemcode, StockIn, cost, StockOut, journal, GRN)
         VALUES ('$itemcode', $looseQty, $unitPrice, 0, '$journal', '$GRN')",
        $db
    );

    // Post to Generalledger via subquery SELECT for account codes + narration
    $funcCurrency = mysqli_real_escape_string($db, $_SESSION['CompanyRecord']['currencydefault']);

    $glSQL = "INSERT INTO Generalledger
                (journalno, Docdate, period, DocumentNo, DocumentType,
                 accountcode, balaccountcode, VATaccountcode,
                 amount, VATamount, currencycode, ExchangeRate, cutomercode, narration)
              SELECT
                '$journal',
                PurchaseHeader.docdate,
                '$PeriodNo',
                PurchaseHeader.documentno,
                PurchaseHeader.documenttype,
                inventorypostinggroup.balancesheet,
                inventorypostinggroup.stockvariance,
                '',
                $AMOUNT,
                0,
                '$funcCurrency',
                1,
                PurchaseHeader.vendorcode,
                CONCAT(
                    RTRIM(PurchaseLine.description), ' X $Quantity ',
                    CASE PurchaseLine.UOM
                        WHEN 'fulqty'
                        THEN (SELECT RTRIM(u.descrip) FROM unit u
                              JOIN stockmaster sm2 ON sm2.units    = u.code
                              AND  sm2.itemcode = PurchaseLine.code)
                        ELSE (SELECT RTRIM(u.descrip) FROM unit u
                              JOIN stockmaster sm2 ON sm2.subunits = u.code
                              AND  sm2.itemcode = PurchaseLine.code)
                    END
                ) AS narration
              FROM PurchaseHeader
              JOIN PurchaseLine         ON PurchaseLine.documentno         = PurchaseHeader.documentno
              JOIN stockmaster          ON PurchaseLine.code               = stockmaster.itemcode
              JOIN inventorypostinggroup ON inventorypostinggroup.code     = stockmaster.postinggroup
              JOIN currencies           ON PurchaseHeader.currencycode     = currencies.currabrev
              WHERE PurchaseLine.entryno = $key";

    DB_query($glSQL, $db);
    GetStockRegister();
}

// ---------------------------------------------------------------------------
// GetFIFO — FIFO stock depletion on sale (cursor replaced with PHP loop)
// ---------------------------------------------------------------------------

/**
 * Deplete stock on a FIFO basis for a sales line and post GL entries.
 *
 * @param string $journal
 * @param string $PeriodNo
 * @param string $salesoderno
 * @param string $stcode       Stock item code
 * @param float  $qty          Quantity sold
 * @param int    $key          SalesLine.entryno
 */
function GetFIFO($journal, $PeriodNo, $salesoderno, $stcode, $qty, $key)
{
    global $db;

    $journal    = mysqli_real_escape_string($db, $journal);
    $PeriodNo   = mysqli_real_escape_string($db, $PeriodNo);
    $salesoderno = mysqli_real_escape_string($db, $salesoderno);
    $stcode     = mysqli_real_escape_string($db, $stcode);
    $qty        = (float)$qty;
    $key        = (int)$key;
    $funcCurrency = mysqli_real_escape_string($db, $_SESSION['CompanyRecord']['currencydefault']);

    // Fetch UOM for this sales line
    $uomResult  = DB_query(
        "SELECT SalesLine.UOM
         FROM SalesHeader
         JOIN SalesLine ON SalesLine.documentno = SalesHeader.documentno
         WHERE SalesLine.entryno = $key",
        $db
    );
    $uomRow = DB_fetch_row($uomResult);
    DB_free_result($uomResult);
    $UOM = isset($uomRow[0]) ? trim($uomRow[0]) : 'loosqty';

    // Get partperunit
    $ppuRow      = DB_fetch_row(DB_query(
        "SELECT partperunit FROM stockmaster WHERE itemcode = '$stcode'", $db
    ));
    $partperunit = isset($ppuRow[0]) ? (float)$ppuRow[0] : 1;

    // Convert to loose units
    $remaining = ($UOM === 'fulqty') ? ($qty * $partperunit) : $qty;

    // FIFO loop over open StockRegister rows
    $srResult = DB_query(
        "SELECT sr.rowid, sr.StockBalance, sm.averagestock
         FROM   StockRegister sr
         JOIN   stockmaster sm ON sm.itemcode = sr.itemcode
         WHERE  sr.itemcode   = '$stcode'
           AND  sr.StockBalance > 0
         ORDER  BY sr.rowid ASC",
        $db
    );

    while ($remaining > 0 && ($srRow = DB_fetch_array($srResult))) {
        $rowid        = (int)$srRow['rowid'];
        $stockBalance = (float)$srRow['StockBalance'];
        $avecost      = (float)$srRow['averagestock'];

        if ($remaining > $stockBalance) {
            $useQty    = $stockBalance;
            $remaining = $remaining - $useQty;
        } else {
            $useQty    = $remaining;
            $remaining = 0;
        }

        // Update StockOut
        DB_query(
            "UPDATE StockRegister SET StockOut = StockOut + $useQty WHERE rowid = $rowid",
            $db
        );

        // Convert back for reporting
        $reportQty  = ($UOM === 'fulqty') ? ($useQty / $partperunit) : $useQty;
        $reportCost = ($UOM === 'fulqty') ? ($avecost * $partperunit) : $avecost;

        if ($reportQty > 0) {
            $glSQL = "INSERT INTO Generalledger
                        (journalno, Docdate, period, DocumentNo, DocumentType,
                         accountcode, balaccountcode, VATaccountcode,
                         amount, VATamount, currencycode, ExchangeRate, cutomercode, narration)
                      SELECT
                        '$journal',
                        SalesHeader.docdate,
                        '$PeriodNo',
                        SalesHeader.documentno,
                        SalesHeader.documenttype,
                        inventorypostinggroup.stockvariance,
                        inventorypostinggroup.balancesheet,
                        '',
                        ($reportCost * $reportQty),
                        0,
                        '$funcCurrency',
                        1,
                        SalesHeader.customercode,
                        CONCAT(
                            RTRIM(SalesLine.description), '@',
                            CAST($reportCost AS CHAR), ' X ',
                            CAST($reportQty  AS CHAR),
                            CASE SalesLine.UOM
                                WHEN 'fulqty'
                                THEN (SELECT RTRIM(u.descrip) FROM unit u
                                      JOIN stockmaster sm2 ON sm2.units    = u.code
                                      AND  sm2.itemcode = SalesLine.code)
                                ELSE (SELECT RTRIM(u.descrip) FROM unit u
                                      JOIN stockmaster sm2 ON sm2.subunits = u.code
                                      AND  sm2.itemcode = SalesLine.code)
                            END
                        ) AS narration
                      FROM SalesHeader
                      JOIN SalesLine          ON SalesLine.documentno          = SalesHeader.documentno
                      JOIN stockmaster        ON SalesLine.code                = stockmaster.itemcode
                      JOIN inventorypostinggroup ON inventorypostinggroup.code = stockmaster.postinggroup
                      JOIN currencies         ON SalesHeader.currencycode      = currencies.currabrev
                      WHERE SalesHeader.documentno = '$salesoderno'
                        AND SalesLine.code         = '$stcode'
                        AND SalesLine.entryno      = $key";

            DB_query($glSQL, $db);
        }
    }

    DB_free_result($srResult);
    GetStockRegister();
}

// ---------------------------------------------------------------------------
// GetEmptiesFIFO — FIFO depletion for container/empties on sale
// ---------------------------------------------------------------------------

/**
 * Deplete container stock on a FIFO basis for a sales line and post GL entries.
 *
 * @param string $journal
 * @param string $PeriodNo
 * @param string $salesoderno
 * @param string $stcode       Container item code (may be empty)
 * @param float  $qty
 * @param int    $key          SalesLine.entryno
 */
function GetEmptiesFIFO($journal, $PeriodNo, $salesoderno, $stcode, $qty, $key)
{
    global $db;

    $journal     = mysqli_real_escape_string($db, $journal);
    $PeriodNo    = mysqli_real_escape_string($db, $PeriodNo);
    $salesoderno = mysqli_real_escape_string($db, $salesoderno);
    $key         = (int)$key;
    $qty         = (float)$qty;
    $funcCurrency = mysqli_real_escape_string($db, $_SESSION['CompanyRecord']['currencydefault']);

    // Resolve container code from SalesLine
    $itemRow = DB_fetch_row(DB_query(
        "SELECT containercode FROM SalesLine WHERE entryno = $key", $db
    ));
    $itemcode = isset($itemRow[0]) ? mysqli_real_escape_string($db, trim($itemRow[0])) : '';

    if (mb_strlen($itemcode) === 0 || $qty <= 0) {
        return;
    }

    $remaining = $qty;

    $srResult = DB_query(
        "SELECT sr.rowid, sr.StockBalance, sm.averagestock
         FROM   StockRegister sr
         JOIN   stockmaster sm ON sm.itemcode = sr.itemcode
         WHERE  sr.itemcode    = '$itemcode'
           AND  sr.StockBalance > 0
         ORDER  BY sr.rowid ASC",
        $db
    );

    while ($remaining > 0 && ($srRow = DB_fetch_array($srResult))) {
        $rowid        = (int)$srRow['rowid'];
        $stockBalance = (float)$srRow['StockBalance'];
        $avecost      = (float)$srRow['averagestock'];

        if ($remaining > $stockBalance) {
            $useQty    = $stockBalance;
            $remaining = $remaining - $useQty;
        } else {
            $useQty    = $remaining;
            $remaining = 0;
        }

        DB_query(
            "UPDATE StockRegister SET StockOut = StockOut + $useQty WHERE rowid = $rowid",
            $db
        );

        if ($useQty > 0) {
            $glSQL = "INSERT INTO Generalledger
                        (journalno, Docdate, period, DocumentNo, DocumentType,
                         accountcode, balaccountcode, VATaccountcode,
                         amount, VATamount, currencycode, ExchangeRate, cutomercode, narration)
                      SELECT
                        '$journal',
                        SalesHeader.docdate,
                        '$PeriodNo',
                        SalesHeader.documentno,
                        SalesHeader.documenttype,
                        inventorypostinggroup.stockvariance,
                        inventorypostinggroup.balancesheet,
                        '',
                        ($avecost * $useQty),
                        0,
                        '$funcCurrency',
                        1,
                        SalesHeader.customercode,
                        CONCAT(
                            RTRIM(SalesLine.description), '@',
                            CAST($avecost AS CHAR), ' X ',
                            CAST($useQty  AS CHAR),
                            CASE SalesLine.UOM
                                WHEN 'fulqty'
                                THEN (SELECT RTRIM(u.descrip) FROM unit u
                                      JOIN stockmaster sm2 ON sm2.units    = u.code
                                      AND  sm2.itemcode = SalesLine.containercode)
                                ELSE (SELECT RTRIM(u.descrip) FROM unit u
                                      JOIN stockmaster sm2 ON sm2.subunits = u.code
                                      AND  sm2.itemcode = SalesLine.containercode)
                            END
                        ) AS narration
                      FROM SalesHeader
                      JOIN SalesLine          ON SalesLine.documentno          = SalesHeader.documentno
                      JOIN stockmaster        ON SalesLine.containercode       = stockmaster.itemcode
                      JOIN inventorypostinggroup ON inventorypostinggroup.code = stockmaster.postinggroup
                      JOIN currencies         ON SalesHeader.currencycode      = currencies.currabrev
                      WHERE SalesHeader.documentno  = '$salesoderno'
                        AND SalesLine.containercode = '$itemcode'
                        AND SalesLine.entryno       = $key";

            DB_query($glSQL, $db);
        }
    }

    DB_free_result($srResult);
    GetStockRegister();
}

// ---------------------------------------------------------------------------
// PostDebitRegister — debit note / return FIFO reversal (cursor → PHP loop)
// ---------------------------------------------------------------------------

/**
 * Process a purchase debit note: deplete StockRegister FIFO and post GL.
 *
 * @param float  $Quantity
 * @param int    $key
 * @param string $journal
 * @param string $PeriodNo
 */
function PostDebitRegister($Quantity, $key, $journal, $PeriodNo)
{
    global $db;

    $key        = (int)$key;
    $Quantity   = (float)$Quantity;
    $journal    = mysqli_real_escape_string($db, $journal);
    $PeriodNo   = mysqli_real_escape_string($db, $PeriodNo);
    $funcCurrency = mysqli_real_escape_string($db, $_SESSION['CompanyRecord']['currencydefault']);

    $SQL = "SELECT
                PurchaseHeader.docdate,
                PurchaseHeader.currencycode,
                PurchaseLine.code,
                PurchaseLine.UnitPrice,
                PurchaseLine.UOM
            FROM PurchaseHeader
            JOIN PurchaseLine ON PurchaseLine.documentno = PurchaseHeader.documentno
            WHERE PurchaseLine.entryno = $key";

    $RESULTS = DB_query($SQL, $db);
    $rows    = DB_fetch_row($RESULTS);
    DB_free_result($RESULTS);

    if (!$rows) {
        return;
    }

    $ExchRates = new ExchangeRates();
    $R         = $ExchRates->get($rows[1]);
    $Price     = (float)$rows[3];
    $cost      = $Price * $R;
    $itemcode  = mysqli_real_escape_string($db, trim($rows[2]));
    $UOM       = trim($rows[4]);

    $remaining = (float)$Quantity;

    $srResult = DB_query(
        "SELECT rowid, cost, StockBalance
         FROM   StockRegister
         WHERE  itemcode     = '$itemcode'
           AND  StockBalance > 0
         ORDER  BY rowid ASC",
        $db
    );

    while ($remaining > 0 && ($srRow = DB_fetch_array($srResult))) {
        $rowid        = (int)$srRow['rowid'];
        $stockBalance = (float)$srRow['StockBalance'];
        $avecost      = (float)$srRow['cost'];

        if ($remaining > $stockBalance) {
            $useQty    = $stockBalance;
            $remaining = $remaining - $useQty;
        } else {
            $useQty    = $remaining;
            $remaining = 0;
        }

        DB_query(
            "UPDATE StockRegister SET StockOut = StockOut + $useQty WHERE rowid = $rowid",
            $db
        );

        if ($useQty > 0) {
            $glSQL = "INSERT INTO Generalledger
                        (journalno, Docdate, period, DocumentNo, DocumentType,
                         accountcode, balaccountcode, VATaccountcode,
                         amount, VATamount, currencycode, ExchangeRate, cutomercode, narration)
                      SELECT
                        '$journal',
                        PurchaseHeader.docdate,
                        '$PeriodNo',
                        PurchaseHeader.documentno,
                        PurchaseHeader.documenttype,
                        inventorypostinggroup.stockvariance,
                        inventorypostinggroup.balancesheet,
                        '',
                        ($avecost * $useQty *
                            CASE PurchaseLine.UOM
                                WHEN 'fulqty' THEN stockmaster.partperunit
                                ELSE 1
                            END
                        ),
                        0,
                        '$funcCurrency',
                        1,
                        PurchaseHeader.vendorcode,
                        CONCAT(
                            RTRIM(PurchaseLine.description), ' X ',
                            CAST($useQty AS CHAR),
                            CASE PurchaseLine.UOM
                                WHEN 'fulqty'
                                THEN (SELECT RTRIM(u.descrip) FROM unit u
                                      JOIN stockmaster sm2 ON sm2.units    = u.code
                                      AND  sm2.itemcode = PurchaseLine.code)
                                ELSE (SELECT RTRIM(u.descrip) FROM unit u
                                      JOIN stockmaster sm2 ON sm2.subunits = u.code
                                      AND  sm2.itemcode = PurchaseLine.code)
                            END
                        ) AS narration
                      FROM PurchaseHeader
                      JOIN PurchaseLine          ON PurchaseLine.documentno          = PurchaseHeader.documentno
                      JOIN stockmaster           ON PurchaseLine.code               = stockmaster.itemcode
                      JOIN inventorypostinggroup ON inventorypostinggroup.code      = stockmaster.postinggroup
                      JOIN currencies            ON PurchaseHeader.currencycode     = currencies.currabrev
                      WHERE PurchaseLine.entryno = $key";

            DB_query($glSQL, $db);
        }
    }

    DB_free_result($srResult);
    GetStockRegister();
}

// ---------------------------------------------------------------------------
// RepopulateGeneral_ledger — replay all GRN lines
// ---------------------------------------------------------------------------

/**
 * Re-post all non-service purchase lines to StockRegister and Generalledger.
 * Used for data repair / initial population.
 */
function RepopulateGeneral_ledger()
{
    global $db;

    $SQL = "SELECT
                entryno, documenttype, docdate, documentno,
                locationcode, stocktype, code, description,
                unitofmeasure, Qunatity_delivered AS Quantity,
                UnitPrice, vatamount, invoiceamount,
                completed, printed, containerprice, containersunits,
                totalchargedcontainers, containercode, vatrate,
                inclusive, UOM, shipping
            FROM PurchaseLine
            JOIN stockmaster ON PurchaseLine.code = stockmaster.itemcode
            WHERE stockmaster.isstock <> 3";

    $ResultIndex = DB_query($SQL, $db);

    while ($Rows = DB_fetch_array($ResultIndex)) {
        $D        = ConvertSQLDate($Rows['docdate']);
        $PeriodNo = GetPeriod($D, $db, false);
        $journal  = FindJournal($Rows['documentno']);
        $key      = $Rows['entryno'];

        if ($Rows['Quantity'] > 0) {
            PostStockRegister($Rows['Quantity'], $key, $journal, $PeriodNo);
        }
    }
}

// ---------------------------------------------------------------------------
// StoreToTank — production: raw materials to tank (procedure → direct INSERT)
// ---------------------------------------------------------------------------

/**
 * Calculate blended cost and insert into StockRegister for a tank receipt.
 *
 * @param array $row  Row from buffertable
 */
function StoreToTank($row = array())
{
    global $db;

    $DATETIME    = $row['date'];
    $partperunit = (float)$row['partperunit'];
    $strdate     = ConvertSQLDate($DATETIME);

    $TotalUnits = ($row['uom'] === 'loosqty' || $row['uom'] !== 'fulqty')
        ? (float)$row['units']
        : (float)$row['units'] * $partperunit;

    $batchno = mysqli_real_escape_string($db, $row['batchno']);
    $itemcode = mysqli_real_escape_string($db, trim($row['itemcode']));

    $SQL = "SELECT
                sm.averagestock, sm.partperunit,
                sl.doctyp, sl.itemcode, sl.invref,
                ABS(sl.fulqty)  AS fulqty,
                ABS(sl.loosqty) AS loosqty
            FROM stockledger sl
            JOIN stockmaster sm ON sl.itemcode = sm.itemcode
            WHERE sl.invref = '$batchno'";

    $totalcost   = 0;
    $ResultIndex = DB_query($SQL, $db);

    while ($xrow = DB_fetch_array($ResultIndex)) {
        $acost = (float)$xrow['averagestock'];
        $SI    = (float)$xrow['partperunit'];
        $totalcost += (((float)$xrow['loosqty'] + ((float)$xrow['fulqty'] * $SI)) * $acost);
    }

    DB_free_result($ResultIndex);

    if ($TotalUnits <= 0) {
        echo 'Nothing ' . $row['itemcode'] . ' A:' . $TotalUnits . '<br/>';
        return;
    }

    $newcost = $totalcost / $TotalUnits;
    $journal = $batchno;
    $period  = GetPeriod($strdate, $db, false);

    if ($newcost > 0) {
        DB_query(
            "INSERT INTO StockRegister (itemcode, StockIn, cost, StockOut, journal, GRN)
             VALUES ('$itemcode', $TotalUnits, $newcost, 0, '$journal', '$batchno')",
            $db
        );
    } else {
        echo 'Nothing ' . $row['itemcode'] . ' A:' . $TotalUnits . '<br/>';
    }
}

// ---------------------------------------------------------------------------
// ledgerManufucture — BOM production: calculate blended cost → StockRegister
// ---------------------------------------------------------------------------

/**
 * Calculate the cost of a manufactured item from its components and
 * insert into StockRegister.
 *
 * @param array $row  Row from buffertable
 */
function ledgerManufucture($row = array())
{
    global $db;

    $partperunit = (float)$row['partperunit'];
    $strdate     = ConvertSQLDate($row['date']);

    $TotalUnits = ($row['uom'] === 'loosqty' || $row['uom'] !== 'fulqty')
        ? abs((float)$row['units'])
        : abs((float)$row['units'] * $partperunit);

    $batchno  = mysqli_real_escape_string($db, $row['batchno']);
    $itemcode = mysqli_real_escape_string($db, trim($row['itemcode']));

    // Sum cost from buffertable components
    $totalcost   = 0;
    $ResultIndex = DB_query(
        "SELECT averagestock, partperunit, ABS(units) AS units, uom
         FROM buffertable
         WHERE Batchno = '$batchno'",
        $db
    );

    while ($xrow = DB_fetch_array($ResultIndex)) {
        $UM    = $xrow['uom'];
        $SI    = (float)$xrow['partperunit'];
        $acost = (float)$xrow['averagestock'];
        $units = (float)$xrow['units'];

        if ($UM === 'loosqty' || $UM !== 'fulqty') {
            $totalcost += $units * $acost;
        } else {
            $totalcost += $units * $SI * $acost;
        }
    }
    DB_free_result($ResultIndex);

    // Add cost from stockledger entries for the same batch
    $ResultIndex = DB_query(
        "SELECT sm.averagestock, sm.partperunit,
                ABS(sl.fulqty)  AS fulqty,
                ABS(sl.loosqty) AS loosqty
         FROM stockledger sl
         JOIN stockmaster sm ON sl.itemcode = sm.itemcode
         WHERE sl.invref = '$batchno'",
        $db
    );

    while ($xrow = DB_fetch_array($ResultIndex)) {
        $acost = (float)$xrow['averagestock'];
        $SI    = (float)$xrow['partperunit'];
        $totalcost += ((float)$xrow['fulqty'] * $SI * $acost)
                    + ((float)$xrow['loosqty'] * $acost);
    }
    DB_free_result($ResultIndex);

    if ($TotalUnits <= 0) {
        return;
    }

    $newcost = $totalcost / $TotalUnits;
    $journal = $batchno;
    $period  = GetPeriod($strdate, $db, false);

    if ($newcost > 0) {
        DB_query(
            "INSERT INTO StockRegister (itemcode, StockIn, cost, StockOut, journal, GRN)
             VALUES ('$itemcode', $TotalUnits, $newcost, 0, '$journal', '$batchno')",
            $db
        );
    }
}

// ---------------------------------------------------------------------------
// ledgerSalePlus — raw-material issue FIFO depletion (cursor → PHP loop)
// ---------------------------------------------------------------------------

/**
 * Deplete raw-material stock on a FIFO basis for a production issue.
 *
 * @param array $row  Row from buffertable
 */
function ledgerSalePlus($row = array())
{
    global $db;

    $PSU        = (float)$row['partperunit'];
    $PQTY       = abs((float)$row['units']);
    $TotalUnits = ($row['uom'] === 'fulqty') ? ($PQTY * $PSU) : $PQTY;
    $itemcode   = mysqli_real_escape_string($db, $row['itemcode']);
    $journal    = mysqli_real_escape_string($db, $row['batchno']);
    $strdate    = ConvertSQLDate($row['date']);
    $period     = GetPeriod($strdate, $db, false);

    $remaining  = $TotalUnits;

    $srResult = DB_query(
        "SELECT rowid, cost, StockBalance
         FROM   StockRegister
         WHERE  itemcode     = '$itemcode'
           AND  StockBalance > 0
         ORDER  BY rowid ASC",
        $db
    );

    while ($remaining > 0 && ($srRow = DB_fetch_array($srResult))) {
        $rowid        = (int)$srRow['rowid'];
        $stockBalance = (float)$srRow['StockBalance'];

        if ($remaining > $stockBalance) {
            $useQty    = $stockBalance;
            $remaining = $remaining - $useQty;
        } else {
            $useQty    = $remaining;
            $remaining = 0;
        }

        DB_query(
            "UPDATE StockRegister SET StockOut = StockOut + $useQty WHERE rowid = $rowid",
            $db
        );
    }

    DB_free_result($srResult);
    GetStockRegister();
}

// ---------------------------------------------------------------------------
// workorder — route a buffertable row to the right posting function
// ---------------------------------------------------------------------------

/**
 * @param array $row  Row from buffertable
 */
function workorder($row = array())
{
    if ($row['doctype'] == 26) {
        StoreToTank($row);
    } elseif ($row['units'] > 0) {
        ledgerManufucture($row);
    } else {
        ledgerSalePlus($row);
    }
}

// ---------------------------------------------------------------------------
// PostProductionGeneralStage1 — tank movements then BOM costing
// ---------------------------------------------------------------------------

function PostProductionGeneralStage1()
{
    global $db;

    $SQL = "SELECT DISTINCT batchno, date FROM tanktrans";
    $ResultIndex = DB_query($SQL, $db);
    while ($rows = DB_fetch_array($ResultIndex)) {
        Stockledgeritems($rows);
    }
    DB_free_result($ResultIndex);

    $SQL = "SELECT averagestock, partperunit, units, uom, date, batchno, doctype, itemcode
            FROM buffertable";
    $ResultIndex = DB_query($SQL, $db);
    while ($rows = DB_fetch_array($ResultIndex)) {
        workorder($rows);
    }
    DB_free_result($ResultIndex);
}

// ---------------------------------------------------------------------------
// PostProductionGeneralStage2 — FIFO depletion for all sales lines
// ---------------------------------------------------------------------------

function PostProductionGeneralStage2()
{
    global $db;

    $SQL = "SELECT
                entryno, documenttype, docdate, documentno,
                locationcode, stocktype, code, description,
                unitofmeasure, Qunatity_delivered AS Quantity,
                UnitPrice, vatamount, invoiceamount,
                completed, printed, containerprice, containersunits,
                containercode, vatrate, inclusive, UOM, shipping
            FROM SalesLine";

    $ResultIndex = DB_query($SQL, $db);

    while ($Rows = DB_fetch_array($ResultIndex)) {
        $D        = ConvertSQLDate($Rows['docdate']);
        $PeriodNo = GetPeriod($D, $db, false);
        $journal  = FindJournal($Rows['documentno']);
        $key      = $Rows['entryno'];
        $stcode   = $Rows['code'];
        $emptyCode = $Rows['containercode'];

        if ($Rows['Quantity'] > 0) {
            GetFIFO($journal, $PeriodNo, $Rows['documentno'], $stcode, $Rows['Quantity'], $key);
            GetEmptiesFIFO($journal, $PeriodNo, $Rows['documentno'], $emptyCode, $Rows['containersunits'], $key);
        }
    }

    DB_free_result($ResultIndex);
}

// ---------------------------------------------------------------------------
// isBOM / isParent — simple boolean lookups
// ---------------------------------------------------------------------------

/**
 * Return true if the item has BOM components.
 *
 * @param  string $itemcode
 * @return bool
 */
function isBOM($itemcode)
{
    global $db;
    $itemcode = mysqli_real_escape_string($db, $itemcode);
    $result   = DB_query(
        "SELECT COUNT(*) FROM BOM_items WHERE parent_itemcode = '$itemcode'", $db
    );
    $number   = DB_fetch_row($result);
    return isset($number[0]) && $number[0] > 0;
}

/**
 * Return true if the batch has more than one buffertable row (multi-component).
 *
 * @param  string $BatchNo
 * @return bool
 */
function isParent($BatchNo)
{
    global $db;
    $BatchNo = mysqli_real_escape_string($db, $BatchNo);
    $result  = DB_query(
        "SELECT COUNT(*) AS rows FROM buffertable WHERE Batchno = '$BatchNo'", $db
    );
    $number  = DB_fetch_row($result);
    return isset($number[0]) && $number[0] > 1;
}

// ---------------------------------------------------------------------------
// insertbuffertable — insert a staging row
// ---------------------------------------------------------------------------

/**
 * Insert a row into buffertable.
 * Note: `TABLE` is backtick-quoted — it is a reserved word in MySQL.
 *
 * @param float  $averagestock
 * @param float  $partperunit
 * @param float  $units
 * @param string $uom
 * @param string $date
 * @param string $batchno
 * @param int    $doctype
 * @param string $itemcode
 * @param string $TABLE
 */
function insertbuffertable($averagestock, $partperunit, $units, $uom, $date, $batchno, $doctype, $itemcode, $TABLE)
{
    global $db;

    if ($units == 0) {
        return;
    }

    $uom      = mysqli_real_escape_string($db, $uom);
    $date     = mysqli_real_escape_string($db, trim($date));
    $batchno  = mysqli_real_escape_string($db, trim($batchno));
    $itemcode = mysqli_real_escape_string($db, trim($itemcode));
    $TABLE    = mysqli_real_escape_string($db, $TABLE);
    $doctype  = (int)$doctype;

    $sql = sprintf(
        "INSERT INTO buffertable (averagestock, partperunit, units, uom, date, batchno, doctype, itemcode, `TABLE`)
         VALUES (%f, %f, %f, '%s', '%s', '%s', %d, '%s', '%s')",
        (float)$averagestock,
        (float)$partperunit,
        (float)$units,
        $uom,
        $date,
        $batchno,
        $doctype,
        $itemcode,
        $TABLE
    );

    DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Stockledgeritems — populate buffertable from tanktrans for a batch
// ---------------------------------------------------------------------------

/**
 * @param array $ProductionMaster  Row with keys: batchno, date
 */
function Stockledgeritems($ProductionMaster = array())
{
    global $db;

    $BatchNo = mysqli_real_escape_string($db, $ProductionMaster['batchno']);

    $sql = "SELECT
                sm.averagestock,
                sm.partperunit,
                tt.itemcode,
                sm.descrip,
                LTRIM(tt.uom)   AS UOM,
                tt.batchno,
                tt.units        AS Quantity,
                tt.doctype,
                tt.date
            FROM tanktrans tt
            LEFT JOIN ProdcutionMasterLine pml
                   ON pml.Batchno  = tt.batchno
                  AND pml.itemcode = tt.itemcode
            JOIN stockmaster sm ON tt.itemcode = sm.itemcode
            WHERE tt.batchno = '$BatchNo'";

    $ResultIndex = DB_query($sql, $db);

    while ($rows = DB_fetch_array($ResultIndex)) {
        $UOM = $rows['UOM'];

        if ($UOM === 'loosqty' || $UOM !== 'fulqty') {
            $units  = (float)$rows['Quantity'];
            $UOMNEW = 'loosqty';
        } else {
            $units  = (float)$rows['Quantity'] * (float)$rows['partperunit'];
            $UOMNEW = 'fulqty';
        }

        insertbuffertable(
            $rows['averagestock'],
            $rows['partperunit'],
            $units,
            $UOMNEW,
            $ProductionMaster['date'],
            $ProductionMaster['batchno'],
            $rows['doctype'],
            $rows['itemcode'],
            'tanktrans'
        );
    }

    DB_free_result($ResultIndex);
}