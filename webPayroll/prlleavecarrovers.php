<?php
include('includes/session.inc');
$Title = _('Leave Carryover Management');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

if(!isset($_POST['carryover_year'])) {
    $_POST['carryover_year'] = date('Y') - 1;
}

if(isset($_POST['process_carryover'])) {
    $from_year = (int)$_POST['carryover_year'];
    $to_year = $from_year + 1;
    $max_carryover = (int)$_POST['max_carryover_days'];
    
    $employees_sql = "SELECT lb.pfno, lb.leavetype_id, lb.available_days, lt.max_carryover_days, lt.allow_carryover
                      FROM prlleavebalances lb
                      INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
                      WHERE lb.year = $from_year 
                      AND lb.available_days > 0
                      AND lt.allow_carryover = 1
                      AND lt.active = 1";
    $result = DB_query($employees_sql, $db);
    
    $processed = 0;
    while($row = DB_fetch_array($result)) {
        $max_allowed = $row['max_carryover_days'] ?: $max_carryover;
        $carryover_days = min($row['available_days'], $max_allowed);
        
        if($carryover_days > 0) {
            $check_sql = "SELECT balance_id FROM prlleavebalances WHERE pfno = '" . $row['pfno'] . "' AND leavetype_id = " . $row['leavetype_id'] . " AND year = $to_year";
            $check_result = DB_query($check_sql, $db);
            
            if(DB_num_rows($check_result) > 0) {
                DB_query("UPDATE prlleavebalances SET carried_over_days = $carryover_days WHERE pfno = '" . $row['pfno'] . "' AND leavetype_id = " . $row['leavetype_id'] . " AND year = $to_year", $db);
            } else {
                $emp = GetEmployee($row['pfno']);
                $months_worked = 12;
                
                $sql = sprintf("INSERT INTO prlleavebalances (pfno, leavetype_id, year, entitled_days, carried_over_days, used_days, pending_days)
                                VALUES ('%s', %d, %d, %d, %d, 0, 0)",
                    $row['pfno'], $row['leavetype_id'], $to_year, $months_worked, $carryover_days
                );
                DB_query($sql, $db);
            }
            
            $expiry_months = 3;
            $expiry_date = date('Y-m-d', strtotime("+$expiry_months months", strtotime("$to_year-01-01")));
            
            $sql = sprintf("INSERT INTO prlleavecarrovers (pfno, leavetype_id, from_year, to_year, days_carried, expiry_date)
                            VALUES ('%s', %d, %d, %d, %d, '%s')",
                $row['pfno'], $row['leavetype_id'], $from_year, $to_year, $carryover_days, $expiry_date
            );
            DB_query($sql, $db);
            $processed++;
        }
    }
    prnMsg(sprintf(_('Processed carryover for %d employees'), $processed), 'success');
}

if(isset($_POST['expire_carryover'])) {
    $expiry_year = (int)$_POST['expire_year'];
    
    DB_query("UPDATE prlleavecarrovers 
              SET status = 'EXPIRED', days_forfeited = days_carried - days_used
              WHERE to_year = $expiry_year AND status = 'ACTIVE' AND expiry_date < CURDATE()", $db);
    
    prnMsg(_('Expired carryover records processed'), 'success');
}

if(isset($_GET['delete'])) {
    DB_query("DELETE FROM prlleavecarrovers WHERE carryover_id = " . (int)$_GET['delete'], $db);
    prnMsg(_('Carryover record deleted'), 'success');
}

$from_year = (int)$_POST['carryover_year'];
$to_year = $from_year + 1;

echo '<div class="container-fluid">';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Carryover') . '" alt="" /> ' . $Title . '</p>';

echo '<div class="row">';
echo '<div class="col-md-4">';

echo '<div class="card mb-3">';
echo '<div class="card-header bg-primary text-white">';
echo '<h5 class="mb-0">' . _('Process Carryover') . '</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-sm">';
echo '<tr><td>' . _('From Year') . '</td><td>';
echo '<select name="carryover_year" class="form-control">';
for($y = date('Y') - 3; $y <= date('Y') - 1; $y++) {
    $selected = ($y == $from_year) ? ' selected' : '';
    echo '<option value="' . $y . '"' . $selected . '>' . $y . '</option>';
}
echo '</select></td></tr>';
echo '<tr><td>' . _('To Year') . '</td><td><input type="text" class="form-control" value="' . $to_year . '" readonly /></td></tr>';
echo '<tr><td>' . _('Max Days to Carryover') . '</td><td>';
echo '<input type="number" name="max_carryover_days" class="form-control" value="7" min="0" max="30" />';
echo '<small class="text-muted">Leave types have their own max settings</small>';
echo '</td></tr>';
echo '<tr><td colspan="2">';
echo '<p class="text-muted small">This will automatically carry over available leave days to the new year, respecting individual leave type limits.</p>';
echo '</td></tr>';
echo '<tr><td colspan="2"><input type="submit" name="process_carryover" value="' . _('Process Carryover') . '" class="btn btn-success btn-block" /></td></tr>';
echo '</table>';
echo '</form>';
echo '</div></div>';

echo '<div class="card">';
echo '<div class="card-header bg-warning text-dark">';
echo '<h5 class="mb-0">' . _('Expire Carryover') . '</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-sm">';
echo '<tr><td>' . _('Year') . '</td><td>';
echo '<select name="expire_year" class="form-control">';
for($y = date('Y') - 2; $y <= date('Y'); $y++) {
    echo '<option value="' . $y . '">' . $y . '</option>';
}
echo '</select></td></tr>';
echo '<tr><td colspan="2"><input type="submit" name="expire_carryover" value="' . _('Expire Unused Carryover') . '" class="btn btn-warning btn-block" /></td></tr>';
echo '</table>';
echo '</form>';
echo '</div></div>';
echo '</div>';

echo '<div class="col-md-8">';
echo '<h5>' . _('Carryover Summary for ') . $from_year . ' -> ' . $to_year . '</h5>';

$sql = "SELECT lc.*, 
        CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name,
        lt.leavetype_name, lt.color_code
        FROM prlleavecarrovers lc
        INNER JOIN prlemployeemaster e ON lc.pfno = e.pf_no
        INNER JOIN prlleavetypes lt ON lc.leavetype_id = lt.leavetype_id
        WHERE lc.from_year = $from_year AND lc.to_year = $to_year
        ORDER BY lc.status, lt.leavetype_name";
$result = DB_query($sql, $db);

echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Employee') . '</th>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th>' . _('Days Carried') . '</th>';
echo '<th>' . _('Days Used') . '</th>';
echo '<th>' . _('Days Forfeited') . '</th>';
echo '<th>' . _('Expiry Date') . '</th>';
echo '<th>' . _('Status') . '</th>';
echo '<th>' . _('Actions') . '</th>';
echo '</tr></thead><tbody>';

$totals = ['carried' => 0, 'used' => 0, 'forfeited' => 0];
while($row = DB_fetch_array($result)) {
    echo '<tr style="border-left: 4px solid ' . $row['color_code'] . ';">';
    echo '<td>' . $row['emp_name'] . '</td>';
    echo '<td>' . $row['leavetype_name'] . '</td>';
    echo '<td class="text-right">' . $row['days_carried'] . '</td>';
    echo '<td class="text-right">' . $row['days_used'] . '</td>';
    echo '<td class="text-right">' . $row['days_forfeited'] . '</td>';
    echo '<td>' . ConvertSQLDate($row['expiry_date']) . '</td>';
    echo '<td><span class="badge badge-' . ($row['status'] == 'ACTIVE' ? 'success' : ($row['status'] == 'EXPIRED' ? 'danger' : 'info')) . '">' . $row['status'] . '</span></td>';
    echo '<td>';
    if($row['status'] == 'ACTIVE') {
        echo '<a href="?delete=' . $row['carryover_id'] . '" class="btn btn-xs btn-danger" onclick="return confirm(\'' . _('Delete this carryover?') . '\');"><i class="fas fa-trash"></i></a>';
    }
    echo '</td>';
    echo '</tr>';
    
    $totals['carried'] += $row['days_carried'];
    $totals['used'] += $row['days_used'];
    $totals['forfeited'] += $row['days_forfeited'];
}

echo '<tr class="table-primary font-weight-bold">';
echo '<td colspan="2">TOTAL</td>';
echo '<td class="text-right">' . $totals['carried'] . '</td>';
echo '<td class="text-right">' . $totals['used'] . '</td>';
echo '<td class="text-right">' . $totals['forfeited'] . '</td>';
echo '<td colspan="3"></td>';
echo '</tr>';

if(DB_num_rows($result) == 0) {
    echo '<tr><td colspan="8" class="text-center text-muted">' . _('No carryover records found for this period') . '</td></tr>';
}
echo '</tbody></table>';
echo '</div>';

echo '<h5>' . _('Carryover by Leave Type') . '</h5>';
$summary_sql = "SELECT lt.leavetype_name, lt.color_code,
                SUM(lc.days_carried) as total_carried,
                SUM(lc.days_used) as total_used,
                SUM(lc.days_forfeited) as total_forfeited,
                COUNT(DISTINCT lc.pfno) as employees
                FROM prlleavecarrovers lc
                INNER JOIN prlleavetypes lt ON lc.leavetype_id = lt.leavetype_id
                WHERE lc.from_year = $from_year AND lc.to_year = $to_year
                GROUP BY lc.leavetype_id, lt.leavetype_name";
$summary_result = DB_query($summary_sql, $db);

echo '<div class="table-responsive">';
echo '<table class="table table-bordered">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th>' . _('Employees') . '</th>';
echo '<th>' . _('Total Carried') . '</th>';
echo '<th>' . _('Total Used') . '</th>';
echo '<th>' . _('Total Forfeited') . '</th>';
echo '</tr></thead><tbody>';
while($row = DB_fetch_array($summary_result)) {
    echo '<tr>';
    echo '<td><span class="badge" style="background-color: ' . $row['color_code'] . '; color: white;">&nbsp;</span> ' . $row['leavetype_name'] . '</td>';
    echo '<td class="text-center">' . $row['employees'] . '</td>';
    echo '<td class="text-right">' . $row['total_carried'] . '</td>';
    echo '<td class="text-right">' . $row['total_used'] . '</td>';
    echo '<td class="text-right">' . $row['total_forfeited'] . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';
echo '</div></div>';

include('includes/footer.inc');
?>
