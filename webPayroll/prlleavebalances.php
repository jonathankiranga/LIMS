<?php
include('includes/session.inc');
$Title = _(' Leave Balance Management');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('ExtFunc/Payrollfunctions.php');

if(isset($_POST['assign_leave'])) {
    $year = (int)$_POST['leave_year'];
    $sql = "SELECT pf_no, dateemployed FROM prlemployeemaster 
            WHERE (dateterminated IS NULL OR dateterminated > CURDATE()) 
            AND (freqcode = 1 OR freqcode = 2 OR freqcode = 3)";
    $result = DB_query($sql, $db);
    
    $leave_types_result = DB_query("SELECT leavetype_id, default_days, pro_rata_applicable FROM prlleavetypes WHERE active = 1", $db);
    $leave_types = [];
    while($lt = DB_fetch_array($leave_types_result)) {
        $leave_types[] = $lt;
    }
    
    $assigned = 0;
    while($emp = DB_fetch_array($result)) {
        $emp_start = strtotime($emp['dateemployed']);
        $year_start = mktime(0, 0, 0, 1, 1, $year);
        $year_end = mktime(0, 0, 0, 12, 31, $year);
        
        $months_worked = 12;
        if($emp_start > $year_start && $emp_start < $year_end) {
            $months_worked = 12 - (int)date('n', $emp_start) + 1;
        }
        
        foreach($leave_types as $lt) {
            $entitled = $lt['default_days'];
            
            if($lt['pro_rata_applicable'] && $months_worked < 12) {
                $entitled = round(($lt['default_days'] / 12) * $months_worked);
            }
            
            if($entitled > 0) {
                $sql = sprintf("INSERT INTO prlleavebalances (pfno, leavetype_id, year, entitled_days, carried_over_days, used_days, pending_days)
                    VALUES ('%s', %d, %d, %d, 0, 0, 0)
                    ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days)",
                    $emp['pf_no'], $lt['leavetype_id'], $year, $entitled
                );
                DB_query($sql, $db);
                $assigned++;
            }
        }
    }
    prnMsg(sprintf(_('Leave assigned to %d employees for year %d'), $assigned, $year), 'success');
}

if(isset($_POST['adjust_balance'])) {
    $sql = sprintf("UPDATE prlleavebalances SET 
        entitled_days = %d,
        carried_over_days = %d
        WHERE balance_id = %d",
        (int)$_POST['entitled_days'],
        (int)$_POST['carried_over_days'],
        (int)$_POST['balance_id']
    );
    DB_query($sql, $db);
    prnMsg(_('Balance updated successfully'), 'success');
}

if(isset($_POST['sync_balances'])) {
    $year = (int)$_POST['sync_year'];
    syncLeaveBalances($db, $year);
    prnMsg(_('Balances synchronized with leave applications'), 'success');
}

function syncLeaveBalances($db, $year) {
    $sql = "SELECT leavetype_id FROM prlleavetypes WHERE active = 1";
    $result = DB_query($sql, $db);
    
    while($lt = DB_fetch_array($result)) {
        $sql = "UPDATE prlleavebalances lb SET
            used_days = COALESCE((
                SELECT SUM(la.applied_days) 
                FROM prlleaveapplications la 
                WHERE la.pfno = lb.pfno 
                AND la.leavetype_id = lb.leavetype_id 
                AND la.year = lb.year 
                AND la.status IN ('APPROVED', 'COMPLETED')
            ), 0),
            pending_days = COALESCE((
                SELECT SUM(la.applied_days) 
                FROM prlleaveapplications la 
                WHERE la.pfno = lb.pfno 
                AND la.leavetype_id = lb.leavetype_id 
                AND la.year = lb.year 
                AND la.status IN ('PENDING', 'PENDING_HOD', 'PENDING_HR')
            ), 0)
            WHERE lb.year = $year AND lb.leavetype_id = " . $lt['leavetype_id'];
        DB_query($sql, $db);
    }
}

if(!isset($_POST['leave_year'])) {
    $_POST['leave_year'] = date('Y');
}
if(!isset($_POST['filter_dept'])) {
    $_POST['filter_dept'] = '';
}

echo '<div class="container-fluid">';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . _('Leave Balances') . '" alt="" /> ' . $Title . '</p>';

echo '<div class="row">';
echo '<div class="col-md-12">';

echo '<ul class="nav nav-tabs" role="tablist">';
echo '<li class="nav-item"><a class="nav-link active" href="#balances" data-toggle="tab">' . _('Leave Balances') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link" href="#assign" data-toggle="tab">' . _('Assign Leave') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link" href="#reports" data-toggle="tab">' . _('Summary Report') . '</a></li>';
echo '</ul>';

echo '<div class="tab-content">';

echo '<div role="tabpanel" class="tab-pane active" id="balances">';
echo '<form method="post" class="form-inline mt-3">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<div class="form-group mr-2">';
echo '<label class="mr-2">Year:</label>';
echo '<select name="leave_year" class="form-control" onchange="this.form.submit();">';
for($y = date('Y') - 2; $y <= date('Y') + 1; $y++) {
    $selected = ($y == $_POST['leave_year']) ? ' selected' : '';
    echo '<option value="' . $y . '"' . $selected . '>' . $y . '</option>';
}
echo '</select></div>';
echo '<div class="form-group mr-2">';
echo '<label class="mr-2">Department:</label>';
echo '<select name="filter_dept" class="form-control">';
echo '<option value="">All Departments</option>';
$departments = GetDepartments();
foreach($departments as $dept) {
    $selected = ($dept['code'] == $_POST['filter_dept']) ? ' selected' : '';
    echo '<option value="' . $dept['code'] . '"' . $selected . '>' . $dept['name'] . '</option>';
}
echo '</select></div>';
echo '<button type="submit" class="btn btn-primary">' . _('Filter') . '</button>';
echo '</form>';

$year = (int)$_POST['leave_year'];
$dept_filter = '';
if(!empty($_POST['filter_dept'])) {
    $dept_filter = " AND e.department = " . (int)$_POST['filter_dept'];
}

echo '<div class="table-responsive mt-3">';
echo '<table class="table table-bordered table-striped table-hover datatable">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('PF No') . '</th>';
echo '<th>' . _('Employee Name') . '</th>';
echo '<th>' . _('Department') . '</th>';
foreach(['ANNUAL', 'MATERNITY', 'PATERNITY', 'SICK_FULL', 'SICK_HALF', 'COMPASSIONATE', 'STUDY'] as $code) {
    $lt_result = DB_query("SELECT leavetype_name_short, color_code FROM prlleavetypes WHERE leavetype_code = '$code'", $db);
    if($lt = DB_fetch_array($lt_result)) {
        echo '<th style="background-color: ' . $lt['color_code'] . '; color: white;">' . _($lt['leavetype_name_short']) . '</th>';
    }
}
echo '<th>' . _('Actions') . '</th>';
echo '</tr></thead><tbody>';

$sql = "SELECT DISTINCT e.pf_no, 
        CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name,
        d.name as dept_name,
        e.department
        FROM prlemployeemaster e
        LEFT JOIN prldepartments d ON e.department = d.code
        WHERE (e.dateterminated IS NULL OR e.dateterminated > CURDATE())
        AND e.Inactive = 0
        $dept_filter
        ORDER BY d.name, emp_name";
$emp_result = DB_query($sql, $db);

while($emp = DB_fetch_array($emp_result)) {
    echo '<tr>';
    echo '<td><strong>' . $emp['pf_no'] . '</strong></td>';
    echo '<td>' . $emp['emp_name'] . '</td>';
    echo '<td>' . ($emp['dept_name'] ?: '-') . '</td>';
    
    $leave_types_result = DB_query("SELECT leavetype_id, leavetype_code, color_code FROM prlleavetypes WHERE active = 1 AND leavetype_code IN ('ANNUAL', 'MATERNITY', 'PATERNITY', 'SICK_FULL', 'SICK_HALF', 'COMPASSIONATE', 'STUDY') ORDER BY sort_order", $db);
    
    while($lt = DB_fetch_array($leave_types_result)) {
        $bal_sql = "SELECT entitled_days, carried_over_days, used_days, pending_days, available_days, balance_id
                    FROM prlleavebalances 
                    WHERE pfno = '" . $emp['pf_no'] . "' AND leavetype_id = " . $lt['leavetype_id'] . " AND year = $year";
        $bal_result = DB_query($bal_sql, $db);
        $bal = DB_fetch_array($bal_result);
        
        if($bal) {
            $available = $bal['available_days'];
            $bg_class = '';
            if($available <= 0) {
                $bg_class = 'bg-danger text-white';
            } elseif($available < 5) {
                $bg_class = 'bg-warning';
            }
            echo '<td class="text-center ' . $bg_class . '" data-balance-id="' . $bal['balance_id'] . '">';
            echo '<span title="Entitled: ' . $bal['entitled_days'] . ', Used: ' . $bal['used_days'] . ', Pending: ' . $bal['pending_days'] . '">';
            echo round($available, 1);
            echo '</span>';
            echo '</td>';
        } else {
            echo '<td class="text-center text-muted">-</td>';
        }
    }
    
    echo '<td>';
    echo '<a href="' . $RootPath . '/prlleavebalances.php?emp=' . $emp['pf_no'] . '&year=' . $year . '" class="btn btn-xs btn-info" title="' . _('View Details') . '"><i class="fas fa-eye"></i></a>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';
echo '</div>';

echo '<div role="tabpanel" class="tab-pane" id="assign">';
echo '<div class="row mt-3">';
echo '<div class="col-md-6">';
echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered">';
echo '<tr><th colspan="2">' . _('Assign Leave for Year') . '</th></tr>';
echo '<tr><td>' . _('Year') . '</td><td>';
echo '<select name="leave_year" class="form-control">';
for($y = date('Y'); $y <= date('Y') + 1; $y++) {
    echo '<option value="' . $y . '">' . $y . '</option>';
}
echo '</select></td></tr>';
echo '<tr><td colspan="2" class="text-muted"><small>';
echo 'This will assign default leave days to all active employees based on their start date (pro-rata for those who joined mid-year).';
echo '</small></td></tr>';
echo '<tr><td colspan="2"><input type="submit" name="assign_leave" value="' . _('Assign Leave to All Employees') . '" class="btn btn-success btn-block" /></td></tr>';
echo '</table>';
echo '</form>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered">';
echo '<tr><th colspan="2">' . _('Sync Balances') . '</th></tr>';
echo '<tr><td>' . _('Year') . '</td><td>';
echo '<select name="sync_year" class="form-control">';
for($y = date('Y') - 1; $y <= date('Y'); $y++) {
    echo '<option value="' . $y . '">' . $y . '</option>';
}
echo '</select></td></tr>';
echo '<tr><td colspan="2" class="text-muted"><small>';
echo 'Recalculate balances based on approved leave applications.';
echo '</small></td></tr>';
echo '<tr><td colspan="2"><input type="submit" name="sync_balances" value="' . _('Sync Balances') . '" class="btn btn-warning btn-block" /></td></tr>';
echo '</table>';
echo '</form>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div role="tabpanel" class="tab-pane" id="reports">';
echo '<div class="mt-3">';

$summary_sql = "SELECT 
    lt.leavetype_name,
    COUNT(DISTINCT lb.pfno) as employees,
    SUM(lb.entitled_days) as total_entitled,
    SUM(lb.carried_over_days) as total_carried,
    SUM(lb.used_days) as total_used,
    SUM(lb.pending_days) as total_pending,
    SUM(lb.available_days) as total_available
    FROM prlleavebalances lb
    INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
    WHERE lb.year = $year AND lt.active = 1
    GROUP BY lb.leavetype_id, lt.leavetype_name
    ORDER BY lt.sort_order";

$summary_result = DB_query($summary_sql, $db);

echo '<h5>' . _('Leave Summary for Year ') . $year . '</h5>';
echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th>' . _('Employees') . '</th>';
echo '<th>' . _('Entitled') . '</th>';
echo '<th>' . _('Carried Over') . '</th>';
echo '<th>' . _('Used') . '</th>';
echo '<th>' . _('Pending') . '</th>';
echo '<th>' . _('Available') . '</th>';
echo '</tr></thead><tbody>';

$totals = ['employees' => 0, 'entitled' => 0, 'carried' => 0, 'used' => 0, 'pending' => 0, 'available' => 0];
while($row = DB_fetch_array($summary_result)) {
    echo '<tr>';
    echo '<td><strong>' . $row['leavetype_name'] . '</strong></td>';
    echo '<td class="text-center">' . $row['employees'] . '</td>';
    echo '<td class="text-right">' . number_format($row['total_entitled'], 1) . '</td>';
    echo '<td class="text-right">' . number_format($row['total_carried'], 1) . '</td>';
    echo '<td class="text-right">' . number_format($row['total_used'], 1) . '</td>';
    echo '<td class="text-right">' . number_format($row['total_pending'], 1) . '</td>';
    echo '<td class="text-right"><strong>' . number_format($row['total_available'], 1) . '</strong></td>';
    echo '</tr>';
    
    $totals['employees'] += $row['employees'];
    $totals['entitled'] += $row['total_entitled'];
    $totals['carried'] += $row['total_carried'];
    $totals['used'] += $row['total_used'];
    $totals['pending'] += $row['total_pending'];
    $totals['available'] += $row['total_available'];
}

echo '<tr class="table-primary">';
echo '<td><strong>TOTAL</strong></td>';
echo '<td class="text-center"><strong>' . $totals['employees'] . '</strong></td>';
echo '<td class="text-right"><strong>' . number_format($totals['entitled'], 1) . '</strong></td>';
echo '<td class="text-right"><strong>' . number_format($totals['carried'], 1) . '</strong></td>';
echo '<td class="text-right"><strong>' . number_format($totals['used'], 1) . '</strong></td>';
echo '<td class="text-right"><strong>' . number_format($totals['pending'], 1) . '</strong></td>';
echo '<td class="text-right"><strong>' . number_format($totals['available'], 1) . '</strong></td>';
echo '</tr>';
echo '</tbody></table>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div></div>';

if(isset($_GET['emp'])) {
    $emp_pfno = $_GET['emp'];
    $emp_year = (int)$_GET['year'];
    
    $emp_result = DB_query("SELECT CONCAT(fname, ' ', mname, ' ', lname) as name FROM prlemployeemaster WHERE pf_no = '$emp_pfno'", $db);
    $emp = DB_fetch_array($emp_result);
    
    echo '<div class="modal fade" id="balanceModal" tabindex="-1" role="dialog">';
    echo '<div class="modal-dialog modal-lg" role="document">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title">' . _('Leave Balance Details') . ' - ' . $emp['name'] . ' (' . $emp_pfno . ')</h5>';
    echo '<button type="button" class="close" data-dismiss="modal">&times;</button>';
    echo '</div>';
    echo '<div class="modal-body">';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered">';
    echo '<thead class="thead-dark"><tr>';
    echo '<th>Leave Type</th>';
    echo '<th>Entitled</th>';
    echo '<th>Carried Over</th>';
    echo '<th>Used</th>';
    echo '<th>Pending</th>';
    echo '<th>Available</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';
    
    $bal_sql = "SELECT lb.*, lt.leavetype_name, lt.leavetype_code
                FROM prlleavebalances lb
                INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
                WHERE lb.pfno = '$emp_pfno' AND lb.year = $emp_year
                ORDER BY lt.sort_order";
    $bal_result = DB_query($bal_sql, $db);
    
    while($bal = DB_fetch_array($bal_result)) {
        echo '<form method="post">';
        echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
        echo '<input type="hidden" name="balance_id" value="' . $bal['balance_id'] . '" />';
        echo '<tr>';
        echo '<td>' . $bal['leavetype_name'] . '</td>';
        echo '<td><input type="number" name="entitled_days" value="' . $bal['entitled_days'] . '" class="form-control form-control-sm" min="0" /></td>';
        echo '<td><input type="number" name="carried_over_days" value="' . $bal['carried_over_days'] . '" class="form-control form-control-sm" min="0" /></td>';
        echo '<td class="text-center">' . $bal['used_days'] . '</td>';
        echo '<td class="text-center">' . $bal['pending_days'] . '</td>';
        echo '<td class="text-center"><strong>' . $bal['available_days'] . '</strong></td>';
        echo '<td><button type="submit" name="adjust_balance" class="btn btn-xs btn-success"><i class="fas fa-save"></i></button></td>';
        echo '</tr>';
        echo '</form>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    echo '</div>';
    echo '</div></div></div>';
    
    echo '<script>$(document).ready(function() { $("#balanceModal").modal("show"); });</script>';
}

echo '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">';
echo '<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>';
echo '<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>';
echo '<script>$(document).ready(function() { $(".datatable").DataTable(); });</script>';

include('includes/footer.inc');
?>
