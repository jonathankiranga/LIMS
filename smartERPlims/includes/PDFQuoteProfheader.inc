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
$YPos = $Page_Height-$Top_Margin-$FontSize*5;
$XPos = 361;
$FontSize=10;
// Print 'Quotation' info
$pdf->Rect(12,10,$Page_Width-$Left_Margin+12,$Page_Height-$Top_Margin+10, "D");
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*2, 200, 10, _('Date').': '. ConvertSQLDate($myrow[1]), 'left');
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*3, 200, 10, _('Page').': '.$PageNumber, 'left');
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*4, 200, 10, _('Terms'). ': '.$myrow[16], 'left');
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*5, 200, 10, _($headerName.' No'). ': '.$_GET['No'], 'left');
///$pdf->RoundRectangle($Left_Margin,$Page_Height-$Top_Margin-$FontSize, 225+10+10, 65, 10, 10);// Function RoundRectangle from includes/class.pdf.php
$pdf->SetLineWidth(0.1); 
$Middlepage = ($Page_Width/2);
$pdf->line($Middlepage,$Page_Height-$Top_Margin-155,$Middlepage,$Page_Height-$Top_Margin+20);

// Print company info
$XPos = 361;
 
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Page_Width-$Right_Margin-140,$Page_Height-$Top_Margin*2,0,50);

$pdf->addText($Page_Width-$Right_Margin-250, $YPos,10,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-12, 10,_('Add1'). ': '. $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-21, 10,_('Add2'). ': '. $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-30, 10,_('Add3'). ': '. $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] );
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-39, 10,_('Tel'). ': ' . $_SESSION['CompanyRecord']['telephone']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-48, 10,_('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-57, 10,_('Sales Rep') . ': ' .$myrow[7]);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-69, 10,_('Email'). ': '. $myrow[8]);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-81, 8, _('Currency') . ' - ' . $myrow[6] . ' ' . $CurrencyName[$myrow[6]]);

//$pdf->RoundRectangle($Page_Width-$Right_Margin-250,$Page_Height-$Top_Margin-$FontSize,250,140, 10, 10);// Function RoundRectangle from includes/class.pdf.php
 
// Print 'Delivery To' info
$XPos = 46;
$YPos = $Page_Height-$Top_Margin-80;
$FontSize=10;
$pdf->addText($Left_Margin, $YPos, $FontSize, _('Customer').':' );
$pdf->addText($Left_Margin, $YPos-15, $FontSize,htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($Left_Margin, $YPos-27, $FontSize,_('Email'). ': '. $myrow[10]);
$pdf->addText($Left_Margin, $YPos-39, $FontSize,_('Tel'). ': '. $myrow[14]);
$pdf->addText($Left_Margin, $YPos-51, $FontSize,_('Contact'). ': '.$myrow[15]);
//$pdf->RoundRectangle($Left_Margin,$YPos, 225+10+10, 65, 10, 10);// Function RoundRectangle from includes/class.pdf.php

// Draw a rectangle with rounded corners around 'Delivery To' info
$XPos= 50;
$YPos = $YPos-72;


// Print table header
$XPos = 40;
$YPos -= 20;
$FontSize=10;
//$pdf->line($Page_Width-$Right_Margin+15,$YPos+$line_height+6,$Left_Margin+10,$YPos+$line_height+6);
$pdf->line(10,$YPos+$line_height+6,$Page_Width-$Right_Margin+12,$YPos+$line_height+6);
$LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos,40, $FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(90, $YPos,80, $FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(240, $YPos, 30, $FontSize, _('UOM'),'right');
$LeftOvers = $pdf->addTextWrap(290, $YPos, 30, $FontSize, _('Price'),'right');
$LeftOvers = $pdf->addTextWrap(330, $YPos, 60, $FontSize, _('VAT(%)'),'right');
$LeftOvers = $pdf->addTextWrap(400, $YPos, 70, $FontSize, _('VAT Amount'),'right');
$LeftOvers = $pdf->addTextWrap(490, $YPos, 90, $FontSize, _('Total Amount'),'right');

// 630
$firstrowpos = $YPos-$line_height ;
// Calculate space required
$lastrow     = $firstrowpos - (($Total_Rows-$Row_Count) * 40);
//$lastrow   = $firstrowpos - $line_height;
$Bottom_Row  = ($Bottom_Margin+($line_height*40));
   
if($Bottom_Row > $lastrow ){
    $lastrow = $Bottom_Row;
}

$columtop = $YPos+($line_height * 1.5);
$pdf->line(10,$YPos-5,$Page_Width-$Right_Margin+12,$YPos-5);

/*Column lines*/
$SummaryYpos = $lastrow-($line_height*4);
 //right first column

$pdf->line(10,$YPos-5,$Page_Width-$Right_Margin+12,$YPos-5);
$pdf->line(85, $columtop,85, $lastrow);
$pdf->line(200, $columtop,200, $lastrow);
$pdf->line(280, $columtop,280, $lastrow);
$pdf->line(340, $columtop,340, $lastrow);

/*put totals*/

$SummaryYpos = $lastrow-$line_height*1.2;
$pdf->line(10,$SummaryYpos+($line_height*1.2),$Page_Width-$Right_Margin+12,$SummaryYpos+($line_height*1.2));

$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos,90,10, _('SUB TOTAL'),'right');
$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos -= ($line_height * 1.2) ,90,10, _('VAT AMOUNT'),'right');
$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos -= ($line_height * 1.2) ,90,10, _('TOTAL INVOICE'),'right');

$pdf->line(400, $columtop,400, $SummaryYpos-10);
$pdf->line(490, $columtop,490, $SummaryYpos-10);
// bottom horizontal line

$pdf->line(10,$SummaryYpos-10,$Page_Width-$Right_Margin+12,$SummaryYpos-10);

$YPos -= (2*$line_height);

?>
