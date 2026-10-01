<?php
$Title="PayRoll By products";
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
include('ExtFunc/gensalary.inc');
include('ExtFunc/employeetypes.inc');
include('ExtFunc/extractdate.inc');

$payrollId = '';
$productCode = '';

if(isset($_POST['year'])){
    $period = GetPayrollPeriodByMonthYear($_POST['year'], $_POST['month'], $db);
    if ($period) {
        $payrollId = $period['pkey'];
        $_POST['datefrom'] = ConvertSQLDate($period['fromdate']);
        $_POST['dateto'] = ConvertSQLDate($period['todate']);
        
        $SQL="SELECT description,employerfactor FROM [prlproducts] where amount >0 and  [code]='".$_POST['productid']."'";
        $ResultIndex = DB_query($SQL,$db);
        $payrolldatesarray = DB_fetch_row($ResultIndex);
        $productCode = $_POST['productid'];
    }
}

if(isset($_POST['productid']) && !empty($payrollId)){
    include('includes/PDFStarter.php');
    $PageNumber = 1;
    $line_height = 12;
    $pageheight = $Page_Height- $Bottom_Margin;
    include('Extfunc/masterrollproductsheader.inc');
  
    if($payrolldatesarray[1] >=2){
       $divide = (($Page_Width-$Right_Margin-$Left_Margin)/10);
    } else {
       $divide = (($Page_Width-$Right_Margin-$Left_Margin)/6);
    }

    $lastrow = $Bottom_Margin;
    $Tamount = 0;
    $employersAmount = 0;
    $TemployersAmount = 0;
    $TlineTotal = 0;
    $TGross = 0;

    $ResultIndex = PrintProducts($payrollId, $productCode, $db);
    while($rows = DB_fetch_array($ResultIndex)){
        $Lt = $Left_Margin+1;
        $Tamount += $rows['amount'];
        $TemployersAmount += $rows['empl'];
        $TlineTotal = ($rows['amount'] + $rows['empl']);
        $TGross += $TlineTotal;

       $pdf->addTextWrap($Lt,$YPos,$divide,$FontSize, $rows['pfno'] );
       $pdf->addTextWrap($Lt +=$divide, $YPos,($divide*3),$FontSize, $rows['names'],'left' );
       if($payrolldatesarray[1] >=2){
           $pdf->addTextWrap($Lt +=($divide*2),$YPos,($divide*2),$FontSize,locale_number_format($rows['amount'],2) ,'right');
           $pdf->addTextWrap($Lt +=($divide*2),$YPos,($divide*2),$FontSize,locale_number_format($rows['empl'],2),'right');
           $pdf->addTextWrap($Lt +=($divide*3),$YPos,($divide*2),$FontSize,locale_number_format($TlineTotal ,2),'right');
       } else {
           $pdf->addTextWrap($Lt +=($divide*3),$YPos,($divide*2),$FontSize,locale_number_format($rows['amount'],2),'right');
       }

       $YPos -= ($line_height);
       if ($YPos < $Bottom_Margin){
           $PageNumber++;
           include('extfunc/masterrollproductsheader.inc');
       }
   }

        $Lt = $Left_Margin+1;
        $YPos -= ($line_height);
        $pdf->line($Left_Margin,$lastrow+$line_height,($Page_Width-$Right_Margin),$lastrow+$line_height,$style2);
        $pdf->addTextWrap($Lt +=$divide,$lastrow,$divide*3,$FontSize,_('Page Totals :'));

      if($payrolldatesarray[1] >=2){
           $pdf->addTextWrap($Lt +=($divide*2),$lastrow,$divide*2,$FontSize,locale_number_format($Tamount,2),'right');
           $pdf->addTextWrap($Lt +=($divide*2),$lastrow,$divide*2,$FontSize,locale_number_format($TemployersAmount,2),'right');
           $pdf->addTextWrap($Lt +=($divide*3),$lastrow,$divide*2,$FontSize,locale_number_format($TGross,2),'right');
       } else {
           $pdf->addTextWrap($Lt +=($divide*3),$lastrow,$divide*2,$FontSize,locale_number_format($TGross,2),'right');
       }

    $pdf->OutputD($_SESSION['DatabaseName'].'_Payroll_Products_'. $payrolldatesarray[0] .'_PrintedOn_'. date('Y-m-d').'.pdf');
    $pdf->__destruct();

   unset($_POST['productid']);

} else {

include('includes/header.inc');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';

$year = date('Y');
$Month = date('m');

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-file-pdf"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Generate Payroll By Products PDF Report</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
if(isset($_GET['costid'])){
    echo '<input type="hidden" name="staffID" value="'. $_GET['costid'] .'" />';
}

echo '<div class="sp-row">';
echo '<div class="sp-col-6">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Product</label>';
echo '<select name="productid" class="sp-select" required/>';
$SQL="Select * from prlproducts where deduction=0 or deduction is null ";
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

echo '<div class="sp-col-3">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Month</label>';
echo '<input type="number" name="month" class="sp-input" value="'.$Month.'" required min="1" max="12"/>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="sp-d-flex sp-justify-between sp-align-center">';
echo '<button type="submit" name="submit" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Generate PDF Report</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';
}

include('includes/footer.inc');
?>
