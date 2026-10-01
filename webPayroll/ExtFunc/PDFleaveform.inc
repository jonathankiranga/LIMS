<?php
class PrintLeave {
    var $Ypos=0;
    var $types = array();

function __construct($docno){

include('includes/PDFStarter.php');

$outputpath  = $_SESSION['X_part_pics_dir'].'/Leave_for_'. $this->types['pfno']. date('Y-m-d').'.pdf';
$PageNumber  = 1;
$line_height = 12;
$pageheight  = $Page_Height - $Bottom_Margin;

if ($PageNumber>1){
    $pdf->newPage();
}
$this->GETLEAVEDOC($docno);
$pdf->addInfo('Title',_('Application Leave Form'));

$FontSize=12;
$XPos = $Left_Margin+0;
$YPos = $Page_Height-$Top_Margin;
$Mid  = ($Page_Width/3);

$pdf->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize,_('Printed').': '. Date($_SESSION['DefaultDateFormat']). '   '. _('Page'). ' ' . $PageNumber);
$pdf->addJpegFromFile($_SESSION['LogoFile'], $XPos, $YPos-($line_height*3), 0,50);
$pdf->addTextWrap(50,$YPos-($line_height*5),300,$FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addTextWrap(50,$YPos-($line_height*6),300,$FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$pdf->addTextWrap(50,$YPos-($line_height*7),300,$FontSize,$_SESSION['CompanyRecord']['regoffice2']);
$pdf->addTextWrap(50,$YPos-($line_height*8),300,$FontSize,$_SESSION['CompanyRecord']['regoffice3']);
$pdf->addTextWrap(50,$YPos-($line_height*9),300,$FontSize,$_SESSION['CompanyRecord']['regoffice4']);
$pdf->addTextWrap(50,$YPos-($line_height*10),300,$FontSize,$_SESSION['CompanyRecord']['regoffice5']);
$pdf->addTextWrap(50,$YPos-($line_height*11),300,$FontSize,$_SESSION['CompanyRecord']['regoffice6']);

$FontSize = 9;

$this->Ypos += $YPos - ($line_height * 14) ;
$pdf->addTextWrap(70,$this->Ypos ,300,$FontSize, 'Dear Sir ,');
$pdf->addText($Mid,$this->Ypos -=($line_height * 1.5) ,14, 'Application For '. $this->types['leavetype']);
$pdf->addText($Mid,$this->Ypos -=($line_height * 1.0),$FontSize, '________________________________________');

$pdf->addText(50,$this->Ypos -=($line_height * 3) ,$FontSize, 'I ');
$pdf->addText($Mid-100,$this->Ypos ,$FontSize, $this->types['appliedby'] );
$pdf->addText($Mid+100,$this->Ypos ,$FontSize, ' of  PF/No :');
$pdf->addText($Mid+220,$this->Ypos ,$FontSize, $this->types['pfno']);
$pdf->addText($Mid-100,$this->Ypos -=($line_height *.2) ,$FontSize, '......................................................');
$pdf->addText($Mid+220,$this->Ypos ,$FontSize, '.........');

$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, 'Designation ');
$pdf->addText($Mid-90,$this->Ypos ,$FontSize, $this->types['appdesignation'] );
$pdf->addText($Mid+100,$this->Ypos ,$FontSize, ' wish to apply for ');
$pdf->addText($Mid+220,$this->Ypos ,$FontSize, $this->types['leavetype']);
$pdf->addText($Mid-90,$this->Ypos -=($line_height *.2) ,$FontSize, '...........................');
$pdf->addText($Mid+220,$this->Ypos  ,$FontSize, '....................................');


$pdf->addText($Mid,$this->Ypos -=($line_height * 2) ,$FontSize,($this->types['days']>1?'For (days) ':'For (day)') );
$pdf->addText($Mid+50,$this->Ypos ,$FontSize,$this->types['days']);
$pdf->addText($Mid+50,$this->Ypos -=($line_height *.2) ,$FontSize, '...........................');


$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, 'From Date');
$pdf->addText($Mid-90,$this->Ypos ,$FontSize, $this->types['fromdate']);
$pdf->addText($Mid+100,$this->Ypos ,$FontSize, ' To date ');
$pdf->addText($Mid+220,$this->Ypos ,$FontSize, $this->types['todate']);
$pdf->addText($Mid-90,$this->Ypos -=($line_height *.2) ,$FontSize, '...........................');
$pdf->addText($Mid+220,$this->Ypos ,$FontSize, '....................................');

$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, 'While i am away from my office, my duties will be under taken by');
$pdf->addText(50,$this->Ypos -=($line_height * 2),$FontSize, ' Name ');
$pdf->addText($Mid-90,$this->Ypos ,$FontSize, $this->types['sub'] );
$pdf->addText($Mid+100,$this->Ypos ,$FontSize, 'Designation ');
$pdf->addText($Mid+220,$this->Ypos ,$FontSize, $this->types['subdesignation']);
$pdf->addText($Mid-90,$this->Ypos -=($line_height *.2) ,$FontSize, '....................................................');
$pdf->addText($Mid+220,$this->Ypos  ,$FontSize, '....................................');

$pdf->addText(50,$this->Ypos -=($line_height * 2),$FontSize, ' Leave Address ');
$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, '....................................................');
$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, '....................................................');
$pdf->addText($Mid+200,$this->Ypos ,$FontSize, 'Yours Faithfully ');
$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, '....................................................');
$pdf->addText($Mid+200,$this->Ypos ,$FontSize,'....................................................');
$pdf->addText(50,$this->Ypos -=($line_height * 2) ,$FontSize, '....................................................');
$pdf->addText($Mid+150,$this->Ypos ,$FontSize, 'Signed By....................................................');


$pdf->addText($Mid-100,$this->Ypos -=($line_height * 3),$FontSize, '______________________________________________________________________');
$pdf->addText(($Mid*3)/2,$this->Ypos -=($line_height * .1),$FontSize, ' PART II ');

$pdf->addText(50,$this->Ypos -=($line_height * 2),$FontSize, 'The officer is employed under my immediate supervision, and his/her application is recommended,');
$pdf->addText(50,$this->Ypos -=($line_height ),$FontSize, 'as satisfactory arrangements have been made to carry out his/her duties for the period stated.');
$pdf->addText(50,$this->Ypos -=($line_height * 2),$FontSize, ' Date..... '. Date($_SESSION['DefaultDateFormat']));
$pdf->addText($Mid,$this->Ypos ,$FontSize, ' Signed ............................ ');

$pdf->addText(50,$this->Ypos -=($line_height * 2),$FontSize,'Approved By: '.$this->types['apprby']);

$pdf->OutputD($outputpath);
$pdf->__destruct();


    include('includes/htmlMimeMail.php');
    $mail = new htmlMimeMail();
    $attachment = $mail->getFile($outputpath);
    $mail->setText(_('Please find your leave application'));
    $mail->setSubject(_('Leave Application'));
    $mail->addAttachment($attachment, 'Leave_application_Printed_On_'. date('Y-m-d').'.pdf', 'application/pdf');
    if($_SESSION['SmtpSetting']==0){
            $mail->setFrom(htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']) . '<' . $_SESSION['CompanyRecord']['email'] . '>');
            $result = $mail->send($this->types['email']);
    }else{
            $result = SendmailBySmtp($mail,$this->types['email']);
    }
	
 
	
}



FUNCTION GETLEAVEDOC($id){

global $db,$approvalstatus,$leavetype ;
/*array (size=14)
  0 => string '5         ' (length=10)
  1 => string '01                  ' (length=20)
  2 => string '02                  ' (length=20)
  3 => string '2015' (length=4)
  4 => string '2015-04-16 00:00:00.000' (length=23)
  5 => string '2015-04-18 23:59:59.000' (length=23)
  6 => string '2' (length=1)
  7 => string '1 ' (length=2)
  8 => string '2' (length=1)
  9 => string '' (length=0)
  10 => string 'jonathan karimi kiranga' (length=23)
  11 => string 'George Otieno obiero' (length=20)
  12 => string 'Cleaner' (length=7)
  13 => string 'Cleaner' (length=7)*/


    printleaveappliction($id);
    $sql="exec printleaveappliction";
    $ResultIndex = DB_query($sql,$db);
    $row = DB_fetch_row($ResultIndex);
    $this->types['pfno'] = $row[1];
    $this->types['email'] = $row[9];
    $this->types['appliedby'] = ucwords($row[10]);
    $this->types['sub'] = ucwords($row[11]);
    $this->types['fromdate'] = ConvertSQLDate($row[4]);
    $this->types['todate']   = ConvertSQLDate($row[5]);
    $this->types['appdesignation'] = $row[12];
    $this->types['subdesignation'] = $row[13];
    $this->types['apprby'] = $_SESSION['UsersRealName'];
    $this->types['leavetype'] = $leavetype[$row[7]];
    $this->types['days'] = $row[6];


}


}
?>