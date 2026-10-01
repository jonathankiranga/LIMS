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
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize, _('Date').': '. ConvertSQLDate(DATE('Y-m-d')), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$Page_Height-$Top_Margin-$FontSize*5, 0, 60);
// Print company info
$XPos = 45;
$YPos = $Page_Height-$Top_Margin-$FontSize*5;
$FontSize=11;
$pdf->addText($Left_Margin, $YPos, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Left_Margin, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Left_Margin, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Left_Margin, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($Left_Margin, $YPos-=$line_height, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($Left_Margin, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['email']);
$YPos -= (2 * $line_height);
// Print 'Quotation' title
$pdf->addText($Left_Margin,$YPos-=$line_height,12, $headerName );
        // Print table header
$XPos = 40;  $YPos -= (2 * $line_height);
$pdf->line($Left_Margin,$YPos,$Left_Margin+530,$YPos);

$firstrowpos = $YPos ; $lastrow  = $Bottom_Margin+100;


?>
