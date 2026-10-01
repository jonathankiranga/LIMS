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

$XPos = 40;
// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

$YPos = $Page_Height - $Top_Margin - ($FontSize * 4);
$FontSize=10;
$pdf->addText($XPos, $YPos, $FontSize,htmlspecialcharsLocal_decode( $_SESSION['CompanyRecord']['coyname']));
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

$pdf->addText(364, $YPos-70, 12,$headerName);
if($_POST['reportin']==3){
    $pdf->addText(308, $YPos-82, 12,$headerdetails,'left');
}else{
    $pdf->addText(370, $YPos-82, 12,$headerdetails,'left');
}

$YPos -= 120;
 $pdf->line(40, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);
$LeftOvers = $pdf->addTextWrap(40, $YPos,100, 12, _('Product Name'),'left');
$LeftOvers = $pdf->addTextWrap(140, $YPos,56, $FontSize, _('JAN'),'right');
$LeftOvers = $pdf->addTextWrap(196, $YPos,56, $FontSize, _('FEB'),'right');
$LeftOvers = $pdf->addTextWrap(252, $YPos,56, $FontSize, _('MARCH'),'right');
$LeftOvers = $pdf->addTextWrap(308, $YPos,56, $FontSize, _('APRIL'),'right');
$LeftOvers = $pdf->addTextWrap(364, $YPos,56, $FontSize, _('MAY'),'right');
$LeftOvers = $pdf->addTextWrap(420, $YPos,56, $FontSize, _('JUNE'),'right');
$LeftOvers = $pdf->addTextWrap(476, $YPos,56, $FontSize, _('JULY'),'right');
$LeftOvers = $pdf->addTextWrap(532, $YPos,56, $FontSize, _('AUG'),'right');
$LeftOvers = $pdf->addTextWrap(588, $YPos,56, $FontSize, _('SEPT'),'right');
$LeftOvers = $pdf->addTextWrap(644, $YPos,56, $FontSize, _('OCT'),'right');
$LeftOvers = $pdf->addTextWrap(700, $YPos,56, $FontSize, _('NOV'),'right');
$LeftOvers = $pdf->addTextWrap(756, $YPos,56, $FontSize, _('DEC'),'right');
$YPos -= $line_height;
$pdf->line(40, $YPos,$Page_Width-$Right_Margin, $YPos);
$firstrowpos = $YPos- ($line_height * 1.5);
$lastrow  = $Bottom_Margin;
$YPos -= (2*$line_height);

?>
