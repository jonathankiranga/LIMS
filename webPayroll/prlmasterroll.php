<?php
include('includes/session.inc');
  
$Title = "Master Roll and Payslip Printing";
include('ExtFunc/gensalary.inc');
include('ExtFunc/employeetypes.inc');
include('ExtFunc/salary.inc');

$self = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

$SQL = "SELECT MONTH(fromdate) AS todate,YEAR(todate) AS todate FROM prlmrollperiods WHERE open = 1";
$ResultIndex = DB_query($SQL, $db);
$defaultdate = DB_fetch_row($ResultIndex);

if(isset($_POST['year']) and isset($_POST['month'])){
    
    $UseDate = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,$_POST['month'],1,$_POST['year']));
    $DateEntry = FormatDateForSQL($UseDate);
    
    $SQL=sprintf("SELECT `pkey`,`type`,`fromdate`,`todate`,`open` FROM `prlmrollperiods`"
            . "  where  pkey in (select `payroll_id` from `prlpayroltransfile`) and fromdate='%s'", $DateEntry);

    $ResultIndex=DB_query($SQL,$db);
    $rowid = DB_fetch_row($ResultIndex);
    
    $_GET['prid']  = $rowid[0];
    $_GET['pdfid'] = $rowid[0];
    $_GET['Mid']   = $rowid[0];
    
    if(DB_num_rows($ResultIndex)==0){
        unset($_POST);
        prnMsg('There is no data for the selected dates', 'warn');
    }
}

if(isset($_POST['submitreport'])){

    if($_POST['StaffID'][0]=='ALL'){
        $_POST['StaffID']=array();
        
        $ResultIndex = GETALLEMPLOYEES();
        while($row = DB_fetch_array($ResultIndex)){
            $_POST['StaffID'][]=trim($row['pf_no']);
        }
    }
      
    $ReportSelected=$_POST['ReportSelected'];

    if($ReportSelected=='Eslips'){
        if(!isset($_POST['StaffID'])){
            prnMsg('You must highlight the staff you want to email to','warn');
        }
    }
  
    if($ReportSelected=='Eslips'){
        include('ExtFunc/Epayslipheader.inc');
        include('ExtFunc/epayslips.inc');
        exit();
    } elseif($ReportSelected=='prid'){
        include('ExtFunc/payslipheader.inc');
        include('ExtFunc/showpayslip.php');
        exit();
    } elseif($ReportSelected=='pdfid') {
        include('ExtFunc/prlpdfPayslipA.php');
        exit();
    } elseif($ReportSelected=='Mid') {
        include('includes/header.inc');

        $gid = mb_strlen($_GET['Mid'])>0?$_GET['Mid']:0;

        $productArray = array();
        $SQL="SELECT code,description,deduction from prlproducts "
            . " where code not in (select `deductcode` from `prlstaffloans`) "
            . " and code in (select code from prlpaydetailstransfile where `payroll_id` >=".($gid).")"
            . " order by deduction desc ";
    
        $arr=DB_query($SQL,$db);
        while($mypructs = DB_fetch_array($arr)){
            $key = rtrim($mypructs['code']);
            $productArray[$key] = $mypructs['description'];
        }

        $allowanceArray = array();
        $SQL="SELECT code,description from prlproducts where deduction=1 "
            . " and code not in (select `deductcode` from `prlstaffloans`) "
            . " and code in (select code from prlpaydetailstransfile where `payroll_id` >=".($gid).")";
        $arr=DB_query($SQL,$db);
        while($mypructs = DB_fetch_array($arr)){
            $key = rtrim($mypructs['code']);
            $allowanceArray[$key] = $mypructs['description'];
        }

        $deductionsArray = array();
        $SQL="SELECT code,description from prlproducts where deduction=0"
            . " and code not in (select `deductcode` from `prlstaffloans`) "
            . " and code in (select code from prlpaydetailstransfile where `payroll_id` >=".($gid).")";
        $arr=DB_query($SQL,$db);
        while($mypructs = DB_fetch_array($arr)){
            $key = rtrim($mypructs['code']);
            $deductionsArray[$key] = $mypructs['description'];
        }

        $staffArray = array();
        $SQL="SELECT pf_no,
             CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names
             FROM prlemployeemaster
             WHERE (dateterminated > '$DateEntry')
                OR (dateterminated IS NULL)
                OR (dateterminated = '0000-00-00 00:00:00')";
        $arr=DB_query($SQL,$db);
        while($mypructs =DB_fetch_array($arr)){
            $key = rtrim($mypructs['pf_no']);
            $staffArray[$key] = $mypructs['names'];
        }

        echo '<div><table id="testTable" class="table table-bordered table-responsive"><thead><tr><th>PayRoll<br/>ID</th><th>Names</th><th>Basic<br/>Pay</th><th>Overtime</th>';

        $line='';
        foreach ($productArray as $key => $value) {
            $line .= '<th>'.$value.'</th>';
        }

        echo $line;
        echo '<th>Absent</th><th>Total Loan</th><th>Net Pay</th></tr></thead>';
  
        $totals = [];
        $rows = getmasterroll();
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['pfno']) . '</td>';
            echo '<td>' . htmlspecialchars($row['names']) . '</td>';
            echo '<td>' . htmlspecialchars($row['basicpay']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Overtime']) . '</td>';

            foreach ($productArray as $key => $value) {
                $columnName = (isset($allowanceArray[$key]) ? 'A_' : 'D_') . $key;
                echo '<td>' . htmlspecialchars(isset($row[$columnName]) ? number_format($row[$columnName],2) : 0) . '</td>';

                if (!isset($totals[$columnName])) {
                    $totals[$columnName] = 0;
                }
                $totals[$columnName] += (isset($row[$columnName]) ? $row[$columnName] : 0);
            }

            echo '<td>' . htmlspecialchars($row['Manhrlost']) . '</td>';
            echo '<td>' . htmlspecialchars(number_format($row['Loans'],2)) . '</td>';
            echo '<td>' . htmlspecialchars(number_format($row['netpay'],2)) . '</td>';
            echo '</tr>';

            if (!isset($totals['basicpay'])) { $totals['basicpay'] = 0; }
            $totals['basicpay'] += $row['basicpay'];

            if (!isset($totals['Overtime'])) { $totals['Overtime'] = 0; }
            $totals['Overtime'] += $row['Overtime'];

            if (!isset($totals['Manhrlost'])) { $totals['Manhrlost'] = 0; }
            $totals['Manhrlost'] += $row['Manhrlost'];

            if (!isset($totals['Loans'])) { $totals['Loans'] = 0; }
            $totals['Loans'] += $row['Loans'];

            if (!isset($totals['netpay'])) { $totals['netpay'] = 0; }
            $totals['netpay'] += $row['netpay'];
        }

        echo '</tbody><tfoot><tr><td>Totals</td><td></td>';
        echo '<td>' . htmlspecialchars($totals['basicpay']) . '</td>';
        echo '<td>' . htmlspecialchars($totals['Overtime']) . '</td>';

        foreach ($productArray as $key => $value) {
            $columnName = (isset($allowanceArray[$key]) ? 'A_' : 'D_') . $key;
            echo '<td>' . htmlspecialchars(isset($totals[$columnName]) ? number_format($totals[$columnName],2) : 0) . '</td>';
        }

        echo '<td>' . htmlspecialchars($totals['Manhrlost']) . '</td>';
        echo '<td>' . htmlspecialchars(number_format($totals['Loans'],2)) . '</td>';
        echo '<td>' . htmlspecialchars(number_format($totals['netpay'],2)) . '</td>';
        echo '</tr></tfoot>';
        echo '</table>';
        echo '<div><input type="button" onclick="tableToExcel(\'testTable\', \'Payroll\')" value="Export to Excel"></div>';
    }
} else {
     
    include('includes/header.inc');
    echo '<link rel="stylesheet" href="css/smartpayroll.css">';

    echo '<div class="sp-page">';
    echo '<div class="sp-header">';
    echo '<div class="sp-header-icon"><i class="fas fa-users"></i></div>';
    echo '<div class="sp-header-title">';
    echo '<h1>' . $Title . '</h1>';
    echo '<p>Master Roll and Payslip Printing</p>';
    echo '</div>';
    echo '</div>';

    echo '<div class="sp-content">';
    
    if(isset($_GET['costid'])){

        echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
        echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
        echo '<input type="hidden" name="StaffID" value="'. $_GET['costid'] .'" />';
        
        echo '<div class="sp-row">';
        echo '<div class="sp-col-3">';
        echo '<div class="sp-form-group">';
        echo '<label class="sp-label">Select Year</label>';
        echo '<input type="number" name="year" class="sp-input" value="'. $defaultdate[1] .'" required min="2000" max="2049"/>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="sp-col-3">';
        echo '<div class="sp-form-group">';
        echo '<label class="sp-label">Select Month</label>';
        echo '<input type="number" name="month" class="sp-input" value="'. $defaultdate[0] .'" required min="1" max="12"/>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="sp-d-flex sp-align-center">';
        echo '<button type="submit" name="prid" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Print Payslip</button>';
        echo '</div>';
        echo '</form>';

    } else {
        
        echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
        echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
        
        echo '<div class="sp-row">';
        echo '<div class="sp-col-4">';
        echo '<div class="sp-form-group">';
        echo '<label class="sp-label">Select Staff</label>';
        echo '<select name="StaffID[]" class="sp-select" multiple size="8">';
        echo '<option value="ALL" selected="selected">ALL</option>';
        
        $ResultIndex = GETALLEMPLOYEES();
        
        while($row = DB_fetch_array($ResultIndex)){
            if($row['Active']==0){
                echo '<option value="'.$row['pf_no'].'">'.trim($row['fname']).' '.trim($row['mname']).' '.trim($row['lname']).'</option>';
            }
        }
        
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="sp-col-8">';
        echo '<div class="sp-form-group">';
        echo '<label class="sp-label">Select Period</label>';
        echo '<div class="sp-row">';
        echo '<div class="sp-col-6">';
        echo '<input type="number" name="month" class="sp-input" placeholder="Month" value="'. $defaultdate[0] .'" required min="1" max="12"/>';
        echo '</div>';
        echo '<div class="sp-col-6">';
        echo '<input type="number" name="year" class="sp-input" placeholder="Year" value="'. $defaultdate[1] .'" required min="2000" max="2049"/>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="sp-form-group">';
        echo '<label class="sp-label">Select Report</label>';
        echo '<select name="ReportSelected" class="sp-select">';
        echo '<option value="prid">Print Payslip</option>';
        echo '<option value="Mid">Show Master Roll</option>';
        echo '<option value="pdfid">Print Master Roll To PDF</option>';
        echo '<option value="Eslips">Email All Payslips</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<button type="submit" name="submitreport" class="sp-btn sp-btn-primary"><i class="fas fa-print"></i> Print Selection</button>';
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    
    echo '</div>';
    echo '</div>';
    
    include('includes/footer.inc');
}
?>
