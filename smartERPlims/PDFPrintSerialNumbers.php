<?php
include('includes/session.inc');
$Title = _('Print Serial Numbers');

if (!isset($_GET['No'])) {
    include('includes/header.inc');
    echo '<p>' . _('No GRN document specified') . '</p>';
    include('includes/footer.inc');
    exit;
}

$GRN = $_GET['No'];

// Check if serial numbers exist for this GRN
$countSQL = "SELECT COUNT(*) FROM StockRegister WHERE GRN='" . $GRN . "' AND serial != ''";
$countResult = DB_query($countSQL, $db);
$countRow = DB_fetch_row($countResult);
$serialCount = (int)$countRow[0];

if ($serialCount == 0) {
    include('includes/header.inc');
    echo '<p>' . _('No serial numbers found for this GRN') . '</p>';
    include('includes/footer.inc');
    exit;
}

// Query GRN header data for the PDF header (requires $myrow)
$hdrSQL = "SELECT
               PurchaseHeader.documentno,
               PurchaseHeader.docdate,
               PurchaseHeader.oderdate,
               PurchaseHeader.duedate,
               PurchaseHeader.vendorcode,
               PurchaseHeader.vendorname,
               PurchaseHeader.currencycode,
               PurchaseHeader.status,
               PurchaseHeader.userid,
               creditors.email,
               creditors.city,
               creditors.postcode,
               creditors.country,
               creditors.phone,
               creditors.contact,
               '" . $GRN . "' AS jobcard
           FROM PurchaseHeader
           JOIN creditors ON creditors.itemcode = PurchaseHeader.vendorcode
           WHERE PurchaseHeader.documentno='" . $GRN . "'
             AND PurchaseHeader.documenttype=30";
$hdrResult = DB_query($hdrSQL, $db);
$myrow = DB_fetch_row($hdrResult);

if (!$myrow) {
    include('includes/header.inc');
    echo '<p>' . _('GRN document not found') . '</p>';
    include('includes/footer.inc');
    exit;
}

$PaperSize = 'A4';
include('includes/PDFStarter.php');
$headerName = _('Serial Numbers');

$pdf->addInfo('Title', _('Serial Numbers - GRN ' . $GRN));
$pdf->addInfo('Subject', _('Serial Numbers'));
$pdf->addInfo('Creator', _('SmartERP'));

$FontSize = 10;
$line_height = 8;

include('includes/PDFgrntheader.inc');

$YPos = $firstrowpos - 20;

// Serial numbers table header
$pdf->addTextWrap(42, $YPos, 100, $FontSize, _('Serial Number'), 'left');
$pdf->addTextWrap(180, $YPos, 100, $FontSize, _('Item Code'), 'left');
$pdf->addTextWrap(300, $YPos, 160, $FontSize, _('Description'), 'left');
$pdf->addTextWrap(480, $YPos, 50, $FontSize, _('Printed'), 'left');
$YPos -= $line_height * 2;

// Query serial numbers
$sql = "SELECT sr.serial, sr.itemcode, sr.printed, sm.descrip
        FROM StockRegister sr
        JOIN stockmaster sm ON sm.itemcode = sr.itemcode
        WHERE sr.GRN='" . $GRN . "'
          AND sr.serial != ''
        ORDER BY sr.serial";
$result = DB_query($sql, $db);

while ($row = DB_fetch_array($result)) {
    if ($YPos < $Bottom_Margin) {
        $PageNumber++;
        include('includes/PDFgrntheader.inc');
        $YPos = $firstrowpos - 20;
        $pdf->addTextWrap(42, $YPos, 100, $FontSize, _('Serial Number'), 'left');
        $pdf->addTextWrap(180, $YPos, 100, $FontSize, _('Item Code'), 'left');
        $pdf->addTextWrap(300, $YPos, 160, $FontSize, _('Description'), 'left');
        $pdf->addTextWrap(480, $YPos, 50, $FontSize, _('Printed'), 'left');
        $YPos -= $line_height * 2;
    }

    $pdf->addTextWrap(42, $YPos, 100, $FontSize, $row['serial'], 'left');
    $pdf->addTextWrap(180, $YPos, 100, $FontSize, $row['itemcode'], 'left');
    $pdf->addTextWrap(300, $YPos, 160, $FontSize, $row['descrip'], 'left');
    $pdf->addTextWrap(480, $YPos, 50, $FontSize, $row['printed'] == 1 ? _('Yes') : _('No'), 'left');
    $YPos -= $line_height * 2;
}

$pdf->OutputD($_SESSION['DatabaseName'] . '_SerialNumbers_' . $GRN . '_' . date('Y-m-d') . '.pdf');
$pdf->__destruct();

// Mark all printed serials as printed
DB_query("UPDATE StockRegister SET printed=1 WHERE GRN='" . $GRN . "' AND serial != ''", $db);
?>
