<?php

$id=$_GET['pdfid'];
if ($PageNumber>1){
   $pdf->newPage();
}

$FontSize=8;
$YPos=$Page_Height-$Top_Margin;
$pdf->addText($Left_Margin, $YPos,$FontSize,  htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize,_('Printed').': '. Date($_SESSION['DefaultDateFormat']). '   '. _('Page'). ' ' . $PageNumber);

$YPos -=($line_height*4);
$Heading = _('Master Roll  ID '.$id.' From: ').' '.ConvertSQLDate($payrolldatesarray[0])._(' To :').ConvertSQLDate($payrolldatesarray[1]);
$pdf->addText((($Page_Width-$Right_Margin-$Left_Margin)/2.5)-$Right_Margin,$YPos,14, $Heading);

$YPos -=($line_height*2);

$pdf->Rectangle($Left_Margin,($YPos-$line_height),($Page_Width-$Right_Margin-$Left_Margin),($YPos - ($Bottom_Margin + ($line_height * 4))));
$pdf->Rectangle($Left_Margin,($YPos-$line_height),($Page_Width-$Right_Margin-$Left_Margin),($line_height * 2));

$divide = (($Page_Width-$Right_Margin-$Left_Margin)/11);
$mytop  = $YPos-$line_height;
$mydown = ($mytop-$Bottom_Margin+$line_height);
$YPos -=($line_height * 2);

$FontSize = 10;
/*Set up headings */
$G  = $divide;
$Lt = $Left_Margin+1;

$pdf->addTextWrap($Lt ,$YPos ,$G ,$FontSize, _('PayRoll ID'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Names'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Basic Pay'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Over-Time'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('O.Allwances'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Gross Pay'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Late/Absent'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Loans'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('O.Deduction'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('T.Deductions'));
$pdf->addTextWrap($Lt +=$divide, $YPos,$G +=$divide,$FontSize, _('Net Payable'));

$YPos -= ($line_height);

?>
