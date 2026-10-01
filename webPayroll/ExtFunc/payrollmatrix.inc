<?php

function Refreshpayroll() {
    global $db;
    
    $sql = "SELECT pkey FROM prlmrollperiods WHERE `open` = 1 ORDER BY pkey DESC";
    $result = DB_query($sql, $db);
    $row = DB_fetch_row($result);
    $gid = (int)$row[0];
    
    $employees_sql = "SELECT pf_no FROM prlemployeemaster 
                      WHERE (dateterminated IS NULL OR (dateterminated < dateemployed) OR (dateterminated = '0000-00-00 00:00:00'))";
    $emp_result = DB_query($employees_sql, $db);
    
    while ($emp = DB_fetch_array($emp_result)) {
        $pfno = $emp['pf_no'];
        
        $matrix_sql = "SELECT prodid, amount, nonrecuring FROM prlmatrix WHERE pfno = '" . db_escape($db, $pfno) . "'";
        $matrix_result = DB_query($matrix_sql, $db);
        
        while ($matrix = DB_fetch_array($matrix_result)) {
            if ($matrix['nonrecuring'] == 1) {
                $check_sql = "SELECT pkey FROM prlpaydetailstransfile 
                              WHERE pfno = '" . db_escape($db, $pfno) . "' 
                              AND code = '" . db_escape($db, $matrix['prodid']) . "'
                              AND payroll_id < " . ($gid - 1);
                $check_result = DB_query($check_sql, $db);
                if (DB_num_rows($check_result) > 0) {
                    continue;
                }
            }
        }
    }
}

function GetMatrixData($payrollId, $db) {
    $data = array();
    
    $sql = "SELECT 
        prlemployeemaster.pf_no AS pfno,
        CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
        COALESCE(prlpayroltransfile.basicpay, 0) AS basicpay,
        COALESCE(prlpayroltransfile.overtime, 0) AS overtime,
        COALESCE(prlpayroltransfile.lateness_absent, 0) AS lateness_absent
    FROM prlemployeemaster
    LEFT JOIN prlpayroltransfile ON prlemployeemaster.pf_no = prlpayroltransfile.pfno 
        AND prlpayroltransfile.payroll_id = " . (int)$payrollId . "
    WHERE (prlemployeemaster.dateterminated IS NULL 
        OR prlemployeemaster.dateterminated = '0000-00-00 00:00:00' 
        OR prlemployeemaster.dateterminated > NOW())";
    
    $result = DB_query($sql, $db);
    while ($row = DB_fetch_array($result)) {
        $pfno = $row['pfno'];
        
        $loan_sql = "SELECT 
            COALESCE(SUM(prlloantrans.amount), 0) + COALESCE(SUM(prlloantrans.interest), 0) AS total_loan
            FROM prlloantrans 
            WHERE prlloantrans.pfno = '" . db_escape($db, $pfno) . "' 
            AND prlloantrans.payroll_id = " . (int)$payrollId;
        $loan_result = DB_query($loan_sql, $db);
        $loan_row = DB_fetch_array($loan_result);
        
        $row['loans'] = $loan_row['total_loan'];
        
        $detail_sql = "SELECT code, amount FROM prlpaydetailstransfile 
                       WHERE pfno = '" . db_escape($db, $pfno) . "' 
                       AND payroll_id = " . (int)$payrollId;
        $detail_result = DB_query($detail_sql, $db);
        while ($detail = DB_fetch_array($detail_result)) {
            $prefix = ($detail['deduction'] == 1) ? 'A_' : 'D_';
            $row[$prefix . trim($detail['code'])] = $detail['amount'];
        }
        
        $netpay = $row['basicpay'] + $row['overtime'] - $row['lateness_absent'] + $row['loans'];
        $netpay_sql = "SELECT SUM(COALESCE(amount, 0)) as total FROM prlpaydetailstransfile 
                       WHERE pfno = '" . db_escape($db, $pfno) . "' 
                       AND payroll_id = " . (int)$payrollId;
        $netpay_result = DB_query($netpay_sql, $db);
        $netpay_row = DB_fetch_array($netpay_result);
        $row['netpay'] = $netpay_row['total'];
        
        $data[] = $row;
    }
    
    return $data;
}

function GetProductsForMatrix($payrollId, $db) {
    $products = array();
    
    $sql = "SELECT code, description, deduction 
            FROM prlproducts 
            WHERE code NOT IN (SELECT deductcode FROM prlstaffloans) 
            AND code IN (SELECT code FROM prlpaydetailstransfile WHERE payroll_id = " . (int)$payrollId . ")
            ORDER BY deduction DESC";
    $result = DB_query($sql, $db);
    while ($row = DB_fetch_array($result)) {
        $products[] = $row;
    }
    return $products;
}

function PrintMasterRollTable() {
    global $db;
    
    $sql = "SELECT code, description, deduction 
            FROM prlproducts 
            WHERE code NOT IN (SELECT deductcode FROM prlstaffloans) 
            AND code != '999999'
            ORDER BY deduction DESC, code ASC";
    $result = DB_query($sql, $db);
    
    $products = array();
    while ($row = DB_fetch_array($result)) {
        $code = trim($row['code']);
        $deduction = (int)$row['deduction'];
        $prefix = $deduction == 1 ? 'A_' : 'D_';
        $products[$prefix . $code] = array(
            'code' => $code,
            'name' => $row['description'],
            'type' => $deduction == 1 ? 'allowance' : 'deduction',
            'prefix' => $prefix
        );
    }
    
    if (empty($products)) {
        echo '<div class="sp-alert sp-alert-warning">No products found</div>';
        return;
    }
    
    $sql = "SELECT pf_no, basicpay, fname, mname, lname
            FROM prlemployeemaster 
            WHERE (dateterminated < dateemployed)
               OR (dateterminated IS NULL)
               OR (dateterminated = '0000-00-00 00:00:00')
            ORDER BY pf_no";
    $empResult = DB_query($sql, $db);
    
    $masterroll = array();
    while ($empRow = DB_fetch_array($empResult)) {
        $pfno = trim($empRow['pf_no']);
        $basicpay = floatval($empRow['basicpay']);
        
        $row = array(
            'pfno' => $pfno,
            'names' => trim($empRow['fname'] . ' ' . $empRow['mname'] . ' ' . $empRow['lname']),
            'basicpay' => $basicpay
        );
        
        foreach ($products as $key => $pdata) {
            $row[$key] = 0;
        }
        
        $matrixSql = "SELECT prodid, amount FROM prlmatrix WHERE pfno = '" . db_escape($db, $pfno) . "'";
        $matrixResult = DB_query($matrixSql, $db);
        
        while ($matrixRow = DB_fetch_array($matrixResult)) {
            $prodid = trim($matrixRow['prodid']);
            $keyFound = null;
            foreach ($products as $pkey => $pdata) {
                if ($pdata['code'] == $prodid) {
                    $keyFound = $pkey;
                    break;
                }
            }
            if ($keyFound) {
                $row[$keyFound] = floatval($matrixRow['amount']);
            }
        }
        
        $loanSql = "SELECT COALESCE(SUM(amount + interest), 0) AS total_loan
                    FROM prlloantrans 
                    WHERE pfno = '" . db_escape($db, $pfno) . "'";
        $loanResult = DB_query($loanSql, $db);
        $loanRow = DB_fetch_array($loanResult);
        $row['Loans'] = floatval($loanRow['total_loan']);
        
        $allowances = 0;
        $deductions = 0;
        foreach ($products as $key => $pdata) {
            if ($pdata['type'] == 'allowance') {
                $allowances += $row[$key];
            } else {
                $deductions += $row[$key];
            }
        }
        
        $row['netpay'] = $basicpay + $allowances - $deductions - $row['Loans'];
        
        $masterroll[] = $row;
    }
    
    if (empty($masterroll)) {
        echo '<div class="sp-alert sp-alert-warning">No employees found</div>';
        return;
    }
    
    echo '<table id="Export" class="sp-table">';
    echo '<thead><tr>';
    echo '<th>PayRoll ID</th>';
    echo '<th>Names</th>';
    echo '<th>Basic Pay</th>';
    
    foreach ($products as $pkey => $pdata) {
        echo '<th>' . htmlspecialchars($pdata['name']) . '</th>';
    }
    
    echo '<th>Loans + Interest + Sacco</th>';
    echo '<th>Net Payable (Expected)</th>';
    echo '</tr></thead>';
    
    echo '<tbody>';
    
    $totals = array('basicpay' => 0, 'Loans' => 0, 'netpay' => 0);
    foreach ($products as $pkey => $pdata) {
        $totals[$pkey] = 0;
    }
    
    foreach ($masterroll as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['pfno']) . '</td>';
        echo '<td>' . htmlspecialchars($row['names']) . '</td>';
        echo '<td class="number">' . number_format($row['basicpay'], 1) . '</td>';
        
        $totals['basicpay'] += $row['basicpay'];
        
        foreach ($products as $pkey => $pdata) {
            $amount = isset($row[$pkey]) ? $row[$pkey] : 0;
            echo '<td class="number">' . number_format($amount, 1) . '</td>';
            $totals[$pkey] += $amount;
        }
        
        echo '<td class="number">' . number_format($row['Loans'], 1) . '</td>';
        $totals['Loans'] += $row['Loans'];
        
        echo '<td class="number"><strong>' . number_format($row['netpay'], 1) . '</strong></td>';
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '<tfoot><tr>';
    echo '<th colspan="2">Totals:</th>';
    echo '<th class="number">' . number_format($totals['basicpay'], 1) . '</th>';
    
    foreach ($products as $pkey => $pdata) {
        echo '<th class="number">' . number_format($totals[$pkey], 1) . '</th>';
    }
    
    echo '<th class="number">' . number_format($totals['Loans'], 1) . '</th>';
    echo '<th class="number">' . number_format($totals['netpay'], 1) . '</th>';
    echo '</tr></tfoot>';
    
    echo '</table>';
}

class emplyeematrix {
    function ShowbyProducts() {
        PrintMasterRollTable();
    }
}

function db_escape($db, $value) {
    if (is_object($db)) {
        return $db->real_escape_string($value);
    }
    return str_replace("'", "''", $value);
}

?>
