<?php
/**
 * RFQ supplier response workbook.
 * Creates a native XLSX file without an external spreadsheet package.
 */
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$rfqId = isset($_GET['rfq_id']) ? (int)$_GET['rfq_id'] : 0;
if ($rfqId <= 0) {
    die('A valid RFQ must be selected.');
}
if (!class_exists('ZipArchive')) {
    die('Excel export requires the PHP ZipArchive extension. Enable php_zip and try again.');
}

$rfqResult = DB_query("SELECT id,rfq_no,title,description,terms_conditions,currencycode,closing_at,status,created_at,issue_date
    FROM rfq_header WHERE id=" . $rfqId . " LIMIT 1", $db);
if (DB_num_rows($rfqResult) !== 1) {
    die('The requested RFQ could not be found.');
}
$rfq = DB_fetch_array($rfqResult);

$inviteResult = DB_query("SELECT i.supplier_code,i.supplier_email,i.status,i.invited_at,i.submitted_at,
        c.customer,
        (SELECT COUNT(*) FROM rfq_email_log l
            WHERE l.rfq_id=i.rfq_id AND l.supplier_code=i.supplier_code AND l.success=1) AS successful_emails
    FROM rfq_invites i
    LEFT JOIN creditors c ON c.itemcode COLLATE utf8mb4_unicode_ci = i.supplier_code COLLATE utf8mb4_unicode_ci
    WHERE i.rfq_id=" . $rfqId . "
    ORDER BY c.customer,i.supplier_code", $db);
$invites = array();
$counts = array('invited'=>0,'submitted'=>0,'declined'=>0,'expired'=>0);
while ($invite = DB_fetch_array($inviteResult)) {
    $invites[] = $invite;
    if (isset($counts[$invite['status']])) {
        $counts[$invite['status']]++;
    }
}

$linesResult = DB_query("SELECT rl.id,rl.itemcode,rl.description,rl.specification,rl.quantity,
        rl.requested_pack,sm.descrip AS inventory_name,u.descrip AS requested_unit_description
    FROM rfq_lines rl
    LEFT JOIN stockmaster sm ON sm.itemcode COLLATE utf8mb4_unicode_ci = rl.itemcode COLLATE utf8mb4_unicode_ci
    LEFT JOIN unit u ON u.code=CASE rl.requested_pack WHEN 'units' THEN sm.units WHEN 'subunits' THEN sm.subunits ELSE rl.requested_pack END
    WHERE rl.rfq_id=" . $rfqId . " ORDER BY rl.line_no", $db);
$rfqLines = array();
while ($line = DB_fetch_array($linesResult)) {
    $rfqLines[] = $line;
}

$quoteResult = DB_query("SELECT q.supplier_code,q.tax_inclusive,q.quote_reference,q.validity_date,
        q.delivery_days,q.payment_terms,q.supplier_notes,ql.rfq_line_id,ql.unit_price,
        ql.tax_rate,ql.availability_notes
    FROM rfq_quotes q
    INNER JOIN rfq_quote_lines ql ON ql.quote_id=q.id
    WHERE q.rfq_id=" . $rfqId, $db);
$quoteMatrix = array();
while ($quote = DB_fetch_array($quoteResult)) {
    $quoteMatrix[$quote['supplier_code']][(int)$quote['rfq_line_id']] = $quote;
}

if (count($invites) > 16383) {
    die('This RFQ has more suppliers than a single Excel worksheet can display as columns (16,383 suppliers plus the stock-item column).');
}
if (count($rfqLines) > 1048574) {
    die('This RFQ has more stock lines than a single Excel worksheet can display.');
}

function rfq_xlsx_col($number) {
    $name = '';
    while ($number > 0) {
        $remainder = ($number - 1) % 26;
        $name = chr(65 + $remainder) . $name;
        $number = (int)(($number - 1) / 26);
    }
    return $name;
}
function rfq_xlsx_xml($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
function rfq_xlsx_cell($reference, $value, $style = 0) {
    $styleAttr = $style ? ' s="' . (int)$style . '"' : '';
    if ($value === null || $value === '') {
        return '<c r="' . $reference . '"' . $styleAttr . '/>';
    }
    if (is_int($value) || is_float($value)) {
        return '<c r="' . $reference . '" t="n"' . $styleAttr . '><v>' . (string)$value . '</v></c>';
    }
    return '<c r="' . $reference . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' .
        rfq_xlsx_xml($value) . '</t></is></c>';
}
function rfq_xlsx_sheet($rows) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<sheetViews><sheetView workbookViewId="0"><pane xSplit="1" ySplit="1" topLeftCell="B2" activePane="bottomRight" state="frozen"/></sheetView></sheetViews>';
    $xml .= '<sheetFormatPr defaultRowHeight="19"/>';
    $columnCount = count($rows[0]);
    $xml .= '<cols><col min="1" max="1" width="58" customWidth="1"/>';
    if ($columnCount > 1) {
        $xml .= '<col min="2" max="' . (int)$columnCount . '" width="24" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData>';
    foreach ($rows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 1;
        $xml .= '<row r="' . $rowNumber . '">';
        foreach ($row as $columnIndex => $cell) {
            $xml .= rfq_xlsx_cell(rfq_xlsx_col($columnIndex + 1) . $rowNumber, $cell[0], isset($cell[1]) ? $cell[1] : 0);
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData><autoFilter ref="A1:' . rfq_xlsx_col(max(1, count($rows[0]))) . count($rows) . '"/>';
    $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
    $xml .= '</worksheet>';
    return $xml;
}

$matrixRows = array();
$header = array(array('Stock item / requested quantity', 1));
foreach ($invites as $invite) {
    $supplierName = trim((string)$invite['customer']) !== '' ? $invite['customer'] : $invite['supplier_code'];
    $header[] = array($supplierName . ' [' . $invite['supplier_code'] . '] (' . $rfq['currencycode'] . ')', 1);
}
$matrixRows[] = $header;
foreach ($rfqLines as $line) {
    $unitDescription = !empty($line['requested_unit_description']) ? $line['requested_unit_description'] : $line['requested_pack'];
    $inventoryName = trim((string)$line['inventory_name']);
    $label = $inventoryName !== '' ? $inventoryName : trim((string)$line['description']);
    $requestedDescription = trim((string)$line['description']);
    if ($requestedDescription !== '' && strcasecmp($requestedDescription, $label) !== 0) {
        $label .= ' | Requested description: ' . $requestedDescription;
    }
    $specification = trim((string)$line['specification']);
    $specification = preg_replace('/^Grade\\s*/i', '', $specification);
    if ($specification !== '') { $label .= ' | ' . $specification; }
    $label .= ' | Qty: ' . $line['quantity'] . ' ' . $unitDescription;
    $row = array(array($label, 2));
    foreach ($invites as $invite) {
        $supplierCode = $invite['supplier_code'];
        $lineId = (int)$line['id'];
        if (isset($quoteMatrix[$supplierCode][$lineId])) {
            $quote = $quoteMatrix[$supplierCode][$lineId];
            $row[] = array((float)$quote['unit_price'], 3);
        } else {
            $row[] = array('Not quoted', 4);
        }
    }
    $matrixRows[] = $row;
}
if (!count($rfqLines)) {
    $matrixRows[] = array(array('No stock items are recorded on this RFQ.', 4));
}

$quoteDetailRows = array(
    array(array('Stock Item',1),array('Item Code',1),array('Supplier',1),array('Supplier Code',1),array('Unit Price',1),array('Currency',1),array('Tax Treatment',1),array('Tax Rate %',1),array('Availability Notes',1))
);
foreach ($rfqLines as $line) {
    foreach ($invites as $invite) {
        $supplierCode = $invite['supplier_code'];
        $lineId = (int)$line['id'];
        if (!isset($quoteMatrix[$supplierCode][$lineId])) { continue; }
        $quote = $quoteMatrix[$supplierCode][$lineId];
        $supplierName = trim((string)$invite['customer']) !== '' ? $invite['customer'] : $supplierCode;
        $quoteDetailRows[] = array(
            array(trim((string)$line['inventory_name']) !== '' ? $line['inventory_name'] : $line['description']), array($line['itemcode']), array($supplierName), array($supplierCode),
            array((float)$quote['unit_price']), array($rfq['currencycode']),
            array(((int)$quote['tax_inclusive'] === 1) ? 'Tax inclusive' : 'Tax exclusive'),
            array((float)$quote['tax_rate']), array($quote['availability_notes'])
        );
    }
}
if (count($quoteDetailRows) === 1) {
    $quoteDetailRows[] = array(array('No supplier line prices have been submitted.'));
}

$invitationRows = array(
    array(array('Supplier',1),array('Supplier Code',1),array('Email Address',1),array('Invitation Status',1),array('Email Dispatch',1),array('Invited At',1),array('Submitted At',1))
);
foreach ($invites as $invite) {
    $supplierName = trim((string)$invite['customer']) !== '' ? $invite['customer'] : $invite['supplier_code'];
    $invitationRows[] = array(
        array($supplierName), array($invite['supplier_code']), array($invite['supplier_email']),
        array(ucfirst($invite['status'])),
        array((int)$invite['successful_emails'] > 0 ? 'Accepted by SMTP' : 'Not confirmed'),
        array($invite['invited_at']), array($invite['submitted_at'])
    );
}
if (!count($invites)) {
    $invitationRows[] = array(array('No supplier invitations recorded.'));
}

$detailRows = array(
    array(array('RFQ DETAILS',1),array('',1)),
    array(array('Company'),array(isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : 'SmartERPlims')),
    array(array('RFQ Number'),array($rfq['rfq_no'])),
    array(array('Title'),array($rfq['title'])),
    array(array('Status'),array(ucfirst($rfq['status']))),
    array(array('Currency'),array($rfq['currencycode'])),
    array(array('Created'),array($rfq['created_at'])),
    array(array('Issued'),array($rfq['issue_date'] ? $rfq['issue_date'] : 'Not issued')),
    array(array('Closing Date'),array($rfq['closing_at'])),
    array(array('Suppliers Invited'),array(count($invites))),
    array(array('Awaiting Response'),array($counts['invited'])),
    array(array('Submitted'),array($counts['submitted'])),
    array(array('Declined'),array($counts['declined'])),
    array(array('Expired'),array($counts['expired'])),
    array(array('Description'),array($rfq['description'])),
    array(array('Terms and Conditions'),array($rfq['terms_conditions']))
);

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
'<fonts count="2"><font><sz val="10"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Calibri"/></font></fonts>' .
'<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF315F82"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF3F6FA"/><bgColor indexed="64"/></patternFill></fill></fills>' .
'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD7DEE7"/></left><right style="thin"><color rgb="FFD7DEE7"/></right><top style="thin"><color rgb="FFD7DEE7"/></top><bottom style="thin"><color rgb="FFD7DEE7"/></bottom><diagonal/></border></borders>' .
'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
'<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf></cellXfs>' .
'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' .
'<sheet name="Quote Matrix" sheetId="1" r:id="rId1"/><sheet name="Quote Details" sheetId="2" r:id="rId2"/><sheet name="Invitations" sheetId="3" r:id="rId3"/><sheet name="RFQ Details" sheetId="4" r:id="rId4"/>' .
'</sheets></workbook>';
$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/>' .
'<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
'</Relationships>';
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>' .
'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
'<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';

$tmpFile = tempnam(sys_get_temp_dir(), 'rfqx_');
$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    @unlink($tmpFile);
    die('The Excel workbook could not be created on the server.');
}
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rootRels);
$zip->addFromString('xl/workbook.xml', $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
$zip->addFromString('xl/styles.xml', $styles);
$zip->addFromString('xl/worksheets/sheet1.xml', rfq_xlsx_sheet($matrixRows));
$zip->addFromString('xl/worksheets/sheet2.xml', rfq_xlsx_sheet($quoteDetailRows));
$zip->addFromString('xl/worksheets/sheet3.xml', rfq_xlsx_sheet($invitationRows));
$zip->addFromString('xl/worksheets/sheet4.xml', rfq_xlsx_sheet($detailRows));
$zip->close();

$fileName = 'RFQ-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $rfq['rfq_no']) . '-Supplier-Responses.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($tmpFile);
@unlink($tmpFile);
exit;
