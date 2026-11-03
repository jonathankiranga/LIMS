<?php
$_SESSION['DocumentNo'] = isset($_POST['documentno']) ? trim($_POST['documentno']) : '';
$_SESSION['invoicerrors'] = 0;
$_SESSION['somedetails'] = 0;
$receivedLines = array();
$grnValidationErrors = array();

$sourceDocumentNo = $_SESSION['DocumentNo'];
if ($sourceDocumentNo === '' || !preg_match('/^[A-Za-z0-9._\\/-]{1,20}$/', $sourceDocumentNo)) {
    $grnValidationErrors[] = 'Invalid Purchase Order number.';
}

$sourceHeader = false;
if (count($grnValidationErrors) === 0) {
    $headerResult = DB_query("SELECT documentno, vendorcode FROM PurchaseHeader WHERE documenttype=18 AND documentno='" . $sourceDocumentNo . "'", $db);
    $sourceHeader = DB_fetch_array($headerResult);
    if (!$sourceHeader) {
        $grnValidationErrors[] = 'The selected Purchase Order was not found.';
    }
}

if (!isset($_POST['Toreceive']) || !is_array($_POST['Toreceive'])) {
    $grnValidationErrors[] = 'No receipt quantities were submitted.';
} else {
    foreach ($_POST['Toreceive'] as $entryNo => $postedQty) {
        if (!ctype_digit((string)$entryNo)) {
            $grnValidationErrors[] = 'Invalid Purchase Order line.';
            continue;
        }
        if (!is_numeric($postedQty)) {
            $grnValidationErrors[] = 'Receipt quantity must be numeric.';
            continue;
        }
        $qty = (float)$postedQty;
        if ($qty < 0) {
            $grnValidationErrors[] = 'Receipt quantities cannot be negative.';
            continue;
        }
        if ($qty == 0) {
            continue;
        }

        $lineResult = DB_query("SELECT entryno, code, description, QuantityToReceive,
                    IFNULL(Qunatity_delivered,0) AS Qunatity_delivered,
                    UnitPrice, invoiceamount, vatamount, partperunit,
                    unitofreceivedIn, vatrate, inclusive, docdate
                FROM PurchaseLine
                WHERE documenttype=18 AND documentno='" . $sourceDocumentNo . "'
                  AND entryno=" . (int)$entryNo, $db);
        $line = DB_fetch_array($lineResult);
        if (!$line) {
            $grnValidationErrors[] = 'A submitted line does not belong to this Purchase Order.';
            continue;
        }

        $outstanding = (float)$line['QuantityToReceive'] - (float)$line['Qunatity_delivered'];
        if ($outstanding < 0) {
            $outstanding = 0;
        }
        if ($qty > $outstanding + 0.00001) {
            $grnValidationErrors[] = 'Received quantity exceeds the outstanding quantity for item ' . $line['code'] . '.';
            continue;
        }

        $location = isset($_POST['location'][$entryNo]) ? trim($_POST['location'][$entryNo]) : '';
        if (!preg_match('/^[A-Za-z0-9_-]{1,10}$/', $location)) {
            $grnValidationErrors[] = 'Select a valid store for item ' . $line['code'] . '.';
            continue;
        }
        $storeResult = DB_query("SELECT code FROM Stores WHERE code='" . $location . "'", $db);
        if (!DB_fetch_array($storeResult)) {
            $grnValidationErrors[] = 'The selected store is invalid for item ' . $line['code'] . '.';
            continue;
        }

        $receivedLines[(int)$entryNo] = array(
            'entryno' => (int)$entryNo,
            'qty' => $qty,
            'location' => $location,
            'line' => $line
        );
    }
}

$_SESSION['invoicerrors'] = count($receivedLines);
$_SESSION['somedetails'] = count($grnValidationErrors);

if (count($grnValidationErrors) > 0) {
    foreach (array_unique($grnValidationErrors) as $validationMessage) {
        prnMsg($validationMessage, 'warn');
    }
} elseif (count($receivedLines) === 0) {
    prnMsg('You have not entered stock units to receive.', 'warn');
} elseif (!empty($_SESSION['DocumentPicking'])) {
    prnMsg('Create a new GRN no', 'warn');
} else {
    $grnNo = isset($_POST['manualdocumentno']) ? trim($_POST['manualdocumentno']) : '';
    if ($grnNo === '' || !preg_match('/^[A-Za-z0-9._\\/-]{1,20}$/', $grnNo)) {
        prnMsg('Enter a valid GRN number.', 'warn');
    } else {
        $ResultIndex = DB_query('SELECT NOW() AS date', $db);
        $rowdate = DB_fetch_row($ResultIndex);
        $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
        $PeriodNo = GetPeriod($_POST['posingdate'], $db, true);
        $TransBuffer = DB_Txn_Begin($db);

        if ($_SESSION['ManualNumber'] == 0) {
            $INVOICENO = GetNextTransNo(30, $db);
        } else {
            $INVOICENO = $grnNo;
        }
        $journal = GetNextTransNo(0, $db);
        $sql = array();

        $receiptValue = 0;
        foreach ($receivedLines as $receipt) {
            $sourceLine = $receipt['line'];
            $sourceQty = (float)$sourceLine['QuantityToReceive'];
            if ($sourceQty > 0) {
                $receiptValue += (float)$sourceLine['invoiceamount'] * ($receipt['qty'] / $sourceQty);
            }
        }
        $orderValueResult = DB_query("SELECT SUM(invoiceamount) AS ordervalue
            FROM PurchaseLine WHERE documenttype=18 AND documentno='" . $sourceDocumentNo . "'", $db);
        $orderValueRow = DB_fetch_array($orderValueResult);
        $orderValue = isset($orderValueRow['ordervalue']) ? (float)$orderValueRow['ordervalue'] : 0;
        $freightRatio = ($orderValue > 0) ? min(1, max(0, $receiptValue / $orderValue)) : 0;

        RecordGRN($INVOICENO, $receivedLines, $freightRatio);

        foreach ($receivedLines as $entryNo => $receipt) {
            $qty = $receipt['qty'];
            $location = $receipt['location'];
            $line = $receipt['line'];
            $salesoderno = $sourceDocumentNo;
            $itemCode = $line['code'];
            $partPerUnit = (float)$line['partperunit'];

            $sql[] = sprintf("UPDATE PurchaseLine SET
                    Qunatity_delivered=IFNULL(Qunatity_delivered,0)+%.4f,
                    Quantity_toinvoice=QuantityToReceive-(IFNULL(Qunatity_delivered,0)+%.4f)
                    WHERE documenttype=18 AND documentno='%s' AND entryno=%d",
                    $qty, $qty, $salesoderno, $entryNo);

            $sql[] = sprintf("INSERT INTO stockledger
                    (date, stname, batch, doctyp, itemcode, invref, netamt, vat, amount,
                     fulqty, loosqty, price, store, journal, period, jobcard, PartPerUnit)
                SELECT PurchaseLine.docdate, LEFT(PurchaseLine.description,30), '%d', '30',
                       PurchaseLine.code, PurchaseLine.documentno,
                       ROUND(PurchaseLine.invoiceamount * (%f / NULLIF(PurchaseLine.QuantityToReceive,0)),2),
                       ROUND(PurchaseLine.vatamount * (%f / NULLIF(PurchaseLine.QuantityToReceive,0)),2),
                       ROUND(PurchaseLine.invoiceamount * (%f / NULLIF(PurchaseLine.QuantityToReceive,0)),2),
                       IF(PurchaseLine.partperunit>1, %.4f, 0),
                       IF(PurchaseLine.partperunit=1, %.4f, 0),
                       PurchaseLine.UnitPrice, '%s', '%s', '%s', '%s', PurchaseLine.partperunit
                FROM PurchaseLine
                WHERE PurchaseLine.documenttype=18 AND PurchaseLine.documentno='%s'
                  AND PurchaseLine.entryno=%d",
                $entryNo, $qty, $qty, $qty, $qty, $qty, $location, $journal,
                $PeriodNo, $INVOICENO, $salesoderno, $entryNo);

            $results = PostStockRegister($qty, $entryNo, $journal, $PeriodNo, $INVOICENO);
        }

        foreach ($sql as $query) {
            $ResultIndex = DB_query($query, $db);
        }

        if (DB_error_no($db) > 0) {
            DB_Txn_Rollback($db);
            prnMsg('There was an Error, Contact your administrator', 'warn');
        } else {
            DB_Txn_Commit($db);
            $serialLink = '<a href="PDFPrintSerialNumbers.php?No='.$INVOICENO.'">' . _('Print Serial Numbers') . '</a>';
            prnMsg('This GRN document '.$INVOICENO.' has been created'.'<a href="PDFPrintgrnNONStock.php?No='.$INVOICENO.'">GRN Reports</a> | ' . $serialLink);
            $_SESSION['DocumentPicking'] = true;
            $_SESSION['DocumentNo'] = true;
            unset($_POST);
        }
    }
}

Function RecordGRN($DOCNO, $receivedLines, $freightRatio){
    Global $db, $sql;
    $sourceDocumentNo = $_SESSION['DocumentNo'];

    $sql[] = "INSERT INTO PurchaseHeader
           (documenttype, documentno, docdate
           ".(Is_date($_POST['Salesoderdate'])?',oderdate':'')."
           ".(Is_date($_POST['datedue'])?',duedate':'')."
           ,vendorcode, vendorname, yourreference, locationcode, postinggroup,
            currencycode, status, userid, period, vatinclusive, Dimension_1,
            Dimension_2, freight)
      (SELECT 30, '".$DOCNO."', '".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?',oderdate':'')."
           ".(Is_date($_POST['datedue'])?',duedate':'')."
           ,vendorcode, vendorname, yourreference, locationcode, postinggroup,
            currencycode, status, userid, period, vatinclusive, Dimension_1,
            Dimension_2, (IFNULL(freight,0) * ".sprintf('%.8f', (float)$freightRatio).")
       FROM PurchaseHeader
       WHERE documenttype=18 AND documentno='".$sourceDocumentNo."')";

    foreach ($receivedLines as $entryNo => $receipt) {
        $qty = $receipt['qty'];
        $sql[] = sprintf("INSERT INTO PurchaseLine
                (documenttype, docdate, documentno, code, description, unitofmeasure,
                 Quantity, UnitPrice, vatamount, invoiceamount, vatrate, inclusive, partperunit)
            SELECT 30, docdate, '%s', code, description, unitofreceivedIn, %.4f,
                   (UnitPrice * Quantity) / NULLIF(QuantityToReceive,0),
                   ROUND(vatamount * (%.4f / NULLIF(QuantityToReceive,0)),2),
                   ROUND(invoiceamount * (%.4f / NULLIF(QuantityToReceive,0)),2),
                   vatrate, inclusive, partperunit
            FROM PurchaseLine
            WHERE documenttype=18 AND documentno='%s' AND entryno=%d",
            $DOCNO, $qty, $qty, $qty, $sourceDocumentNo, $entryNo);
    }
    return $sql;
}
?>