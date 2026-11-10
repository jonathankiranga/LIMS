<?php
/**
 * RFQ Supplier Invitation Summary
 * Generates a printable, landscape PDF report for the selected RFQ.
 */
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$rfqId = isset($_GET['rfq_id']) ? (int)$_GET['rfq_id'] : 0;
if ($rfqId <= 0) {
    die('A valid RFQ must be selected.');
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

require_once('includes/tcpdf/tcpdf.php');

class RFQInviteSummaryPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-10);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(110, 120, 135);
        $this->Cell(0, 6, 'RFQ Supplier Invitation Summary  |  Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

$paperSize = count($invites) > 8 ? 'A2' : (count($invites) > 4 ? 'A3' : 'A4');
$pdf = new RFQInviteSummaryPDF('L', 'mm', $paperSize, true, 'UTF-8', false);
$pdf->SetCreator('SmartERPlims');
$pdf->SetAuthor(isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : 'SmartERPlims');
$pdf->SetTitle('RFQ Invitation Summary - ' . $rfq['rfq_no']);
$pdf->SetMargins(12, 12, 12);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(8);
$pdf->SetAutoPageBreak(true, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->AddPage();

$companyName = isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : 'SmartERPlims';
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(35, 55, 78);
$pdf->Cell(0, 9, htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'), 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 8, 'SUPPLIER INVITATION SUMMARY', 0, 1, 'L');
$pdf->SetDrawColor(47, 111, 159);
$pdf->SetLineWidth(0.6);
$pdf->Line(12, $pdf->GetY(), 285, $pdf->GetY());
$pdf->Ln(4);

$h = function($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$pdfCss = '<style>
    table { border-collapse: collapse; }
    th { background-color: #315f82; color: #ffffff; font-weight: bold; }
    td, th { border: 1px solid #d7dee7; padding: 5px; }
    .report-label { background-color: #edf2f7; color: #263445; font-weight: bold; }
    .report-muted { color: #667085; }
</style>';
$pdf->writeHTML($pdfCss, true, false, true, false, '');

$summaryHtml = '
<table cellpadding="5" cellspacing="0" border="0">
<tr>
<td width="18%" bgcolor="#edf2f7"><b>RFQ Number</b></td><td width="32%">' . $h($rfq['rfq_no']) . '</td>
<td width="18%" bgcolor="#edf2f7"><b>Status</b></td><td width="32%">' . $h(ucfirst($rfq['status'])) . '</td>
</tr>
<tr>
<td bgcolor="#edf2f7"><b>RFQ Title</b></td><td>' . $h($rfq['title']) . '</td>
<td bgcolor="#edf2f7"><b>Currency</b></td><td>' . $h($rfq['currencycode']) . '</td>
</tr>
<tr>
<td bgcolor="#edf2f7"><b>Created</b></td><td>' . $h($rfq['created_at']) . '</td>
<td bgcolor="#edf2f7"><b>Closing Date</b></td><td>' . $h($rfq['closing_at']) . '</td>
</tr>
<tr>
<td bgcolor="#edf2f7"><b>Issued Date</b></td><td>' . $h($rfq['issue_date'] ? $rfq['issue_date'] : 'Not issued') . '</td>
<td bgcolor="#edf2f7"><b>Suppliers Invited</b></td><td>' . count($invites) . '</td>
</tr>
</table>
<br/>
<table cellpadding="5" cellspacing="0" border="0">
<tr>
<td width="25%" bgcolor="#eaf0ff"><b>Awaiting response: ' . $counts['invited'] . '</b></td>
<td width="25%" bgcolor="#e8f6ef"><b>Submitted: ' . $counts['submitted'] . '</b></td>
<td width="25%" bgcolor="#fff4e5"><b>Declined: ' . $counts['declined'] . '</b></td>
<td width="25%" bgcolor="#f0f2f5"><b>Expired: ' . $counts['expired'] . '</b></td>
</tr>
</table><br/>';
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($summaryHtml, true, false, true, false, '');


/* Build the supplier-by-stock-item quotation comparison matrix. */
$rfqLinesResult = DB_query("SELECT rl.id,rl.itemcode,rl.description,rl.specification,rl.quantity,
        rl.requested_pack,u.descrip AS requested_unit_description
    FROM rfq_lines rl
    LEFT JOIN unit u ON u.code=rl.requested_pack
    WHERE rl.rfq_id=" . $rfqId . " ORDER BY rl.line_no", $db);
$rfqLines = array();
while ($rfqLine = DB_fetch_array($rfqLinesResult)) {
    $rfqLines[] = $rfqLine;
}

$quoteLinesResult = DB_query("SELECT q.supplier_code,q.tax_inclusive,ql.rfq_line_id,ql.unit_price,
        ql.tax_rate,ql.availability_notes
    FROM rfq_quotes q
    INNER JOIN rfq_quote_lines ql ON ql.quote_id=q.id
    WHERE q.rfq_id=" . $rfqId, $db);
$quoteMatrix = array();
while ($quoteLine = DB_fetch_array($quoteLinesResult)) {
    $quoteMatrix[$quoteLine['supplier_code']][(int)$quoteLine['rfq_line_id']] = $quoteLine;
}

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(35, 55, 78);
$pdf->Cell(0, 8, 'SUPPLIER QUOTATION COMPARISON', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(90, 100, 115);
$pdf->MultiCell(0, 5, 'Rows show the RFQ stock items. Each supplier column shows the unit price submitted for that item. "Not submitted" means no quotation response was recorded.', 0, 'L');
$pdf->Ln(2);

$supplierCount = count($invites);
$itemWidth = $supplierCount > 8 ? 24 : ($supplierCount > 4 ? 28 : 34);
$supplierWidth = $supplierCount > 0 ? (100 - $itemWidth) / $supplierCount : 0;
$matrixHtml = '<table border="1" cellpadding="4" cellspacing="0">';
$matrixHtml .= '<thead><tr bgcolor="#315f82" color="#ffffff">';
$matrixHtml .= '<th width="' . $itemWidth . '%"><b>Stock item / requested quantity</b></th>';
foreach ($invites as $invite) {
    $supplierName = $invite['customer'] ? $invite['customer'] : $invite['supplier_code'];
    $matrixHtml .= '<th width="' . $supplierWidth . '%"><b>' . $h($supplierName) . '</b><br/><small>' .
        $h($invite['supplier_code']) . '</small></th>';
}
$matrixHtml .= '</tr></thead><tbody>';

if (!count($rfqLines)) {
    $matrixHtml .= '<tr><td colspan="' . (count($invites) + 1) . '" align="center">No stock items are recorded on this RFQ.</td></tr>';
} else {
    foreach ($rfqLines as $rfqLine) {
        $unitDescription = !empty($rfqLine['requested_unit_description']) ? $rfqLine['requested_unit_description'] : $rfqLine['requested_pack'];
        $itemLabel = $h($rfqLine['description']);
        if (!empty($rfqLine['itemcode'])) {
            $itemLabel .= '<br/><small>Code: ' . $h($rfqLine['itemcode']) . '</small>';
        }
        if (!empty($rfqLine['specification'])) {
            $itemLabel .= '<br/><small>' . nl2br($h($rfqLine['specification'])) . '</small>';
        }
        $itemLabel .= '<br/><b>Qty:</b> ' . $h($rfqLine['quantity'] . ' ' . $unitDescription);

        $matrixHtml .= '<tr><td width="' . $itemWidth . '%" bgcolor="#f3f6fa">' . $itemLabel . '</td>';
        foreach ($invites as $invite) {
            $supplierCode = $invite['supplier_code'];
            $lineId = (int)$rfqLine['id'];
            if (isset($quoteMatrix[$supplierCode][$lineId])) {
                $quoteLine = $quoteMatrix[$supplierCode][$lineId];
                $price = number_format((float)$quoteLine['unit_price'], 2);
                $taxRate = number_format((float)$quoteLine['tax_rate'], 2);
                $taxLabel = ((int)$quoteLine['tax_inclusive'] === 1) ? 'Tax inclusive' : 'Tax exclusive';
                $cell = '<b>' . $h($rfq['currencycode']) . ' ' . $price . '</b><br/><small>' .
                    $taxLabel . ' · Tax ' . $taxRate . '%</small>';
                if (trim((string)$quoteLine['availability_notes']) !== '') {
                    $cell .= '<br/><small>' . $h($quoteLine['availability_notes']) . '</small>';
                }
                $matrixHtml .= '<td width="' . $supplierWidth . '%">' . $cell . '</td>';
            } else {
                $matrixHtml .= '<td width="' . $supplierWidth . '%" bgcolor="#f5f6f8" color="#7b8492"><i>Not submitted</i></td>';
            }
        }
        $matrixHtml .= '</tr>';
    }
}
$matrixHtml .= '</tbody></table>';
$pdf->SetFont('helvetica', '', 8);
$pdf->writeHTML($matrixHtml, true, false, true, false, '');
$pdf->Ln(4);

$table = '<table border="1" cellpadding="5" cellspacing="0">
<thead>
<tr bgcolor="#315f82" color="#ffffff">
<th width="6%"><b>#</b></th>
<th width="25%"><b>Supplier</b></th>
<th width="13%"><b>Supplier Code</b></th>
<th width="22%"><b>Email Address</b></th>
<th width="12%"><b>Invitation Status</b></th>
<th width="11%"><b>Email Dispatch</b></th>
<th width="11%"><b>Invited / Submitted</b></th>
</tr>
</thead><tbody>';
if (!count($invites)) {
    $table .= '<tr><td colspan="7" align="center">No supplier invitations have been recorded for this RFQ.</td></tr>';
} else {
    foreach ($invites as $index => $invite) {
        $supplierName = $invite['customer'] ? $invite['customer'] : $invite['supplier_code'];
        $dateText = $invite['submitted_at'] ? $invite['submitted_at'] : $invite['invited_at'];
        $table .= '<tr>
<td align="center">' . ($index + 1) . '</td>
<td>' . $h($supplierName) . '</td>
<td>' . $h($invite['supplier_code']) . '</td>
<td>' . $h($invite['supplier_email']) . '</td>
<td>' . $h(ucfirst($invite['status'])) . '</td>
<td>' . ((int)$invite['successful_emails'] > 0 ? 'Accepted by SMTP' : 'Not confirmed') . '</td>
<td>' . $h($dateText) . '</td>
</tr>';
    }
}
$table .= '</tbody></table>';
$pdf->writeHTML($table, true, false, true, false, '');

if (trim((string)$rfq['description']) !== '') {
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'Purpose / Description', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $rfq['description'], 0, 'L');
}
if (trim((string)$rfq['terms_conditions']) !== '') {
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'RFQ Terms and Conditions', 0, 1);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 4, $rfq['terms_conditions'], 0, 'L');
}

$pdf->Output('RFQ-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $rfq['rfq_no']) . '-Invitations.pdf', 'I');
exit;
