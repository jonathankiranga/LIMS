<?php


 $mymonth = Array();
 $mymonth[0]='January';
 $mymonth[1]='February';
 $mymonth[2]='March';
 $mymonth[3]='April';
 $mymonth[4]='May';
 $mymonth[5]='June';
 $mymonth[6]='July';
 $mymonth[7]='August';
 $mymonth[8]='September';
 $mymonth[9]='October';
 $mymonth[10]='November';
 $mymonth[11]='December';


$SQL="SELECT description,employerfactor,membership_no FROM [prlproducts] where [code]='".$_POST['productid']."'";
$ResultIndex = DB_query($SQL,$db);
$payrolldatesarray = DB_fetch_row($ResultIndex);

if ($PageNumber>1){
   $pdf->newPage();
}

$FontSize=9;

$XPos = $Left_Margin+0;
$YPos = $Page_Height-$Top_Margin;
$pdf->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize,_('Printed').': '. Date($_SESSION['DefaultDateFormat']). '   '. _('Page'). ' ' . $PageNumber);

$centre = ($Page_Width-($Right_Margin+$Left_Margin))/2 ;
$rightalign = $Page_Width-$Right_Margin;

$pdf->addJpegFromFile('flags/kralogo.jpg',$centre-20, $YPos-($line_height*3), 0,40);
$pdf->addTextWrap($centre-60,$YPos-($line_height*4),300,14,'DOMESTIC TAXES DEPARTMENT');
$pdf->addTextWrap($Left_Margin,$YPos-($line_height*5),300,12,'Employer\'s Name:'. htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addTextWrap($centre-60,$YPos-($line_height*5),300,12,'P9A -TAX DEDUCTIONS CARD YEAR '.$_POST['year']);
$pdf->addTextWrap($rightalign-200,$YPos-($line_height*5),300,12,'Employer\'s PIN :'.$payrolldatesarray[2]);

$pdf->addTextWrap($Left_Margin,$YPos-($line_height*7),300,12,'Employee\'s Main Name :'. $_SESSION['employeeclass']['lname'][$pikey]);
$pdf->addTextWrap($rightalign-200,$YPos-($line_height*7),300,12,'Employee\'s PIN :'. $_SESSION['employeeclass']['pinno'][$pikey]);
$pdf->addTextWrap($Left_Margin,$YPos-($line_height*9),300,12,'Employee\'s Other Names :'. $_SESSION['employeeclass']['fname'][$pikey] .' '.$_SESSION['employeeclass']['mname'][$pikey]);

$pdf->Rectangle($Left_Margin,$tabletop-($line_height*4),($Page_Width-$Right_Margin-$Left_Margin),(($line_height*2)));
$pdf->Rectangle($Left_Margin,$tabletop,($Page_Width-$Right_Margin-$Left_Margin),($tabletop - $Bottom_Margin - ($line_height * 14)));

// draw colums

$st=$Left_Margin;
$ro=0;

for( $i= 0 ; $i <= 15 ; $i++ ){
          switch ($i) {
            case 0:
             $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'MONTH');

              break;
            case 1:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Basic Pay');
                $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'A','center');
                break;
            case 2:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Benefits');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Non-Cash');
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'B','center');
                break;
            case 3:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Value of');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Quarters');
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'C','center');
                break;
            case 4:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Total');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'GrossPay');
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'D','center');
                break;
            case 5:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth*3,8,'Defined Contribution','center');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth*3,8,'Retirement Scheme','center');
                $pdf->addTextWrap($st,$tabletop-($line_height*3),$columswidth*3,14,'E','center');
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'E1','center');
                break;
            case 6:
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'E2','center');
                break;
            case 7:
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'E3','center');
                break;
            case 8:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Owner');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Occupied');
                $pdf->addTextWrap($st,$tabletop-($line_height*3),$columswidth,8,'Interest');
                $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'F','center');
               break;
            case 9:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Retirement');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Contributions &');
                $pdf->addTextWrap($st,$tabletop-($line_height*3),$columswidth,8,'Owner Occupied');
                $pdf->addTextWrap($st,$tabletop-($line_height*4),$columswidth,8,'Interest');
                $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'G','center');
                break;
            case 10:
                 $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Chargeable');
                 $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Pay');
                 $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'H','center');
                break;
            case 11:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Tax');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Charged');
                $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'J','center');
               break;
           case 12:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Personal');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Relief');
                $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth * 2,12,'K','center');
               break;
           case 13:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'Insurance');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'Relief');
               break;
           case 14:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'PAYE');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'TAX');
                $pdf->addTextWrap($st,$tabletop-($line_height*3),$columswidth,8,'(J-K)');
                $pdf->addTextWrap($st,$tabletop-($line_height * 5),$columswidth,12,'L','center');
               break;
    }
     $_SESSION['p9colums'][$i] = $st;
    $st += ($columswidth);
}




$st = $Left_Margin;
$fw = (($Page_Height - $Top_Margin)-($line_height * 18));;
$i=0;

foreach ($mymonth as $key => $value) {
    $_SESSION['p9rows'][$i] = $fw;
    $pdf->addTextWrap($st,$fw ,$columswidth,10,$value);
    $fw -= $line_height   ;
    $i++;
}

$bottomrow = $_SESSION['p9rows'][$i-1];
$endoftable = ($tabletop-$Bottom_Margin-$bottomrow );

$st = $Left_Margin;
for( $i= 0 ; $i <= 14 ; $i++ ){

      switch ($i) {
        case 5:
            $pdf->Rectangle($st,$tabletop-($line_height*4),$columswidth,$endoftable);
            break;
        case 6:
            $pdf->Rectangle($st,$tabletop-($line_height*4),$columswidth,$endoftable);
            break;
        case 7:
            $pdf->Rectangle($st,$tabletop-($line_height*4),$columswidth,$endoftable);
            break;
        case 12:
            break;
        case 13:
            $pdf->line($st,$tabletop,$st, $tabletop -($line_height * 4));
            $pdf->line($st ,$tabletop -($line_height * 6), $st ,$Bottom_Margin + ($line_height * 14));
            break;
         default:
             $pdf->Rectangle($st,$tabletop,$columswidth,($tabletop-$Bottom_Margin - $bottomrow + ($line_height*4) ));
         break;
    }


  $st += ($columswidth);
}


$st = $Left_Margin;
$fw = (($Page_Height - $Top_Margin)-($line_height * 18));;
$i=0;

for ($i=0; $i < 11; $i++){
    $fw -= $line_height   ;
    $pdf->line($st,$fw+$line_height,$Page_Width - ($Right_Margin + $Left_Margin-$st),$fw+$line_height);
}
?>
