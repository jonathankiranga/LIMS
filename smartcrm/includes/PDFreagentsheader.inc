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
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Date').': '. ConvertSQLDate($myrow[1]), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*6, 200, $FontSize, _('For Sales Order '). ': '.$myrow[16], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*7, 200, $FontSize, _($headerName.' No'). ': '.$myrow[0], 'right');
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],60,$Page_Height-$Top_Margin-$FontSize * 6,0,50);
// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize*6)-30;
$FontSize=14;
$pdf->addText($XPos, $YPos, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
$pdf->addText(($Page_Width/2.5)- $Right_Margin ,$YPos-80,16, $headerName.' Useage' );

// Print 'Delivery To' info
$XPos = 46;
$FontSize=14;

// Print 'Quotation For' info
$YPos -= 100;
$pdf->addText($XPos, $YPos, $FontSize, _($headerName.' For').':');
$pdf->addText($XPos, $YPos-15, $FontSize, htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($XPos, $YPos-30, $FontSize, $myrow[12]);
$pdf->addText($XPos, $YPos-45, $FontSize, $myrow[13]);
$pdf->addText($XPos, $YPos-45, $FontSize, $myrow[15]);

// Draw a rectangle with rounded corners around 'Delivery To' info
$XPos= 50;
$YPos += 12;

// Function RoundRectangle from includes/class.pdf.php
// Draw a rectangle with rounded corners around 'Quotation For' info
$YPos -= 82;
$pdf->RoundRectangle($XPos-10, $YPos+60+10, 250+10+10, 60+10+10, 10, 10);
// Function RoundRectangle from includes/class.pdf.php
// Print table header
$XPos = 40;
$YPos -= 37;
$FontSize=10;

$LeftOvers = $pdf->addTextWrap( 42, $YPos,103, $FontSize, _('Item Code'),'left');
$LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(360, $YPos, 85, $FontSize, _('UOM'),'right');
$LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, _('Quantity'),'right');

$firstrowpos = $YPos-$line_height ;
$lastrow  = $Bottom_Margin+150;

/*Column lines*/
$pdf->line(410, $Bottom_Margin+360+130,410,$lastrow+130);
$pdf->line(460, $Bottom_Margin+360+130,460,$lastrow+130);
$pdf->line($Page_Width-$Right_Margin,$lastrow+130,$Left_Margin,$lastrow+130);

$pos350=350+130;
$pos360=360+130;
$pos335=335+130;
$posBottom_Margin=$Bottom_Margin+140;

/*draw a box with nice round corner for entering line items */
/*90 degree arc at top right of box 0 degrees starts a bottom */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $Bottom_Margin+$pos350,0,90,10,10);
/*line to the top left */
$pdf->line($Page_Width-$Right_Margin-10, $Bottom_Margin+$pos360,$Left_Margin+10, $Bottom_Margin+$pos360);
/*line under headings to top left */
$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin+$pos335,$Left_Margin, $Bottom_Margin+$pos335);
/*Dow top left corner */
$pdf->partEllipse($Left_Margin+10, $Bottom_Margin+$pos350,90,180,10,10);
/*Do a line to the bottom left corner */
$pdf->line($Left_Margin, $Bottom_Margin+$pos350,$Left_Margin,$posBottom_Margin);
/*Now do the bottom left corner 180 - 270 coming back west*/
$pdf->partEllipse($Left_Margin+10,$posBottom_Margin,180,270,10,10);
/*Now a line to the bottom right */
$pdf->line($Left_Margin+10,$posBottom_Margin-10,$Page_Width-$Right_Margin-10,$posBottom_Margin-10);
/*Now do the bottom right corner */
$pdf->partEllipse($Page_Width-$Right_Margin-10,$posBottom_Margin,270,350,10,10);
/*Finally join up to the top right corner where started */
$pdf->line($Page_Width-$Right_Margin,$posBottom_Margin,$Page_Width-$Right_Margin, $Bottom_Margin+$pos350);


$YPos -= (2 * $line_height);


?>
