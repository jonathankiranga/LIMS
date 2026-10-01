<?php
include('includes/session.inc');
include('ExtFunc/gensalary.inc');
include('ExtFunc/Payrollfunctions.php');

$Title = _('Payroll Online Banking remittance');
$BankArray = populatebankarray();

if(isset($_GET['Payrollid'])){
    $payrollId = (int)$_GET['Payrollid'];
    
    $xls_filename = 'SmartERP_'.date('Y-m-d').'.csv';
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$xls_filename");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $sep = ",";
    $endline = "\r\n";
    
    echo "Payroll NO" . $sep;
    echo "Names" . $sep;
    echo "Bank Code" . $sep;
    echo "Bank Branch Code" . $sep;
    echo "Bank Ac No" . $sep;
    echo "Net payable" . $sep;
    echo $endline;

    $netPayData = GetNetPayByPayrollId($payrollId, $db);
    
    foreach ($netPayData as $rows) {
        $netpay = $rows['allowances'] - $rows['deductions'];
        $bankrow = isset($BankArray[$rows['Bankcode']]) ? $BankArray[$rows['Bankcode']] : array();
        
        if ($netpay > 0) {
            $pfno = trim($rows['pfno']) != '' ? trim($rows['pfno']) : ' ';
            $names = trim($rows['names']) != '' ? trim($rows['names']) : ' ';
            $bankcode = isset($bankrow['parentcode']) ? $bankrow['parentcode'] : ' ';
            $branchcode = $rows['Bankcode'];
            $bankacno = trim($rows['Bankaccount']) != '' ? trim($rows['Bankaccount']) : ' ';
            
            echo $pfno . $sep;
            echo $names . $sep;
            echo $bankcode . $sep;
            echo $branchcode . $sep;
            echo $bankacno . $sep;
            echo number_format($netpay, 2);
            echo $endline;
        }
    }

} else {

include('includes/header.inc');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-university"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Generate bank transfer file for online banking</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';

$Msg = _('Select the payroll period to export NETPAY to a comma delimited file for online banking');
echo '<div class="sp-alert sp-alert-info"><i class="fas fa-info-circle"></i> ' . $Msg . '</div>';

$SQL = "SELECT type, fromdate, todate, open, pkey 
        FROM prlmrollperiods 
        WHERE open = 1 OR pkey NOT IN (SELECT payroll_id FROM prlpayroltransfile) 
        ORDER BY pkey DESC";
$ResultIndex = DB_query($SQL, $db);

if (DB_num_rows($ResultIndex) == 0) {
    echo '<div class="sp-alert sp-alert-warning"><i class="fas fa-exclamation-triangle"></i> ' . _('No payroll periods available') . '</div>';
} else {
    echo '<table class="sp-table"><thead><tr>';
    echo '<th>Start Date</th>';
    echo '<th>End Date</th>';
    echo '<th>Status</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';
    
    while ($row = DB_fetch_array($ResultIndex)) {
        echo '<tr>';
        echo '<td>' . ConvertSQLDate($row['fromdate']) . '</td>';
        echo '<td>' . ConvertSQLDate($row['todate']) . '</td>';
        echo '<td>';
        if ($row['open'] == 1) {
            echo '<span class="sp-badge sp-badge-success">' . _('Open') . '</span>';
        } else {
            echo '<span class="sp-badge sp-badge-secondary">' . _('Closed') . '</span>';
        }
        echo '</td>';
        echo '<td>';
        echo '<a href="?Payrollid=' . $row['pkey'] . '" class="sp-btn sp-btn-primary sp-btn-sm">';
        echo '<i class="fas fa-download"></i> ' . _('Generate Bank File');
        echo '</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

echo '</div>';
echo '</div>';

include('includes/footer.inc');

}

function GetNetPayByPayrollId($payrollId, $db) {
    $data = array();
    
    $sql = "SELECT 
                prlpayroltransfile.pfno,
                CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
                prlemployeemaster.bankcode,
                prlemployeemaster.bankacno AS Bankaccount,
                COALESCE(prlpayroltransfile.basicpay, 0) + COALESCE(prlpayroltransfile.allowances, 0) AS allowances,
                COALESCE(prlpayroltransfile.lateness_absent, 0) AS deductions
            FROM prlpayroltransfile
            JOIN prlemployeemaster ON prlpayroltransfile.pfno = prlemployeemaster.pf_no
            WHERE prlpayroltransfile.payroll_id = " . (int)$payrollId;
    
    $result = DB_query($sql, $db);
    while ($row = DB_fetch_array($result)) {
        $data[] = $row;
    }
    
    return $data;
}


?>
