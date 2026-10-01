<?php
function GeneratePayslips($payroll_id = 0) {
    global $db;
    
    if($payroll_id == 0) {
        $result = DB_query("SELECT pkey FROM prlmrollperiods ORDER BY pkey DESC LIMIT 1", $db);
        $row = DB_fetch_array($result);
        $payroll_id = $row['pkey'];
    }
    
    $payslips = [];
    $auto = 0;
    
    $periodResult = DB_query("SELECT pkey, MONTHNAME(todate) as monthof, YEAR(todate) as yearof, todate FROM prlmrollperiods WHERE pkey = " . $payroll_id, $db);
    $period = DB_fetch_array($periodResult);
    $monthof = $period['monthof'];
    $yearof = $period['yearof'];
    $todate = $period['todate'];
    
    $transSQL = "SELECT pt.pfno, 
                        CONCAT(RTRIM(pm.status), ' ', RTRIM(pm.fname), ' ', RTRIM(pm.mname), ' ', RTRIM(pm.lname)) AS names,
                        COALESCE(pt.basicpay, 0) AS basicpay, 
                        pt.overtime, 
                        pt.lateness_absent,
                        pt.allowances,
                        pt.pension,
                        pt.non_cash_benefits,
                        pm.idno, 
                        pm.pin_no, 
                        bk.bankbranch, 
                        pm.bankacno, 
                        prlpositions.name AS designation, 
                        lc.name AS branch,
                        pm.email,
                        pm.insurancerelief, 
                        pm.mortagerelief,
                        pt.pension AS pension_amount,
                        pt.nhif AS nhif_amount,
                        pt.paye AS paye_amount,
                        pt.other_deductions
                 FROM prlemployeemaster pm
                 LEFT JOIN employeebnkbranch bk ON pm.bankcode = bk.parentcode AND pm.bankcode2 = bk.code
                 LEFT JOIN prlpositions ON pm.position = prlpositions.code
                 LEFT JOIN prlestablishment lc ON pm.branch = lc.code
                 INNER JOIN prlpayroltransfile pt ON pt.pfno = pm.pf_no AND pt.payroll_id = " . $payroll_id . "
                 WHERE pt.payroll_id = " . $payroll_id . "
                 ORDER BY pm.pf_no";
    
    $transResult = DB_query($transSQL, $db);
    
    while ($row = DB_fetch_array($transResult)) {
        $basicpay = $row['basicpay'];
        $overtime = $row['overtime'];
        $allowances = $row['allowances'];
        $gross = $basicpay + $overtime + $allowances;
        
        $paye = $row['paye_amount'];
        $nhif = $row['nhif_amount'];
        $pension = $row['pension_amount'];
        $other_ded = $row['other_deductions'];
        $lateness = $row['lateness_absent'];
        
        $total_deductions = $paye + $nhif + $pension + $other_ded + $lateness;
        $netpay = $gross - $total_deductions;
        
        $payslips[] = [
            'pfno' => $row['pfno'],
            'names' => $row['names'],
            'idno' => $row['idno'],
            'pin_no' => $row['pin_no'],
            'designation' => $row['designation'],
            'branch' => $row['branch'],
            'bankbranch' => $row['bankbranch'],
            'bankacno' => $row['bankacno'],
            'email' => $row['email'],
            'monthof' => $monthof,
            'yearof' => $yearof,
            'basicpay' => $basicpay,
            'overtime' => $overtime,
            'allowances' => $allowances,
            'gross' => $gross,
            'paye' => $paye,
            'nhif' => $nhif,
            'pension' => $pension,
            'other_deductions' => $other_ded,
            'lateness' => $lateness,
            'total_deductions' => $total_deductions,
            'netpay' => $netpay
        ];
    }
    
    return $payslips;
}

if(isset($_GET['payroll_id']) && is_numeric($_GET['payroll_id'])) {
    include('includes/session.inc');
    include('Databaseconnector.php');
    
    $payslips = GeneratePayslips(intval($_GET['payroll_id']));
    header('Content-Type: application/json');
    echo json_encode($payslips);
}
?>
