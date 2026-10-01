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
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*6, 200, $FontSize, _('For Sales Order '). ': '.$myrow[0], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize*7, 200, $FontSize, _($headerName.' No'). ': '.$_GET['No'], 'right');
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
$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-80,16, $headerName );

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

// Print table header
$XPos = 40;
$YPos -= 37;
$FontSize=10;

$LeftOvers = $pdf->addTextWrap( 42, $YPos,103, $FontSize, _('Item Code'),'left');
$LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(360, $YPos, 85, $FontSize, _('UOM'),'right');
$LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, _('Quantity'),'right');

$firstrowpos = $YPos-$line_height ;
$tableTop    = $YPos;
$lastrow     = $firstrowpos - (($Page_Height/2)-$Top_Margin)+($line_height*10)+($line_height*10);
$tableBottom = $firstrowpos - (($Page_Height/2)-$Top_Margin)+($line_height*10);
/*Column lines*/
$pdf->line(400, $tableTop+10,400,$lastrow);
$pdf->line(460, $tableTop+10,460,$lastrow);

/*draw a box with nice round corner for entering line items */
/*90 degree arc at top right of box 0 degrees starts a bottom */
$pdf->partEllipse($Page_Width-$Right_Margin-10,$tableTop,0,90,10,10);
/*line to the top left */
$pdf->line($Page_Width-$Right_Margin-10,$tableTop+10,$Left_Margin+10,$tableTop+10);
/*line under headings to top left */
/*Dow top left corner */
$pdf->partEllipse($Left_Margin+10,$tableTop,90,180,10,10);
/*Do a line to the bottom left corner */
$pdf->line($Left_Margin,$tableTop,$Left_Margin,$tableBottom);
/*Now do the bottom left corner 180 - 270 coming back west*/
$pdf->partEllipse($Left_Margin+10,$tableBottom,180,270,10,10);
/*Now a line to the bottom right */
$pdf->line($Left_Margin,$tableTop-10,$Page_Width-$Right_Margin,$tableTop-10);
$pdf->line($Page_Width-$Right_Margin,$tableTop,$Page_Width-$Right_Margin,$tableBottom);
$pdf->partEllipse($Page_Width-$Right_Margin-10,$tableBottom,270,350,10,10);

/*Now do the bottom right corner */
$pdf->line($Page_Width-$Right_Margin-10, $tableBottom-10,$Left_Margin+10, $tableBottom-10);
$pdf->line($Page_Width-$Right_Margin,$lastrow,$Left_Margin,$lastrow);
/*Finally join up to the top right corner where started */
$YPos -= (2 * $line_height);
$YPos1 = $lastrow  ;
$YPos1 -= $line_height;
$LeftOvers = $pdf->addTextWrap(40,$YPos1,100, $FontSize,_('Name of Driver :'),'left');
$LeftOvers = $pdf->addTextWrap(245,$YPos1,50, $FontSize,_('ID No:'),'left');
$LeftOvers = $pdf->addTextWrap(400,$YPos1,50, $FontSize,_('Mobile:'),'left');
$YPos1 -= $line_height ;
$LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos1 - $line_height,$Left_Margin,$YPos1- $line_height);
$YPos1 -= $line_height*3;
$LeftOvers = $pdf->addTextWrap(40,$YPos1 - $line_height ,100, $FontSize,_('Vehicle REG:'),'left');
$LeftOvers = $pdf->addTextWrap(245,$YPos1 - $line_height,100, $FontSize,_('Sign:'),'left');
$YPos1 -= $line_height ;
$LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos1 - $line_height,$Left_Margin,$YPos1- $line_height);
$YPos1 -= $line_height*3;
$LeftOvers = $pdf->addTextWrap(40,$YPos1 - $line_height,100, $FontSize,_('Received By:'),'left');
$LeftOvers = $pdf->addTextWrap(245,$YPos1 - $line_height,100, $FontSize,_('Sign:'),'left');


if(isset($_SESSION['RomalpaClause'])){
   $Y = $YPos1 - ($line_height*4) ;
   $LeftOvers = $pdf->addTextWrap(40,$Y,600, $FontSize,html_entity_decode($_SESSION['RomalpaClause']),'left');
}  
$Y = $YPos1 - ($line_height*6) ;
//$imageSummary = $pdf->GetY();
$urls = json_decode($urlString,true);
//$pdf->SetY($imageSummary);
//$Y = $pdf->GetY() ;
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
?>
