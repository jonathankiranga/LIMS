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
//$pdf->addText(361,500,16, $headerName );

// Print 'Quotation' info
$dueDateText = !empty($myrow['duedate']) ? _('Order Due:').' '.ConvertSQLDate($myrow['duedate']) : '';
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize, $dueDateText, 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Date').': '. ConvertSQLDate($myrow['docdate']), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*6, 200, $FontSize, _('Your Ref '). ': '.$myrow['yourreference'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*7, 200, $FontSize, _($headerName.' No'). ': '.$myrow['documentno'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*8, 200, $FontSize, _('This Order is Only Valid when Approved'), 'right');

// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'], 361, 470, 0, 100);

// Print company info
$XPos = 361;
$HeaderYPos = 482;
$FontSize=14;

// Print 'Delivery To' info
$XPos = 46;
$HeaderYPos = 566;
$FontSize=9;
$pdf->addText($XPos, $HeaderYPos, $FontSize, _('Delivery To').':' );
$pdf->addText($XPos, $HeaderYPos-15, $FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($XPos, $HeaderYPos-25, $FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $HeaderYPos-35, $FontSize,$_SESSION['CompanyRecord']['regoffice2'].' '.$_SESSION['CompanyRecord']['regoffice3'].' '.$_SESSION['CompanyRecord']['regoffice4']);
$pdf->addText($XPos, $HeaderYPos-45, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $HeaderYPos-55, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $HeaderYPos-65, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print 'Quotation For' info
$HeaderYPos -= 80;
$pdf->addText($XPos, $HeaderYPos, $FontSize, _($headerName.' TO').':');
$pdf->addText($XPos, $HeaderYPos-15, $FontSize, htmlspecialcharsLocal_decode($myrow['vendorname']));
$supplierAddress = trim($myrow['supplier_city'].' '.$myrow['supplier_postcode'].' '.$myrow['supplier_country']);
$pdf->addText($XPos, $HeaderYPos-30, $FontSize, $supplierAddress);
$pdf->addText($XPos, $HeaderYPos-45, $FontSize, _('Phone').': '.$myrow['supplier_phone']);
$supplierContact = trim($myrow['supplier_contact'].' '.$myrow['supplier_email']);
$pdf->addText($XPos, $HeaderYPos-60, $FontSize, $supplierContact);

// Draw a rectangle with rounded corners around 'Delivery To' info
$XPos= 50;
$HeaderYPos += 12;
$pdf->RoundRectangle($XPos-10, $HeaderYPos+60+10, 225+10+10, 60+10+10, 10, 10);// Function RoundRectangle from includes/class.pdf.php

// Draw a rectangle with rounded corners around 'Quotation For' info
$HeaderYPos -= 82;
$pdf->RoundRectangle($XPos-10, $HeaderYPos+60+10, 225+10+10, 60+10+10, 10, 10);// Function RoundRectangle from includes/class.pdf.php

// Print 'Quotation' title
$pdf->addText(361,$HeaderYPos+30,16, $headerName );
// Print the currency name
$currencyCode = $myrow['currencycode'];
$currencyName = isset($CurrencyName[$currencyCode]) ? $CurrencyName[$currencyCode] : $currencyCode;
$pdf->addText($Page_Width/2-60, $HeaderYPos-5, $FontSize, _('Currency') . ' - ' . $currencyCode . ' ' . $currencyName);

// Print table header
$XPos = 40;
$HeaderYPos -= 37;
$FontSize=10;
$LeftOvers = $pdf->addTextWrap( 42, $HeaderYPos,95, $FontSize, _('Item Code'),'left');
	
        if (strlen($LeftOvers) > 0) { // If translated text is greater than 103, prints remainder
		$LeftOvers = $pdf->addTextWrap(585,$HeaderYPos-$FontSize,65, $FontSize, $LeftOvers,'left');
	}
$LeftOvers = $pdf->addTextWrap(145, $HeaderYPos,145, $FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(300, $HeaderYPos, 75, $FontSize, _('UOM'),'right');
$LeftOvers = $pdf->addTextWrap(380, $HeaderYPos, 55, $FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(440, $HeaderYPos, 70, $FontSize, _('Price'),'right');
$LeftOvers = $pdf->addTextWrap(515, $HeaderYPos, 50, $FontSize, _('Disc'),'right');
$LeftOvers = $pdf->addTextWrap(570, $HeaderYPos, 55, $FontSize, _('VAT Rate (%)'),'right');

$firstrowpos = $HeaderYPos-$line_height ;
$lastrow  = $Bottom_Margin+100;

if (strlen($LeftOvers) > 0) { // If translated text is greater than 55, prints remainder
    $LeftOvers = $pdf->addTextWrap(580,$HeaderYPos-$FontSize,55, $FontSize, $LeftOvers,'right');
}

$LeftOvers = $pdf->addTextWrap(630, $HeaderYPos, 75, $FontSize, _('VAT Amount'),'right');
if (strlen($LeftOvers) > 0) { // If translated text is greater than 70, prints remainder
    $LeftOvers = $pdf->addTextWrap(665, $HeaderYPos-$FontSize, 70, $FontSize, $LeftOvers,'right');
}
$LeftOvers = $pdf->addTextWrap(720, $HeaderYPos, 90, $FontSize, _('Gross Amount'),'right');
/*Column lines*/
$pdf->line(140, $Bottom_Margin+360,140, $Bottom_Margin+100);
$pdf->line(295, $Bottom_Margin+360,295, $Bottom_Margin+100);
$pdf->line(380, $Bottom_Margin+360,380, $Bottom_Margin+100);
$pdf->line(440, $Bottom_Margin+360,440, $Bottom_Margin+100);
$pdf->line(515, $Bottom_Margin+360,515, $Bottom_Margin+100);
$pdf->line(570, $Bottom_Margin+360,570, $Bottom_Margin);
$pdf->line(630, $Bottom_Margin+360,630, $Bottom_Margin+100);
$pdf->line(710, $Bottom_Margin+360,710, $Bottom_Margin);
/*put totals*/
$SummaryYpos = $Bottom_Margin+80;

$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos,200,10, _('SUB TOTAL'),'right');
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos -=$line_height *2,200,10, _('Disc AMOUNT'),'right');
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos -=$line_height *2,200,10, _('VAT AMOUNT'),'right');
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos -=$line_height *2,200,10, _('TOTAL INVOICE'),'right');
// under line sub total
$pdf->line(580, $Bottom_Margin+75,$Page_Width-$Right_Margin, $Bottom_Margin+75);
// under line vat
$pdf->line(580, $Bottom_Margin+50,$Page_Width-$Right_Margin, $Bottom_Margin+50);

$pdf->line(580, $Bottom_Margin+25,$Page_Width-$Right_Margin, $Bottom_Margin+25);
/*draw a box with nice round corner for entering line items */
/*90 degree arc at top right of box 0 degrees starts a bottom */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $Bottom_Margin+350,0,90,10,10);
/*line to the top left */
$pdf->line($Page_Width-$Right_Margin-10, $Bottom_Margin+360,$Left_Margin+10, $Bottom_Margin+360);
/*line under headings to top left */
$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin+335,$Left_Margin, $Bottom_Margin+335);
/*Dow top left corner */
$pdf->partEllipse($Left_Margin+10, $Bottom_Margin+350,90,180,10,10);
/*Do a line to the bottom left corner */
$pdf->line($Left_Margin, $Bottom_Margin+350,$Left_Margin, $Bottom_Margin+10);
/*Now do the bottom left corner 180 - 270 coming back west*/
$pdf->partEllipse($Left_Margin+10, $Bottom_Margin+10,180,270,10,10);
/*Now a line to the bottom right */
$pdf->line($Left_Margin+10, $Bottom_Margin,$Page_Width-$Right_Margin-10, $Bottom_Margin);

$pdf->line($Left_Margin, $Bottom_Margin+100,$Page_Width-$Right_Margin, $Bottom_Margin+100);
///////////////left////////////top/////////////////////left///////////////////top
/*Now do the bottom right corner */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $Bottom_Margin+10,270,350,10,10);
/*Finally join up to the top right corner where started */
$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin+10,$Page_Width-$Right_Margin, $Bottom_Margin+350);

?>
