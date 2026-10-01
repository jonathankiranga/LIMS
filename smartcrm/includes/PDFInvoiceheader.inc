<?php

function printCompanyInfo($pdf, $x, $y, $fontSize, $company) {
    $lines = [
        htmlspecialcharsLocal_decode($company['coyname']),
        trim($company['regoffice1']),
        _('Ph') . ': ' . $company['telephone'],
        trim($company['email']),
        _('PIN') . ': ' . $company['PIN'],
        trim($company['regoffice2']) . ' ' . $company['regoffice3'],
        trim($company['regoffice4']) . ' ' . $company['regoffice5'] . ' ' . $company['regoffice6']
    ];
    foreach ($lines as $i => $line) {
        $pdf->addText($x, $y - ($i * 10 + 2), $fontSize, $line);
    }
}

function printAddressBlock($pdf, $x, $y, $fontSize, $label, $data) {
    $pdf->addTextWrap($x, $y, 100, $fontSize, _($label) . ':');
    foreach ($data as $i => $line) {
        $pdf->addTextWrap($x, $y - 15 * ($i + 1), 240, $fontSize, $line);
    }
}

function drawRoundedBox($pdf, $x, $y, $width, $height) {
    $pdf->RoundRectangle($x, $y, $width, $height, 10, 10);
}

function printTableHeader($pdf, $x, $y, $fontSize, $lineHeight) {
    $pdf->addTextWrap($x + 2, $y, 50, $fontSize, _('Item Code'), 'left');
    $pdf->addTextWrap($x + 52, $y, 150, $fontSize, _('Item'), 'left');
    $pdf->addTextWrap($x + 52, $y - $lineHeight, 150, $fontSize, _('Description'), 'left');
    $pdf->addTextWrap($x + 210, $y, 50, $fontSize, _('Package'), 'right');
    $pdf->addTextWrap($x + 260, $y, 50, $fontSize, _('Quantity'), 'right');
    $pdf->addTextWrap($x + 315, $y, 50, $fontSize, _('Price'), 'right');
    $pdf->addTextWrap($x + 315, $y - $lineHeight, 50, $fontSize, _('Per Package'), 'right');
    $pdf->addTextWrap($x + 340, $y, 55, $fontSize, _('VAT'), 'right');
    $pdf->addTextWrap($x + 340, $y - $lineHeight, 55, $fontSize, _('Rate'), 'right');
    $pdf->addTextWrap($x + 390, $y, 70, $fontSize, _('VAT'), 'right');
    $pdf->addTextWrap($x + 390, $y - $lineHeight, 70, $fontSize, _('Amount'), 'right');
    $pdf->addTextWrap($x + 450, $y, 70, $fontSize, _('Total'), 'right');
    $pdf->addTextWrap($x + 450, $y - $lineHeight, 70, $fontSize, _('Amount'), 'right');
}

function drawColumnLines($pdf, $topY, $bottomY) {
    foreach ([90, 260, 305, 350, 410, 440, 500] as $x) {
        $pdf->line($x, $topY, $x, $bottomY);
    }
}

function drawLastColumnLine($pdf, $topY, $bottomY) {
    foreach ([500] as $x) {
        $pdf->line($x, $topY, $x, $bottomY);
    }
}

$PageNumber++;
if ($PageNumber > 1) $pdf->newPage();

$pdf->addText(($Page_Width - $Right_Margin)/2 + 50, $Page_Height - $Top_Margin, 16, $headerName);

$infoY = $Page_Height - $Top_Margin - $FontSize * 2;
if ($headerName == 'ORDER') {
    $pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY, 200, $FontSize, _('Order Due: ') . ConvertSQLDate($myrow[3]), 'right');
} elseif ($headerName == 'INVOICE') {
    $pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY, 200, $FontSize, _('Your REF: ') . $myrow[16], 'right');
}

$pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY - $FontSize, 200, $FontSize, _('Date') . ': ' . ConvertSQLDate($myrow[1]), 'right');
$pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY - $FontSize * 2, 200, $FontSize, _('Page') . ': ' . $PageNumber, 'right');
$pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY - $FontSize * 3, 200, $FontSize, _('Delivery Note number'), 'right');
$pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY - $FontSize * 4, 200, $FontSize, $INVOICEno, 'right');
$pdf->addTextWrap($Page_Width - $Right_Margin - 200, $infoY - $FontSize * 5, 200, $FontSize, _($headerName . ' No') . ': ' . $_GET['No'], 'right');

$pdf->addJpegFromFile($_SESSION['LogoFile'], (($Page_Width - $Right_Margin)/2)+50, $Page_Height - $Top_Margin - $FontSize * 7, 0, 60);

$XPos = 45;
$YPos = $Page_Height - $Top_Margin - $FontSize;
printCompanyInfo($pdf, $XPos, $YPos, 10, $_SESSION['CompanyRecord']);

$address = [htmlspecialcharsLocal_decode($myrow[5]), $myrow[12], $myrow[13], $myrow[15]];
printAddressBlock($pdf, 50, $YPos - 85, 10, 'Delivery To', $address);
printAddressBlock($pdf, (($Page_Width - $Right_Margin) / 2) + 50, $YPos - 85, 10, $headerName . ' For', $address);

drawRoundedBox($pdf, 50, $YPos - 85, 265, 80);
drawRoundedBox($pdf, (($Page_Width - $Right_Margin) / 2) + 40, $YPos - 85, 265, 80);

$pdf->addText(($Page_Width/2)-60, $YPos - 170, 10, _('Currency') . ' - ' . $myrow[6] . ' ' . $CurrencyName[$myrow[6]]);

$tableTop = $YPos - 195;
$FontSize = 10;
$lineHeight = 10;
printTableHeader($pdf, 40, $tableTop-10, $FontSize, $lineHeight);
$firstrowpos = $tableTop - $lineHeight * 2;
$tableBottom = $firstrowpos - (($Page_Height / 2) - $Top_Margin) + 75;

// Leave body printing to parent program after this point
// Just draw the structure and summary lines
$pdf->line($Left_Margin + 10, $tableTop, $Page_Width - $Right_Margin - 10, $tableTop);
$pdf->line($Left_Margin, $tableTop - 30, $Page_Width - $Right_Margin, $tableTop - 30);
$pdf->line($Left_Margin, $tableTop - 10, $Left_Margin, $tableBottom + 10);
$pdf->line($Left_Margin + 10, $tableBottom, $Page_Width - $Right_Margin - 10, $tableBottom);
$pdf->line($Left_Margin, $tableBottom + 125, $Page_Width - $Right_Margin, $tableBottom + 125);
$pdf->line($Page_Width - $Right_Margin, $tableTop - 10, $Page_Width - $Right_Margin, $tableBottom + 10);

$pdf->partEllipse($Page_Width - $Right_Margin - 10, $tableTop - 10, 0, 90, 10, 10);
$pdf->partEllipse($Left_Margin + 10, $tableTop - 10, 90, 180, 10, 10);
$pdf->partEllipse($Left_Margin + 10, $tableBottom + 10, 180, 270, 10, 10);
$pdf->partEllipse($Page_Width - $Right_Margin - 10, $tableBottom + 10, 270, 350, 10, 10);

drawColumnLines($pdf, $tableTop, $tableBottom + 125);
drawLastColumnLine($pdf, $tableTop, $tableBottom);

$summaryY = $tableBottom + 100;
$pdf->addTextWrap(312, $summaryY, 120, 15, _('SUB TOTAL'), 'right');
$BelowSummary = $pdf->GetY();
$pdf->addTextWrap(312, $summaryY -= $lineHeight * 1.8, 120, 15, _('VAT AMOUNT'), 'right');
$pdf->addTextWrap(312, $summaryY -= $lineHeight * 2.2, 120, 15, _('SHIPPING'), 'right');
$pdf->addTextWrap(312, $summaryY -= $lineHeight * 2, 120, 15, _('PACKAGING'), 'right');
$pdf->addTextWrap(312, $summaryY -= $lineHeight * 2, 120, 15, _('TOTAL INVOICE'), 'right');

foreach ([100, 75, 50, 25] as $offset) {
    $pdf->line(500, $tableBottom + $offset, $Page_Width - $Right_Margin, $tableBottom + $offset);
}



if (isset($_SESSION['RomalpaClause'])) {
    $pdf->SetY($BelowSummary);
    $pdf->SetFont('helvetica', 'B', 10); // (font, style, size)
    $pdf->writeHTMLCell(0, 0, 42,$BelowSummary, html_entity_decode($_SESSION['RomalpaClause']));
}

$Bottom_Margin -= $lineHeight * 2;
$pdf->addTextWrap(145, $Bottom_Margin, 250, $FontSize, _('Date:'), 'right');
$pdf->line(400, $Bottom_Margin, 500, $Bottom_Margin);

$CoyAuthorisedBy=$_SESSION['CompanyRecord']['CoyAuthorisedBy'];
$pdf->addTextWrap(42, $Bottom_Margin + 50, 250, $FontSize, _('Authorised By :').$CoyAuthorisedBy, 'left');
$pdf->addTextWrap(145, $Bottom_Margin + 50, 250, $FontSize, _('Sign:'), 'right');

$pdf->addTextWrap(($Page_Width / 2) - 60, $tableBottom - 60, 120, 15, _('Gallery'), 'right');

if (is_array($urls = json_decode($urlString, true)) && count($urls) > 0) {
    $pdf->SetY($pdf->GetY());
    $myX = 45;
    foreach ($urls as $url) {
        $validUrl = str_replace(['[', ']'], '', urldecode(htmlspecialchars($url, ENT_QUOTES, 'utf-8')));
        $pdf->Image($validUrl, $myX, $pdf->GetY() + $lineHeight, 50, 0);
        $myX += 180;
    }
}
