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
$pdf->addText($Left_Margin, $YPos, $FontSize, _('Report Number :').$ReportNo);
$YPos -=$line_height;

$pdf->addText($Left_Margin, $YPos, $FontSize, _($Title));
$YPos -=$line_height;
$pdf->addText($Left_Margin, $YPos, $FontSize, _('From '.$_POST['FromDate'].' To '.$_POST['ToDate'] ));

$FontSize = 8;
$pdf->addText($Page_Width-$Right_Margin-220,$YPos,$FontSize, _('Report for Dynamics NAV ERP') .': '. Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber);
$YPos -=(2 * $line_height);

/*Draw a rectangle to put the headings in     */
$pdf->line($Page_Width-$Right_Margin, $YPos-5,$Left_Margin, $YPos-5);
$pdf->line($Page_Width-$Right_Margin, $YPos+$line_height,$Left_Margin, $YPos+$line_height);
$pdf->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos-5);
$pdf->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos-5);

/*set up the headings */
$Xpos = $Left_Margin + 1;
$LeftOvers = $pdf->addTextWrap($Xpos,$YPos,50,$FontSize,_('Name'),'centre');
$LeftOvers = $pdf->addTextWrap(150,$YPos,50,$FontSize,_('Amount'),'centre');
$LeftOvers = $pdf->addTextWrap(250,$YPos,50,$FontSize,_('DR Type'),'centre');
$LeftOvers = $pdf->addTextWrap(300,$YPos,80,$FontSize,_('DR Code'),'centre');
$LeftOvers = $pdf->addTextWrap(400,$YPos,100,$FontSize,_('CR Type'),'centre');
$LeftOvers = $pdf->addTextWrap(500,$YPos,100,$FontSize,_('CR Code'),'centre');

$YPos = $YPos - (2 * $line_height);

$FontSize=10;

?>