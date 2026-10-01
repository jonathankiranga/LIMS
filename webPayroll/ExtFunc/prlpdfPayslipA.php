<?php

$_GET['Mid'] = $_GET['pdfid'];
 
$PaperSize = 'A4_Landscape';
include('includes/PDFStarter.php');
$PageNumber = 1;
$line_height = 12;
$pageheight = $Page_Height- $Bottom_Margin;

$YPos -= ($line_height * 2);
$FontSize = 8;
$divide = ($Page_Width-$Right_Margin-$Left_Margin)/11;
$lastrow = ($Bottom_Margin + ($line_height * 4));

$totalnetpay = 0;
$totals = 0;

$Tbasicpay=0;
$TOvertime=0;
$Toallowables=0;
$Tallowances=0;
$TManhrlost=0;
$TLoans=0;
$Todeductables=0;
$Tdeductions=0;
$Tnetpay=0;

$SQL="SELECT [fromdate],[todate] FROM [prlmrollperiods] where [pkey]='".$_GET['pdfid']."'";
$ResultIndex = DB_query($SQL,$db);
$payrolldatesarray = DB_fetch_row($ResultIndex);

include('ExtFunc/masterrollheader.inc');

$masterarray=printmasteroll();
foreach ($masterarray as $rows) {
        $Lt = $Left_Margin;
        $row1 = $Lt + 20;
        $Tbasicpay   += $rows['basicpay'];
        $TOvertime   += $rows['Overtime'];
        $Tallowances += $rows['allowances'];
        $TManhrlost  += $rows['Manhrlost'];
        $TLoans      += $rows['Loans'];
        $Tdeductions += $rows['deductions'];
        $oallowables  = $rows['allowances'] - ($rows['Overtime'] + $rows['basicpay']);
        $odeductables = $rows['deductions'] - ($rows['Manhrlost']+ $rows['Loans']);
        $netpay       = $rows['allowances'] - $rows['deductions'];
        $Toallowables  += $oallowables;
        $Todeductables += $odeductables;
        $Tnetpay       += $netpay;

       $namePOS  = $YPos ;
       $namePOS -= ($line_height) * 1.4;
       
       $pdf->addText($Lt,$YPos ,$FontSize, $rows['pfno'] );
       $pdf->addTextWrap($Lt,$namePOS,($divide * 2),$FontSize, $rows['names'],'left' );
       $pdf->addText($Lt += ($divide * 2), $YPos,$FontSize,locale_number_format( $rows['basicpay']) );
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $rows['Overtime'],2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $oallowables ,2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $rows['allowances'],2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $rows['Manhrlost'],2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $rows['Loans'],2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $odeductables ,2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $rows['deductions'],2));
       $pdf->addText($Lt += $divide, $YPos,$FontSize,locale_number_format( $netpay,2));

       $YPos -= ($line_height) * 2;
       if ($YPos - ($line_height) < $lastrow){
           $PageNumber++;
	   include('ExtFunc/masterrollheader.inc');
	  }
   }

$Lt = $Left_Margin+1;
$YPos -= ($line_height);
$footer = $lastrow -($line_height *2);

$pdf->addText($Lt +=$divide,$footer,10,_('Page Totals:'));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($Tbasicpay));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($TOvertime,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($Toallowables ,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($Tallowances,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($TManhrlost,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($TLoans,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($Todeductables,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($Tdeductions,2));
$pdf->addText($Lt +=$divide,$footer,10,locale_number_format($Tnetpay,2));

$footer -= ($line_height * 2);

$pdf->line($Left_Margin+1,$footer,($Page_Width-$Right_Margin),$footer);
$pdf->Output($_SESSION['DatabaseName'].'_Payroll_id('. $pid .')PrintedOn('. date('Y-m-d').').pdf','I');
$pdf->__destruct();

unset($_GET['Mid']);
?>
