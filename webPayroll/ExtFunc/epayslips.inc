<?php

if(isset($_POST['StaffID'])){
    
  if(isset($_GET['prid'])){
    
    $pid = $_GET['prid'];
    
    $clsPdf = new PDFoneslip();
    foreach ($_POST['StaffID'] as $staffid) {
         PreparePDF($staffid);
    }
    $pdf->__destruct();
     
    include('includes/header.inc') ;
   
    echo '<div><a href="'.$self.'">Back to Home Page</a></div>';
   
     include('includes/footer.inc');
  }
  
  
}


function PreparePDF($staffid){
    Global $YPos,$clsPdf,$db,$pdf, $Page_Height, $Top_Margin, $Bottom_Margin, $line_height,$underline_header, $Left_Margin, $Page_Width, $Right_Margin;
    
    $staff_array = getemail($staffid);
    
    $FontSize=9;
    $PaperSize='A5';
    $pdf->addInfo('Title', _('Pay Slip') );
    $PageNumber = 1;
    $line_height = 12;
    
    $clsPdf->__construct() ;
    $clsPdf->pgheader();
    $YPos = $clsPdf->underline_header - $line_height;
    $clsPdf->columRow = 0;

    $payslipsarray = epayslip($staffid) ;
    
    foreach ($payslipsarray as $rows) {
                    // Note : this procedure is case sensitive
                    $case = $rows['Format'];

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

                       if($case=='g'){
                           $YPos -= (2 * $line_height); $FontSize=12; 
                       } else { 
                           $FontSize=9;
                       }

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

                    $YPos -= ($line_height);
                    if($case =='K') {
                       $YPos = $clsPdf->underline_header - $line_height;
                    }

                }


    $clsPdf->pdf->Output('companies/'.$_SESSION['DatabaseName'].'/reports/_' . $staffid . '.pdf','F'); //save to file 
    $attachpayslip = 'companies/'.$_SESSION['DatabaseName']. '/reports/_' . $staffid . '.pdf';
    $address = $staff_array[0]['email'];

    sleep(2);

    $mymailer = new MyMailer();
    $mymailer->sendmail($address,'Payroll', _('Please find herewith your payslip '. $staffid), $attachpayslip);

}


?>