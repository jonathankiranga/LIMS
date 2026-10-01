<?php
include('includes/session.inc');
$Title = _('Leave Encashment Processing');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

if(isset($_POST['submit_encashment'])) {
    $errors = 0;
    
    if(empty($_POST['pfno']) || empty($_POST['leavetype_id']) || empty($_POST['days_to_encash'])) {
        prnMsg(_('All fields are required'), 'error');
        $errors++;
    }
    
    if($errors == 0) {
        $year = date('Y');
        $bal_result = DB_query("SELECT available_days, entitled_days, encashed_days FROM prlleavebalances 
                                WHERE pfno = '" . $_POST['pfno'] . "' AND leavetype_id = " . (int)$_POST['leavetype_id'] . " AND year = $year", $db);
        $bal = DB_fetch_array($bal_result);
        
        $lt_result = DB_query("SELECT encashable, max_encashment_days FROM prlleavetypes WHERE leavetype_id = " . (int)$_POST['leavetype_id'], $db);
        $lt = DB_fetch_array($lt_result);
        
        if(!$lt['encashable']) {
            prnMsg(_('This leave type is not encashable'), 'error');
            $errors++;
        } elseif($bal['available_days'] < $_POST['days_to_encash']) {
            prnMsg(_('Insufficient leave balance for encashment'), 'error');
            $errors++;
        } else {
            $max_encash = $lt['max_encashment_days'] ?: $bal['available_days'];
            $days_to_encash = min($_POST['days_to_encash'], $max_encash);
            
            $emp = GetEmployee($_POST['pfno']);
            
            $daily_rate = ($emp['basicpay'] / 30);
            $gross_amount = $daily_rate * $days_to_encash;
            
            $ref = 'EN' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            
            $sql = sprintf("INSERT INTO prlleaveencashments 
                (encashment_ref, pfno, leavetype_id, days_encashed, daily_rate, gross_amount, status, request_date)
                VALUES ('%s', '%s', %d, %d, %f, %f, 'APPROVED', CURDATE())",
                $ref, $_POST['pfno'], (int)$_POST['leavetype_id'], $days_to_encash, $daily_rate, $gross_amount
            );
            DB_query($sql, $db);
            
            DB_query("UPDATE prlleavebalances SET encashed_days = encashed_days + $days_to_encash 
                     WHERE pfno = '" . $_POST['pfno'] . "' AND leavetype_id = " . (int)$_POST['leavetype_id'] . " AND year = $year", $db);
            
            prnMsg(sprintf(_('Leave encashment of %d days processed. Ref: %s. Amount: KES %s'), 
                $days_to_encash, $ref, number_format($gross_amount, 2)), 'success');
            unset($_POST);
        }
    }
}

if(isset($_POST['approve_encashment'])) {
    $encashment_id = (int)$_POST['encashment_id'];
    $status = $_POST['encashment_status'];
    
    $sql = sprintf("UPDATE prlleaveencashments SET 
                    status = '%s', 
                    approval_date = CURDATE(),
                    approved_by = '%s',
                    rejection_reason = '%s'
                    WHERE encashment_id = %d",
                    $status, $_SESSION['UserID'], $_POST['rejection_reason'], $encashment_id
    );
    DB_query($sql, $db);
    
    if($status == 'REJECTED') {
        $enc_result = DB_query("SELECT pfno, leavetype_id, days_encashed FROM prlleaveencashments WHERE encashment_id = $encashment_id", $db);
        $enc = DB_fetch_array($enc_result);
        DB_query("UPDATE prlleavebalances SET encashed_days = encashed_days - " . $enc['days_encashed'] . "
                 WHERE pfno = '" . $enc['pfno'] . "' AND leavetype_id = " . $enc['leavetype_id'], $db);
    }
    
    prnMsg(_('Encashment status updated'), 'success');
}

echo '<div class="container-fluid">';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . _('Encashment') . '" alt="" /> ' . $Title . '</p>';

echo '<ul class="nav nav-tabs mb-3">';
echo '<li class="nav-item"><a class="nav-link active" href="#new" data-toggle="tab">' . _('New Request') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link" href="#pending" data-toggle="tab">' . _('Pending Approval') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link" href="#history" data-toggle="tab">' . _('History') . '</a></li>';
echo '</ul>';

echo '<div class="tab-content">';

echo '<div class="tab-pane active" id="new">';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered">';
echo '<tr><th colspan="2">' . _('New Encashment Request') . '</th></tr>';

echo '<tr><td>' . _('Employee') . '</td><td>';
echo '<select name="pfno" class="form-control select2" required>';
echo '<option value="">-- Select Employee --</option>';
$employees = GetEmployees();
foreach($employees as $emp) {
    $emp_name = GetEmployeeFullName($emp['pf_no']);
    echo '<option value="' . $emp['pf_no'] . '">' . $emp['pf_no'] . ' - ' . $emp_name . '</option>';
}
echo '</select>';
echo '</td></tr>';

echo '<tr><td>' . _('Leave Type to Encash') . '</td><td>';
echo '<select name="leavetype_id" class="form-control" required>';
echo '<option value="">-- Select Leave Type --</option>';
$lt_result = DB_query("SELECT leavetype_id, leavetype_name, color_code, encashable FROM prlleavetypes WHERE active = 1 AND encashable = 1 ORDER BY leavetype_name", $db);
while($lt = DB_fetch_array($lt_result)) {
    echo '<option value="' . $lt['leavetype_id'] . '" style="color: ' . $lt['color_code'] . ';">' . $lt['leavetype_name'] . '</option>';
}
echo '</select>';
echo '</td></tr>';

echo '<tr><td>' . _('Days to Encash') . '</td><td>';
echo '<input type="number" name="days_to_encash" class="form-control" min="1" value="1" required />';
echo '<small class="text-muted">Only encashable days from annual leave can be encashed (max 7 days per year per Kenyan law)</small>';
echo '</td></tr>';

echo '<tr><td colspan="2"><input type="submit" name="submit_encashment" value="' . _('Process Encashment') . '" class="btn btn-success btn-block" /></td></tr>';
echo '</table>';
echo '</form>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-info text-white">';
echo '<h5 class="mb-0">' . _('Encashment Policy') . '</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<ul>';
echo '<li>Only annual leave can be encashed</li>';
echo '<li>Maximum 7 days per calendar year (per Kenyan Employment Act 2007)</li>';
echo '<li>Encashment is calculated at daily rate (Basic Pay / 30)</li>';
echo '<li>Encashment is subject to tax deduction</li>';
echo '<li>Requires HR and Finance approval</li>';
echo '<li>Processing usually takes 5-7 working days</li>';
echo '</ul>';
echo '<hr/><h6>Leave Encashment Rates</h6>';
echo '<table class="table table-sm">';
echo '<thead><tr><th>Leave Type</th><th>Encashable</th><th>Max Days</th></tr></thead><tbody>';
$encash_result = DB_query("SELECT leavetype_name, encashable, max_encashment_days FROM prlleavetypes WHERE active = 1 ORDER BY leavetype_name", $db);
while($row = DB_fetch_array($encash_result)) {
    echo '<tr>';
    echo '<td>' . $row['leavetype_name'] . '</td>';
    echo '<td>' . ($row['encashable'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>') . '</td>';
    echo '<td>' . ($row['max_encashment_days'] ?: 'Per policy') . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div></div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="tab-pane" id="pending">';
$pending_result = DB_query("SELECT le.*, CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name, lt.leavetype_name
                           FROM prlleaveencashments le
                           INNER JOIN prlemployeemaster e ON le.pfno = e.pf_no
                           INNER JOIN prlleavetypes lt ON le.leavetype_id = lt.leavetype_id
                           WHERE le.status = 'PENDING'
                           ORDER BY le.request_date DESC", $db);

echo '<div class="table-responsive">';
echo '<table class="table table-bordered">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Ref') . '</th>';
echo '<th>' . _('Employee') . '</th>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th>' . _('Days') . '</th>';
echo '<th>' . _('Amount') . '</th>';
echo '<th>' . _('Request Date') . '</th>';
echo '<th>' . _('Actions') . '</th>';
echo '</tr></thead><tbody>';
while($row = DB_fetch_array($pending_result)) {
    echo '<tr>';
    echo '<td><strong>' . $row['encashment_ref'] . '</strong></td>';
    echo '<td>' . $row['emp_name'] . '</td>';
    echo '<td>' . $row['leavetype_name'] . '</td>';
    echo '<td class="text-right">' . $row['days_encashed'] . '</td>';
    echo '<td class="text-right">KES ' . number_format($row['gross_amount'], 2) . '</td>';
    echo '<td>' . ConvertSQLDate($row['request_date']) . '</td>';
    echo '<td>';
    echo '<form method="post" style="display:inline;">';
    echo '<input type="hidden" name="encashment_id" value="' . $row['encashment_id'] . '" />';
    echo '<select name="encashment_status" class="form-control form-control-sm" style="width:auto;display:inline;">';
    echo '<option value="APPROVED">Approve</option>';
    echo '<option value="REJECTED">Reject</option>';
    echo '</select> ';
    echo '<input type="text" name="rejection_reason" placeholder="Reason" class="form-control form-control-sm" style="width:150px;display:inline;" /> ';
    echo '<button type="submit" name="approve_encashment" class="btn btn-sm btn-success">Go</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr>';
}
if(DB_num_rows($pending_result) == 0) {
    echo '<tr><td colspan="7" class="text-center text-muted">' . _('No pending encashment requests') . '</td></tr>';
}
echo '</tbody></table>';
echo '</div>';
echo '</div>';

echo '<div class="tab-pane" id="history">';
$history_result = DB_query("SELECT le.*, CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name, lt.leavetype_name
                            FROM prlleaveencashments le
                            INNER JOIN prlemployeemaster e ON le.pfno = e.pf_no
                            INNER JOIN prlleavetypes lt ON le.leavetype_id = lt.leavetype_id
                            ORDER BY le.request_date DESC LIMIT 50", $db);

echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Ref') . '</th>';
echo '<th>' . _('Employee') . '</th>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th>' . _('Days') . '</th>';
echo '<th>' . _('Amount') . '</th>';
echo '<th>' . _('Request Date') . '</th>';
echo '<th>' . _('Status') . '</th>';
echo '</tr></thead><tbody>';
while($row = DB_fetch_array($history_result)) {
    $status_class = $row['status'] == 'APPROVED' ? 'success' : ($row['status'] == 'REJECTED' ? 'danger' : 'warning');
    echo '<tr>';
    echo '<td><strong>' . $row['encashment_ref'] . '</strong></td>';
    echo '<td>' . $row['emp_name'] . '</td>';
    echo '<td>' . $row['leavetype_name'] . '</td>';
    echo '<td class="text-right">' . $row['days_encashed'] . '</td>';
    echo '<td class="text-right">KES ' . number_format($row['gross_amount'], 2) . '</td>';
    echo '<td>' . ConvertSQLDate($row['request_date']) . '</td>';
    echo '<td><span class="badge badge-' . $status_class . '">' . _($row['status']) . '</span></td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

echo '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">';
echo '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
echo '<script>$(document).ready(function() { $(".select2").select2(); });</script>';

include('includes/footer.inc');
?>
