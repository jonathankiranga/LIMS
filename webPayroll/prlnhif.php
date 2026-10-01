<?php
include('includes/session.inc');
include('ExtFunc/gensalary.inc');
include('ExtFunc/extractdate.inc');

$payrollId = '';
$productCode = '';

if(isset($_POST['productid']) and isset($_POST['year']) and isset($_POST['month'])){
    $period = GetPayrollPeriodByMonthYear($_POST['year'], $_POST['month'], $db);
    if ($period) {
        $payrollId = $period['pkey'];
        $datefrom = ConvertSQLDate($period['fromdate']);
        $dateto = ConvertSQLDate($period['todate']);
        $productCode = $_POST['productid'];
    }
    
    if (!empty($payrollId)) {
        include('includes/PDFStarter.php');
        $PageNumber = 1;
        $line_height = 12;
        $pageheight = $Page_Height - $Bottom_Margin;

        include('Extfunc/nhifheader.inc');

        $YPos -= ($line_height * 2);
        $divide = (($Page_Width-$Right_Margin-$Left_Margin)/6);
        $lastrow = $Bottom_Margin;
        $Tamount = 0;
        $employersAmount = 0;
        $TemployersAmount = 0;
        $TlineTotal = 0;
        $TGross = 0;

        if(isset($_POST['StaffID'])){
          $ResultIndex = FilterPrintByMonth($payrollId, $productCode, $_POST['StaffID'], $db);
        } else {
          $ResultIndex = PrintByMonth($payrollId, $productCode, $db);
        }

        while($rows = DB_fetch_array($ResultIndex)){
            $Lt = $Left_Margin+1;
            $Tamount += $rows['amount'];
            $TemployersAmount += $rows['empl'];
            $TlineTotal = ($rows['amount'] + $rows['empl']);
            $TGross += $TlineTotal;

           $pdf->addTextWrap($Lt,$YPos,$divide*2,$FontSize, $rows['nhifno'] );
           $pdf->addTextWrap($Lt +=$divide*2, $YPos,($divide*3),$FontSize, $rows['names'],'left' );
           $pdf->addTextWrap($Lt +=($divide*2),$YPos,($divide*2),$FontSize,locale_number_format($rows['amount'],2) ,'right');

           $YPos -= ($line_height);
           if ($YPos - ($line_height) < $Bottom_Margin){
               $PageNumber++;
               include('Extfunc/nhifheader.inc');
           }
       }

            $Lt = $Left_Margin+1;
            $YPos -= ($line_height);
            $pdf->line($Left_Margin,$lastrow+$line_height,($Page_Width-$Right_Margin),$lastrow+$line_height,$style2);
            $pdf->addTextWrap($Lt +=$divide*2,$lastrow,$divide*2,$FontSize,_('Page Totals :'));
            $pdf->addTextWrap($Lt +=($divide*2),$lastrow,$divide*2,$FontSize,locale_number_format($Tamount,2),'right');


           $YPos -= ($line_height);
           if ($YPos - ($line_height * 2) < $Bottom_Margin){
               $PageNumber++;
               include('extfunc/nssfheader.inc');
           }

        $pdf->OutputD($_SESSION['DatabaseName'].'_NHIF_PrintedOn_'. date('Y-m-d').'.pdf');
        $pdf->__destruct();

        unset($_POST['productid']);
    }

} else {
$Title = "N.H.I.F. REPORT CARD"  ;
include('includes/header.inc');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-file-medical"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Generate NHIF Report Card</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
if(isset($_GET['costid'])){
    echo '<input type="hidden" name="StaffID" value="'. $_GET['costid'] .'" />';
}

echo '<div class="sp-row">';
echo '<div class="sp-col-6">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Product</label>';
echo '<select name="productid" class="sp-select" required/>';
    $SQL="Select * from prlproducts where pens_tax is null and deduction=0";
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
echo '<input type="number" name="year" class="sp-input" value="'. date('Y')  .'" required min="2000" max="2049"/>';
echo '</div>';
echo '</div>';

echo '<div class="sp-col-3">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Month</label>';
echo '<input type="number" name="month" class="sp-input" value="'. date('m')  .'" required min="1" max="12"/>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="sp-d-flex sp-justify-between sp-align-center">';
echo '<button type="submit" name="submitp9" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Print NHIF</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';

include('includes/footer.inc') ;
}
?>
