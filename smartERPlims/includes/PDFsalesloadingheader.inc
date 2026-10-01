<?php
/* $Id: PDFQuotationPageHeader.inc 6540 2014-01-15 02:18:07Z rchacon $*/
/* pdf-php by R&OS code to set up a new sales order page */
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if($PageNumber>1) {
    $pdf->newPage();
}


// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize, 200, $FontSize, _('Date Printed').': '. date('Y-m-d'), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$YPos = $Page_Height-$Top_Margin-$FontSize*4;
$XPos = 60;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$XPos,$YPos, 0, 60);
// Print company info
$FontSize=14;
$pdf->addText($XPos, $YPos, $FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-=$line_height, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-=$line_height, $FontSize, _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat'].' '. _('PIN') . ': ' .$_SESSION['CompanyRecord']['PIN']);
$pdf->addText($XPos, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-=$line_height, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5'] .' '.$_SESSION['CompanyRecord']['regoffice6']);
 
// Print 'Quotation For' info
$YPos -= $line_height*3;

$pdf->addText($XPos, $YPos, $FontSize,  _('ORDER FOR').':'.htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($XPos, $YPos-15, $FontSize,  _('PHONE').':'.$myrow[14]);
$pdf->addText($XPos, $YPos-30, $FontSize,  _('EMAIL').':'.$myrow[10]);
// Print 'Quotation' title
$pdf->addText(240,$YPos-=$line_height * 5,16, _('LOADING ORDER') );
// Print table header
$XPos = 40;
$YPos -= $line_height * 5 ;;
$FontSize=12;
	
$LeftOvers = $pdf->addTextWrap( 42,$YPos-$line_height,200, $FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(300,$YPos-$line_height, 85, $FontSize, _('Package'),'right');
$LeftOvers = $pdf->addTextWrap(400,$YPos-$line_height, 85, $FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(400,$YPos-$line_height * 2,85, $FontSize, _('Per Package'),'right');

$lastrow=$Bottom_Margin+200;
/*Column lines*/
$pdf->line(242, $YPos ,242, $lastrow);
$pdf->line(400, $YPos ,400, $lastrow);

/*draw a box with nice round corner for entering line items */
/*90 degree arc at top right of box 0 degrees starts a bottom */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $YPos-10,0,90,10,10);
/*line to the top left */
$pdf->line($Page_Width-$Right_Margin-10,$YPos,$Left_Margin+10, $YPos);
/*line under headings to top left */
 $pdf->line($Page_Width-$Right_Margin,$YPos-25,$Left_Margin, $YPos-25);
/*Dow top left corner */
$pdf->partEllipse($Left_Margin+10,$YPos-10,90,180,10,10);
/*Do a line to the bottom left corner */
$pdf->line($Left_Margin,$YPos-10,$Left_Margin, $lastrow+10);
/*Now do the bottom left corner 180 - 270 coming back west*/
$pdf->partEllipse($Left_Margin+10, $lastrow+10,180,270,10,10);
/*Now a line to the bottom right */
$pdf->line($Left_Margin+10, $lastrow,$Page_Width-$Right_Margin-10, $lastrow);
///////////////left////////////top/////////////////////left///////////////////top
/*Now do the bottom right corner */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $lastrow+10,270,350,10,10);
/*Finally join up to the top right corner where started */
$pdf->line($Page_Width-$Right_Margin, $lastrow+10,$Page_Width-$Right_Margin, $YPos-10);

$YPos -= ($line_height * 2);
$firstrowpos = $YPos - ($line_height * 2) ;

foreach ($mysignitories as $value) {
    $pdf->addText($XPos,$lastrow -= ($line_height*2), $FontSize ,$value);
}

?>
