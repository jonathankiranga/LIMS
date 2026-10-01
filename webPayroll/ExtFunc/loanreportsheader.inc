<?php


if ($PageNumber>1){
   $pdf->newPage();
}

$FontSize=10;

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
$Heading = _(trim($payrolldatesarray[0]).' '.trim($Title)).' '.$_POST['datefrom']._(' To :').$_POST['dateto'];

$pdf->addText(100,$YPos,10, $Heading);

$YPos -=($line_height*4);

$divide = (($Page_Width-$Right_Margin-$Left_Margin)/10);
$mytop  = $YPos-$line_height;
$mydown = ($mytop-$Bottom_Margin+$line_height);
$YPos -= ($line_height*2);


/*Set up headings */
$G  = $divide;
$Lt = $Left_Margin+1;

$pdf->addTextWrap($Lt ,$YPos ,$divide,$FontSize, _('ID'),'left');
$pdf->addTextWrap($Lt +=($divide), $YPos,($divide*2),$FontSize, _('Names'),'left');
$pdf->addTextWrap($Lt +=($divide*2), $YPos,$divide*2,$FontSize,_('Principle '),'right');
$pdf->addTextWrap($Lt +=($divide*2), $YPos,$divide*2,$FontSize,_('Interest '),'right');
$pdf->addTextWrap($Lt +=($divide*3), $YPos,$divide*2,$FontSize,_('Total'),'right');

$YPos -= ($line_height * 2);

?>
