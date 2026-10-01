<?php
if(isset($_GET['prid'])){
$pid = $_GET['prid'];
$singleSlip = count($_POST['StaffID'])==1;

if($singleSlip){ 
   $Slips='one';
 } else {
   $Slips='many';
}


$FontSize=9;
$pdf->addInfo('Title', _('Pay Slip') );
$PageNumber=1;
$line_height=12;

if($singleSlip){ 
   $clsPdf = new ONEPDFslip();
} else {
   $clsPdf = new PDFslip();
}

$clsPdf->pgheader();

$YPos = $clsPdf->underline_header - $line_height;
$clsPdf->columRow = 0;

    // Note : this procedure is case sensitive
$payslipsarray = $singleSlip ? epayslip($_POST['StaffID'][0]) : payslips();
foreach ($payslipsarray as $rows) {
    $case = $rows['Format'];
    if($clsPdf->columRow==0){
        switch ($case) {
            case 'l':
                $clsPdf->putline1($YPos);
                 break;
            case 'b':
                $clsPdf->putline1($YPos);
                break;
            case 'K':
                $YPos -= (2 * $line_height);
                break;
           default:

               if($case=='g'){$YPos -= (2 * $line_height); $FontSize=12; } else { $FontSize=9;}
                if($rows['Amount']==0){
                   $clsPdf->pdf->addTextWrap(50,$YPos,300,$FontSize, $rows['Notes'],'left');
                }else{
                   $clsPdf->pdf->addTextWrap(50,$YPos,130,$FontSize, $rows['Notes'],'left');
                }
                 if($rows['Amount']!=0){
                   $clsPdf->pdf->addTextWrap(170,$YPos,50,$FontSize, locale_number_format($rows['Amount'],2),'right');
                 }
                if($rows['LoanBalance']!=0){
                   $clsPdf->pdf->addTextWrap(240,$YPos,50,$FontSize, locale_number_format($rows['LoanBalance'],2),'right');
                 }
                 
               break;
        }

        $YPos -= ( $line_height);

    } elseif($Slips=='many') {
        $spce = $clsPdf->colum2start;

        switch ($case) {
            case 'l':
                $clsPdf->putline2($YPos);
                break;
            case 'b':
                $clsPdf->putline2($YPos);
                break;
            case 'K':
                $YPos -= (2 * $line_height);
               break;
           default:
               if($case=='g'){
                   $YPos -= (2 * $line_height);$FontSize=12;
                 }else{
                   $FontSize=9;
                 }
                 
               
               if($rows['Amount']==0){
                  $clsPdf->pdf->addTextWrap($spce,$YPos,300,$FontSize, $rows['Notes'],'left');
                }else{
                  $clsPdf->pdf->addTextWrap($spce,$YPos,130,$FontSize, $rows['Notes'],'left');
                }
               if($rows['Amount']!=0){
                 $clsPdf->pdf->addTextWrap($spce+120,$YPos,50,$FontSize, locale_number_format($rows['Amount'],2),'right');
                }
                
               if($rows['LoanBalance']!=0){
                 $clsPdf->pdf->addTextWrap($spce+200,$YPos,50,$FontSize, locale_number_format($rows['LoanBalance'],2),'right');
                }
               
               break;
        }

        $YPos -= ( $line_height);


    }

    if($case =='K' and $Slips=='many') {
      if($clsPdf->columRow==1){
     }

    $YPos = $clsPdf->underline_header - $line_height;
    if($clsPdf->columRow==0){
       $clsPdf->columRow=1 ;
     }elseif($Slips=='many'){
       $clsPdf->columRow=0 ;
       $PageNumber++;
       $clsPdf->pgheader();
     }else{
       $clsPdf->columRow=0 ;
       $PageNumber++;
       $clsPdf->pgheader();
     }
  }
    
}

$pdf->Output($_SESSION['DatabaseName'].'_Payroll_id_'. $pid .'_PrintedOn_'. date('Y-m-d').'.pdf','I');
$pdf->__destruct();

}



?>
