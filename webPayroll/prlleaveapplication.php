<?php
include('includes/session.inc');
$Title = _('Leave Application');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('ExtFunc/Payrollfunctions.php');

$PageSecurity = 1;

if(isset($_GET['emp'])) {
    $_POST['selected_pfno'] = $_GET['emp'];
}

if(isset($_SESSION['pfno'])) {
    $_POST['selected_pfno'] = $_SESSION['pfno'];
}

if(isset($_POST['selected_pfno'])) {
    $emp_pfno = $_POST['selected_pfno'];
} else {
    $emp_pfno = '';
}

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

function generateApplicationRef() {
    return 'LV' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

function getLeaveBalance($pfno, $leavetype_id, $year) {
    global $db;
    $sql = "SELECT available_days, entitled_days, carried_over_days, used_days, pending_days 
            FROM prlleavebalances 
            WHERE pfno = '$pfno' AND leavetype_id = $leavetype_id AND year = $year";
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function getEmployeeInfo($pfno) {
    global $db;
    $sql = "SELECT e.*, d.name as dept_name, p.name as position_name
            FROM prlemployeemaster e
            LEFT JOIN prldepartments d ON e.department = d.code
            LEFT JOIN prlpositions p ON e.position = p.code
            WHERE e.pf_no = '$pfno'";
    $result = DB_query($sql, $db);
    return DB_fetch_array($result);
}

function getExpectedReturnDate($from_date, $days) {
    global $db;
    $from_ts = strtotime($from_date);
    $days_counted = 0;
    
    $holidays_result = DB_query("SELECT holiday_date FROM prlkenyaholidays WHERE year = " . date('Y', $from_ts), $db);
    $holidays = [];
    while($row = DB_fetch_array($holidays_result)) {
        $holidays[] = $row['holiday_date'];
    }
    
    while($days_counted < $days) {
        $from_ts = strtotime('+1 day', $from_ts);
        $day_of_week = date('w', $from_ts);
        $current_date = date('Y-m-d', $from_ts);
        
        if($day_of_week != 0 && $day_of_week != 6 && !in_array($current_date, $holidays)) {
            $days_counted++;
        }
    }
    
    return date('Y-m-d', $from_ts);
}

if(isset($_POST['submit_application'])) {
    $errors = 0;
    
    if(empty($_POST['leavetype_id'])) {
        prnMsg(_('Please select a leave type'), 'error');
        $errors++;
    }
    if(empty($_POST['from_date'])) {
        prnMsg(_('Please select a start date'), 'error');
        $errors++;
    }
    if(empty($_POST['no_of_days']) || $_POST['no_of_days'] <= 0) {
        prnMsg(_('Please enter the number of days'), 'error');
        $errors++;
    }
    
    $year = date('Y', strtotime($_POST['from_date']));
    $balance = getLeaveBalance($_POST['selected_pfno'], $_POST['leavetype_id'], $year);
    
    if($balance && $_POST['no_of_days'] > $balance['available_days']) {
        prnMsg(_('Insufficient leave balance. Available: ') . $balance['available_days'], 'error');
        $errors++;
    }
    
    $leavetype_check = DB_query("SELECT * FROM prlleavetypes WHERE leavetype_id = " . (int)$_POST['leavetype_id'], $db);
    $leavetype = DB_fetch_array($leavetype_check);
    
    if($leavetype['requires_medical_certificate'] && $_POST['no_of_days'] > 3) {
        if(empty($_POST['medical_cert_no']) || empty($_POST['medical_cert_date'])) {
            prnMsg(_('Medical certificate number and date are REQUIRED for sick leave exceeding 3 days as per Kenyan Employment Act'), 'error');
            $errors++;
        }
    }
    
    if($errors == 0) {
        $app_ref = generateApplicationRef();
        $to_date = getExpectedReturnDate($_POST['from_date'], $_POST['no_of_days'] - 1);
        $expected_return = date('Y-m-d', strtotime('+1 day', strtotime($to_date)));
        
        $sql = sprintf("INSERT INTO prlleaveapplications 
            (application_ref, pfno, leavetype_id, applied_days, from_date, to_date, 
             expected_return_date, handover_to, reason, medical_certificate_no, medical_certificate_date,
             status, is_paid, year, created_by, requires_hr_approval)
            VALUES ('%s', '%s', %d, %d, '%s', '%s', '%s', %s, '%s', %s, %s, 
                    'PENDING', %d, %d, '%s', %d)",
            $app_ref,
            $_POST['selected_pfno'],
            (int)$_POST['leavetype_id'],
            (int)$_POST['no_of_days'],
            $_POST['from_date'],
            $to_date,
            $expected_return,
            !empty($_POST['handover_to']) ? "'" . $_POST['handover_to'] . "'" : 'NULL',
            $_POST['reason'],
            !empty($_POST['medical_cert_no']) ? "'" . $_POST['medical_cert_no'] . "'" : 'NULL',
            !empty($_POST['medical_cert_date']) ? "'" . $_POST['medical_cert_date'] . "'" : 'NULL',
            $leavetype['requires_medical_certificate'] && $_POST['no_of_days'] > 3 ? 0 : 1,
            $year,
            $_SESSION['UserID'],
            ($leavetype['requires_medical_certificate'] && $_POST['no_of_days'] > 3) ? 1 : 0
        );
        
        DB_query($sql, $db);
        
        DB_query("UPDATE prlleavebalances SET pending_days = pending_days + " . (int)$_POST['no_of_days'] . "
                  WHERE pfno = '" . $_POST['selected_pfno'] . "' AND leavetype_id = " . (int)$_POST['leavetype_id'] . " AND year = $year", $db);
        
        createApprovalWorkflow($app_ref, $_POST['selected_pfno'], (int)$_POST['leavetype_id'], $_POST['no_of_days'], $db);
        
        prnMsg(_('Leave application submitted successfully. Reference: ') . $app_ref, 'success');
        unset($_POST);
    }
}

function createApprovalWorkflow($app_ref, $pfno, $leavetype_id, $days_applied, $db) {
    $emp = GetEmployee($pfno);
    $emp_dept = $emp['department'];
    
    $workflow_sql = "SELECT * FROM prlleaveworkflows WHERE leavetype_id = $leavetype_id AND active = 1 ORDER BY approval_level";
    $workflow_result = DB_query($workflow_sql, $db);
    
    $level = 1;
    $requires_hr = false;
    
    while($workflow = DB_fetch_array($workflow_result)) {
        $approver_pfno = null;
        
        if($workflow['approver_type'] == 'HOD') {
            $dept = GetDepartment($emp_dept);
            if($dept && !empty($dept['hod'])) {
                $approver_pfno = $dept['hod'];
            }
        } elseif($workflow['approver_type'] == 'SPECIFIC_USER' && !empty($workflow['approver_pfno'])) {
            $approver_pfno = $workflow['approver_pfno'];
        }
        
        if($approver_pfno) {
            $sql = sprintf("INSERT INTO prlleaveapprovaltrans 
                (application_ref, approval_level, approver_pfno, department, approval_status, sequence_order)
                VALUES ('%s', %d, '%s', %d, 'PENDING', %d)",
                $app_ref, $level, $approver_pfno, $emp_dept, $level
            );
            DB_query($sql, $db);
        }
        $level++;
    }
    
    $lt_result = DB_query("SELECT leavetype_code, requires_medical_certificate FROM prlleavetypes WHERE leavetype_id = $leavetype_id", $db);
    $lt = DB_fetch_array($lt_result);
    
    if(($lt['requires_medical_certificate'] && $days_applied > 3) || 
       $lt['leavetype_code'] == 'SICK_FULL' || $lt['leavetype_code'] == 'SICK_HALF' ||
       $lt['leavetype_code'] == 'MATERNITY') {
        $requires_hr = true;
    }
    
    if($requires_hr) {
        $hr_result = DB_query("SELECT e.pf_no FROM prlemployeemaster e
                              INNER JOIN prlpositions p ON e.position = p.code
                              WHERE p.name LIKE '%HR%' OR p.name LIKE '%Human Resource%' OR p.name LIKE '%Director%'
                              LIMIT 1", $db);
        if($hr = DB_fetch_array($hr_result)) {
            $sql = sprintf("INSERT INTO prlleaveapprovaltrans 
                (application_ref, approval_level, approver_pfno, department, approval_status, sequence_order)
                VALUES ('%s', %d, '%s', %d, 'PENDING', %d)",
                $app_ref, $level, $hr['pf_no'], $emp_dept, $level
            );
            DB_query($sql, $db);
        }
    }
}

$current_year = date('Y');
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-calendar-plus"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Submit a new leave request for approval'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <?php if(!isset($_POST['selected_pfno']) && !isset($_GET['emp'])): ?>
            
            <div class="sp-card" style="max-width: 600px; margin: 0 auto;">
                <div class="sp-card-header">
                    <i class="fas fa-user"></i>
                    <h3><?php echo _('Select Employee'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <form method="post">
                        <div class="sp-form-group">
                            <label class="sp-label"><?php echo _('Employee'); ?></label>
                            <select name="selected_pfno" class="sp-select" onchange="this.form.submit();" style="font-size: 16px; padding: 12px;">
                                <option value="">-- <?php echo _('Select Employee'); ?> --</option>
                                <?php
                                $sql = "SELECT pf_no, CONCAT(fname, ' ', mname, ' ', lname) as name, department
                                        FROM prlemployeemaster 
                                        WHERE (dateterminated IS NULL OR dateterminated > CURDATE()) 
                                        AND Inactive = 0
                                        ORDER BY name";
                                $result = DB_query($sql, $db);
                                while($row = DB_fetch_array($result)) {
                                    echo '<option value="' . $row['pf_no'] . '">' . $row['pf_no'] . ' - ' . $row['name'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

        <?php else:
            $emp = getEmployeeInfo($emp_pfno);
        ?>

        <div class="sp-row">
            <div class="sp-col-8">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-user-circle"></i>
                        <h3><?php echo _('Employee Information'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <div class="sp-row">
                            <div class="sp-col-6">
                                <p><strong><?php echo _('Name:'); ?></strong> <?php echo $emp['fname'] . ' ' . $emp['mname'] . ' ' . $emp['lname']; ?></p>
                            </div>
                            <div class="sp-col-6">
                                <p><strong><?php echo _('Department:'); ?></strong> <?php echo $emp['dept_name'] ?: '-'; ?></p>
                            </div>
                            <div class="sp-col-6">
                                <p><strong><?php echo _('Position:'); ?></strong> <?php echo $emp['position_name'] ?: '-'; ?></p>
                            </div>
                            <div class="sp-col-6">
                                <p><strong><?php echo _('Date Employed:'); ?></strong> <?php echo ConvertSQLDate($emp['dateemployed']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                    <div class="sp-card-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h3><?php echo _('Leave Application Form'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" id="leaveApplicationForm">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            <input type="hidden" name="selected_pfno" value="<?php echo $emp_pfno; ?>" />

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Leave Type'); ?> *</label>
                                <select name="leavetype_id" id="leavetype_id" class="sp-select" required onchange="updateBalance();">
                                    <option value="">-- <?php echo _('Select Leave Type'); ?> --</option>
                                    <?php
                                    $sql = "SELECT lt.*, lb.available_days 
                                            FROM prlleavetypes lt
                                            LEFT JOIN prlleavebalances lb ON lt.leavetype_id = lb.leavetype_id 
                                                AND lb.pfno = '$emp_pfno' AND lb.year = $current_year
                                            WHERE lt.active = 1 
                                            ORDER BY lt.sort_order";
                                    $result = DB_query($sql, $db);
                                    while($row = DB_fetch_array($result)) {
                                        $available = $row['available_days'] ?: 0;
                                        echo '<option value="' . $row['leavetype_id'] . '" data-balance="' . $available . '" data-code="' . $row['leavetype_code'] . '">';
                                        echo $row['leavetype_name'] . ' (Available: ' . $available . ' days)';
                                        echo '</option>';
                                    }
                                    ?>
                                </select>
                                <small class="sp-text-muted"><?php echo _('Balance shown may not reflect pending applications'); ?></small>
                            </div>

                            <div class="sp-row">
                                <div class="sp-col-4">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('Start Date'); ?> *</label>
                                        <input type="date" name="from_date" id="from_date" class="sp-input" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateEndDate();" />
                                    </div>
                                </div>
                                <div class="sp-col-4">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('Working Days'); ?> *</label>
                                        <input type="number" name="no_of_days" id="no_of_days" class="sp-input" required min="1" value="1" onchange="calculateEndDate();" />
                                    </div>
                                </div>
                                <div class="sp-col-4">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('End Date'); ?></label>
                                        <input type="text" id="to_date_display" class="sp-input" readonly style="background: #f8f9fa;" />
                                        <input type="hidden" name="to_date" id="to_date" />
                                    </div>
                                </div>
                            </div>

                            <div class="sp-row">
                                <div class="sp-col-6">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('Expected Return Date'); ?></label>
                                        <input type="text" id="return_date_display" class="sp-input" readonly style="background: #f8f9fa;" />
                                    </div>
                                </div>
                                <div class="sp-col-6">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('Hand Over To'); ?></label>
                                        <select name="handover_to" id="handover_to" class="sp-select">
                                            <option value="">-- <?php echo _('Select Replacement'); ?> --</option>
                                            <?php
                                            $handover_sql = "SELECT pf_no, CONCAT(fname, ' ', mname, ' ', lname) as name 
                                                              FROM prlemployeemaster 
                                                              WHERE pf_no != '$emp_pfno' 
                                                              AND (dateterminated IS NULL OR dateterminated > CURDATE())
                                                              AND Inactive = 0
                                                              ORDER BY name";
                                            $handover_result = DB_query($handover_sql, $db);
                                            while($row = DB_fetch_array($handover_result)) {
                                                echo '<option value="' . $row['pf_no'] . '">' . $row['name'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="medicalCertRow" style="display: none;">
                                <div class="sp-alert sp-alert-danger">
                                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                                    <div>
                                        <h5><?php echo _('Medical Certificate Required'); ?></h5>
                                        <p><?php echo _('As per Kenyan Employment Act, sick leave exceeding 3 days requires a medical certificate from a registered medical practitioner.'); ?></p>
                                        <div class="sp-row">
                                            <div class="sp-col-4">
                                                <label class="sp-label"><?php echo _('Certificate Number'); ?> *</label>
                                                <input type="text" name="medical_cert_no" id="medical_cert_no" class="sp-input" placeholder="MC/2024/001" />
                                            </div>
                                            <div class="sp-col-4">
                                                <label class="sp-label"><?php echo _('Certificate Date'); ?> *</label>
                                                <input type="date" name="medical_cert_date" id="medical_cert_date" class="sp-input" max="<?php echo date('Y-m-d'); ?>" />
                                            </div>
                                            <div class="sp-col-4">
                                                <label class="sp-label"><?php echo _('Issuing Hospital/Doctor'); ?></label>
                                                <input type="text" name="medical_cert_hospital" id="medical_cert_hospital" class="sp-input" placeholder="Kenyatta Hospital" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Reason / Notes'); ?></label>
                                <textarea name="reason" class="sp-textarea" rows="3" placeholder="<?php echo _('Provide additional details for your leave request...'); ?>"></textarea>
                            </div>

                            <div id="ajaxMessage" class="sp-mb-3"></div>

                            <button type="submit" name="submit_application" class="sp-btn sp-btn-success sp-btn-lg sp-btn-block">
                                <i class="fas fa-paper-plane"></i> <?php echo _('Submit Leave Application'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header" style="background: var(--sp-primary); color: white;">
                        <i class="fas fa-wallet"></i>
                        <h3><?php echo _('Leave Balances'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <?php
                        $sql = "SELECT lt.leavetype_name, lt.color_code, lt.leavetype_code, lb.available_days, lb.entitled_days, lb.used_days, lb.pending_days
                                FROM prlleavetypes lt
                                LEFT JOIN prlleavebalances lb ON lt.leavetype_id = lb.leavetype_id 
                                    AND lb.pfno = '$emp_pfno' AND lb.year = $current_year
                                WHERE lt.active = 1 AND lt.paid_leave = 1
                                ORDER BY lt.sort_order";
                        $result = DB_query($sql, $db);
                        ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php while($row = DB_fetch_array($result)):
                                $available = $row['available_days'] ?: 0;
                                $bar_class = $available > 10 ? 'sp-badge-success' : ($available > 5 ? 'sp-badge-warning' : 'sp-badge-danger');
                            ?>
                            <li style="padding: 12px 16px; border-bottom: 1px solid var(--sp-border); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span class="sp-badge" style="background: <?php echo $row['color_code']; ?>; font-size: 8px; padding: 4px 6px;">&nbsp;</span>
                                    <?php echo $row['leavetype_name']; ?>
                                    <?php if($row['leavetype_code'] == 'ANNUAL'): ?>
                                        <br><small class="sp-text-muted">Entitled: <?php echo $row['entitled_days']; ?>, Used: <?php echo $row['used_days']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="sp-badge <?php echo $bar_class; ?>"><?php echo round($available, 1); ?></span>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                    <div class="sp-card-header" style="background: var(--sp-info); color: white;">
                        <i class="fas fa-umbrella-beach"></i>
                        <h3><?php echo _('Upcoming Holidays'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <?php
                        $holidays_sql = "SELECT holiday_name, holiday_date FROM prlkenyaholidays 
                                         WHERE holiday_date >= CURDATE() AND holiday_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
                                         ORDER BY holiday_date LIMIT 5";
                        $holidays_result = DB_query($holidays_sql, $db);
                        ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php while($h = DB_fetch_array($holidays_result)):
                                $is_today = $h['holiday_date'] == date('Y-m-d');
                            ?>
                            <li style="padding: 10px 16px; border-bottom: 1px solid var(--sp-border); <?php echo $is_today ? 'background: var(--sp-bg-success);' : ''; ?>">
                                <i class="fas fa-star" style="color: var(--sp-warning); margin-right: 8px;"></i>
                                <?php echo $h['holiday_name']; ?>
                                <br><small class="sp-text-muted"><?php echo ConvertSQLDate($h['holiday_date']); ?></small>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <div class="sp-alert sp-alert-info" style="margin-top: var(--sp-spacing-md);">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong><?php echo _('Quick Tips'); ?></strong>
                        <ul style="margin: 8px 0 0; padding-left: 20px;">
                            <li><?php echo _('Weekends are excluded from leave days'); ?></li>
                            <li><?php echo _('Public holidays within leave period are excluded'); ?></li>
                            <li><?php echo _('Medical certificate required for sick leave > 3 days'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function calculateEndDate() {
    var fromDate = $("#from_date").val();
    var days = parseInt($("#no_of_days").val()) || 1;
    
    if(fromDate && days > 0) {
        $.ajax({
            url: "<?php echo $RootPath; ?>/AjaxLeaveCalculator.php",
            type: "POST",
            data: {
                action: "calculateDates",
                from_date: fromDate,
                days: days
            },
            success: function(response) {
                var data = JSON.parse(response);
                $("#to_date_display").val(data.to_date_display);
                $("#to_date").val(data.to_date);
                $("#return_date_display").val(data.return_date_display);
                
                var balance = parseFloat($("#leavetype_id option:selected").data("balance")) || 0;
                if(days > balance) {
                    $("#ajaxMessage").html('<div class="sp-alert sp-alert-warning"><i class="fas fa-exclamation-triangle"></i> Insufficient balance. You have only ' + balance + ' days available.</div>');
                } else {
                    $("#ajaxMessage").html('');
                }
            }
        });
    }
}

function updateBalance() {
    var selected = $("#leavetype_id option:selected");
    var balance = parseFloat(selected.data("balance")) || 0;
    var days = parseInt($("#no_of_days").val()) || 0;
    
    if(days > balance && balance >= 0) {
        $("#ajaxMessage").html('<div class="sp-alert sp-alert-warning"><i class="fas fa-exclamation-triangle"></i> Insufficient balance. You have only ' + balance + ' days available.</div>');
    } else {
        $("#ajaxMessage").html('');
    }
    
    checkMedicalCertRequirement();
}

function checkMedicalCertRequirement() {
    var leavetypeId = $("#leavetype_id").val();
    var days = parseInt($("#no_of_days").val()) || 0;
    var code = $("#leavetype_id option:selected").data("code") || '';
    
    var requiresCert = false;
    
    if((code === 'SICK_FULL' || code === 'SICK_HALF') && days > 3) {
        requiresCert = true;
    }
    
    if(code === 'MATERNITY') {
        requiresCert = true;
    }
    
    if(requiresCert) {
        $("#medicalCertRow").slideDown();
        $("#medical_cert_no").prop("required", true);
        $("#medical_cert_date").prop("required", true);
    } else {
        $("#medicalCertRow").slideUp();
        $("#medical_cert_no").prop("required", false);
        $("#medical_cert_date").prop("required", false);
    }
}

$("#leavetype_id, #no_of_days").on("change", function() {
    checkMedicalCertRequirement();
    calculateEndDate();
});
</script>

<?php include('includes/footer.inc'); ?>
