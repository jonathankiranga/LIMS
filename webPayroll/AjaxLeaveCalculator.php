<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../includes/session.inc');
include('../includes/SQL_CommonFunctions.inc');

header('Content-Type: application/json');

$response = ['success' => false, 'data' => null, 'message' => ''];

if(!isset($db)) {
    echo json_encode(['success' => false, 'message' => 'Database connection not established']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'getLeaveBalance':
        getLeaveBalance($db, $_POST['pfno'] ?? '', $_POST['year'] ?? date('Y'));
        break;
    
    case 'calculateDates':
        calculateDates($db, $_POST['from_date'] ?? '', $_POST['days'] ?? 1);
        break;
    
    case 'checkEligibility':
        checkEligibility($db, $_POST['pfno'] ?? '', $_POST['leavetype_id'] ?? 0);
        break;
    
    case 'checkMedicalCertRequired':
        checkMedicalCertRequired($db, $_POST['leavetype_id'] ?? 0, $_POST['days'] ?? 0);
        break;
    
    case 'getProRata':
        getProRata($db, $_POST['pfno'] ?? '', $_POST['leavetype_id'] ?? 0, $_POST['year'] ?? date('Y'));
        break;
    
    case 'calculateWorkingDays':
        calculateWorkingDays($db, $_POST['from_date'] ?? '', $_POST['to_date'] ?? '');
        break;
    
    case 'getUpcomingHolidays':
        getUpcomingHolidays($db);
        break;
    
    case 'getEmployees':
        getEmployees($db, $_POST['department'] ?? '');
        break;
    
    case 'getApprovalChain':
        getApprovalChain($db, $_POST['pfno'] ?? '');
        break;
    
    default:
        $response['message'] = 'Invalid action';
        echo json_encode($response);
}

function getLeaveBalance($db, $pfno, $year) {
    $sql = "SELECT lb.*, lt.leavetype_name, lt.leavetype_code, lt.color_code, lt.default_days
            FROM prlleavebalances lb
            INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
            WHERE lb.pfno = '$pfno' AND lb.year = $year AND lt.active = 1
            ORDER BY lt.sort_order";
    $result = DB_query($sql, $db);
    
    $balances = [];
    while($row = DB_fetch_array($result)) {
        $balances[] = [
            'leavetype_name' => $row['leavetype_name'],
            'leavetype_code' => $row['leavetype_code'],
            'color_code' => $row['color_code'],
            'entitled' => (float)$row['entitled_days'],
            'carried_over' => (float)$row['carried_over_days'],
            'used' => (float)$row['used_days'],
            'pending' => (float)$row['pending_days'],
            'available' => (float)$row['available_days']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $balances]);
}

function calculateDates($db, $from_date, $days) {
    if(empty($from_date) || empty($days) || $days < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }
    
    $from_ts = strtotime($from_date);
    $days = (int)$days;
    $working_days_counted = 0;
    $current_ts = $from_ts;
    
    $holidays_result = DB_query("SELECT holiday_date FROM prlkenyaholidays WHERE year = " . date('Y', $from_ts), $db);
    $holidays = [];
    while($row = DB_fetch_array($holidays_result)) {
        $holidays[] = $row['holiday_date'];
    }
    
    while($working_days_counted < $days) {
        $day_of_week = date('w', $current_ts);
        $current_date = date('Y-m-d', $current_ts);
        
        if($day_of_week != 0 && $day_of_week != 6 && !in_array($current_date, $holidays)) {
            $working_days_counted++;
        }
        
        if($working_days_counted < $days) {
            $current_ts = strtotime('+1 day', $current_ts);
        }
    }
    
    $to_ts = $current_ts;
    $return_ts = strtotime('+1 day', $to_ts);
    
    while(date('w', $return_ts) == 0 || date('w', $return_ts) == 6) {
        $return_ts = strtotime('+1 day', $return_ts);
    }
    
    echo json_encode([
        'success' => true,
        'to_date' => date('Y-m-d', $to_ts),
        'to_date_display' => date('d/m/Y', $to_ts),
        'return_date' => date('Y-m-d', $return_ts),
        'return_date_display' => date('d/m/Y', $return_ts),
        'working_days' => $days
    ]);
}

function checkEligibility($db, $pfno, $leavetype_id) {
    $sql = "SELECT lt.*, e.dateemployed, e.status as gender
            FROM prlleavetypes lt
            INNER JOIN prlemployeemaster e ON 1=1
            WHERE lt.leavetype_id = $leavetype_id AND e.pf_no = '$pfno'";
    $result = DB_query($sql, $db);
    $lt = DB_fetch_array($result);
    
    if(!$lt) {
        echo json_encode(['success' => false, 'message' => 'Leave type not found']);
        return;
    }
    
    $eligible = true;
    $reasons = [];
    
    if($lt['gender_applicable'] != 'ALL') {
        $emp_gender = strtoupper(substr($lt['gender'], 0, 1));
        if($lt['gender_applicable'] == 'FEMALE' && $emp_gender != 'F') {
            $eligible = false;
            $reasons[] = 'This leave type is only for female employees';
        }
        if($lt['gender_applicable'] == 'MALE' && $emp_gender != 'M') {
            $eligible = false;
            $reasons[] = 'This leave type is only for male employees';
        }
    }

function checkMedicalCertRequired($db, $leavetype_id, $days) {
    if(empty($leavetype_id)) {
        echo json_encode(['success' => true, 'requires_cert' => false]);
        return;
    }
    
    $sql = "SELECT leavetype_code, requires_medical_certificate, leavetype_name FROM prlleavetypes WHERE leavetype_id = " . (int)$leavetype_id;
    $result = DB_query($sql, $db);
    $lt = DB_fetch_array($result);
    
    $requires_cert = false;
    $message = '';
    
    if($lt['requires_medical_certificate'] && $days > 3) {
        $requires_cert = true;
        $message = $lt['leavetype_name'] . ' exceeding 3 days requires a medical certificate as per Kenyan Employment Act.';
    }
    
    if($lt['leavetype_code'] == 'SICK_FULL' || $lt['leavetype_code'] == 'SICK_HALF') {
        if($days > 3) {
            $requires_cert = true;
            $message = 'Sick leave exceeding 3 days requires a medical certificate as per Kenyan Employment Act Section 30.';
        }
    }
    
    if($lt['leavetype_code'] == 'MATERNITY') {
        $requires_cert = true;
        $message = 'Maternity leave requires a medical certificate from a registered medical practitioner.';
    }
    
    echo json_encode([
        'success' => true,
        'requires_cert' => $requires_cert,
        'message' => $message,
        'leavetype_name' => $lt['leavetype_name']
    ]);
}
    
    if($lt['min_service_months'] > 0) {
        $months_worked = floor((time() - strtotime($lt['dateemployed'])) / (30 * 24 * 60 * 60));
        if($months_worked < $lt['min_service_months']) {
            $eligible = false;
            $reasons[] = "Requires minimum {$lt['min_service_months']} months of service. You have $months_worked months.";
        }
    }
    
    echo json_encode([
        'success' => true,
        'eligible' => $eligible,
        'reasons' => $reasons,
        'requires_medical_cert' => (bool)$lt['requires_medical_certificate'],
        'requires_handover' => (bool)$lt['requires_handover']
    ]);
}

function getProRata($db, $pfno, $leavetype_id, $year) {
    $sql = "SELECT e.dateemployed, lt.pro_rata_applicable, lt.default_days
            FROM prlemployeemaster e
            CROSS JOIN prlleavetypes lt
            WHERE e.pf_no = '$pfno' AND lt.leavetype_id = $leavetype_id";
    $result = DB_query($sql, $db);
    $data = DB_fetch_array($result);
    
    if(!$data['pro_rata_applicable']) {
        echo json_encode([
            'success' => true,
            'pro_rata' => false,
            'entitled_days' => $data['default_days']
        ]);
        return;
    }
    
    $year_start = mktime(0, 0, 0, 1, 1, $year);
    $year_end = mktime(0, 0, 0, 12, 31, $year);
    $emp_start = strtotime($data['dateemployed']);
    
    if($emp_start > $year_start) {
        $months_worked = 12 - (int)date('n', $emp_start) + 1;
        if(date('j', $emp_start) > 1) {
            $months_worked--;
        }
        $months_worked = max(0, $months_worked);
    } else {
        $months_worked = 12;
    }
    
    $pro_rata_days = round(($data['default_days'] / 12) * $months_worked);
    
    echo json_encode([
        'success' => true,
        'pro_rata' => true,
        'months_worked' => $months_worked,
        'entitled_days' => $pro_rata_days,
        'full_days' => $data['default_days']
    ]);
}

function calculateWorkingDays($db, $from_date, $to_date) {
    if(empty($from_date) || empty($to_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates']);
        return;
    }
    
    $from_ts = strtotime($from_date);
    $to_ts = strtotime($to_date);
    $working_days = 0;
    $holidays_list = [];
    $weekends = [];
    
    $holidays_result = DB_query("SELECT holiday_date FROM prlkenyaholidays WHERE year = " . date('Y', $from_ts), $db);
    while($row = DB_fetch_array($holidays_result)) {
        $holidays_list[] = $row['holiday_date'];
    }
    
    while($from_ts <= $to_ts) {
        $day_of_week = date('w', $from_ts);
        $current_date = date('Y-m-d', $from_ts);
        
        if($day_of_week == 0 || $day_of_week == 6) {
            $weekends[] = $current_date;
        } elseif(in_array($current_date, $holidays_list)) {
            $holidays_list = array_diff($holidays_list, [$current_date]);
        } else {
            $working_days++;
        }
        
        $from_ts = strtotime('+1 day', $from_ts);
    }
    
    echo json_encode([
        'success' => true,
        'working_days' => $working_days,
        'weekends_count' => count($weekends),
        'holidays_count' => count($holidays_list)
    ]);
}

function getUpcomingHolidays($db) {
    $sql = "SELECT holiday_name, holiday_date 
            FROM prlkenyaholidays 
            WHERE holiday_date >= CURDATE() 
            ORDER BY holiday_date 
            LIMIT 10";
    $result = DB_query($sql, $db);
    
    $holidays = [];
    while($row = DB_fetch_array($result)) {
        $holidays[] = [
            'name' => $row['holiday_name'],
            'date' => $row['holiday_date'],
            'display' => date('d M Y', strtotime($row['holiday_date']))
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $holidays]);
}

function getEmployees($db, $department = '') {
    $dept_filter = !empty($department) ? "AND department = " . (int)$department : "";
    
    $sql = "SELECT pf_no, CONCAT(fname, ' ', mname, ' ', lname) as name, department
            FROM prlemployeemaster 
            WHERE (dateterminated IS NULL OR dateterminated > CURDATE())
            AND Inactive = 0
            $dept_filter
            ORDER BY name";
    $result = DB_query($sql, $db);
    
    $employees = [];
    while($row = DB_fetch_array($result)) {
        $employees[] = [
            'pfno' => $row['pf_no'],
            'name' => $row['name'],
            'department' => $row['department']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $employees]);
}

function getApprovalChain($db, $pfno) {
    $sql = "SELECT department FROM prlemployeemaster WHERE pf_no = '$pfno'";
    $result = DB_query($sql, $db);
    $emp = DB_fetch_array($result);
    
    if(!$emp) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }
    
    $chain = [];
    
    $hod_sql = "SELECT e.pf_no, e.fname, e.mname, e.lname, p.name as position
                FROM prldepartments d
                INNER JOIN prlemployeemaster e ON d.hod = e.pf_no
                LEFT JOIN prlpositions p ON e.position = p.code
                WHERE d.code = " . (int)$emp['department'];
    $hod_result = DB_query($hod_sql, $db);
    if($hod = DB_fetch_array($hod_result)) {
        $chain[] = [
            'level' => 1,
            'type' => 'HOD',
            'name' => $hod['fname'] . ' ' . $hod['lname'],
            'position' => $hod['position']
        ];
    }
    
    $hr_sql = "SELECT e.pf_no, e.fname, e.mname, e.lname, p.name as position
               FROM prlemployeemaster e
               INNER JOIN prlpositions p ON e.position = p.code
               WHERE p.name LIKE '%HR%' OR p.name LIKE '%Human Resource%'
               LIMIT 1";
    $hr_result = DB_query($hr_sql, $db);
    if($hr = DB_fetch_array($hr_result)) {
        $chain[] = [
            'level' => 2,
            'type' => 'HR',
            'name' => $hr['fname'] . ' ' . $hr['lname'],
            'position' => $hr['position']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $chain]);
}
?>
