<?php
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
   $pdf->newPage();
}
$FontSize=8;
// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-170, $Page_Height-$Top_Margin,170 ,8 ,_('Date Printed').' : '. date('l jS F Y'));
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-15, 200, 8, _('Page').' : '.$PageNumber, 'right');

$XPos = 42;
// Print company logo
$FontSize=8;
$pdf->addJpegFromFile($_SESSION['LogoFile'],42,$Page_Height-$Top_Margin-37, 0,30);

// Print company info
$YPos = ($Page_Height-$Top_Margin-40);

$pdf->addText($XPos, $YPos, $FontSize, $_SESSION['CompanyRecord']['coyname']);
$pdf->addText($XPos, $YPos-8, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-16, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-24, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-32, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-40, $FontSize, $_SESSION['CompanyRecord']['email']);
// Print 'Delivery To' info

$YPos = ($Page_Height-$Top_Margin-48);
$YPos-=70;
$FontSize=10;

$headlength = (strlen(trim($headerName)) * $FontSize)/2 ;
$pdf->addText((($Page_Width-$Right_Margin-$Left_Margin-$headlength )/2),$YPos+15,12, $headerName );
$FontSize = 8;
// Print 'Quotation For' info
$pdf->addText($XPos, $YPos,$FontSize, _('Date Produced:').' : '. ConvertSQLDate($HeaderRow[1]));
$pdf->addText($XPos, $YPos-10,$FontSize, _('PRODUCT Name:').' : '. $HeaderRow[5], 'right');
$pdf->addText($XPos, $YPos-20,$FontSize, _('PRODUCT Quantity:').' : '. $HeaderRow[3], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-6,170,$FontSize, _('Batch No:'). ' : '.$HeaderRow[0], 'left');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-17,170,$FontSize, _('Produced By:'). ': '.$HeaderRow[6], 'left');
$pdf->RoundRectangle(38,$YPos+17,$Page_Width-$Right_Margin-$Left_Margin,60, 10, 10);

$YPos = $YPos - 80;
$FontSize  = 8;

$LeftOvers = $pdf->addTextWrap(42, $YPos- $line_height,200, $FontSize, _('PARAMETER'),'left');
$LeftOvers = $pdf->addTextWrap(245,$YPos- $line_height,50, $FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(300,$YPos- $line_height,50, $FontSize, _('UOM'),'right');
$LeftOvers = $pdf->addTextWrap(350,$YPos- $line_height,50, $FontSize, _('Percent(%)'),'right');
$LeftOvers = $pdf->addTextWrap(400,$YPos- $line_height,50, $FontSize, _('Cost'),'right');
$LeftOvers = $pdf->addTextWrap(480,$YPos- $line_height,50, $FontSize, _('Total Cost'),'right');

$pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
$pdf->line($Page_Width-$Right_Margin,$YPos-$line_height *2 ,$Left_Margin,$YPos-$line_height *2);

$firstrowpos = $YPos - ($line_height * 4) ;
$lastrow     = $Bottom_Margin + ($line_height * 8) ;
?>
