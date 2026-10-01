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
// Draw a rectangle with rounded corners around 'Delivery To' info
//$pdf->RoundRectangle(38,$Page_Height-$Top_Margin-5,250,95, 10, 10);

$YPos = ($Page_Height-$Top_Margin-48);
$YPos-=70;
$FontSize=10;

$headlength = (strlen(trim($headerName)) * $FontSize)/2 ;
$pdf->addText((($Page_Width-$Right_Margin-$Left_Margin-$headlength )/2),$YPos+15,12, $headerName );
$FontSize=8;
// Print 'Quotation For' info
$pdf->addText($XPos, $YPos,$FontSize, _('Date Received').' : '. ConvertSQLDate($HeaderRow[1]));
$pdf->addText($XPos, $YPos-10,$FontSize, _('Date Completed').' : '. ConvertSQLDateTime($HeaderRow[2]), 'right');
$pdf->addText($XPos, $YPos-20,$FontSize, _('Sample Name').' : '. $HeaderRow[5], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-6,170,$FontSize, _('Batch No'). ' : '.$HeaderRow[0], 'left');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-17,170,$FontSize, _('Tested By'). ': '.$HeaderRow[6], 'left');
$pdf->RoundRectangle(38,$YPos+17,$Page_Width-$Right_Margin-$Left_Margin,60, 10, 10);

$YPos = $YPos - 45;//45
$FontSize  = 8;

$LeftOvers = $pdf->addTextWrap(42, $YPos- $line_height,200, $FontSize, _('Parameter'),'left');
$LeftOvers = $pdf->addTextWrap(245,$YPos- $line_height,100, $FontSize, _('Method'),'left');
$LeftOvers = $pdf->addTextWrap(350,$YPos- $line_height,100, $FontSize, _('Results'),'left');
$LeftOvers = $pdf->addTextWrap(455,$YPos- $line_height,50 , $FontSize, _('Min'),'left');
$LeftOvers = $pdf->addTextWrap(505,$YPos- $line_height,50 , $FontSize, _('Max'),'left');

$firstrowpos = $YPos - $line_height ;
$lastrow     = $Bottom_Margin + ($line_height * 8) ;
$pdf->line($Page_Width-$Right_Margin-10,$YPos ,$Left_Margin+10,$YPos );
$pdf->partEllipse($Page_Width-$Right_Margin-10,$YPos-10,0,90,10,10);
$pdf->partEllipse($Left_Margin+10,$YPos-10,90,180,10,10);//,90,180,10,10
$pdf->line($Page_Width-$Right_Margin,$YPos-$line_height *2 ,$Left_Margin,$YPos-$line_height *2);

?>
