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
/*$Page_Width=595;
$Page_Height=842;*/
// Print 'Quotation' title
$pdf->addText((($Page_Width-$Right_Margin)/2)+50,($Page_Height-$Top_Margin),16, $headerName );

// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize,($headerName=='ORDER')?_('Order Due:'):''. ($headerName=='ORDER'? ConvertSQLDate($myrow[3]):''), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Date').': '. ConvertSQLDate($myrow[1]), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*6, 200, $FontSize, _('Your Ref '). ': '.$myrow[16], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*7, 200, $FontSize, _($headerName.' No'). ': '.$_GET['No'], 'right');

if($myrow[17]==1){
    $pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*8, 200,12, _('INVOICE COPY'), 'right');
}
$XPos = 45;
$YPos = $Page_Height-$Top_Margin-$FontSize;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],(($Page_Width-$Right_Margin)/2)+50,($Page_Height-$Top_Margin-$FontSize*7), 0, 60);
// Print company info

$pdf->addText($XPos, $YPos, $FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($XPos, $YPos-12, $FontSize,trim($_SESSION['CompanyRecord']['regoffice1']));
$pdf->addText($XPos, $YPos-21, $FontSize,_('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] );
$pdf->addText($XPos, $YPos-31, $FontSize,trim($_SESSION['CompanyRecord']['email']));
$pdf->addText($XPos, $YPos-41, $FontSize,_('PIN') . ': ' .$_SESSION['CompanyRecord']['PIN']);
$pdf->addText($XPos, $YPos-51, $FontSize,trim($_SESSION['CompanyRecord']['regoffice2']) . ' ' . $_SESSION['CompanyRecord']['regoffice3']);
$pdf->addText($XPos, $YPos-61, $FontSize,trim($_SESSION['CompanyRecord']['regoffice4']) . ' ' . $_SESSION['CompanyRecord']['regoffice5'] .' '.$_SESSION['CompanyRecord']['regoffice6']);
 
// Print 'Delivery To' info
$XPos = 50;
$YPos -= 81;
$pdf->addText($XPos+5, $YPos, $FontSize,_('Delivery To').':' );
$pdf->addText($XPos+5, $YPos-15, $FontSize,htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($XPos+5, $YPos-30, $FontSize,$myrow[12]);
$pdf->addText($XPos+5, $YPos-45, $FontSize,$myrow[13]);
$pdf->addText($XPos+5, $YPos-60, $FontSize,$myrow[15]);

// Print 'Quotation For' info
$XPos1=(($Page_Width-$Right_Margin)/2)+50;
$pdf->addText($XPos1, $YPos, $FontSize, _($headerName.' For').':');
$pdf->addText($XPos1, $YPos-15, $FontSize, htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($XPos1, $YPos-30, $FontSize, $myrow[12]);
$pdf->addText($XPos1, $YPos-45, $FontSize, $myrow[13]);
$pdf->addText($XPos1, $YPos-60, $FontSize, $myrow[15]);

// Draw a rectangle with rounded corners around 'Delivery To' info
 
$pdf->RoundRectangle($XPos, $YPos, 225+10+10, 60+10+10, 10, 10);// Function RoundRectangle from includes/class.pdf.php
// Draw a rectangle with rounded corners around 'Quotation For' info
$pdf->RoundRectangle($XPos1-10, $YPos, 225+10+10, 60+10+10, 10, 10);// Function RoundRectangle from includes/class.pdf.php
// Print the currency name
$pdf->addText(($Page_Width/2)-60,$YPos-90, $FontSize, _('Currency') . ' - ' . $myrow[6] . ' ' . $CurrencyName[$myrow[6]]);

$tableTop    = $YPos-110;
// Print table header
$XPos = 40;
$YPos -= 120;
$FontSize=10;
$LeftOvers = $pdf->addTextWrap(42,  $YPos,50, $FontSize, _('Item Code'),'left');

$LeftOvers = $pdf->addTextWrap(92,  $YPos,150, $FontSize, _('Item '),'left');
$LeftOvers = $pdf->addTextWrap(92,  $YPos-$line_height,150, $FontSize, _('Description'),'left');

$LeftOvers = $pdf->addTextWrap(250, $YPos, 50, $FontSize, _('Package'),'right');
$LeftOvers = $pdf->addTextWrap(300, $YPos, 50, $FontSize, _('Quantity'),'right');

$LeftOvers = $pdf->addTextWrap(355, $YPos, 50, $FontSize, _('Price'),'right');
$LeftOvers = $pdf->addTextWrap(355, $YPos-$line_height,50, $FontSize, _('Per Package'),'right');

$LeftOvers = $pdf->addTextWrap(380, $YPos, 55, $FontSize, _('VAT'),'right');
$LeftOvers = $pdf->addTextWrap(380, $YPos-$line_height, 55, $FontSize, _('Rate'),'right');

$LeftOvers = $pdf->addTextWrap(430, $YPos, 70, $FontSize, _('VAT'),'right');
$LeftOvers = $pdf->addTextWrap(430, $YPos-$line_height, 70, $FontSize, _('Amount'),'right');

$LeftOvers = $pdf->addTextWrap(490, $YPos, 70, $FontSize, _('Total'),'right');
$LeftOvers = $pdf->addTextWrap(490, $YPos-$line_height, 70, $FontSize, _('Amount'),'right');

$firstrowpos = $YPos-$line_height ;
$lastrow     = $firstrowpos - (($Page_Height/2)-$Top_Margin);
$tableBottom = $firstrowpos - (($Page_Height/2)-$Top_Margin);
/*Column lines*/
$pdf->line(90, $tableTop,90, $lastrow+100);
$pdf->line(260, $tableTop,260, $lastrow+100);
$pdf->line(305, $tableTop,305, $lastrow+100);
$pdf->line(350, $tableTop,350, $lastrow+100);
$pdf->line(410, $tableTop,410, $lastrow+100);
$pdf->line(440, $tableTop,440, $lastrow+100);
$pdf->line(500, $tableTop,500, $lastrow);
/*put totals*/
$SummaryYpos = $tableBottom+80;
$LeftOvers = $pdf->addTextWrap(312,$SummaryYpos,120, 15, _('SUB TOTAL'),'right');
$BelowSummary = $pdf->GetY();
$LeftOvers = $pdf->addTextWrap(312,$SummaryYpos -=$line_height *2.5,120,15, _('VAT AMOUNT'),'right');
$LeftOvers = $pdf->addTextWrap(312,$SummaryYpos -=$line_height *2.5,120,15, _('TOTAL INVOICE'),'right');
// under line sub total
$pdf->line(500, $tableBottom+75,$Page_Width-$Right_Margin, $tableBottom+75);
// under line vat
$pdf->line(500, $tableBottom+50,$Page_Width-$Right_Margin, $tableBottom+50);
if(isset($_SESSION['RomalpaClause'])){
   $pdf->SetY($BelowSummary);
   $Y = $pdf->GetY() ;
   $LeftOvers = $pdf->writeHTMLCell(0,0,42,$Y,html_entity_decode($_SESSION['RomalpaClause']));
}  
 
/*90 degree arc at top right of box 0 degrees starts a bottom */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $tableTop-10,0,90,10,10);
/*Dow top left corner */
$pdf->partEllipse($Left_Margin+10, $tableTop-10,90,180,10,10);
/*line to the top left */
$pdf->line($Page_Width-$Right_Margin-10, $tableTop,$Left_Margin+10, $tableTop);
/*line under headings to top left */
$pdf->line($Page_Width-$Right_Margin, $tableTop-30,$Left_Margin, $tableTop-30);
/*Do a line to the bottom left corner */
$pdf->line($Left_Margin,$tableTop-10,$Left_Margin,$tableBottom+10);
/*Now do the bottom left corner 180 - 270 coming back west*/
$pdf->partEllipse($Left_Margin+10,$tableBottom+10,180,270,10,10);
/*Now a line to the bottom right */
$pdf->line($Left_Margin+10,$tableBottom,$Page_Width-$Right_Margin-10,$tableBottom);
$pdf->line($Left_Margin,$tableBottom+100,$Page_Width-$Right_Margin,$tableBottom+100);
///////////////left////////////top/////////////////////left///////////////////top
/*Now do the bottom right corner */
$pdf->partEllipse($Page_Width-$Right_Margin-10,$tableBottom+10,270,350,10,10);
/*Finally join up to the top right corner where started */
$pdf->line($Page_Width-$Right_Margin,$tableTop-10,$Page_Width-$Right_Margin,$tableBottom+10);

$LeftOvers = $pdf->addTextWrap(($Page_Width/2)-60,$tableBottom-30,120,15, _('Gallery'),'right');

$imageSummary = $pdf->GetY();
    
$urls = json_decode($urlString,true);

$pdf->SetY($imageSummary);
$Y = $pdf->GetY() ;
if (is_array($urls) && count($urls) > 0) {
   $myX=45;
    if (is_array($urls) && count($urls) > 0) {
         foreach ($urls as $url) {
            $validUrl = htmlspecialchars($url,ENT_QUOTES,'utf-8'); // Decode the HTML entities
            $validUrl = urldecode($url); // Decode the URL-encoded characters
            $validUrl = str_replace('[', '' ,$validUrl);
            $validUrl = str_replace( ']', '' ,$validUrl);
      
            $pdf->Image($validUrl,$myX,$Y+$line_height, 160, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);

            $myX +=180;
         }
     } 
} 
  
$YPos -= (2*$line_height);

 
?>
