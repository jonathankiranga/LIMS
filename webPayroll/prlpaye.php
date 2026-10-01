<?php
include('includes/session.inc');
include('ExtFunc/gensalary.inc');
include('ExtFunc/extractdate.inc');

if(isset($_POST['costid']) and isset($_POST['productid']) and isset($_POST['year'])){
    $code = $_POST['costid'];
    
    $_SESSION['p9colums'] = array();
    $_SESSION['p9rows']   = array();
    
    $_SESSION['employeeclass'] = $Extrdate->filteremployeeheader($code);
    $datefrom  = $Extrdate->returndate(1,$_POST['year']);
    $dateto    = LastDayOfMonth($Extrdate->returndate(12,$_POST['year']));
    $PaperSize = 'A4_Landscape';

    include('includes/PDFStarter.php');
    $PageNumber = 1;
    $line_height = 12;

    $pageheight = $Page_Height- $Bottom_Margin;
    $employeenamerow = (($Page_Height - $Top_Margin)-($line_height * 7));
    $employeeothernamerow = (($Page_Height - $Top_Margin)-($line_height * 9));
    $tabletop    =  $employeeothernamerow - ($line_height * 2);
    $Rowheight   = ($Page_Height - ($tabletop + $Bottom_Margin + ($line_height * 4)))/12;
    $columswidth = (($Page_Width - ($Right_Margin + $Left_Margin))/15);
    $firstrow    = $tabletop - ($line_height * 7) ;
    $byy         = $line_height  ;

    $p9Data = PrintP9($_POST['year'], rtrim($code), $db);
    
    foreach ($p9Data as $rows) {
        $Totalchargeablepay = 0;
        $Totaltax = 0;
        
        printp9(rtrim($code));
        
        include('Extfunc/paye9header.inc');
        $Yloc1 = $firstrow + $line_height   ;

        $m = $rows['month'];
        $Yloc = $Yloc1 - ($byy * $m);

        $Totalchargeablepay += $rows['payetax']+$rows['inrelief']+$rows['perelief'];
        $Totaltax  += $rows['payetax'];

        $pdf->addTextWrap($_SESSION['p9colums'][1],$Yloc,$columswidth,10,number_format($rows['basicPay'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][2],$Yloc,$columswidth,10,number_format($rows['noncash'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][3],$Yloc,$columswidth,10,number_format(0,2));
        $pdf->addTextWrap($_SESSION['p9colums'][4],$Yloc,$columswidth,10,number_format($rows['basicPay']+$rows['noncash'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][5],$Yloc,$columswidth,10,number_format(($rows['basicPay']+$rows['noncash'])/3,2));
        $pdf->addTextWrap($_SESSION['p9colums'][6],$Yloc,$columswidth,10,number_format($rows['pension'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][7],$Yloc,$columswidth,10,number_format(20000,2));
        $pdf->addTextWrap($_SESSION['p9colums'][8],$Yloc,$columswidth,10,number_format(0,2));
        $pdf->addTextWrap($_SESSION['p9colums'][9],$Yloc,$columswidth,10,number_format(0,2));
        $pdf->addTextWrap($_SESSION['p9colums'][10],$Yloc,$columswidth,10,number_format($rows['basicPay']+$rows['noncash']-$rows['pension'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][11],$Yloc,$columswidth,10,number_format(($rows['payetax']>0)?$rows['payetax']+$rows['inrelief']+$rows['perelief']:0,2));
        $pdf->addTextWrap($_SESSION['p9colums'][12],$Yloc,$columswidth,10,number_format($rows['perelief'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][13],$Yloc,$columswidth,10,number_format($rows['inrelief'],2));
        $pdf->addTextWrap($_SESSION['p9colums'][14],$Yloc,$columswidth,10,number_format($rows['payetax'],2));

       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 12),10,'TOTAL CHARGEABLE PAY (COL J) :  ' . number_format($Totalchargeablepay,2).'           '.' TOTAL TAX (COL L) : '.number_format($Totaltax,2));
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 10),10,'CERTIFICATE OF PAY AND TAX');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 8),10,'Name........................................');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 6.5),10,'Address........................................');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 5),10,'Signature........................................');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 3),10,'Date and Stamp........................................');

        $PageNumber++;
    }

    $pdf->OutputD($_SESSION['DatabaseName'].'_P9_'. $_POST['costid'] .'_'. date('Y-m-d').'.pdf');
    $pdf->__destruct();

} elseif(isset($_POST['productid']) and isset($_POST['year']) ){

    $_SESSION['p9colums'] = array();
    $_SESSION['p9rows']   = array();
  
    $datefrom  = $Extrdate->returndate(1,$_POST['year']);
    $dateto    = LastDayOfMonth($Extrdate->returndate(12,$_POST['year']));
    $_SESSION['employeeclass'] = getemployeeheader($datefrom,$dateto);

    $PaperSize = 'A4_Landscape';

    include('includes/PDFStarter.php');
    $PageNumber = 1;
    $line_height = 12;

    $pageheight = $Page_Height- $Bottom_Margin;
    $employeenamerow = (($Page_Height - $Top_Margin)-($line_height * 7));
    $employeeothernamerow = (($Page_Height - $Top_Margin)-($line_height * 9));
    $tabletop    =  $employeeothernamerow -($line_height * 2);
    $Rowheight   = ($Page_Height - ($tabletop + $Bottom_Margin + ($line_height * 4)))/12;
    $columswidth = (($Page_Width - ($Right_Margin + $Left_Margin))/15);
    $firstrow    = $tabletop - ($line_height * 7) ;
    $byy         = $line_height  ;

    foreach ($_SESSION['employeeclass']['pfno'] as $pikey => $value) {
        $Totalchargeablepay = 0;
        $Totaltax = 0;
        printp9(rtrim($value));
        include('Extfunc/paye9header.inc');
        $Yloc1 = $firstrow + $line_height   ;

        $p9Data = PrintP9($_POST['year'], rtrim($value), $db);
        foreach ($p9Data as $rows) {
            $m = $rows['month'];
            $Yloc = $Yloc1 - ($byy * $m);

            $Totalchargeablepay += $rows['payetax']+$rows['inrelief']+$rows['perelief'];
            $Totaltax  += $rows['payetax'];

            $pdf->addTextWrap($_SESSION['p9colums'][1],$Yloc,$columswidth,10,number_format($rows['basicPay'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][2],$Yloc,$columswidth,10,number_format($rows['noncash'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][3],$Yloc,$columswidth,10,number_format(0,2));
            $pdf->addTextWrap($_SESSION['p9colums'][4],$Yloc,$columswidth,10,number_format($rows['basicPay']+$rows['noncash'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][5],$Yloc,$columswidth,10,number_format(($rows['basicPay']+$rows['noncash'])/3,2));
            $pdf->addTextWrap($_SESSION['p9colums'][6],$Yloc,$columswidth,10,number_format($rows['pension'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][7],$Yloc,$columswidth,10,number_format(20000,2));
            $pdf->addTextWrap($_SESSION['p9colums'][8],$Yloc,$columswidth,10,number_format(0,2));
            $pdf->addTextWrap($_SESSION['p9colums'][9],$Yloc,$columswidth,10,number_format(0,2));
            $pdf->addTextWrap($_SESSION['p9colums'][10],$Yloc,$columswidth,10,number_format($rows['basicPay']+$rows['noncash']-$rows['pension'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][11],$Yloc,$columswidth,10,number_format(($rows['payetax']>0)?$rows['payetax']+$rows['inrelief']+$rows['perelief']:0,2));
            $pdf->addTextWrap($_SESSION['p9colums'][12],$Yloc,$columswidth,10,number_format($rows['perelief'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][13],$Yloc,$columswidth,10,number_format($rows['inrelief'],2));
            $pdf->addTextWrap($_SESSION['p9colums'][14],$Yloc,$columswidth,10,number_format($rows['payetax'],2));
         }

       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 12),10,'TOTAL CHARGEABLE PAY (COL J) :  ' . number_format($Totalchargeablepay,2).'           '.' TOTAL TAX (COL L) : '.number_format($Totaltax,2));
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 10),10,'CERTIFICATE OF PAY AND TAX');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 8),10,'Name........................................');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 6.5),10,'Address........................................');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 5),10,'Signature........................................');
       $pdf->addText($_SESSION['p9colums'][1],$Bottom_Margin + ($line_height * 3),10,'Date and Stamp........................................');

        $PageNumber++;
    }

    $pdf->OutputD($_SESSION['DatabaseName'].'_P9_All_'. date('Y-m-d').'.pdf');
    $pdf->__destruct();

} 
else {

$Title = "P9 PAYE REPORT CARD"  ;
include('includes/header.inc');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';
$year = date('Y');

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-file-invoice"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Generate P9 PAYE Report Card</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
if(isset($_GET['costid'])) {
   echo '<input type="hidden" name="costid" value="'. $_GET['costid'] .'" />';
}

echo '<div class="sp-row">';
echo '<div class="sp-col-6">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select PAYE Item</label>';
echo '<select name="productid" class="sp-select" required/>';

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
echo '<button type="submit" name="submitp9" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Print P9</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';
include('includes/footer.inc') ;
}


function getemployeeheader($datefrom,$dateto){
global $db;
$i=0;
$employeeclass = array();

$ResultIndex =DB_query("select pkey from prlmrollperiods
    where fromdate >='". FormatDateForSQL($datefrom) ."' "
        . " and todate <='". FormatDateForSQL($dateto) ."'",$db);
$pkeyrow =DB_fetch_row($ResultIndex);
$PKEY=$pkeyrow[0];
DB_free_result($ResultIndex);

    $sql="select * from prlemployeemaster where pf_no in "
            . "(select pfno from prlpaydetailstransfile where payroll_id=".$PKEY.")";
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $employeeclass['pfno'][$i] = $row['pf_no'];
        $employeeclass['fname'][$i] = $row['fname'];
        $employeeclass['mname'][$i] = $row['mname'];
        $employeeclass['lname'][$i] = $row['lname'];
        $employeeclass['pinno'][$i] = $row['pin_no'];
        $i++;
    }

return $employeeclass;
}


?>
