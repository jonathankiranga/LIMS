<?php
/* $Id: PDFBOMListingPageHeader.inc 3242 2009-12-16 22:06:53Z tim_schofield $*/
/*PDF page header for inventory valuation report */

$PageNumber++;
/* first time round $PageNumber will only be 1 and page created on initiation of $pdf */
if ($PageNumber>1){
    $pdf->newPage();
}

$FontSize=8;
$YPos= $Page_Height-$Top_Margin;
$pdf->addText($Left_Margin, $YPos,$FontSize,  htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));

$YPos -=$line_height;
$FontSize =10;
/*      $FromDate = str_replace('/','-',FormatDateForSQL($_POST['FromDate']).' 00:00:00');
	$ToDate = str_replace('/','-',FormatDateForSQL($_POST['ToDate']).' 23:59:59');
*/
$pdf->addText($Left_Margin, $YPos, $FontSize, _('Changes In Payroll items Between '.$_POST['FromDate'].' and '.$_POST['ToDate'] ));
$FontSize = 8;
$pdf->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, _('Printed') .': '. Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber);
$YPos -=(2 * $line_height);

/*Draw a rectangle to put the headings in     */
$pdf->line($Page_Width-$Right_Margin, $YPos-5,$Left_Margin, $YPos-5);
$pdf->line($Page_Width-$Right_Margin, $YPos+$line_height,$Left_Margin, $YPos+$line_height);
$pdf->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos-5);
$pdf->line($Left_Margin,$YPos+$line_height,$Left_Margin, $YPos-5);

/*set up the headings */
$Xpos = $Left_Margin + 1;
$LeftOvers = $pdf->addTextWrap($Xpos,$YPos,50 ,$FontSize,_('Date Time'),'centre');
$LeftOvers = $pdf->addTextWrap(100,$YPos,50,$FontSize,_('User ID'),'centre');
$LeftOvers = $pdf->addTextWrap(150,($YPos + ($line_height *.5)),100,$FontSize,_('PayRoll'),'centre');
$LeftOvers = $pdf->addTextWrap(150,($YPos - ($line_height *.2)),100,$FontSize,_('Number'),'centre');
$LeftOvers = $pdf->addTextWrap(200,$YPos,100,$FontSize,_('Item Name'),'centre');
$LeftOvers = $pdf->addTextWrap(250,$YPos,100,$FontSize,_('Item Value'),'centre');
$LeftOvers = $pdf->addTextWrap(350,($YPos + ($line_height *.5)),100,$FontSize,_('Employer'),'centre');
$LeftOvers = $pdf->addTextWrap(350,($YPos - ($line_height *.2)),100,$FontSize,_('Contribution'),'centre');
$LeftOvers = $pdf->addTextWrap(400,$YPos,100,$FontSize,_('Type'),'centre');
$LeftOvers = $pdf->addTextWrap(450,$YPos,100,$FontSize,_('Frequency'),'centre');

$YPos = $YPos - (2 * $line_height);
$FontSize = 10;

?>