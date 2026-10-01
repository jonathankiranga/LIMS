<?php



function GetEmployees($active_only = true) {
    global $db;
    $sql = "SELECT pm.*, 
                   prlpositions.name AS position_name,
                   prldepartments.name AS department_name
            FROM prlemployeemaster pm
            LEFT JOIN prlpositions ON pm.position = prlpositions.code
            LEFT JOIN prldepartments ON pm.department = prldepartments.code";
    if($active_only) {
        $sql .= " WHERE pm.Inactive IS NULL OR pm.Inactive = 0";
    }
    $sql .= " ORDER BY pm.pf_no";
    $result = DB_query($sql, $db);
    $employees = [];
    while($row = DB_fetch_array($result)) {
        $employees[] = $row;
    }
    return $employees;
}

function GetEmployeeCount($active_only = true) {
    global $db;
    $sql = "SELECT COUNT(*) AS cnt FROM prlemployeemaster";
    if($active_only) {
        $sql .= " WHERE Inactive IS NULL OR Inactive = 0";
    }
    $result = DB_query($sql, $db);
    $row = DB_fetch_array($result);
    return $row['cnt'];
}

function SearchEmployees($keyword) {
    global $db;
    $keyword = DB_escape_string($keyword);
    $sql = "SELECT pm.*, prlpositions.name AS position_name
            FROM prlemployeemaster pm
            LEFT JOIN prlpositions ON pm.position = prlpositions.code
            WHERE pm.pf_no LIKE '%$keyword%'
               OR pm.fname LIKE '%$keyword%'
               OR pm.mname LIKE '%$keyword%'
               OR pm.lname LIKE '%$keyword%'
               OR pm.idno LIKE '%$keyword%'
            ORDER BY pm.pf_no";
    $result = DB_query($sql, $db);
    $employees = [];
    while($row = DB_fetch_array($result)) {
        $employees[] = $row;
    }
    return $employees;
}

function GetEmployeeFullName($pfno) {
    $emp = GetEmployee($pfno);
    if($emp) {
        return trim($emp['fname'] . ' ' . $emp['mname'] . ' ' . $emp['lname']);
    }
    return '';
}

function SaveEmployee($data) {
    global $db;
    $pfno = isset($data['pf_no']) ? $data['pf_no'] : '';
    $fname = isset($data['fname']) ? DB_escape_string($data['fname']) : '';
    $mname = isset($data['mname']) ? DB_escape_string($data['mname']) : '';
    $lname = isset($data['lname']) ? DB_escape_string($data['lname']) : '';
    $idno = isset($data['idno']) ? DB_escape_string($data['idno']) : '';
    $pin_no = isset($data['pin_no']) ? DB_escape_string($data['pin_no']) : '';
    $nssf_no = isset($data['nssf_no']) ? DB_escape_string($data['nssf_no']) : '';
    $nhif_no = isset($data['nhif_no']) ? DB_escape_string($data['nhif_no']) : '';
    $basicpay = isset($data['basicpay']) ? $data['basicpay'] : 0;
    $email = isset($data['email']) ? DB_escape_string($data['email']) : '';
    $telno = isset($data['telno']) ? DB_escape_string($data['telno']) : '';
    $department = isset($data['department']) ? intval($data['department']) : 'NULL';
    $position = isset($data['position']) ? intval($data['position']) : 'NULL';
    
    $sql = "INSERT INTO prlemployeemaster (pf_no, fname, mname, lname, idno, pin_no, nssf_no, nhif_no, basicpay, email, telno, department, position)
            VALUES ('" . DB_escape_string($pfno) . "', '$fname', '$mname', '$lname', '$idno', '$pin_no', '$nssf_no', '$nhif_no', $basicpay, '$email', '$telno', $department, $position)
            ON DUPLICATE KEY UPDATE 
                fname = '$fname', mname = '$mname', lname = '$lname', idno = '$idno', 
                pin_no = '$pin_no', nssf_no = '$nssf_no', nhif_no = '$nhif_no', 
                basicpay = $basicpay, email = '$email', telno = '$telno', 
                department = $department, position = $position";
    return DB_query($sql, $db);
}

function GetEmployeeMatrix($pfno, $payroll_id = 0) {
    global $db;
    $sql = "SELECT m.*, p.description, p.deduction, p.hastable
            FROM prlmatrix m
            INNER JOIN prlproducts p ON m.prodid = p.code
            WHERE m.pfno = '" . DB_escape_string($pfno) . "'";
    if($payroll_id > 0) {
        $sql .= " AND m.payroll_id = " . $payroll_id;
    }
    $result = DB_query($sql, $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function SetEmployeeMatrixItem($pfno, $prodid, $amount, $employeramount = null, $deduction = 1) {
    global $db;
    $employer_sql = $employeramount !== null ? $employeramount : 'NULL';
    $sql = "INSERT INTO prlmatrix (pfno, prodid, name, amount, employeramount, deduction, posted)
            VALUES ('" . DB_escape_string($pfno) . "', $prodid, 
                    (SELECT description FROM prlproducts WHERE code = $prodid),
                    $amount, $employer_sql, $deduction, NULL)
            ON DUPLICATE KEY UPDATE amount = $amount, employeramount = $employer_sql";
    return DB_query($sql, $db);
}

function DeleteEmployeeMatrixItem($pfno, $prodid) {
    global $db;
    $sql = "DELETE FROM prlmatrix WHERE pfno = '" . DB_escape_string($pfno) . "' AND prodid = $prodid";
    return DB_query($sql, $db);
}

function GetPayrollPeriods($open_only = false) {
    global $db;
    $sql = "SELECT * FROM prlmrollperiods";
    if($open_only) {
        $sql .= " WHERE `open` = 1";
    }
    $sql .= " ORDER BY pkey DESC";
    $result = DB_query($sql, $db);
    $periods = [];
    while($row = DB_fetch_array($result)) {
        $periods[] = $row;
    }
    return $periods;
}

function GetPayrollPeriod($pkey) {
    global $db;
    $sql = "SELECT * FROM prlmrollperiods WHERE pkey = " . intval($pkey);
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function GetCurrentPayrollPeriod() {
    global $db;
    $sql = "SELECT * FROM prlmrollperiods WHERE `open` = 1 ORDER BY pkey DESC LIMIT 1";
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function CreatePayrollPeriod($fromdate, $todate, $type = 1) {
    global $db;
    $sql = "INSERT INTO prlmrollperiods (type, fromdate, todate, `open`)
            VALUES ($type, '$fromdate', '$todate', 1)";
    return DB_query($sql, $db);
}

function ClosePayrollPeriod($pkey) {
    global $db;
    $sql = "UPDATE prlmrollperiods SET `open` = 0 WHERE pkey = " . intval($pkey);
    return DB_query($sql, $db);
}

function GetPayrollTransaction($pfno, $payroll_id) {
    global $db;
    $sql = "SELECT * FROM prlpayroltransfile 
            WHERE pfno = '" . DB_escape_string($pfno) . "' AND payroll_id = " . intval($payroll_id);
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function GetPayrollTransactions($payroll_id) {
    global $db;
    $sql = "SELECT pt.*, pm.fname, pm.mname, pm.lname, pm.pin_no, pm.nssf_no, pm.nhif_no
            FROM prlpayroltransfile pt
            INNER JOIN prlemployeemaster pm ON pt.pfno = pm.pf_no
            WHERE pt.payroll_id = " . intval($payroll_id) . "
            ORDER BY pt.pfno";
    $result = DB_query($sql, $db);
    $trans = [];
    while($row = DB_fetch_array($result)) {
        $trans[] = $row;
    }
    return $trans;
}

function SavePayrollTransaction($pfno, $payroll_id, $data) {
    global $db;
    $basicpay = isset($data['basicpay']) ? $data['basicpay'] : 0;
    $allowances = isset($data['allowances']) ? $data['allowances'] : 0;
    $deductions = isset($data['deductions']) ? $data['deductions'] : 0;
    $overtime = isset($data['overtime']) ? $data['overtime'] : 0;
    $lateness = isset($data['lateness_absent']) ? $data['lateness_absent'] : 0;
    $pension = isset($data['pension']) ? $data['pension'] : 0;
    
    $sql = "INSERT INTO prlpayroltransfile (pfno, payroll_id, basicpay, allowances, deductions, overtime, lateness_absent, pension, rowid)
            VALUES ('" . DB_escape_string($pfno) . "', " . intval($payroll_id) . ", $basicpay, $allowances, $deductions, $overtime, $lateness, $pension, 0)
            ON DUPLICATE KEY UPDATE 
                basicpay = $basicpay, 
                allowances = $allowances, 
                deductions = $deductions,
                overtime = $overtime,
                lateness_absent = $lateness,
                pension = $pension";
    return DB_query($sql, $db);
}

function DeletePayrollTransactions($payroll_id) {
    global $db;
    $sql = "DELETE FROM prlpayroltransfile WHERE payroll_id = " . intval($payroll_id);
    return DB_query($sql, $db);
}

function GetPayrollDetails($pfno, $payroll_id) {
    global $db;
    $sql = "SELECT pd.*, p.description, p.deduction
            FROM prlpaydetailstransfile pd
            INNER JOIN prlproducts p ON pd.code = p.code
            WHERE pd.pfno = '" . DB_escape_string($pfno) . "' AND pd.payroll_id = " . intval($payroll_id);
    $result = DB_query($sql, $db);
    $details = [];
    while($row = DB_fetch_array($result)) {
        $details[] = $row;
    }
    return $details;
}

function GetPayrollLoanTransactions($pfno, $payroll_id) {
    global $db;
    $sql = "SELECT p.description AS loan_type, lt.amount, lt.interest
            FROM prlloantrans lt
            INNER JOIN prlstaffloans sl ON lt.loanindex = sl.loanindex
            INNER JOIN prlproducts p ON sl.deductcode = p.code
            WHERE lt.pfno = '" . DB_escape_string($pfno) . "'
              AND lt.payroll_id = " . intval($payroll_id) . "
            ORDER BY lt.loanindex";
    $result = DB_query($sql, $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function SavePayrollDetail($pfno, $payroll_id, $code, $amount, $description, $employercontribution = null, $deduction = 1) {
    global $db;
    $emp_sql = $employercontribution !== null ? $employercontribution : 'NULL';
    $sql = "INSERT INTO prlpaydetailstransfile (payroll_id, pfno, code, description, amount, employercontribution, deduction)
            VALUES (" . intval($payroll_id) . ", '" . DB_escape_string($pfno) . "', $code, '" . DB_escape_string($description) . "', $amount, $emp_sql, $deduction)";
    return DB_query($sql, $db);
}

function DeletePayrollDetails($payroll_id) {
    global $db;
    $sql = "DELETE FROM prlpaydetailstransfile WHERE payroll_id = " . intval($payroll_id);
    return DB_query($sql, $db);
}

function sqlGetDepartments() {
    global $db;
    $result = DB_query("SELECT * FROM prldepartments ORDER BY code", $db);
    $depts = [];
    while($row = DB_fetch_array($result)) {
        $depts[] = $row;
    }
    return $depts;
}

function GetDepartment($code) {
    global $db;
    $result = DB_query("SELECT * FROM prldepartments WHERE code = " . intval($code), $db);
    return DB_fetch_array($result);
}

function SaveDepartment($code, $name, $hod = null) {
    global $db;
    $hod_sql = $hod !== null ? "'" . DB_escape_string($hod) . "'" : 'NULL';
    $sql = "INSERT INTO prldepartments (code, name, hod) VALUES (" . intval($code) . ", '" . DB_escape_string($name) . "', $hod_sql)
            ON DUPLICATE KEY UPDATE name = '" . DB_escape_string($name) . "', hod = $hod_sql";
    return DB_query($sql, $db);
}

function GetPositions() {
    global $db;
    $result = DB_query("SELECT p.*, d.name AS department_name 
                        FROM prlpositions p 
                        LEFT JOIN prldepartments d ON p.department = d.code 
                        ORDER BY p.code", $db);
    $positions = [];
    while($row = DB_fetch_array($result)) {
        $positions[] = $row;
    }
    return $positions;
}

function GetPosition($code) {
    global $db;
    $result = DB_query("SELECT * FROM prlpositions WHERE code = " . intval($code), $db);
    return DB_fetch_array($result);
}

function SavePosition($code, $name, $jobgroup = null, $department = null) {
    global $db;
    $jg_sql = $jobgroup !== null ? "'" . DB_escape_string($jobgroup) . "'" : 'NULL';
    $dept_sql = $department !== null ? intval($department) : 'NULL';
    $sql = "INSERT INTO prlpositions (code, name, jobgroup, department) VALUES (" . intval($code) . ", '" . DB_escape_string($name) . "', $jg_sql, $dept_sql)
            ON DUPLICATE KEY UPDATE name = '" . DB_escape_string($name) . "', jobgroup = $jg_sql, department = $dept_sql";
    return DB_query($sql, $db);
}

function GetProducts() {
    global $db;
    $result = DB_query("SELECT * FROM prlproducts ORDER BY code", $db);
    $products = [];
    while($row = DB_fetch_array($result)) {
        $products[] = $row;
    }
    return $products;
}

function GetProduct($code) {
    global $db;
    $result = DB_query("SELECT * FROM prlproducts WHERE code = " . intval($code), $db);
    return DB_fetch_array($result);
}

function GetAllowances() {
    global $db;
    $result = DB_query("SELECT * FROM prlproducts WHERE deduction = 1 ORDER BY code", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetDeductions() {
    global $db;
    $result = DB_query("SELECT * FROM prlproducts WHERE deduction = 0 ORDER BY code", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}



function GetBanks() {
    global $db;
    $result = DB_query("SELECT * FROM employeebanks ORDER BY code", $db);
    $banks = [];
    while($row = DB_fetch_array($result)) {
        $banks[] = $row;
    }
    return $banks;
}

function GetBankBranches($bankcode) {
    global $db;
    $result = DB_query("SELECT * FROM employeebnkbranch WHERE parentcode = '" . DB_escape_string($bankcode) . "' ORDER BY code", $db);
    $branches = [];
    while($row = DB_fetch_array($result)) {
        $branches[] = $row;
    }
    return $branches;
}

function GetStaffLoans($pfno = null, $open_only = true) {
    global $db;
    $sql = "SELECT sl.*, pm.fname, pm.mname, pm.lname, p.description AS loan_type
            FROM prlstaffloans sl
            INNER JOIN prlemployeemaster pm ON sl.pfno = pm.pf_no
            INNER JOIN prlproducts p ON sl.deductcode = p.code";
    $conditions = [];
    if($pfno !== null) {
        $conditions[] = "sl.pfno = '" . DB_escape_string($pfno) . "'";
    }
    if($open_only) {
        $conditions[] = "(sl.closed IS NULL OR sl.closed = 0)";
    }
    if(count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY sl.loanindex DESC";
    $result = DB_query($sql, $db);
    $loans = [];
    while($row = DB_fetch_array($result)) {
        $loans[] = $row;
    }
    return $loans;
}

function sqlGetLoanBalance($loanindex) {
    global $db;
    $sql = "SELECT sl.*, 
                   COALESCE(SUM(pt.amount), 0) AS total_paid
            FROM prlstaffloans sl
            LEFT JOIN prlloantrans pt ON sl.loanindex = pt.loanindex
            WHERE sl.loanindex = " . intval($loanindex) . "
            GROUP BY sl.loanindex";
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function CreateStaffLoan($pfno, $deductcode, $principal, $instalment, $interest = null, $startdate) {
    global $db;
    $int_sql = $interest !== null ? $interest : 'NULL';
    $sql = "INSERT INTO prlstaffloans (pfno, deductcode, principal, instalment, interest, startdate, closed, saving, interesttype, openbalance)
            VALUES ('" . DB_escape_string($pfno) . "', " . intval($deductcode) . ", $principal, $instalment, $int_sql, '$startdate', 0, 0, 0, $principal)";
    return DB_query($sql, $db);
}

function CloseLoan($loanindex) {
    global $db;
    $sql = "UPDATE prlstaffloans SET closed = 1 WHERE loanindex = " . intval($loanindex);
    return DB_query($sql, $db);
}

function RecordLoanPayment($loanindex, $amount, $payroll_id, $pfno = null) {
    global $db;
    $pfno_sql = $pfno !== null ? "'" . DB_escape_string($pfno) . "'" : "(SELECT pfno FROM prlstaffloans WHERE loanindex = $loanindex)";
    $sql = "INSERT INTO prlloantrans (loanindex, amount, payroll_id, pfno) VALUES (" . intval($loanindex) . ", $amount, " . intval($payroll_id) . ", $pfno_sql)";
    return DB_query($sql, $db);
}



function GetTimesheet($pfno, $period = null, $date = null) {
    global $db;
    $sql = "SELECT * FROM prltimesheet WHERE pfno = '" . DB_escape_string($pfno) . "'";
    if($period !== null) {
        $sql .= " AND period = " . intval($period);
    }
    if($date !== null) {
        $sql .= " AND DATE(date) = '$date'";
    }
    $sql .= " ORDER BY date DESC";
    $result = DB_query($sql, $db);
    $entries = [];
    while($row = DB_fetch_array($result)) {
        $entries[] = $row;
    }
    return $entries;
}

function ClockIn($pfno, $period, $date) {
    global $db;
    $sql = "INSERT INTO prltimesheet (pfno, timein, ShouldLogIn, date, period, DidtheylogIn)
            VALUES ('" . DB_escape_string($pfno) . "', NOW(), 1, '$date', " . intval($period) . ", 1)
            ON DUPLICATE KEY UPDATE timein = NOW(), DidtheylogIn = 1";
    return DB_query($sql, $db);
}

function ClockOut($pfno, $date) {
    global $db;
    $sql = "UPDATE prltimesheet SET timeout = NOW() 
            WHERE pfno = '" . DB_escape_string($pfno) . "' AND DATE(date) = '$date' AND timeout IS NULL";
    return DB_query($sql, $db);
}



function GetLeaveApplications($pfno = null, $status = null) {
    global $db;
    $sql = "SELECT slp.*, pm.fname, pm.mname, pm.lname, lt.type AS leave_type_name
            FROM prlstaffleaveplanner slp
            INNER JOIN prlemployeemaster pm ON slp.pfno = pm.pf_no
            LEFT JOIN prlleavetypes lt ON slp.typeofleave = lt.id";
    $conditions = [];
    if($pfno !== null) {
        $conditions[] = "slp.pfno = '" . DB_escape_string($pfno) . "'";
    }
    if($status !== null) {
        $conditions[] = "slp.status = " . intval($status);
    }
    if(count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY slp.refno DESC";
    $result = DB_query($sql, $db);
    $applications = [];
    while($row = DB_fetch_array($result)) {
        $applications[] = $row;
    }
    return $applications;
}

function CreateLeaveApplication($refno, $pfno, $handover, $year, $leavedue, $leavend, $days, $typeofleave) {
    global $db;
    $sql = "INSERT INTO prlstaffleaveplanner (refno, pfno, handover, year, leavedue, leavend, days, typeofleave, status, approvelevel)
            VALUES ('" . DB_escape_string($refno) . "', '" . DB_escape_string($pfno) . "', '" . DB_escape_string($handover) . "', 
                    " . intval($year) . ", '$leavedue', '$leavend', " . intval($days) . ", " . intval($typeofleave) . ", 0, 1)";
    return DB_query($sql, $db);
}

function ApproveLeave($refno, $approver_level) {
    global $db;
    $next_level = $approver_level + 1;
    $status = ($next_level > 2) ? 1 : 0;
    $sql = "UPDATE prlstaffleaveplanner SET status = $status, approvelevel = $next_level WHERE refno = '" . DB_escape_string($refno) . "'";
    return DB_query($sql, $db);
}

function RejectLeave($refno) {
    global $db;
    $sql = "UPDATE prlstaffleaveplanner SET status = 2 WHERE refno = '" . DB_escape_string($refno) . "'";
    return DB_query($sql, $db);
}



function GetPayrollSummary($payroll_id) {
    global $db;
    $sql = "SELECT 
                COUNT(DISTINCT pt.pfno) AS employee_count,
                SUM(pt.basicpay) AS total_basicpay,
                SUM(pt.overtime) AS total_overtime,
                SUM(IFNULL(ad.total_allowances, 0)) AS total_allowances,
                SUM(IFNULL(dd.total_deductions, 0) + IFNULL(lt.total_loans, 0)) AS total_deductions,
                SUM(pt.basicpay + IFNULL(ad.total_allowances, 0) + pt.overtime) AS total_gross,
                SUM(pt.basicpay + IFNULL(ad.total_allowances, 0) + pt.overtime - pt.lateness_absent - IFNULL(dd.total_deductions, 0) - IFNULL(lt.total_loans, 0)) AS total_net
            FROM prlpayroltransfile pt
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount) AS total_allowances
                FROM prlpaydetailstransfile
                WHERE deduction = 1
                GROUP BY payroll_id, pfno
            ) ad ON pt.payroll_id = ad.payroll_id AND pt.pfno = ad.pfno
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount) AS total_deductions
                FROM prlpaydetailstransfile
                WHERE deduction = 0
                GROUP BY payroll_id, pfno
            ) dd ON pt.payroll_id = dd.payroll_id AND pt.pfno = dd.pfno
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount + IFNULL(interest, 0)) AS total_loans
                FROM prlloantrans
                GROUP BY payroll_id, pfno
            ) lt ON pt.payroll_id = lt.payroll_id AND pt.pfno = lt.pfno
            WHERE pt.payroll_id = " . intval($payroll_id);
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function GetDeductionsSummary($payroll_id) {
    global $db;
    $sql = "SELECT pd.description,
                   SUM(pd.amount) AS total_amount,
                   SUM(pd.employercontribution) AS total_employer
            FROM prlpaydetailstransfile pd
            WHERE pd.payroll_id = " . intval($payroll_id) . "
              AND pd.deduction = 0
            GROUP BY pd.code, pd.description";
    $result = DB_query($sql, $db);
    $summary = [];
    while($row = DB_fetch_array($result)) {
        $summary[] = $row;
    }
    return $summary;
}

function GetNetPayByBank($payroll_id) {
    global $db;
    $sql = "SELECT bk.bankname, bk.code AS bank_code,
                   SUM(pt.basicpay + IFNULL(ad.total_allowances, 0) + pt.overtime - pt.lateness_absent - IFNULL(dd.total_deductions, 0) - IFNULL(lt.total_loans, 0)) AS total_net
            FROM prlpayroltransfile pt
            INNER JOIN prlemployeemaster pm ON pt.pfno = pm.pf_no
            INNER JOIN employeebanks bk ON pm.bankcode = bk.code
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount) AS total_allowances
                FROM prlpaydetailstransfile
                WHERE deduction = 1
                GROUP BY payroll_id, pfno
            ) ad ON pt.payroll_id = ad.payroll_id AND pt.pfno = ad.pfno
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount) AS total_deductions
                FROM prlpaydetailstransfile
                WHERE deduction = 0
                GROUP BY payroll_id, pfno
            ) dd ON pt.payroll_id = dd.payroll_id AND pt.pfno = dd.pfno
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount + IFNULL(interest, 0)) AS total_loans
                FROM prlloantrans
                GROUP BY payroll_id, pfno
            ) lt ON pt.payroll_id = lt.payroll_id AND pt.pfno = lt.pfno
            WHERE pt.payroll_id = " . intval($payroll_id) . "
            GROUP BY bk.code, bk.bankname
            ORDER BY bk.bankname";
    $result = DB_query($sql, $db);
    $summary = [];
    while($row = DB_fetch_array($result)) {
        $summary[] = $row;
    }
    return $summary;
}

function CalculateNetPay($basicpay, $allowances, $overtime, $deductions) {
    return floatval($basicpay) + floatval($allowances) + floatval($overtime) - floatval($deductions);
}

function GetConfigValue($confname) {
    global $db;
    $sql = "SELECT confvalue FROM config WHERE confname = '" . DB_escape_string($confname) . "'";
    $result = DB_query($sql, $db);
    $row = DB_fetch_array($result);
    return $row ? $row['confvalue'] : null;
}

function SetConfigValue($confname, $confvalue) {
    global $db;
    $sql = "INSERT INTO config (confname, confvalue) VALUES ('" . DB_escape_string($confname) . "', '" . DB_escape_string($confvalue) . "')
            ON DUPLICATE KEY UPDATE confvalue = '" . DB_escape_string($confvalue) . "'";
    return DB_query($sql, $db);
}

function GetEstablishments() {
    global $db;
    $result = DB_query("SELECT * FROM prlestablishment ORDER BY code", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetSalaryScales() {
    global $db;
    $result = DB_query("SELECT ss.*, jg.name AS job_group_name 
                        FROM prlsalaryscale ss
                        LEFT JOIN prljobgroup jg ON ss.jbgroup = jg.name
                        ORDER BY ss.jbgroup, ss.code", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetJobGroups() {
    global $db;
    $result = DB_query("SELECT * FROM prljobgroup ORDER BY name", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetReliefs() {
    global $db;
    $result = DB_query("SELECT r.*, p.description AS product_name
                        FROM prlreliefs r
                        INNER JOIN prlproducts p ON r.productcode = p.code
                        ORDER BY r.code", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetSpecialDays() {
    global $db;
    $result = DB_query("SELECT * FROM prlspecialdays ORDER BY rowid", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetWorkingDays() {
    global $db;
    $result = DB_query("SELECT * FROM dayoftheweeks ORDER BY Dayoftheweek", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GeneratePayslipData($pfno, $payroll_id) {
    global $db;
    $emp = GetEmployee($pfno);
    if(!$emp) return null;
    
    $period = GetPayrollPeriod($payroll_id);
    if(!$period) return null;
    
    $trans = GetPayrollTransaction($pfno, $payroll_id);
    if(!$trans) return null;
    
    $details = GetPayrollDetails($pfno, $payroll_id);
    
    $basicpay = floatval($trans['basicpay']);
    $overtime = floatval($trans['overtime']);
    $lateness = floatval($trans['lateness_absent']);
    $loan_rows = GetPayrollLoanTransactions($pfno, $payroll_id);
    
    $allowances = 0;
    $deductions = 0;
    $loan_deductions = 0;
    $allowance_items = [];
    $deduction_items = [];
    
    foreach($details as $detail) {
        if($detail['deduction'] == 1) {
            $allowances += floatval($detail['amount']);
            $allowance_items[] = ['description' => $detail['description'], 'amount' => floatval($detail['amount'])];
        } else {
            $deductions += floatval($detail['amount']);
            $deduction_items[] = ['description' => $detail['description'], 'amount' => floatval($detail['amount'])];
        }
    }

    foreach($loan_rows as $loan) {
        $loan_amount = floatval($loan['amount']);
        $interest_amount = floatval($loan['interest']);

        if($loan_amount > 0) {
            $loan_deductions += $loan_amount;
            $deduction_items[] = ['description' => $loan['loan_type'], 'amount' => $loan_amount];
        }
        if($interest_amount > 0) {
            $loan_deductions += $interest_amount;
            $deduction_items[] = ['description' => '% on ' . $loan['loan_type'], 'amount' => $interest_amount];
        }
    }
    
    $gross = $basicpay + $overtime + $allowances;
    $deductions += $loan_deductions;
    $netpay = $gross - $lateness - $deductions;
    
    return [
        'pfno' => $pfno,
        'names' => GetEmployeeFullName($pfno),
        'idno' => $emp['idno'],
        'pin_no' => $emp['pin_no'],
        'bank' => $emp['bankcode2'],
        'bankacno' => $emp['bankacno'],
        'branch' => $emp['branch'],
        'designation' => $emp['position'],
        'month' => date('F', strtotime($period['todate'])),
        'year' => date('Y', strtotime($period['todate'])),
        'basicpay' => $basicpay,
        'overtime' => $overtime,
        'lateness' => $lateness,
        'allowances' => $allowances,
        'allowance_items' => $allowance_items,
        'deductions' => $deductions,
        'loan_deductions' => $loan_deductions,
        'deduction_items' => $deduction_items,
        'gross' => $gross,
        'netpay' => $netpay
    ];
}

function GetPayslipByPeriod($payroll_id) {
    global $db;
    $sql = "SELECT pt.pfno,
                   CONCAT(TRIM(pm.fname), ' ', TRIM(pm.mname), ' ', TRIM(pm.lname)) AS names,
                   pm.idno, pm.pin_no, pm.bankcode2, pm.bankacno, pm.branch, pm.position,
                   pt.basicpay, pt.overtime, pt.lateness_absent,
                   IFNULL(ad.total_allowances, 0) AS allowances,
                   (pt.basicpay + pt.overtime + IFNULL(ad.total_allowances, 0)) AS gross,
                   (pt.basicpay + pt.overtime + IFNULL(ad.total_allowances, 0) - pt.lateness_absent - IFNULL(dd.total_deductions, 0) - IFNULL(lt.total_loans, 0)) AS netpay
            FROM prlpayroltransfile pt
            INNER JOIN prlemployeemaster pm ON pt.pfno = pm.pf_no
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount) AS total_allowances
                FROM prlpaydetailstransfile
                WHERE deduction = 1
                GROUP BY payroll_id, pfno
            ) ad ON pt.payroll_id = ad.payroll_id AND pt.pfno = ad.pfno
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount) AS total_deductions
                FROM prlpaydetailstransfile
                WHERE deduction = 0
                GROUP BY payroll_id, pfno
            ) dd ON pt.payroll_id = dd.payroll_id AND pt.pfno = dd.pfno
            LEFT JOIN (
                SELECT payroll_id, pfno, SUM(amount + IFNULL(interest, 0)) AS total_loans
                FROM prlloantrans
                GROUP BY payroll_id, pfno
            ) lt ON pt.payroll_id = lt.payroll_id AND pt.pfno = lt.pfno
            WHERE pt.payroll_id = " . intval($payroll_id) . "
            ORDER BY pt.pfno";
    $result = DB_query($sql, $db);
    $payslips = [];
    while($row = DB_fetch_array($result)) {
        $payslips[] = $row;
    }
    return $payslips;
}


function GetProductItems() {
    global $db;
    $result = DB_query("SELECT * FROM prlitemaintenace ORDER BY code, taxband", $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}


function FetchProductItems($code) {
    global $db;
    $sql = "SELECT p.code, p.description, i.*
            FROM prlproducts p
            LEFT JOIN prlitemaintenace i ON p.code = i.code
            WHERE p.code = " . intval($code) . "
            ORDER BY i.taxband";
    $result = DB_query($sql, $db);
    $items = [];
    while($row = DB_fetch_array($result)) {
        $items[] = $row;
    }
    return $items;
}

function GetProductItem($code, $taxband) {
    global $db;
    $sql = "SELECT p.code, p.description, i.*
            FROM prlproducts p
            LEFT JOIN prlitemaintenace i ON p.code = i.code
            WHERE p.code = " . intval($code) . " AND i.taxband = " . intval($taxband);
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function SaveProductItem($code, $taxband, $lowerlimit, $upperlimit, $percentageamount, $ceiling) {
    global $db;
    $sql = "INSERT INTO prlitemaintenace (code, taxband, lowerlimit, upperlimit, percentageamount, ceiling)
            VALUES (" . intval($code) . ", " . intval($taxband) . ", " . floatval($lowerlimit) . ", " . floatval($upperlimit) . ", " . floatval($percentageamount) . ", " . floatval($ceiling) . ")
            ON DUPLICATE KEY UPDATE 
                lowerlimit = " . floatval($lowerlimit) . ", 
                upperlimit = " . floatval($upperlimit) . ", 
                percentageamount = " . floatval($percentageamount) . ", 
                ceiling = " . floatval($ceiling);
    return DB_query($sql, $db);
}

function UpdateProductItem($code, $oldtaxband, $newtaxband, $lowerlimit, $upperlimit, $percentageamount, $ceiling) {
    global $db;
    $sql = "UPDATE prlitemaintenace 
            SET taxband = " . intval($newtaxband) . ", 
                lowerlimit = " . floatval($lowerlimit) . ", 
                upperlimit = " . floatval($upperlimit) . ", 
                percentageamount = " . floatval($percentageamount) . ", 
                ceiling = " . floatval($ceiling) . "
            WHERE code = " . intval($code) . " AND taxband = " . intval($oldtaxband);
    return DB_query($sql, $db);
}

function DeleteProductItem($code, $taxband) {
    global $db;
    $sql = "DELETE FROM prlitemaintenace WHERE code = " . intval($code) . " AND taxband = " . intval($taxband);
    return DB_query($sql, $db);
}

function SaveProductFlatRate($code, $taxableamount, $percentageamount, $ceiling) {
    global $db;
    $sql = "INSERT INTO prlitemaintenace (code, taxband, taxableamount, percentageamount, ceiling)
            VALUES (" . intval($code) . ", 0, " . floatval($taxableamount) . ", " . floatval($percentageamount) . ", " . floatval($ceiling) . ")
            ON DUPLICATE KEY UPDATE 
                taxableamount = " . floatval($taxableamount) . ", 
                percentageamount = " . floatval($percentageamount) . ", 
                ceiling = " . floatval($ceiling);
    return DB_query($sql, $db);
}

function GetProductFlatRate($code) {
    global $db;
    $sql = "SELECT * FROM prlitemaintenace WHERE code = " . intval($code) . " AND taxband = 0";
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

?>
