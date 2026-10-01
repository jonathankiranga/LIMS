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
// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],150,$Page_Height-$Top_Margin-$FontSize*6, 0,50);
// Print company info
$XPos = 60;
$YPos = ($Page_Height - $Top_Margin - ($FontSize * 6))-30;
$FontSize=14;
$pdf->addText($XPos, $YPos, $FontSize,htmlspecialcharsLocal_decode( $_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
$pdf->addText(42,$YPos-80,16, $headerName );

$XPos = 46;
$YPos -= 120;
$FontSize=10;

$LeftOvers = $pdf->addTextWrap( 42, $YPos,50, $FontSize, _('ID'),'left');
$LeftOvers = $pdf->addTextWrap(100, $YPos,250, $FontSize, _('Employee Names'),'left');
$LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, _('Wages Amount'),'right');

$firstrowpos = $YPos- ($line_height *3 );
$lastrow  = $Bottom_Margin+120;
$YPos -= (2*$line_height);

?>
