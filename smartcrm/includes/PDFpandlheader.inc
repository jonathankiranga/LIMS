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
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],150,$Page_Height-$Top_Margin-$FontSize*5, 0,50);
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize*6)-30;
$FontSize=12;
$pdf->addText($XPos, $YPos, $FontSize,htmlspecialcharsLocal_decode( $_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-70,10, $headerName );
$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-90,8,_('From :'). ConvertSQLDate($finacialdates[0])._(' To :'). ConvertSQLDate($finacialdates[1]) );
$pdf->line($Page_Width-$Right_Margin,$YPos-100,$Left_Margin,$YPos-100);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 120;
$FontSize = 12;
// Draw a rectangle with rounded corners around 'Delivery To' info
$FontSize=10;
$LeftOvers = $pdf->addTextWrap( 42, $YPos,103, $FontSize, _('Account'),'left');
$LeftOvers = $pdf->addTextWrap(320, $YPos, 85, $FontSize, _('This Year'),'right');
$LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, _('Last Year'),'right');
$pdf->line($Page_Width-$Right_Margin, $YPos - $line_height ,$Left_Margin, $YPos - $line_height );

$firstrowpos = ($YPos - $line_height) -20 ;
$lastrow = $Bottom_Margin + ($line_height * 2);
$YPos -= (2 * $line_height);


?>
