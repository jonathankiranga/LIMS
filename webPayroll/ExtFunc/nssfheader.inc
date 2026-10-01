<?php

$SQL="SELECT description,employerfactor FROM [prlproducts] where [code]='".$_POST['productid']."'";
$ResultIndex = DB_query($SQL,$db);
$payrolldatesarray = DB_fetch_row($ResultIndex);

if ($PageNumber>1){
   $pdf->newPage();
}

$FontSize=9;

$XPos = $Left_Margin+0;
$YPos = $Page_Height-$Top_Margin;
$pdf->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize,_('Printed').': '. Date($_SESSION['DefaultDateFormat']). '   '. _('Page'). ' ' . $PageNumber);

$pdf->addJpegFromFile($_SESSION['LogoFile'], $XPos, $YPos-($line_height*3), 0,50);
$pdf->addTextWrap(50,$YPos-($line_height*5),300,$FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addTextWrap(50,$YPos-($line_height*6),300,$FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$pdf->addTextWrap(50,$YPos-($line_height*7),300,$FontSize,$_SESSION['CompanyRecord']['regoffice2']);
$pdf->addTextWrap(50,$YPos-($line_height*8),300,$FontSize,$_SESSION['CompanyRecord']['regoffice3']);
$pdf->addTextWrap(50,$YPos-($line_height*9),300,$FontSize,$_SESSION['CompanyRecord']['regoffice4']);
$pdf->addTextWrap(50,$YPos-($line_height*10),300,$FontSize,$_SESSION['CompanyRecord']['regoffice5']);
$pdf->addTextWrap(50,$YPos-($line_height*11),300,$FontSize,$_SESSION['CompanyRecord']['regoffice6']);


$YPos -=($line_height*13);
$Heading = _($payrolldatesarray[0].' Remittance For The Month of: ').' '.$_POST['month']._(' Year :').$_POST['year']  ;
$pdf->addText((($Page_Width-$Right_Margin-$Left_Margin)/3)-$Right_Margin,$YPos,14, $Heading);

$YPos -=($line_height*4);
$pdf->Rectangle($Left_Margin,($YPos-$line_height),($Page_Width-$Right_Margin-$Left_Margin),($YPos-$Bottom_Margin));

$divide = (($Page_Width-$Right_Margin-$Left_Margin)/10);

$mytop  = $YPos-$line_height;
$mydown = ($mytop-$Bottom_Margin+$line_height);
$YPos -= ($line_height*2);
$pdf->Rectangle($Left_Margin,($YPos-$line_height),($Page_Width-$Right_Margin-$Left_Margin),($YPos-$Bottom_Margin));
$FontSize = 12;

/*Set up headings */
$G  = $divide;
$Lt = $Left_Margin+1;

$pdf->addTextWrap($Lt ,$YPos ,$divide,$FontSize, _('NSSF no'),'left');
$pdf->addTextWrap($Lt +=($divide*2), $YPos,($divide*2),$FontSize, _('Names'),'left');
$pdf->addTextWrap($Lt +=($divide*2), $YPos,$divide*2,$FontSize,_('Employee\'s '),'right');
$pdf->addTextWrap($Lt +=($divide*2), $YPos,$divide*2,$FontSize,_('Employer\'s '),'right');
$pdf->addTextWrap($Lt +=($divide*2), $YPos,$divide*2,$FontSize,_('Total Contribution'),'right');


?>
