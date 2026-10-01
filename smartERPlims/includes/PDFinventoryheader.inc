<?php
/* $Id: PDFQuotationPageHeader.inc 6540 2014-01-15 02:18:07Z rchacon $*/
/* pdf-php by R&OS code to set up a new sales order page */

// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
    $pdf->newPage();
}
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 1, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize*2)-30;
$FontSize=12;
$pdf->addText($XPos, $YPos, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize, _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-70,12,_('Stock Balance Report').'  '. $store_name );

$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-90,8,_('From :'). $_POST['fromdate']._(' To :'). $_POST['todate'] );
$pdf->line($Page_Width-$Right_Margin,$YPos-100,$Left_Margin,$YPos-100);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 120;
$FontSize=12;
// Draw a rectangle with rounded corners around 'Delivery To' info
$FontSize=10;

$LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Stock Name'),'left');
$LeftOvers = $pdf->addTextWrap(150, $YPos,80, $FontSize,_('UOM/SUB UOM'),'left');
$LeftOvers = $pdf->addTextWrap(230, $YPos,80, $FontSize,_('Opn Stock'),'center');
$LeftOvers = $pdf->addTextWrap(310, $YPos,80, $FontSize,_('Purchases'),'center');
$LeftOvers = $pdf->addTextWrap(390, $YPos,80, $FontSize,_('Production'),'center');
$LeftOvers = $pdf->addTextWrap(470, $YPos,80, $FontSize,_('Available'),'center');
$LeftOvers = $pdf->addTextWrap(550, $YPos,80, $FontSize,_('WO Requests'),'center');
$LeftOvers = $pdf->addTextWrap(630, $YPos,80, $FontSize,_('Sales Ord'),'center');
$LeftOvers = $pdf->addTextWrap(710, $YPos,80, $FontSize,_('Closing Stock'),'center');

$pdf->line($Page_Width-$Right_Margin,($YPos - $line_height),$Left_Margin,($YPos - $line_height) );

$firstrowpos = ($YPos - $line_height) -20 ;
$lastrow = $Bottom_Margin + ($line_height * 2);

$YPos -= (2 * $line_height);


?>
