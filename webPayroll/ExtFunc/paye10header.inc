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

$FontSize=12;

$XPos = $Left_Margin+0;
$YPos = $Page_Height-$Top_Margin;
$centre = ($Page_Width-($Right_Margin+$Left_Margin))/2 ;
$rightalign = $Page_Width-$Right_Margin;

$pdf->addJpegFromFile('flags/kralogo.jpg',$centre-45, $YPos-=($line_height*2), 0,45);
$pdf->addTextWrap($centre-60,$YPos-=$line_height,300,14,'INCOME TAXES DEPARTMENT');
$pdf->addTextWrap($Left_Margin,$YPos,300,14,'P10');
$pdf->addText($Page_Width-$Right_Margin-120,$YPos -=$line_height ,8,'EMPLOYER’S PIN:'.$payrolldatesarray[2]);
$pdf->addTextWrap($centre-60,$YPos -=($line_height*3),300,10,'P.A.Y.E – EMPLOYER’S CERTIFICATE');
$pdf->addTextWrap($centre-60,$YPos -=$line_height,300,10,' YEAR :'.$_POST['year']);
$pdf->addTextWrap($Left_Margin,$YPos -=$line_height,300,10,'To Senior Assistant Commissioner:');
$pdf->addTextWrap($Left_Margin,$YPos -=$line_height,300,12,str_repeat('.',100));
$LeftOvers =$pdf->addTextWrap($Left_Margin,$YPos -=$line_height,500,10,'We/I forward herewith………………Tax Deduction Cards (P9A/P9B) showing the total tax deducted (as listed
on P.10A) amounting to Kshs…………………' ,'left');
   while (mb_strlen($LeftOvers) > 0) {
        $YPos -= $line_height;// Suggestion: $line_height = $FontSize;
        $LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos,400,10, $LeftOvers, 'left');
    }
    
$LeftOvers =$pdf->addTextWrap($Left_Margin,$YPos -=$line_height,400,10,'This total tax has been paid as follows:-' ,'left');
$tabletop  =$YPos-$line_height;

$st = $Left_Margin;
$ro = 0;

for( $i= 0 ; $i <= 4 ; $i++ ){
          switch ($i) {
            case 0:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'MONTH','center');
             break;
            case 1:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'PAYE TAX ','center');
                $pdf->addTextWrap($st,$tabletop-($line_height * 3),$columswidth,12,'KSHS','center');
                break;
            case 2:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'AUDIT TAX,','center');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'INTEREST/PENALTY','center');
                $pdf->addTextWrap($st,$tabletop-($line_height * 3),$columswidth,12,'KSHS','center');
                break;
            case 3:
                 $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'FRINGE','center');
                 $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'BENEFIT TAX','center');
                 $pdf->addTextWrap($st,$tabletop-($line_height * 3),$columswidth,12,'KSHS','center');
                break;
            case 4:
                $pdf->addTextWrap($st,$tabletop-($line_height),$columswidth,8,'DATE PAID PER','center');
                $pdf->addTextWrap($st,$tabletop-($line_height*2),$columswidth,8,'(RECEIVING BANK)','center');
                $pdf->addTextWrap($st,$tabletop-($line_height * 3),$columswidth,12,'KSHS','center');
                break;
            }
            
     $_SESSION['p9colums'][$i] = $st;
     $st += ($columswidth);
}




$st = $Left_Margin;
$fw = $tabletop-($line_height *4);
$i=0;

foreach ($mymonth as $key => $value) {
    $_SESSION['p9rows'][$i] = $fw;
    $pdf->addTextWrap($st,$fw ,$columswidth,10,$value);
    $fw -= $line_height   ;
    $i++;
}
$TotalRowPosition = $fw;
$pdf->addTextWrap($st,$TotalRowPosition,$columswidth,10,'TOTAL TAX KSHS.');
  
$bottomrow = $_SESSION['p9rows'][$i-1];
$endoftable = ($tabletop-$Bottom_Margin-$bottomrow );

$st = $Left_Margin;
for( $i= 0 ; $i <= 4 ; $i++ ){
  $pdf->Rectangle($st,$tabletop,$columswidth,($tabletop-$Bottom_Margin - $bottomrow + ($line_height*4) ));
 
  $st += ($columswidth);
}


$st = $Left_Margin;
$i=0;
$fw = $tabletop-($line_height *3);
for ($i=0; $i < 13; $i++){
    $fw -= $line_height   ;
    $pdf->line($st,$fw+$line_height,$Page_Width - ($Right_Margin + $Left_Margin-$st),$fw+$line_height);
}


?>
