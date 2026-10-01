<?php
   
$ConfirmationURL = $_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"] ;
$pathtourlapprove = htmlspecialchars($ConfirmationURL.'/smartpayroll/prlapproveleave.php',ENT_QUOTES,'UTF-8');

function mydatediff($dateto,$datefrom){
/*
    Note: Be aware of dates in the m/d/y or d-m-y formats;
     * if the separator is a slash (/), then the American m/d/y is assumed.
     * If the separator is a dash (-) or a dot (.), then the European d-m-y format is assumed.
     * To avoid potential errors, you should YYYY-MM-DD dates or date_create_from_format() when possible.
*/
        $x = strtotime($dateto);
        $y = strtotime($datefrom);
        $str = ($x - $y) ;
        return  floor($str/3600/24/365.25);
}

function mydaysdiff($dateto,$datefrom){
/*
    Note: Be aware of dates in the m/d/y or d-m-y formats;
     * if the separator is a slash (/), then the American m/d/y is assumed.
     * If the separator is a dash (-) or a dot (.), then the European d-m-y format is assumed.
     * To avoid potential errors, you should YYYY-MM-DD dates or date_create_from_format() when possible.
*/
        $x = strtotime($dateto);
        $y = strtotime($datefrom);
        $str = ($x - $y) ;
        return  floor($str/3600/24);
}

function chkemployee(){
    global $db;
    $status=false;

    $sql="select * from prlemployeemaster where pf_no='".$_POST['pfno']."'";
    $results = DB_query($sql,$db);
    if(DB_num_rows($results)>= 1){
       $status=true;
    }

    return $status;
}

function savemployee(){
    global $db;
    
    $password = rand(9999,99999999);
    $Encrpt = New Encryptme();
    $emailpassword = $Encrpt::encrypt($password);

    $date1 = ConvertSQLDateTime( Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,Date('m'),Date('d'),Date('y'))));
    $date2 = ConvertSQLDate($_POST['dob']);
    $diff  = mydatediff($date1,$date2);


    if($diff<18){
            $Msg='Check your date of birth.<br/> This person should be over 18 ';
            prnMsg($Msg,'warn');
     }

    if(chkemployee()){
        $Msg='This Personal file number or payroll number is currently in use';
        prnMsg($Msg,'warn');
    } else {
        $sql = sprintf("INSERT INTO prlemployeemaster
           ([pf_no],[status] ,[fname] ,[mname],[lname],[idno],[pin_no],[nssf_no] ,[nhif_no],[telno] 
           ,[email],[noofhrsperday],[dateob],[dateemployed],[freqcode],[branch],[bankacno],
           [bankcode],[bankcode2] ,[position]
           ,[property1],[property2],[property3],[property4],[property5],[property6],[property7]
           ,[property8],[property9],[property10],[department],[userid] ,[userpassword])
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' ,'%s','%s','%s','%s',
           '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',N'%s') ;",
           $_POST['pfno'],
           $_POST['status']
           ,$_POST['fname']
           ,$_POST['mname']
           ,$_POST['lname']
           ,$_POST['idno']
           ,$_POST['pinno']
           ,$_POST['nssfno']
           ,$_POST['nhifno']
           ,$_POST['telno']
           ,$_POST['email']
           ,$_POST['noofhrsperday']
           ,FormatDateForSQL($_POST['dob'])
           ,FormatDateForSQL($_POST['doe'])
           ,$_POST['freqcode']
           ,$_POST['branch']
           ,$_POST['bankaccount']
           ,$_POST['bank']
            ,$_POST['bankbranch']
            ,$_POST['position']
            ,$_POST['property1']
            ,$_POST['property2']
            ,$_POST['property3']
            ,$_POST['property4']
            ,$_POST['property5']
            ,$_POST['property6']
            ,$_POST['property7']
            ,$_POST['property8']
            ,$_POST['property9']
            ,$_POST['property10'],
            $_POST['department'],
            $_POST['email'],
            $password);

      
    DB_query($sql,$db);


}

}

function editemployee(){
    global $db;

if(chkemployee()){

$date1 = ConvertSQLDate( Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,Date('m'),Date('d'),Date('y'))));
$date2 = ConvertSQLDate($_POST['dob']);
$diff  = mydatediff($date1,$date2);

        if($diff<18){
            $Msg='Check your date of birth.<br/> This person should be over 18 ';
            prnMsg($Msg,'warn');
         }

        $sql = sprintf("UPDATE prlemployeemaster SET
           `status`='%s', `fname`='%s', `mname`='%s', `lname`='%s',
           `idno`='%s', `pin_no`='%s', `nssf_no`='%s', `nhif_no`='%s',
           `telno`='%s', `email`='%s', `noofhrsperday`='%s', `dateob`='%s',
           `dateemployed`='%s', `freqcode`='%s', `branch`='%s', `bankacno`='%s',
           `bankcode`='%s', `bankcode2`='%s', `position`='%s', `property1`='%s',
           `property2`='%s', `property3`='%s', `property4`='%s', `property5`='%s',
           `property6`='%s', `property7`='%s', `property8`='%s', `property9`='%s',
           `property10`='%s', `department`='%s' WHERE pf_no='%s'",
            $_POST['status'],
            $_POST['fname'],
            $_POST['mname'],
            $_POST['lname'],
            $_POST['idno'],
            $_POST['pinno'],
            $_POST['nssfno'],
            $_POST['nhifno'],
            $_POST['telno'],
            $_POST['email'],
            $_POST['noofhrsperday'],
            FormatDateForSQL($_POST['dob']),
            FormatDateForSQL($_POST['doe']),
            $_POST['freqcode'],
            $_POST['branch'],
            $_POST['bankaccount'],
            $_POST['bank'],
            $_POST['bankbranch'],
            $_POST['position'],
            $_POST['property1'],
            $_POST['property2'],
            $_POST['property3'],
            $_POST['property4'],
            $_POST['property5'],
            $_POST['property6'],
            $_POST['property7'],
            $_POST['property8'],
            $_POST['property9'],
            $_POST['property10'],
            $_POST['department'],
            $_POST['pfno']);

        if(isset($_POST['dot']) and $_POST['dot']!=null && $_POST['dot']!=''){
            $date1 = ConvertSQLDate($_POST['dot']);
            $date2 = ConvertSQLDate($_POST['doe']);
            $diff  = mydatediff($date1, $date2);
            
            if($diff >= 0){
                $sql2 = sprintf("UPDATE prlemployeemaster SET `dateterminated`='%s' WHERE pf_no='%s'",
                    FormatDateForSQL($_POST['dot']), $_POST['pfno']);
                DB_query($sql, $db);
                DB_query($sql2, $db);
            } else {
                $Msg='Your date of termination must be later than your date of employment';
                prnMsg($Msg,'warn');
            }
        } else {
            $sql2 = sprintf("UPDATE prlemployeemaster SET `dateterminated`=NULL WHERE pf_no='%s'",
                $_POST['pfno']);
            DB_query($sql, $db);
            DB_query($sql2, $db);
        }

    } else {
        $Msg='This Personal file number or payroll number is not found';
        prnMsg($Msg,'warn');
    }

}

function showemployee(){
    global $db;

    $sql="select M.* from prlemployeemaster  M where pf_no='".$_POST['pfno']."'";

    $results = DB_query($sql,$db);
    while($row = DB_fetch_array($results)){
        $_POST['idno'] = $row['idno'] ;
        $_POST['pfno'] = $row['pf_no'] ;
        $_POST['status'] = $row['status'] ;
        $_POST['fname'] = $row['fname'] ;
        $_POST['mname'] = $row['mname'] ;
        $_POST['lname'] = $row['lname'] ;
        $_POST['pinno'] = $row['pin_no'] ;
        $_POST['nssfno'] = $row['nssf_no'] ;
        $_POST['nhifno'] = $row['nhif_no'] ;
        $_POST['telno'] = $row['telno'] ;
        $_POST['email'] = trim($row['email']) ;
        $_POST['noofhrsperday'] = $row['noofhrsperday'] ;
        $_POST['freqcode'] = $row['freqcode'] ;
        $_POST['branch'] = $row['branch'] ;
        $_POST['bankaccount'] = $row['bankacno'];
        $_POST['bank'] = $row['bankcode'];
        $_POST['bankbranch'] = $row['bankcode2']; 
       
        if(isset($row['dateob']) && $row['dateob'] != '0000-00-00 00:00:00' && !empty($row['dateob'])){
           $_POST['dob']  = date('Y-m-d', strtotime($row['dateob'])) ;
        }else{
            $_POST['dob'] = '';
        }
        
        if(isset($row['dateemployed']) && $row['dateemployed'] != '0000-00-00 00:00:00' && !empty($row['dateemployed'])){
           $_POST['doe']  = date('Y-m-d', strtotime($row['dateemployed'])) ;
        }else{
            $_POST['doe'] = '';
        }
        
        
        $diff  = mydatediff($row['dateterminated'],$row['dateemployed']);
        if($diff > 0 && isset($row['dateterminated']) && $row['dateterminated'] != '0000-00-00 00:00:00' && !empty($row['dateterminated'])){
             $_POST['dot'] = date('Y-m-d', strtotime($row['dateterminated'])) ;
        } else {
            $_POST['dot'] = '';
        }
           $_POST['position']    = $row['position'];
           $_POST['properties1'] = $row['properties1'];
           $_POST['properties2'] = $row['properties2'];
           $_POST['properties3'] = $row['properties3'];
           $_POST['properties4'] = $row['properties4'];
           $_POST['properties5'] = $row['properties5'];
           $_POST['properties6'] = $row['properties6'];
           $_POST['properties7'] = $row['properties7'];
           $_POST['properties8'] = $row['properties8'];
           $_POST['properties9'] = $row['properties9'];
           $_POST['properties10'] = $row['properties10'];            
           $_POST['department']   = $row['department'];
    }
}

function cleardata(){
    unset($_POST['pfno']);
    unset($_POST['status']);
    unset($_POST['fname']);
    unset($_POST['mname']);
    unset($_POST['lname']);
    unset($_POST['idno']);
    unset($_POST['pinno']);
    unset($_POST['nssfno']);
    unset($_POST['nhifno']);
    unset($_POST['telno']);
    unset($_POST['email']);
    unset($_POST['dob']);
    unset($_POST['doe']);
    unset($_POST['dot']);
    unset($_POST['freqcode']);
    unset($_POST['branch']);
    unset($_POST['bankaccount']);
    unset($_POST['bank']);
    unset($_POST['bankbranch']);
    unset($_POST['position']);
    $_POST['noofhrsperday'] = 8;
}

function GetleaveDaysBalance($pfno){
    Global $db;
    
    $SQL="Select COALESCE(sum([days]),0) from [prlstaffleavemaster] where  pfno='".$pfno."'";
    $ResultIndex=DB_query($SQL,$db);
    $rows=DB_fetch_row($ResultIndex);
    $Days[0]= $rows[0];
        
    $SQL="SELECT COALESCE(sum([days]),0)  from [prlstaffleaveplanner] where pfno='".$pfno."' and [status]=1 ";
    $ResultIndex=DB_query($SQL,$db);
    $rows=DB_fetch_row($ResultIndex);
    $Days[1]= $rows[0];
    
    $SQL="SELECT  COALESCE(sum([days]),0)  from [prlstaffleaveplanner] where pfno='".$pfno."' and ([status]=2 or [status]=3) ";
    $ResultIndex=DB_query($SQL,$db);
    $rows = DB_fetch_row($ResultIndex);
    $Days[2] = $rows[0];
    
    
    $status = sprintf('<div><ul><li>Annual Days Balance:%s</li>'
            . '<li>Days Applied For and Not yet approved:%s</li>'
            . '<li>Days Already Taken:%s</li>'
            . '<li>Days Eligible:%s</li></ul></div>',
            $Days[0]-$Days[2],$Days[1],$Days[2],($Days[0]-($Days[1]+$Days[2])));
    $status . '</table>';
    
    echo $status;
    
    return ($Days[0]-($Days[1]+$Days[2]));
}

function GetNewLeaveBalance($pfno, $leavetype_id = null, $year = null) {
    Global $db;
    
    if($year === null) $year = date('Y');
    
    $whereClause = "WHERE lb.pfno = '".$pfno."' AND lb.year = " . (int)$year;
    if($leavetype_id !== null) {
        $whereClause .= " AND lb.leavetype_id = " . (int)$leavetype_id;
    }
    
    $SQL = "SELECT lb.*, lt.leavetype_name, lt.color_code, lt.leavetype_code
            FROM prlleavebalances lb
            INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
            $whereClause
            ORDER BY lt.sort_order";
    $ResultIndex = DB_query($SQL, $db);
    
    $balances = [];
    while($row = DB_fetch_array($ResultIndex)) {
        $balances[] = $row;
    }
    
    return $balances;
}

function GetLeaveTypeInfo($leavetype_code) {
    Global $db;
    
    $SQL = "SELECT * FROM prlleavetypes WHERE leavetype_code = '".$leavetype_code."'";
    $ResultIndex = DB_query($SQL, $db);
    return DB_fetch_array($ResultIndex);
}

function GetWorkingDaysNew($from_date, $to_date) {
    Global $db;
    
    $from_ts = strtotime($from_date);
    $to_ts = strtotime($to_date);
    $working_days = 0;
    
    $holidays_result = DB_query("SELECT holiday_date FROM prlkenyaholidays WHERE year = " . date('Y', $from_ts), $db);
    $holidays = [];
    while($row = DB_fetch_array($holidays_result)) {
        $holidays[] = $row['holiday_date'];
    }
    
    while($from_ts <= $to_ts) {
        $day_of_week = date('w', $from_ts);
        $current_date = date('Y-m-d', $from_ts);
        
        if($day_of_week != 0 && $day_of_week != 6 && !in_array($current_date, $holidays)) {
            $working_days++;
        }
        $from_ts = strtotime('+1 day', $from_ts);
    }
    return $working_days;
}

function GetExpectedReturnDateNew($from_date, $days) {
    Global $db;
    
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

function CalculateProRataLeave($pfno, $leavetype_id, $year) {
    Global $db;
    
    $emp_result = DB_query("SELECT dateemployed FROM prlemployeemaster WHERE pf_no = '".$pfno."'", $db);
    $emp = DB_fetch_array($emp_result);
    
    $lt_result = DB_query("SELECT default_days, pro_rata_applicable FROM prlleavetypes WHERE leavetype_id = " . (int)$leavetype_id, $db);
    $lt = DB_fetch_array($lt_result);
    
    if(!$lt['pro_rata_applicable']) {
        return $lt['default_days'];
    }
    
    $year_start = mktime(0, 0, 0, 1, 1, $year);
    $emp_start = strtotime($emp['dateemployed']);
    
    if($emp_start > $year_start) {
        $months_worked = 12 - (int)date('n', $emp_start) + 1;
        if(date('j', $emp_start) > 1) {
            $months_worked--;
        }
        $months_worked = max(0, $months_worked);
    } else {
        $months_worked = 12;
    }
    
    return round(($lt['default_days'] / 12) * $months_worked);
}

function CheckLeaveEligibility($pfno, $leavetype_id) {
    Global $db;
    
    $sql = "SELECT lt.*, e.dateemployed, e.status as gender
            FROM prlleavetypes lt
            CROSS JOIN prlemployeemaster e
            WHERE lt.leavetype_id = " . (int)$leavetype_id . " AND e.pf_no = '".$pfno."'";
    $result = DB_query($sql, $db);
    $lt = DB_fetch_array($result);
    
    if(!$lt) {
        return ['eligible' => false, 'reason' => 'Leave type not found'];
    }
    
    if($lt['gender_applicable'] != 'ALL') {
        $emp_gender = strtoupper(substr($lt['gender'], 0, 1));
        if($lt['gender_applicable'] == 'FEMALE' && $emp_gender != 'F') {
            return ['eligible' => false, 'reason' => 'This leave type is only for female employees'];
        }
        if($lt['gender_applicable'] == 'MALE' && $emp_gender != 'M') {
            return ['eligible' => false, 'reason' => 'This leave type is only for male employees'];
        }
    }
    
    if($lt['min_service_months'] > 0) {
        $months_worked = floor((time() - strtotime($lt['dateemployed'])) / (30 * 24 * 60 * 60));
        if($months_worked < $lt['min_service_months']) {
            return ['eligible' => false, 'reason' => "Requires minimum {$lt['min_service_months']} months of service"];
        }
    }
    
    return ['eligible' => true, 'reason' => 'Eligible'];
}

function GetLeaveStatistics($year = null) {
    Global $db;
    
    if($year === null) $year = date('Y');
    
    $SQL = "SELECT 
        lt.leavetype_name,
        COUNT(DISTINCT lb.pfno) as employees,
        SUM(lb.entitled_days) as total_entitled,
        SUM(lb.carried_over_days) as total_carried,
        SUM(lb.used_days) as total_used,
        SUM(lb.pending_days) as total_pending,
        SUM(lb.available_days) as total_available
        FROM prlleavebalances lb
        INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
        WHERE lb.year = " . (int)$year . " AND lt.active = 1
        GROUP BY lb.leavetype_id, lt.leavetype_name
        ORDER BY lt.sort_order";
    $ResultIndex = DB_query($SQL, $db);
    
    $stats = [];
    while($row = DB_fetch_array($ResultIndex)) {
        $stats[] = $row;
    }
    
    return $stats;
}

?>
