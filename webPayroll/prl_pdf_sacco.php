<?php
$Title="SACCO Remittance";
$ForceConfigReload=true;
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
    }
}

if(isset($_POST['payrollid'])){
    $payrollId = $_POST['payrollid'];
    $SQLdate = sprintf("SELECT [pkey],[type],[fromdate],[todate],[open] FROM [prlmrollperiods] where pkey='%s'", $_POST['payrollid']);
    $ResultIndex = DB_query($SQLdate,$db);
    $ansrow = DB_fetch_row($ResultIndex);
    $_POST['datefrom'] = convertSQLDate($ansrow[2]);
    $_POST['dateto'] = convertSQLDate($ansrow[3]);
}

if(isset($_POST['productid'])){
    $SQL="SELECT description,employerfactor FROM [prlproducts] where   [code]='".$_POST['productid']."'";
    $ResultIndex = DB_query($SQL,$db);
    $payrolldatesarray = DB_fetch_row($ResultIndex);
    $productCode = $_POST['productid'];
}

if(isset($_POST['productid']) && !empty($payrollId)){
    
    include('includes/PDFStarter.php');
    $PageNumber = 1;
    $line_height = 12;
    $pageheight = $Page_Height- $Bottom_Margin;
    include('Extfunc/saccoreportsheader.inc');
    $divide = (($Page_Width-$Right_Margin-$Left_Margin)/10);
    $lastrow = $Bottom_Margin;
    $Tamount = 0;
    $employersAmount = 0;
    $TemployersAmount = 0;
    $TlineTotal = 0;
    $TGross = 0;

    $ResultIndex = PrintSacco($payrollId, $productCode, $db);
    while($rows = DB_fetch_array($ResultIndex)){
        $Lt = $Left_Margin+1;
        $Tamount += $rows['amount'];
        $TemployersAmount += $rows['gross'];

        $pdf->addTextWrap($Lt,$YPos,$divide,$FontSize, $rows['pfno'] );
        $pdf->addTextWrap($Lt +=$divide, $YPos,($divide*3),$FontSize, $rows['names'],'left' );
        $pdf->addTextWrap($Lt +=($divide*2),$YPos,($divide*2),$FontSize,locale_number_format($rows['amount'],2) ,'right');
        $pdf->addTextWrap($Lt +=($divide*2),$YPos,($divide*2),$FontSize,locale_number_format($rows['gross'],2),'right');
        
       $YPos -= ($line_height);
       if ($YPos - ($line_height) < $Bottom_Margin){
           $PageNumber++;
           include('extfunc/saccoreportsheader.inc');
       }
   }

        $Lt = $Left_Margin+1;
        $YPos -= ($line_height);
        $pdf->line($Left_Margin,$lastrow+$line_height,($Page_Width-$Right_Margin),$lastrow+$line_height,$style2);
        $pdf->addTextWrap($Lt +=$divide,$lastrow,$divide*3,$FontSize,_('Page Totals :'));
        $pdf->addTextWrap($Lt +=($divide*2),$lastrow,$divide*2,$FontSize,locale_number_format($Tamount,2),'right');
        $pdf->addTextWrap($Lt +=($divide*2),$lastrow,$divide*2,$FontSize,locale_number_format($TemployersAmount,2),'right');

        $pdf->OutputD($_SESSION['DatabaseName'].'_Sacco_PrintedOn_'. date('Y-m-d').'.pdf');
        $pdf->__destruct();

   unset($_POST['productid']);

} else {

include('includes/header.inc');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-file-pdf"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Generate SACCO Remittance PDF Report</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<div class="sp-row">';
echo '<div class="sp-col-6">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Savings</label>';
echo '<select name="productid" class="sp-select" required/>';

$SQL="Select * from prlproducts where code in "
        . "(select [deductcode] from prlstaffloans where [saving]=1) ";

$ResultIndex=DB_query($SQL, $db);
while($row = DB_fetch_array($ResultIndex)){
    echo '<option value="'.$row['code'].'">'.$row['description'].'</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="sp-col-3">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Month</label>';
echo '<input type="number" name="month" class="sp-input" value="'. date('m') .'" required min="1" max="12"/>';
echo '</div>';
echo '</div>';

echo '<div class="sp-col-3">';
echo '<div class="sp-form-group">';
echo '<label class="sp-label">Select Year</label>';
echo '<input type="number" name="year" class="sp-input" value="'. date('Y') .'" required min="2000" max="2049"/>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="sp-d-flex sp-justify-between sp-align-center">';
echo '<button type="submit" name="submit" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Print Report</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';

}

include('includes/footer.inc') ;
?>
