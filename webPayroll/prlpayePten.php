<?php
include('includes/session.inc');
include('ExtFunc/gensalary.inc');
include('ExtFunc/extractdate.inc');

if(isset($_POST['productid']) and isset($_POST['year']) ){
    $_SESSION['p9colums'] = array();
    $_SESSION['p9rows']   = array();
  
    $datefrom  = $Extrdate->returndate(1,$_POST['year']);
    $dateto    = LastDayOfMonth($Extrdate->returndate(12,$_POST['year']));
    
    printp10();
    
    $PaperSize = 'A4';

    include('includes/PDFStarter.php');
        $PageNumber = 1;
        $line_height = 15;
        
        $pageheight  = $Page_Height- $Bottom_Margin;
        $Rowheight   = ($Page_Height - ($tabletop + $Bottom_Margin + ($line_height * 4)))/12;
        $columswidth = (($Page_Width - ($Right_Margin + $Left_Margin))/5);
        $firstrow    = $tabletop - ($line_height * 2) ;
        $byy         = $line_height  ;
        
        include('Extfunc/paye10header.inc');

        $Rowheight   = ($Page_Height - ($tabletop + $Bottom_Margin + ($line_height * 4)))/12;
        $columswidth = (($Page_Width - ($Right_Margin + $Left_Margin))/5);
        $firstrow    = $tabletop - ($line_height * 3) ;
        $byy         = $line_height  ;

        $Totalchargeablepay = 0;
        $Totaltax = 0;
        $Yloc1 = $firstrow ;

        $p10Data = PrintP10($_POST['year'], $db);
        
        foreach ($p10Data as $rows) {
            $m          = $rows['month'];
            $Yloc       = $Yloc1 - ($byy * $m);
            $Totaltax  += $rows['netpaye'];
            $pdf->addTextWrap($_SESSION['p9colums'][1],$Yloc,$columswidth,10,number_format($rows['netpaye'],2),'right');
         }
         
        $pdf->addTextWrap($Left_Margin+$columswidth,$TotalRowPosition ,$columswidth,10,number_format($Totaltax,2),'right');

        $LeftOvers =$pdf->addTextWrap($Left_Margin,$TotalRowPosition -=($line_height*5),50,10,"NOTE:-",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin+10,$TotalRowPosition -=$line_height,500,10,"(1) Attach Photostat copies of ALL the Pay-In Credit Slips (P11s) for the year",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin+10,$TotalRowPosition -=$line_height,500,10,"(4) Complete this certificate in triplicate sending the top two copies with the enclosures to your Income",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin+10,$TotalRowPosition -=$line_height,500,10,"Tax Office not later than 28TH FEBRUARY.",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin+10,$TotalRowPosition -=$line_height,500,10,"(5) Provide Statistical information required by Central Bureau of Statistics.",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin,$TotalRowPosition -=($line_height*2),500,10,"We/I certify that the particulars entered above are correct.",'left');
        
        $LeftOvers =$pdf->addTextWrap($Left_Margin,$TotalRowPosition -=($line_height*2),200,10,"NAME OF EMPLOYER:..",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin+120,$TotalRowPosition ,500,10, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']),'left');
       
        $LeftOvers =$pdf->addTextWrap($Left_Margin,$TotalRowPosition -=($line_height*2),500,10,"ADDRESS :",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin+120,$TotalRowPosition ,500,10,$_SESSION['CompanyRecord']['regoffice1'].' '.$_SESSION['CompanyRecord']['regoffice2'],'left');
       
        $LeftOvers =$pdf->addTextWrap($Left_Margin,$TotalRowPosition -=($line_height*2),500,10,"SIGNATURE................................................................................................",'left');
        $LeftOvers =$pdf->addTextWrap($Left_Margin,$TotalRowPosition -=$line_height*2,500,10,"DATE....................................................................................................",'left');
  

    $pdf->OutputD($_SESSION['DatabaseName'].'_P10_'. date('Y-m-d').'.pdf');
    $pdf->__destruct();

} else {

$Title = "P10 PAYE REPORT CARD"  ;
include('includes/header.inc');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';
$year = date('Y');

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-file-invoice"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Generate P10 PAYE Report Card</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<div class="sp-row">';
echo '<div class="sp-col-6">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select PAYE Item</label>';
echo '<select name="productid" class="sp-select" required="required"/>';

    $SQL="Select * from prlproducts where pens_tax=1 and deduction=0";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        echo '<option value="'.$row['code'].'">'.$row['description'].'</option>';
    }
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="sp-col-3">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Year</label>';
echo '<input type="number" name="year" class="sp-input" value="'.$year.'" required min="2000" max="2049"/>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="sp-d-flex sp-justify-between sp-align-center">';
echo '<button type="submit" name="submitp9" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Print P10</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';
include('includes/footer.inc') ;
}

?>
