<?php
include('includes/session.inc');
$Title = _('Leave Escalation Rules');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

if(isset($_POST['save_escalation'])) {
    DB_query("DELETE FROM prlleaveescalations", $db);
    
    foreach($_POST['escalation_days'] as $key => $days) {
        if($days > 0) {
            $sql = sprintf("INSERT INTO prlleaveescalations (escalation_id, leavetype_id, escalation_level, escalation_days, escalation_to, escalation_position, message)
                VALUES (%d, %d, %d, %d, '%s', %d, '%s')",
                $key + 1,
                (int)$_POST['leavetype_id'][$key],
                (int)$_POST['escalation_level'][$key],
                (int)$days,
                $_POST['escalation_to'][$key],
                (int)($_POST['escalation_position'][$key] ?: 0),
                addslashes($_POST['message'][$key])
            );
            DB_query($sql, $db);
        }
    }
    prnMsg(_('Escalation rules updated successfully'), 'success');
}

echo '<div class="container-fluid">';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Escalation Rules') . '" alt="" /> ' . $Title . '</p>';

echo '<div class="alert alert-info">';
echo '<h5>How Escalation Works</h5>';
echo '<ul>';
echo '<li>When a leave application is not acted upon within the specified days, it is automatically escalated.</li>';
echo '<li>Escalation can go to HR, a Manager, or a specific user.</li>';
echo '<li>The original approver will be notified that the request was escalated.</li>';
echo '<li>Multiple escalation levels can be configured for long-pending requests.</li>';
echo '</ul>';
echo '</div>';

$sql = "SELECT * FROM prlleaveescalations ORDER BY leavetype_id, escalation_level";
$escalations_result = DB_query($sql, $db);
$existing_escalations = [];
while($row = DB_fetch_array($escalations_result)) {
    $existing_escalations[$row['leavetype_id']][$row['escalation_level']] = $row;
}

echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table class="table table-bordered">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th>' . _('Escalation Level') . '</th>';
echo '<th>' . _('Days Pending') . '</th>';
echo '<th>' . _('Escalate To') . '</th>';
echo '<th>' . _('Position/Role') . '</th>';
echo '<th>' . _('Notification Message') . '</th>';
echo '</tr></thead><tbody>';

$sql = "SELECT leavetype_id, leavetype_name FROM prlleavetypes WHERE active = 1 ORDER BY sort_order";
$leavetypes_result = DB_query($sql, $db);

$counter = 0;
while($lt = DB_fetch_array($leavetypes_result)) {
    for($level = 1; $level <= 3; $level++) {
        $existing = $existing_escalations[$lt['leavetype_id']][$level] ?? [];
        
        echo '<tr>';
        echo '<td>';
        if($level == 1) {
            echo '<strong>' . $lt['leavetype_name'] . '</strong>';
        }
        echo '<input type="hidden" name="leavetype_id[' . $counter . ']" value="' . $lt['leavetype_id'] . '" />';
        echo '</td>';
        
        echo '<td><strong>Level ' . $level . '</strong><input type="hidden" name="escalation_level[' . $counter . ']" value="' . $level . '" /></td>';
        
        echo '<td><input type="number" name="escalation_days[' . $counter . ']" class="form-control" value="' . ($existing['escalation_days'] ?? '') . '" placeholder="e.g. 3" min="0" /></td>';
        
        echo '<td>';
        echo '<select name="escalation_to[' . $counter . ']" class="form-control">';
        echo '<option value="HR">HR Department</option>';
        echo '<option value="MANAGER">Department Manager</option>';
        echo '<option value="SKIP">Skip Original Approver</option>';
        $users_result = DB_query("SELECT userid, realname FROM www_users WHERE blocked = 0 ORDER BY realname", $db);
        while($user = DB_fetch_array($users_result)) {
            $selected = ($existing['escalation_to'] ?? '') == $user['userid'] ? ' selected' : '';
            echo '<option value="' . $user['userid'] . '"' . $selected . '>' . $user['realname'] . '</option>';
        }
        echo '</select>';
        echo '</td>';
        
        echo '<td>';
        echo '<select name="escalation_position[' . $counter . ']" class="form-control">';
        echo '<option value="0">Any Position</option>';
        $positions = GetPositions();
        foreach($positions as $pos) {
            $selected = ($existing['escalation_position'] ?? 0) == $pos['code'] ? ' selected' : '';
            echo '<option value="' . $pos['code'] . '"' . $selected . '>' . $pos['name'] . '</option>';
        }
        echo '</select>';
        echo '</td>';
        
        echo '<td><input type="text" name="message[' . $counter . ']" class="form-control" value="' . htmlspecialchars($existing['message'] ?? '') . '" placeholder="Optional message" /></td>';
        
        echo '</tr>';
        $counter++;
    }
    echo '<tr><td colspan="6"><hr/></td></tr>';
}

echo '</tbody></table>';
echo '<button type="submit" name="save_escalation" class="btn btn-primary btn-lg">' . _('Save Escalation Rules') . '</button>';
echo '</form>';

echo '<hr/>';
echo '<h4>' . _('Process Escalations') . '</h4>';
echo '<p>Escalations are processed automatically. To manually process pending escalations:</p>';

if(isset($_POST['process_escalations'])) {
    $processed = ProcessEscalations($db);
    prnMsg(sprintf(_('Processed %d escalations'), $processed), 'success');
}

echo '<form method="post">';
echo '<button type="submit" name="process_escalations" class="btn btn-warning">' . _('Process Pending Escalations Now') . '</button>';
echo '</form>';

echo '<h5>' . _('Pending Escalations') . '</h5>';
$pending_sql = "SELECT la.application_ref, la.pfno, CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name,
                lt.leavetype_name, la.from_date, DATEDIFF(CURDATE(), la.from_date) as days_pending,
                la.status
                FROM prlleaveapplications la
                INNER JOIN prlemployeemaster e ON la.pfno = e.pf_no
                INNER JOIN prlleavetypes lt ON la.leavetype_id = lt.leavetype_id
                WHERE la.status IN ('PENDING', 'PENDING_HOD')
                ORDER BY days_pending DESC";
$pending_result = DB_query($pending_sql, $db);

echo '<table class="table table-bordered table-striped">';
echo '<thead><tr><th>Ref</th><th>Employee</th><th>Leave Type</th><th>Applied</th><th>Days Pending</th><th>Status</th></tr></thead><tbody>';
while($row = DB_fetch_array($pending_result)) {
    $badge_class = $row['days_pending'] > 5 ? 'danger' : ($row['days_pending'] > 3 ? 'warning' : 'info');
    echo '<tr>';
    echo '<td>' . $row['application_ref'] . '</td>';
    echo '<td>' . $row['emp_name'] . '</td>';
    echo '<td>' . $row['leavetype_name'] . '</td>';
    echo '<td>' . ConvertSQLDate($row['from_date']) . '</td>';
    echo '<td><span class="badge badge-' . $badge_class . '">' . $row['days_pending'] . ' days</span></td>';
    echo '<td>' . $row['status'] . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

echo '</div>';
include('includes/footer.inc');

function ProcessEscalations($db) {
    $processed = 0;
    
    $sql = "SELECT la.application_ref, la.pfno, la.leavetype_id, DATEDIFF(CURDATE(), la.from_date) as days_pending,
            CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name, lt.leavetype_name,
            e.department
            FROM prlleaveapplications la
            INNER JOIN prlemployeemaster e ON la.pfno = e.pf_no
            INNER JOIN prlleavetypes lt ON la.leavetype_id = lt.leavetype_id
            WHERE la.status IN ('PENDING', 'PENDING_HOD')
            AND la.escalated = 0";
    $result = DB_query($sql, $db);
    
    while($app = DB_fetch_array($result)) {
        $escalation_sql = "SELECT * FROM prlleaveescalations 
                          WHERE leavetype_id = " . $app['leavetype_id'] . "
                          AND escalation_days <= " . $app['days_pending'] . "
                          ORDER BY escalation_level ASC LIMIT 1";
        $escalation_result = DB_query($escalation_sql, $db);
        
        if($escalation = DB_fetch_array($escalation_result)) {
            $escalate_to_pfno = getEscalationUser($escalation, $app, $db);
            
            if($escalate_to_pfno) {
                $sql = sprintf("INSERT INTO prlleaveapprovaltrans 
                    (application_ref, approval_level, approver_pfno, department, approval_status, sequence_order, escalated)
                    VALUES ('%s', %d, '%s', %d, 'PENDING', %d, 1)",
                    $app['application_ref'],
                    $escalation['escalation_id'],
                    $escalate_to_pfno,
                    $app['department'],
                    $escalation['escalation_level'] + 10
                );
                DB_query($sql, $db);
                
                DB_query("UPDATE prlleaveapplications SET escalated = 1 WHERE application_ref = '" . $app['application_ref'] . "'", $db);
                
                CreateLeaveNotification($app['application_ref'], 'ESCALATION', $escalate_to_pfno, 
                    "Leave application $app[application_ref] for $app[emp_name] ($app[leavetype_name]) has been escalated after $app[days_pending] days pending.", $db);
                
                $processed++;
            }
        }
    }
    
    return $processed;
}

function getEscalationUser($escalation, $app, $db) {
    switch($escalation['escalation_to']) {
        case 'HR':
            $sql = "SELECT e.pf_no FROM prlemployeemaster e
                    INNER JOIN prlpositions p ON e.position = p.code
                    WHERE p.name LIKE '%HR%' OR p.name LIKE '%Human Resource%'
                    LIMIT 1";
            break;
        case 'MANAGER':
            $sql = "SELECT hod FROM prldepartments WHERE code = " . $app['department'];
            break;
        default:
            return $escalation['escalation_to'];
    }
    
    $result = DB_query($sql, $db);
    if($row = DB_fetch_array($result)) {
        return $row[0];
    }
    
    return null;
}
?>
