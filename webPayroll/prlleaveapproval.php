<?php
include('includes/session.inc');
$Title = _('Leave Approval');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('ExtFunc/Payrollfunctions.php');

$PageSecurity = 1;

if(isset($_POST['approve_leave'])) {
    $ref = $_POST['application_ref'];
    $status = $_POST['new_status'];
    $comments = $_POST['approval_comments'];
    $pfno = $_SESSION['pfno'] ?? $_SESSION['UserID'];
    
    $sql = sprintf("UPDATE prlleaveapprovaltrans 
                    SET approval_status = '%s', 
                        approval_date = NOW(), 
                        comments = '%s'
                    WHERE application_ref = '%s' 
                    AND approver_pfno = '%s' 
                    AND approval_status = 'PENDING'
                    ORDER BY sequence_order ASC LIMIT 1",
                    $status, $comments, $ref, $pfno);
    DB_query($sql, $db);
    
    if($status == 'REJECTED') {
        $sql = sprintf("UPDATE prlleaveapplications SET status = 'REJECTED', rejection_reason = '%s' WHERE application_ref = '%s'",
                        $comments, $ref);
        DB_query($sql, $db);
        
        $app_result = DB_query("SELECT pfno, leavetype_id, applied_days, year FROM prlleaveapplications WHERE application_ref = '$ref'", $db);
        $app = DB_fetch_array($app_result);
        
        DB_query("UPDATE prlleavebalances SET pending_days = pending_days - " . $app['applied_days'] . "
                 WHERE pfno = '" . $app['pfno'] . "' AND leavetype_id = " . $app['leavetype_id'] . " AND year = " . $app['year'], $db);
    } else {
        $pending_result = DB_query("SELECT approval_id FROM prlleaveapprovaltrans WHERE application_ref = '$ref' AND approval_status = 'PENDING'", $db);
        
        if(DB_num_rows($pending_result) == 0) {
            $sql = "UPDATE prlleaveapplications SET status = 'APPROVED' WHERE application_ref = '$ref'";
            DB_query($sql, $db);
            
            $app_result = DB_query("SELECT pfno, leavetype_id, applied_days, year FROM prlleaveapplications WHERE application_ref = '$ref'", $db);
            $app = DB_fetch_array($app_result);
            
            DB_query("UPDATE prlleavebalances SET pending_days = pending_days - " . $app['applied_days'] . ", used_days = used_days + " . $app['applied_days'] . "
                     WHERE pfno = '" . $app['pfno'] . "' AND leavetype_id = " . $app['leavetype_id'] . " AND year = " . $app['year'], $db);
            
            sendApprovalNotification($ref, 'APPROVED', $db);
        } else {
            $sql = "UPDATE prlleaveapplications SET status = 'PENDING_HOD' WHERE application_ref = '$ref' AND status = 'PENDING'";
            DB_query($sql, $db);
        }
    }
    
    prnMsg(_('Leave application updated successfully'), 'success');
}

function sendApprovalNotification($ref, $status, $db, $comments = '') {
    $sql = "SELECT la.*, e.email, lt.leavetype_name,
            CONCAT(e2.fname, ' ', e2.mname, ' ', e2.lname) as emp_name
            FROM prlleaveapplications la
            INNER JOIN prlemployeemaster e ON la.pfno = e.pf_no
            INNER JOIN prlemployeemaster e2 ON la.pfno = e2.pf_no
            INNER JOIN prlleavetypes lt ON la.leavetype_id = lt.leavetype_id
            WHERE la.application_ref = '$ref'";
    $result = DB_query($sql, $db);
    $app = DB_fetch_array($result);
    
    if($status == 'APPROVED') {
        $message = "<h3>Leave Application Approved</h3>";
        $message .= "<p>Dear " . $app['emp_name'] . ",</p>";
        $message .= "<p>Your leave application for <strong>" . $app['leavetype_name'] . "</strong> has been <strong>APPROVED</strong>.</p>";
        $message .= "<p><strong>Details:</strong></p>";
        $message .= "<ul>";
        $message .= "<li><strong>Reference:</strong> " . $ref . "</li>";
        $message .= "<li><strong>From:</strong> " . ConvertSQLDate($app['from_date']) . "</li>";
        $message .= "<li><strong>To:</strong> " . ConvertSQLDate($app['to_date']) . "</li>";
        $message .= "<li><strong>Days:</strong> " . $app['applied_days'] . "</li>";
        $message .= "<li><strong>Expected Return:</strong> " . ConvertSQLDate($app['expected_return_date']) . "</li>";
        $message .= "</ul>";
        if($comments) {
            $message .= "<p><strong>Comments:</strong> " . $comments . "</p>";
        }
        $message .= "<p>Please ensure proper handover before your leave period.</p>";
        $message .= "<p>Kind regards,<br/>HR Department</p>";
    } else {
        $message = "<h3>Leave Application Rejected</h3>";
        $message .= "<p>Dear " . $app['emp_name'] . ",</p>";
        $message .= "<p>Your leave application for <strong>" . $app['leavetype_name'] . "</strong> has been <strong>REJECTED</strong>.</p>";
        $message .= "<p><strong>Reference:</strong> " . $ref . "</p>";
        if($comments) {
            $message .= "<p><strong>Reason:</strong> " . $comments . "</p>";
        }
        $message .= "<p>If you have any questions, please contact HR.</p>";
    }
    
    CreateLeaveNotification($ref, strtoupper($status), $app['pfno'], $message, 'EMAIL', $db);
    
    $approvers_result = DB_query("SELECT DISTINCT approver_pfno FROM prlleaveapprovaltrans WHERE application_ref = '$ref' AND approval_status = 'PENDING'", $db);
    while($next_approver = DB_fetch_array($approvers_result)) {
        $message = "<h3>Leave Application Pending Your Approval</h3>";
        $message .= "<p><strong>Employee:</strong> " . $app['emp_name'] . "</p>";
        $message .= "<p><strong>Leave Type:</strong> " . $app['leavetype_name'] . "</p>";
        $message .= "<p><strong>From:</strong> " . ConvertSQLDate($app['from_date']) . "</p>";
        $message .= "<p><strong>To:</strong> " . ConvertSQLDate($app['to_date']) . "</p>";
        $message .= "<p><strong>Days:</strong> " . $app['applied_days'] . "</p>";
        $message .= "<p><a href='" . $RootPath . "/prlleaveapproval.php?view=" . $ref . "'>Click here to review</a></p>";
        
        CreateLeaveNotification($ref, 'APPLICATION', $next_approver['approver_pfno'], $message, 'EMAIL', $db);
    }
}

$approver_pfno = $_SESSION['pfno'] ?? $_SESSION['UserID'];

$filter_status = $_POST['filter_status'] ?? 'pending';
$filter_year = $_POST['filter_year'] ?? date('Y');

echo '<div class="container-fluid">';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . _('Leave Approvals') . '" alt="" /> ' . $Title . '</p>';

echo '<ul class="nav nav-tabs mb-3">';
echo '<li class="nav-item"><a class="nav-link ' . ($filter_status == 'pending' ? 'active' : '') . '" href="?status=pending">' . _('Pending Approvals') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link ' . ($filter_status == 'approved' ? 'active' : '') . '" href="?status=approved">' . _('Approved') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link ' . ($filter_status == 'rejected' ? 'active' : '') . '" href="?status=rejected">' . _('Rejected') . '</a></li>';
echo '<li class="nav-item"><a class="nav-link ' . ($filter_status == 'all' ? 'active' : '') . '" href="?status=all">' . _('All Applications') . '</a></li>';
echo '</ul>';

if(isset($_GET['view'])) {
    $ref = $_GET['view'];
    
    $sql = "SELECT la.*, lt.leavetype_name, lt.leavetype_code, lt.color_code, lt.requires_medical_certificate,
            CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name, e.department,
            d.name as dept_name, e.email as emp_email, e.telno
            FROM prlleaveapplications la
            INNER JOIN prlemployeemaster e ON la.pfno = e.pf_no
            INNER JOIN prlleavetypes lt ON la.leavetype_id = lt.leavetype_id
            LEFT JOIN prldepartments d ON e.department = d.code
            WHERE la.application_ref = '$ref'";
    $result = DB_query($sql, $db);
    $app = DB_fetch_array($result);
    
    if($app) {
        echo '<div class="card">';
        echo '<div class="card-header" style="background-color: ' . $app['color_code'] . '; color: white;">';
        echo '<h5 class="mb-0">' . _('Leave Application Details') . ' - ' . $app['application_ref'] . '</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<table class="table">';
        echo '<tr><th>' . _('Employee') . ':</th><td>' . $app['emp_name'] . '</td></tr>';
        echo '<tr><th>' . _('Department') . ':</th><td>' . $app['dept_name'] . '</td></tr>';
        echo '<tr><th>' . _('Leave Type') . ':</th><td>' . $app['leavetype_name'] . '</td></tr>';
        echo '<tr><th>' . _('From Date') . ':</th><td>' . ConvertSQLDate($app['from_date']) . '</td></tr>';
        echo '<tr><th>' . _('To Date') . ':</th><td>' . ConvertSQLDate($app['to_date']) . '</td></tr>';
        echo '<tr><th>' . _('Number of Days') . ':</th><td><strong>' . $app['applied_days'] . '</strong></td></tr>';
        echo '<tr><th>' . _('Expected Return') . ':</th><td>' . ConvertSQLDate($app['expected_return_date']) . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<table class="table">';
        echo '<tr><th>' . _('Status') . ':</th><td><span class="badge badge-' . getStatusBadge($app['status']) . '">' . _($app['status']) . '</span></td></tr>';
        echo '<tr><th>' . _('Is Paid') . ':</th><td>' . ($app['is_paid'] ? _('Yes') : _('No')) . '</td></tr>';
        if($app['handover_to']) {
            $handover = GetEmployee($app['handover_to']);
            $handover_name = GetEmployeeFullName($app['handover_to']);
            echo '<tr><th>' . _('Handed Over To') . ':</th><td>' . $handover_name . '</td></tr>';
        }
        if($app['requires_medical_certificate']) {
            echo '<tr><th>' . _('Medical Certificate') . ':</th><td>' . ($app['medical_certificate_no'] ?: _('Not provided')) . '</td></tr>';
        }
        if($app['reason']) {
            echo '<tr><th>' . _('Reason') . ':</th><td>' . $app['reason'] . '</td></tr>';
        }
        if($app['rejection_reason']) {
            echo '<tr><th>' . _('Rejection Reason') . ':</th><td class="text-danger">' . $app['rejection_reason'] . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
        echo '</div>';
        
        $approval_sql = "SELECT lat.*, 
                        CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as approver_name,
                        p.name as position
                        FROM prlleaveapprovaltrans lat
                        LEFT JOIN prlemployeemaster e ON lat.approver_pfno = e.pf_no
                        LEFT JOIN prlpositions p ON e.position = p.code
                        WHERE lat.application_ref = '$ref'
                        ORDER BY lat.sequence_order";
        $approval_result = DB_query($approval_sql, $db);
        
        echo '<h5>' . _('Approval History') . '</h5>';
        echo '<table class="table table-bordered">';
        echo '<thead><tr><th>Level</th><th>Approver</th><th>Status</th><th>Date</th><th>Comments</th></tr></thead><tbody>';
        while($approval = DB_fetch_array($approval_result)) {
            echo '<tr>';
            echo '<td>' . $approval['approval_level'] . '</td>';
            echo '<td>' . ($approval['approver_name'] ?: '-') . '<br><small>' . ($approval['position'] ?: '') . '</small></td>';
            echo '<td><span class="badge badge-' . getApprovalBadge($approval['approval_status']) . '">' . _($approval['approval_status']) . '</span></td>';
            echo '<td>' . ($approval['approval_date'] ? ConvertSQLDate($approval['approval_date']) : '-') . '</td>';
            echo '<td>' . ($approval['comments'] ?: '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        $can_approve = false;
        $pending_approval = DB_query("SELECT approval_id FROM prlleaveapprovaltrans 
                                       WHERE application_ref = '$ref' AND approver_pfno = '$approver_pfno' AND approval_status = 'PENDING'", $db);
        if(DB_num_rows($pending_approval) > 0) {
            $can_approve = true;
        }
        
        if($can_approve && $app['status'] != 'REJECTED' && $app['status'] != 'COMPLETED') {
            echo '<hr/><form method="post">';
            echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
            echo '<input type="hidden" name="application_ref" value="' . $ref . '" />';
            echo '<div class="form-group">';
            echo '<label>' . _('Decision') . '</label>';
            echo '<select name="new_status" class="form-control" required>';
            echo '<option value="APPROVED">' . _('Approve') . '</option>';
            echo '<option value="REJECTED">' . _('Reject') . '</option>';
            echo '</select>';
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label>' . _('Comments') . '</label>';
            echo '<textarea name="approval_comments" class="form-control" rows="3"></textarea>';
            echo '</div>';
            echo '<button type="submit" name="approve_leave" class="btn btn-success">' . _('Submit Decision') . '</button>';
            echo '</form>';
        }
        
        echo '</div></div>';
    }
} else {
    $status_filter = '';
    switch($filter_status) {
        case 'pending':
            $status_filter = "AND lat.approver_pfno = '$approver_pfno' AND lat.approval_status = 'PENDING'";
            break;
        case 'approved':
            $status_filter = "AND la.status IN ('APPROVED', 'COMPLETED')";
            break;
        case 'rejected':
            $status_filter = "AND la.status = 'REJECTED'";
            break;
    }
    
    $sql = "SELECT DISTINCT la.application_ref, la.pfno, la.leavetype_id, la.applied_days, 
            la.from_date, la.to_date, la.status, la.year,
            CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name,
            lt.leavetype_name, lt.color_code,
            d.name as dept_name
            FROM prlleaveapplications la
            INNER JOIN prlemployeemaster e ON la.pfno = e.pf_no
            INNER JOIN prlleavetypes lt ON la.leavetype_id = lt.leavetype_id
            LEFT JOIN prldepartments d ON e.department = d.code
            LEFT JOIN prlleaveapprovaltrans lat ON la.application_ref = lat.application_ref
            WHERE la.year = $filter_year $status_filter
            ORDER BY la.from_date DESC";
    $result = DB_query($sql, $db);
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-striped">';
    echo '<thead class="thead-dark"><tr>';
    echo '<th>' . _('Ref') . '</th>';
    echo '<th>' . _('Employee') . '</th>';
    echo '<th>' . _('Department') . '</th>';
    echo '<th>' . _('Leave Type') . '</th>';
    echo '<th>' . _('From') . '</th>';
    echo '<th>' . _('To') . '</th>';
    echo '<th>' . _('Days') . '</th>';
    echo '<th>' . _('Status') . '</th>';
    echo '<th>' . _('Actions') . '</th>';
    echo '</tr></thead><tbody>';
    
    while($row = DB_fetch_array($result)) {
        echo '<tr>';
        echo '<td><strong>' . $row['application_ref'] . '</strong></td>';
        echo '<td>' . $row['emp_name'] . '</td>';
        echo '<td>' . ($row['dept_name'] ?: '-') . '</td>';
        echo '<td><span class="badge" style="background-color: ' . $row['color_code'] . '; color: white;">' . $row['leavetype_name'] . '</span></td>';
        echo '<td>' . ConvertSQLDate($row['from_date']) . '</td>';
        echo '<td>' . ConvertSQLDate($row['to_date']) . '</td>';
        echo '<td class="text-center">' . $row['applied_days'] . '</td>';
        echo '<td><span class="badge badge-' . getStatusBadge($row['status']) . '">' . _($row['status']) . '</span></td>';
        echo '<td>';
        echo '<a href="?view=' . $row['application_ref'] . '" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>';
        echo '</td>';
        echo '</tr>';
    }
    
    if(DB_num_rows($result) == 0) {
        echo '<tr><td colspan="9" class="text-center text-muted">' . _('No applications found') . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

function getStatusBadge($status) {
    $badges = [
        'PENDING' => 'warning',
        'PENDING_HOD' => 'warning',
        'PENDING_HR' => 'warning',
        'APPROVED' => 'success',
        'REJECTED' => 'danger',
        'CANCELLED' => 'secondary',
        'COMPLETED' => 'info'
    ];
    return $badges[$status] ?? 'secondary';
}

function getApprovalBadge($status) {
    $badges = [
        'PENDING' => 'warning',
        'APPROVED' => 'success',
        'REJECTED' => 'danger',
        'ESCALATED' => 'info'
    ];
    return $badges[$status] ?? 'secondary';
}

echo '</div>';
include('includes/footer.inc');
?>
