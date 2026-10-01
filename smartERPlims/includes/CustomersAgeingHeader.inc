<?php
    
 $FontSize = 12;
 $PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
  $pdf->newPage();
}
    
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 2, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Right_Margin+5,$Page_Height-$Top_Margin-50, 0,40);
$YPos=$topRow-25;
$FontSize = 9;
 
$pdf->addText($Right_Margin,$YPos,$FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Right_Margin, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Right_Margin, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Right_Margin, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($Right_Margin, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($Right_Margin, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($Right_Margin, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print company info
$XPos = 60;
$YPos = $YPos-70;
$FontSize=12;
$FontSize =10;

$pdf->addText((($Page_Width)/2)-$Right_Margin-$Left_Margin,$YPos,12,_('Customers Ageing Analysis'));
$pdf->addText((($Page_Width)/2)-$Right_Margin-$Left_Margin,$YPos-20,8,_('As At ').$_POST['date']);
$pdf->line($Page_Width-$Right_Margin,$YPos-30,$Left_Margin,$YPos-30);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 40;
$FontSize=12;

 $firstrowpos =($YPos - $line_height)  ;
 $LeftOvers = $pdf->addTextWrap(45,$firstrowpos,200, $FontSize, _('Name'),'left');
 $LeftOvers = $pdf->addTextWrap(250,$firstrowpos,50, $FontSize, _('Total'),'right');
 $LeftOvers = $pdf->addTextWrap(320,$firstrowpos,50, $FontSize, _('30 days'),'right');
 $LeftOvers = $pdf->addTextWrap(400,$firstrowpos,50, $FontSize, _('60 days'),'right');
 $LeftOvers = $pdf->addTextWrap(500,$firstrowpos,50, $FontSize, _('90 days'),'right');
 $LeftOvers = $pdf->addTextWrap(600,$firstrowpos,50, $FontSize, _('over 90 days'),'right');
 
 $lastrow = $Bottom_Margin + ($line_height * 2);
 $YPos -=  $line_height * 3 ;
?>