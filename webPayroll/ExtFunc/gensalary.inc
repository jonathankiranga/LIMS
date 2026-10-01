<?php
$employment = array();
$employment[1] = 'Permanent';
$employment[2] = 'Contract';
$employment[3] = 'Attachment';

   function mydatediff($date1,$date2){
       $x = strtotime($date1);
       $y = strtotime($date2);
       $str = ($x - $y) ;
       return  floor($str/3600/24);
    }


   


function addmonth(){
    Global $db;
    
      $SQL="SELECT `fromdate` FROM `prlmrollperiods` where (`open`=1 and pkey in (select `payroll_id` from `prlpayroltransfile`) )
        order by pkey desc";
    $ResultIndex = DB_query($SQL,$db);
    $row=DB_fetch_row($ResultIndex);
        
    $Month = (isset($row[0])? $row[0]:$_SESSION['MonthTypePayroll']);
    $sql = sprintf("SELECT DATE_ADD('%s', INTERVAL 1 MONTH) as month,  DATE_SUB(DATE_ADD('%s', INTERVAL 2 MONTH), INTERVAL 1 DAY) as lastday,  '%s' as curdate",   $Month,   $Month,  $Month);  
    $results=DB_query($sql,$db);
    $row=DB_fetch_row($results);
    
    return $row;
}

function setnewmonth($date){
    global $db;
    
    $_SESSION['MonthTypePayroll'] = $date;
     DB_query("UPDATE `config` SET `confvalue` ='".$date."'  WHERE `confname` = 'MonthTypePayroll' ",$db);
}


function populatebankarray(){
    Global $db;
    
    $bankArray;
    
    $ResultIndex=DB_query("SELECT `parentcode`,`code`,`bankbranch`,`address` FROM `employeebnkbranch`", $db);
    while($rows=DB_fetch_array($ResultIndex)){
        $bankArray[$rows['code']] = $rows ;
        
    }
    
    return $bankArray ;
}

function Reademployeefile(){
    global $db;
    $ok=0;

    $SQL="SELECT `type`,`fromdate`,`todate`,`open` FROM `prlmrollperiods` where `open`=1  order by pkey desc ";
    $ResultIndex = DB_query($SQL,$db);
    $no_of_active_periods=DB_num_rows($ResultIndex);

    if($no_of_active_periods==0){
        prnMsg ('You have not defined any Pay Rolls to generate payslips<br/>Please Create a Payroll first' 
                .'<a href="prlgenerateslips.php">Click Here</a>');
    } else {
        $ok=1;
    }

return $ok;
}

function createpayroll(){
global $db;

$process=true;
$sqlarray=array();
 
    if(!isset($_SESSION['MonthTypePayroll'])){
        prnMsg('Please configure the system parameter [MonthTypePayroll]','warn');
        $process=false;
    }
  
    if($process==true){
        $dateto = addmonth();
        setnewmonth($dateto[0]);
      // check if the user had pressed serveral times        
            $SQL=sprintf("SELECT pkey FROM `prlmrollperiods` where fromdate='%s' order by pkey desc limit 1",$dateto[0]);
            $ResultIndex = DB_query($SQL,$db);
            $row=DB_fetch_row($ResultIndex);
            $pkey = $row[0];
         // 
          DB_Txn_Begin($db);
            $sqlarray[] = "Update `prlmrollperiods` set `open`=0 ";
            if(isset($pkey)){
                // if he had just activate the account
             $sqlarray[] = "Update `prlmrollperiods` set `open`=1 where pkey= ".$pkey;
            }else{      
                  // if not create the new period 
             $sqlarray[] = sprintf("insert into `prlmrollperiods` (`type`,`fromdate`,`todate`,`OPEN`) values ('1','%s','%s',1)",$dateto[0],$dateto[1]);
            }
            foreach ($sqlarray as $SQL) {
                 $r=DB_query($SQL,$db);
            }
          
             
          if(DB_error_no($db)==0){
                prnMsg('You have created a new payroll successfuly');
                DB_Txn_Commit($db);
             }else{
                DB_Txn_Rollback($db);
             }
          
    }

}

function closepayroll(){
  global $db;

  $InputError = 0;
  DB_Txn_Begin($db);

  $SQL="update prlmatrix  set  posted=1 where `nonrecuring`=1 ;";
  DB_query($SQL,$db);
    if(DB_error_no($db)>0){
        $InputError = 1;
    }

    if ($InputError == 1 ) { //exited loop with errors so rollback
        prnMsg(_('Failed to execute, trans has been rolled back'),'error');
        DB_Txn_Rollback($db);
    } else { //all good so commit data transaction
        DB_Txn_Commit($db);
        prnMsg('You have closed the period successfuly');
    }

}


function sqlselectnet(){
 global $db;

  $sum =" (basicpay";
 
  $SQLproducts="SELECT code,description from prlproducts where deduction=1 ";
  $ResultIndex=DB_query($SQLproducts,$db);
  $numrows = DB_num_rows($ResultIndex);
  $sqlselect  = "Select pfno,names,Bankcode,Bankaccount,basicpay,Overtime ,";
  while($rows = DB_fetch_array($ResultIndex)){
       $field = 'A_'.rtrim($rows['code']);
       $sqlselect  .=  $field." ,";
       $sum .= "+ COALESCE(".$field .',0)';
  }
  $sum .= '+ isnull(Overtime,0) ) as allowances ,(Manhrlost';
 // sum allowances

  $SQLproducts="SELECT code,description from prlproducts where deduction=0 or deduction is null ";
  $ResultIndex=DB_query($SQLproducts,$db);
  $numrows = DB_num_rows($ResultIndex);
  while($rows = DB_fetch_array($ResultIndex)){
       $field = 'D_'.rtrim($rows['code']);
       $sqlselect  .=  $field." ,";
       $sum .= "+ COALESCE(".$field.',0)' ;
  }
//sum deductions
  $sum .= ' + isnull(Loans,0)) as deductions';
  $sqlselect  .= ' Manhrlost ,Loans ,'.$sum.' from @masterroll ';
  return  $sqlselect;
}


function SelectApprover($docno){
    global $db;
     
    $SQLstmt=SPRINTF("SELECT COALESCE(`approvelevel`,1) FROM `prlstaffleaveplanner` where `refno`='%s' ",$docno);
    $ResultIndex = DB_query($SQLstmt,$db);
    $RowApp = DB_fetch_row($ResultIndex);
    $docstatus= $RowApp[0];
    DB_free_result($ResultIndex);
 
 
    $UserPayID = $_SESSION['payrollid'];
    if(mb_strlen($UserPayID)> 0){
       
      $SQL=sprintf("SELECT t.`docno`,t.`userdepartment`,t.`position`,t.`authoritylevel`
      FROM `prlleaveapprovaltrans` T join `prlemployeemaster` M on T.`userdepartment`=M.department 
      where T.`docno`='%s' and M.`pf_no`='%s' ",$docno,$UserPayID);
      
      $results=DB_query($SQL,$db);
      if(DB_num_rows($results)==0){
          prnMsg('You " '.$_SESSION['UsersRealName'].' " Had not been set up in the system as an approver.'
                  . '<br/> Please contact the administrator','warn');
      }else{
          
          $linedetails=DB_fetch_row($results);
          $Approval_level = $linedetails[3];
          DB_free_result($results);
                    
          if($docstatus>$Approval_level){
              
                 if($Approval_level==0){ 
                    $sql =sprintf("Update `prlstaffleaveplanner` set `approvelevel`='%s',`status`='2' "
                     . " where `refno`='%s' ",$Approval_level,$docno);
                    DB_query($sql,$db);
                 }else{
                    $sql =sprintf("Update `prlstaffleaveplanner` set `approvelevel`='%s',`status`=1 "
                    . " where `refno`='%s' ",$Approval_level,$docno);
                    DB_query($sql,$db);
                 }
          }else{
              prnMsg('This document needs to be approved by a higher authority than You');
          }
      }
      
    } 
    else {
        prnMsg('Your user account needs to be configured before you can approve any document.'
                . '<br /> Contact your system administrator','warn');
    }
    
}

Function Go_on_leave(){
     Global $db;
     $sql = "UPDATE `prlstaffleaveplanner` SET `approvelevel`='3' WHERE `leavedue` <= CURDATE() ";
     DB_query($sql,$db);
}

Function GetWDOWK(){
    Global $db;
    $status=array();
    
   $SQL="SELECT `Dayoftheweek`,`isworkingday` FROM `Dayoftheweeks` ";
   $ResultIndex=DB_query($SQL,$db);
   while($row=DB_fetch_array($ResultIndex)){
        $status[$row['Dayoftheweek']]=$row['isworkingday'];
    }
    return $status;
}


function printNetPay($payrollId){
    global $db;

    // 1. Build product fields dynamically (Allowances & Deductions)
    $allowanceFields = [];
    $deductionFields = [];
    $productCodes = [];

    $SQLproducts = "SELECT code, description, deduction FROM prlproducts ORDER BY deduction DESC";
    $ResultIndex = DB_query($SQLproducts, $db);

    while ($row = DB_fetch_array($ResultIndex)) {
        $prefix = ($row['deduction'] == 1) ? 'A_' : 'D_';
        $fieldAlias = $prefix . trim($row['code']);
        $productCodes[] = [
            'code' => trim($row['code']),
            'alias' => $fieldAlias,
            'deduction' => $row['deduction'],
        ];
        if ($row['deduction'] == 1) {
            $allowanceFields[] = $fieldAlias;
        } else {
            $deductionFields[] = $fieldAlias;
        }
    }

    // 2. Build the main query dynamically
    $selectProductFields = [];
    $joinProductTables = [];

    foreach ($productCodes as $product) {
        $selectProductFields[] = "ISNULL(pfle_{$product['code']}.amount, 0) AS {$product['alias']}";
        $joinProductTables[] = "
            LEFT JOIN prlpaydetailstransfile pfle_{$product['code']} 
            ON prlpayroltransfile.payroll_id = pfle_{$product['code']}.payroll_id 
            AND prlpayroltransfile.pfno = pfle_{$product['code']}.pfno 
            AND pfle_{$product['code']}.code = '{$product['code']}'";
    }

    // 3. Construct Allowances and Deductions SUM
    $sumAllowances = "basicpay";
    foreach ($allowanceFields as $field) {
        $sumAllowances .= " + COALESCE($field, 0)";
    }
    $sumAllowances .= " + ISNULL(Overtime, 0)";

    $sumDeductions = "ISNULL(Manhrlost, 0)";
    foreach ($deductionFields as $field) {
        $sumDeductions .= " + COALESCE($field, 0)";
    }
    $sumDeductions .= " + ISNULL(Loans, 0)";

    // 4. Final Query Assembly
    $sql = "
    SELECT 
        prlpayroltransfile.pfno,
        RTRIM(prlemployeemaster.fname) + ' ' + RTRIM(prlemployeemaster.mname) + ' ' + RTRIM(prlemployeemaster.lname) AS names,
        prlemployeemaster.bankcode2 AS Bankcode,
        prlemployeemaster.bankacno AS Bankaccount,
        ISNULL(prlpayroltransfile.basicpay, 0) AS basicpay,
        ISNULL(prlpayroltransfile.overtime, 0) AS Overtime,
        " . implode(",\n        ", $selectProductFields) . ",
        ISNULL(prlpayroltransfile.lateness_absent, 0) AS Manhrlost,
        SUM(ISNULL(prlloantrans.amount, 0) + ISNULL(prlloantrans.interest, 0)) AS Loans,
        ($sumAllowances) AS allowances,
        ($sumDeductions) AS deductions
    FROM prlpayroltransfile
    JOIN prlemployeemaster 
        ON prlpayroltransfile.pfno = prlemployeemaster.pf_no
    LEFT JOIN prlloantrans 
        ON prlpayroltransfile.payroll_id = prlloantrans.payroll_id
        AND prlpayroltransfile.pfno = prlloantrans.pfno
    " . implode("\n", $joinProductTables) . "
    WHERE prlpayroltransfile.payroll_id = '$payrollId'
    GROUP BY prlpayroltransfile.pfno,
             prlemployeemaster.bankcode2,
             prlemployeemaster.bankacno,
             prlpayroltransfile.basicpay,
             prlpayroltransfile.overtime,
             prlpayroltransfile.lateness_absent,
             prlemployeemaster.fname,
             prlemployeemaster.mname,
             prlemployeemaster.lname,
             " . implode(", ", $allowanceFields) . ",
             " . implode(", ", $deductionFields) . ";
    ";

    return $sql;
}

function printmasteroll() {
    global $db;
   // Get the 'Mid' parameter
    $gid = $_GET['Mid'];
   // Initialize the SQL for the masterroll table and select query
    $sqlfunction = "CREATE TEMPORARY TABLE masterroll (";
    $SQLproducts = "SELECT code, description, deduction FROM prlproducts ORDER BY deduction DESC";
    $ResultIndex = mysqli_query($db, $SQLproducts);
    // Dynamically create columns for deductions and codes
    while ($rows = mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC)) {
        $sqlfunction .= ($rows['deduction'] == 1 ? 'A' : 'D') . '_' . rtrim($rows['code']) . " DECIMAL(10,2),";
    }
   // Adding more columns for pfno, names, etc.
    $sqlfunction .= 'pfno CHAR(20), names VARCHAR(50), basicpay FLOAT, Overtime FLOAT, Manhrlost FLOAT, Loans FLOAT)';
   // Create the temporary table
    mysqli_query($db, $sqlfunction);  
  // Insert data into masterroll table
    $sqlfunction = 'INSERT INTO masterroll SELECT';
    $ResultIndex = mysqli_query($db, $SQLproducts);
    // Dynamically add amounts for each product
    while ($rows = mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC)) {
        $sqlfunction .= " pfle_" . rtrim($rows['code']) . ".amount,";
    }
   // Select additional data
    $sqlfunction .= " prlpayroltransfile.pfno, 
                      CONCAT(RTRIM(prlemployeemaster.fname), ' ', RTRIM(prlemployeemaster.mname), ' ', RTRIM(prlemployeemaster.lname)) AS names, 
                      prlpayroltransfile.basicpay, 
                      prlpayroltransfile.overtime, 
                      prlpayroltransfile.lateness_absent, 
                      SUM(IFNULL(prlloantrans.amount, 0) + IFNULL(prlloantrans.interest, 0)) AS totalloan
                      FROM prlpayroltransfile
                      JOIN prlemployeemaster ON prlpayroltransfile.pfno = prlemployeemaster.pf_no
                      LEFT JOIN prlloantrans ON prlpayroltransfile.payroll_id = prlloantrans.payroll_id
                      AND prlpayroltransfile.pfno = prlloantrans.pfno ";

    // Dynamically join the products for each deduction
    $ResultIndex = mysqli_query($db, $SQLproducts);
    while ($rows = mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC)) {
        $sqlfunction .= " LEFT JOIN prlpaydetailstransfile pfle_" . rtrim($rows['code']) . " ON
                          prlpayroltransfile.payroll_id = pfle_" . rtrim($rows['code']) . ".payroll_id
                          AND prlpayroltransfile.pfno = pfle_" . rtrim($rows['code']) . ".pfno
                          AND pfle_" . rtrim($rows['code']) . ".code = '" . $rows['code'] . "'";
    }

    // Filtering by payroll ID
    $sqlfunction .= " WHERE prlpayroltransfile.payroll_id = '" . $gid . "'
                      GROUP BY";

    // Dynamically add group by for each product
    $ResultIndex = mysqli_query($db, $SQLproducts);
    while ($rows = mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC)) {
        $sqlfunction .= " pfle_" . rtrim($rows['code']) . ".amount,";
    }

    // Final group by fields
    $sqlfunction .= " prlpayroltransfile.pfno, 
                      prlpayroltransfile.basicpay, 
                      prlpayroltransfile.overtime, 
                      prlpayroltransfile.lateness_absent, 
                      prlemployeemaster.fname, 
                      prlemployeemaster.mname, 
                      prlemployeemaster.lname";

    // Execute the insertion query
    mysqli_query($db, $sqlfunction);

    // Execute the summary SQL
    $sqlSummary = sqlselectsummary();
   if (mysqli_query($db, $sqlSummary)) {
        // Select data from the temporary table
        $result = mysqli_query($db,$sqlSummary);
       // Prepare an array to hold the fetched data
        $data = [];
      // Check if there are any rows returned
        if ($result && mysqli_num_rows($result) > 0) {
            // Fetch the data into the array
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
      // Clean up the temporary table
        mysqli_query($db, "DROP TEMPORARY TABLE IF EXISTS masterroll");
     // Return the data array
        return $data;
    } else {
        // If the query failed, return an empty array and display an error message
        echo "Error: " . mysqli_error($db);
        return [];
    }
    
}

function sqlselectsummary() {
    global $db;

    // Initialize summary SQL
    $sum = "(basicpay";
    $SQLproducts = "SELECT code, description FROM prlproducts WHERE deduction = 1";
    
    // Use mysqli to execute the query
    $ResultIndex = mysqli_query($db, $SQLproducts);
    
    // Generate field names and sum calculations for allowances
    $sqlselect = "SELECT pfno, names, basicpay, Overtime,";
    while ($rows = mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC)) {
        $field = 'A_' . rtrim($rows['code']);
        $sqlselect .= " $field,";
        $sum .= " + IFNULL($field, 0)";
    }

    $sum .= " + IFNULL(Overtime, 0)) AS allowances, (Manhrlost";

    // Generate field names and sum calculations for deductions
    $SQLproducts = "SELECT code, description FROM prlproducts WHERE deduction = 0 OR deduction IS NULL";
    $ResultIndex = mysqli_query($db, $SQLproducts);
    while ($rows = mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC)) {
        $field = 'D_' . rtrim($rows['code']);
        $sqlselect .= " $field,";
        $sum .= " + IFNULL($field, 0)";
    }

    $sum .= " + IFNULL(Loans, 0)) AS deductions";
    $sqlselect .= " Manhrlost, Loans, $sum FROM masterroll";

    return $sqlselect;
}

function printproductsfilter($id) {
    global $db;

    // Get the POST data
    $productid = $_POST['productid'];
    $payrollid = $_POST['payrollid'];

    // SQL to create a temporary table for storing results
    $sqlfunction = "CREATE TEMPORARY TABLE masterroll (
                        pfno CHAR(20),
                        names VARCHAR(50),
                        amount FLOAT,
                        empl FLOAT
                    );

                    INSERT INTO masterroll
                    SELECT prlemployeemaster.idno AS pfno,
                        CONCAT(RTRIM(prlemployeemaster.fname), ' ', RTRIM(prlemployeemaster.mname), ' ', RTRIM(prlemployeemaster.lname)) AS names,
                        SUM(prlpaydetailstransfile.amount) AS total,
                        IFNULL(SUM(prlpaydetailstransfile.employercontribution), 0) AS empl
                    FROM prlpaydetailstransfile
                    JOIN prlemployeemaster ON prlpaydetailstransfile.pfno = prlemployeemaster.pf_no
                    WHERE prlpaydetailstransfile.pfno = '$id'
                        AND prlpaydetailstransfile.code = '$productid'
                        AND prlpaydetailstransfile.payroll_id = '$payrollid'
                    GROUP BY prlemployeemaster.idno, prlemployeemaster.fname, prlemployeemaster.mname, prlemployeemaster.lname";

    // Execute the creation of the temporary table and the insertion of data
    if (mysqli_query($db, $sqlfunction)) {
        // Select data from the temporary table
        $sqlSelect = "SELECT pfno, names, amount, empl FROM masterroll";
        $result = mysqli_query($db, $sqlSelect);

        // Prepare an array to hold the fetched data
        $data = [];

        // Check if there are any rows returned
        if ($result && mysqli_num_rows($result) > 0) {
            // Fetch the data into the array
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }

        // Clean up the temporary table
        mysqli_query($db, "DROP TEMPORARY TABLE IF EXISTS masterroll");

        // Return the data array
        return $data;
    } else {
        // If the query failed, return an empty array and display an error message
        echo "Error: " . mysqli_error($db);
        return [];
    }
}

function printproducts() {
    global $db;

    // Get POST data
    $productid = $_POST['productid'];
    $payrollid = $_POST['payrollid'];

    // SQL to create a temporary table and insert the required data
    $sqlfunction = "
    CREATE TEMPORARY TABLE masterroll (
        pfno CHAR(20),
        names VARCHAR(50),
        amount FLOAT,
        empl FLOAT
    );

    INSERT INTO masterroll
    SELECT prlemployeemaster.idno AS pfno,
        CONCAT(RTRIM(prlemployeemaster.fname), ' ', RTRIM(prlemployeemaster.mname), ' ', RTRIM(prlemployeemaster.lname)) AS names,
        SUM(prlpaydetailstransfile.amount) AS total,
        IFNULL(SUM(prlpaydetailstransfile.employercontribution), 0) AS empl
    FROM prlpaydetailstransfile
    JOIN prlemployeemaster ON prlpaydetailstransfile.pfno = prlemployeemaster.pf_no
    WHERE prlpaydetailstransfile.code = '$productid'
        AND prlpaydetailstransfile.payroll_id = '$payrollid'
    GROUP BY prlemployeemaster.idno, prlemployeemaster.fname, prlemployeemaster.mname, prlemployeemaster.lname
    ";

    // Execute the SQL function to create the temporary table and insert data
    if (mysqli_query($db, $sqlfunction)) {
        // SQL to fetch data from the temporary table
        $sqlSelect = "SELECT pfno, names, amount, empl FROM masterroll";

        // Execute the query and fetch the results
        $result = mysqli_query($db, $sqlSelect);

        // Prepare an array to hold the fetched data
        $data = [];

        // Check if there are rows returned
        if ($result && mysqli_num_rows($result) > 0) {
            // Fetch all rows into the array
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }

        // Clean up the temporary table
        mysqli_query($db, "DROP TEMPORARY TABLE IF EXISTS masterroll");

        // Return the data array
        return $data;
    } else {
        // If the query failed, display an error and return an empty array
        echo "Error: " . mysqli_error($db);
        return [];
    }
}

function filterprintbymonth($staffid, $productid, $datefrom, $dateto) {
    global $db;

    $sql = "SELECT prlpaydetailstransfile.pfno,
        prlemployeemaster.nssf_no AS nssfno,
        prlemployeemaster.nhif_no AS nhifno,
        prlemployeemaster.pin_no AS payeno,
        CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
        SUM(prlpaydetailstransfile.amount) AS total,
        COALESCE(SUM(prlpaydetailstransfile.employercontribution), 0) AS empl
    FROM prlpaydetailstransfile
    JOIN prlemployeemaster ON prlpaydetailstransfile.pfno = prlemployeemaster.pf_no
    WHERE prlpaydetailstransfile.pfno = '" . db_escape($db, $staffid) . "'
        AND prlpaydetailstransfile.code = '" . db_escape($db, $productid) . "'
        AND prlpaydetailstransfile.payroll_id IN (
            SELECT pkey FROM prlmrollperiods
            WHERE fromdate >= '" . db_escape($db, FormatDateForSQL($datefrom)) . "' 
            AND todate <= '" . db_escape($db, FormatDateForSQL($dateto)) . "'
        )
    GROUP BY prlpaydetailstransfile.pfno, prlemployeemaster.nssf_no, prlemployeemaster.nhif_no, prlemployeemaster.pin_no,
             prlemployeemaster.fname, prlemployeemaster.mname, prlemployeemaster.lname
    ";

    return DB_query($sql, $db);
}

if (!function_exists('PrintByMonth')) {
    function PrintByMonth($payrollId, $productCode, $db) {
        $sql = "SELECT 
            prlpaydetailstransfile.pfno,
            prlemployeemaster.nssf_no AS nssfno,
            prlemployeemaster.nhif_no AS nhifno,
            prlemployeemaster.pin_no AS payeno,
            CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
            SUM(prlpaydetailstransfile.amount) AS total,
            COALESCE(SUM(prlpaydetailstransfile.employercontribution), 0) AS empl
        FROM prlpaydetailstransfile 
        JOIN prlemployeemaster ON prlpaydetailstransfile.pfno = prlemployeemaster.pf_no
        WHERE prlpaydetailstransfile.code = '" . db_escape($db, $productCode) . "' 
        AND prlpaydetailstransfile.payroll_id = '" . db_escape($db, $payrollId) . "'
        GROUP BY prlpaydetailstransfile.pfno,
            prlemployeemaster.nssf_no,
            prlemployeemaster.nhif_no,
            prlemployeemaster.pin_no,
            prlemployeemaster.fname,
            prlemployeemaster.mname,
            prlemployeemaster.lname
        ORDER BY prlpaydetailstransfile.pfno";
        
        return DB_query($sql, $db);
    }
}

if (!function_exists('FilterPrintByMonth')) {
    function FilterPrintByMonth($payrollId, $productCode, $staffId, $db) {
        $sql = "SELECT 
            prlpaydetailstransfile.pfno,
            prlemployeemaster.nssf_no AS nssfno,
            prlemployeemaster.nhif_no AS nhifno,
            prlemployeemaster.pin_no AS payeno,
            CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
            SUM(prlpaydetailstransfile.amount) AS total,
            COALESCE(SUM(prlpaydetailstransfile.employercontribution), 0) AS empl
        FROM prlpaydetailstransfile 
        JOIN prlemployeemaster ON prlpaydetailstransfile.pfno = prlemployeemaster.pf_no
        WHERE prlpaydetailstransfile.code = '" . db_escape($db, $productCode) . "' 
        AND prlpaydetailstransfile.payroll_id = '" . db_escape($db, $payrollId) . "'
        AND prlpaydetailstransfile.pfno = '" . db_escape($db, $staffId) . "'
        GROUP BY prlpaydetailstransfile.pfno,
            prlemployeemaster.nssf_no,
            prlemployeemaster.nhif_no,
            prlemployeemaster.pin_no,
            prlemployeemaster.fname,
            prlemployeemaster.mname,
            prlemployeemaster.lname
        ORDER BY prlpaydetailstransfile.pfno";
        
        return DB_query($sql, $db);
    }
}

if (!function_exists('PrintLoans')) {
    function PrintLoans($payrollId, $productCode, $db) {
        $sql = "SELECT 
            prlemployeemaster.idno AS pfno,
            CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
            prlloantrans.amount,
            prlloantrans.interest,
            COALESCE(prlloantrans.amount, 0) + COALESCE(prlloantrans.interest, 0) AS gross
        FROM prlloantrans 
        JOIN prlemployeemaster ON prlemployeemaster.pf_no = prlloantrans.pfno
        LEFT JOIN prlstaffloans ON prlloantrans.loanindex = prlstaffloans.loanindex 
        JOIN prlproducts ON prlstaffloans.deductcode = prlproducts.code 
        WHERE prlloantrans.payroll_id = '" . db_escape($db, $payrollId) . "'  
        AND prlproducts.code = '" . db_escape($db, $productCode) . "' 
        AND (prlstaffloans.saving = 0 OR prlstaffloans.saving IS NULL) 
        AND prlloantrans.amount > 0
        GROUP BY prlemployeemaster.idno, 
            prlemployeemaster.fname,
            prlemployeemaster.mname, 
            prlemployeemaster.lname, 
            prlloantrans.amount,
            prlloantrans.interest
        ORDER BY prlemployeemaster.idno";
        
        return DB_query($sql, $db);
    }
}

if (!function_exists('PrintSacco')) {
    function PrintSacco($payrollId, $productCode, $db) {
        $sql = "SELECT 
            prlemployeemaster.idno AS pfno,
            CONCAT(prlemployeemaster.fname, ' ', prlemployeemaster.mname, ' ', prlemployeemaster.lname) AS names,
            prlloantrans.amount,
            prlstaffloans.principal + COALESCE(SUM(prlloantrans.amount), 0) AS gross
        FROM prlstaffloans
        JOIN prlemployeemaster ON prlemployeemaster.pf_no = prlstaffloans.pfno
        JOIN prlloantrans ON prlloantrans.loanindex = prlstaffloans.loanindex 
        JOIN prlproducts ON prlstaffloans.deductcode = prlproducts.code 
        WHERE prlloantrans.payroll_id = '" . db_escape($db, $payrollId) . "' 
        AND prlproducts.code = '" . db_escape($db, $productCode) . "' 
        AND prlstaffloans.saving = 1 
        GROUP BY prlemployeemaster.idno, 
            prlemployeemaster.fname,
            prlemployeemaster.mname, 
            prlemployeemaster.lname, 
            prlstaffloans.instalment,
            prlstaffloans.principal,
            prlloantrans.amount
        ORDER BY prlemployeemaster.idno";
        
        return DB_query($sql, $db);
    }
}

if (!function_exists('PrintProducts')) {
    function PrintProducts($payrollId, $productCode, $db) {
        $sql = "SELECT 
            prlemployeemaster.idno AS pfno,
            CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
            SUM(prlpaydetailstransfile.amount) AS total,
            COALESCE(SUM(prlpaydetailstransfile.employercontribution), 0) AS empl
        FROM prlpaydetailstransfile 
        JOIN prlemployeemaster ON prlpaydetailstransfile.pfno = prlemployeemaster.pf_no
        WHERE prlpaydetailstransfile.code = '" . db_escape($db, $productCode) . "' 
        AND prlpaydetailstransfile.payroll_id = '" . db_escape($db, $payrollId) . "'
        GROUP BY prlemployeemaster.idno, 
            prlemployeemaster.fname,
            prlemployeemaster.mname, 
            prlemployeemaster.lname
        ORDER BY prlemployeemaster.idno";
        
        return DB_query($sql, $db);
    }
}

if (!function_exists('PrintP9')) {
    function PrintP9($year, $pfNo, $db) {
        $p9Data = array();
        
        for ($month = 1; $month <= 12; $month++) {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $sql = "SELECT 
                prlemployeemaster.pf_no AS pfno,
                " . (int)$month . " AS month,
                prlemployeemaster.pin_no AS pinno,
                CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
                COALESCE(prlpayroltransfile.basicpay, 0) + COALESCE(prlpayroltransfile.allowances, 0) + COALESCE(prlpayroltransfile.overtime, 0) - COALESCE(prlpayroltransfile.lateness_absent, 0) AS basicPay,
                COALESCE(prlpayroltransfile.non_cash_benefits, 0) AS noncash,
                COALESCE(prlpayroltransfile.pension, 0) AS pension,
                COALESCE(prlpayroltransfile.personalrelief, 0) AS perelief,
                COALESCE(prlpayroltransfile.insurancerelief, 0) AS inrelief,
                COALESCE((SELECT SUM(amount) FROM prlpaydetailstransfile WHERE pfno = prlemployeemaster.pf_no AND code = '1' AND payroll_id = prlpayroltransfile.payroll_id), 0) AS payetax
            FROM prlemployeemaster
            LEFT JOIN prlpayroltransfile ON prlemployeemaster.pf_no = prlpayroltransfile.pfno
            WHERE prlemployeemaster.pf_no = '" . db_escape($db, $pfNo) . "'
            AND prlpayroltransfile.payroll_id IN (
                SELECT pkey FROM prlmrollperiods 
                WHERE fromdate >= '" . db_escape($db, $startDate) . "' 
                AND todate <= '" . db_escape($db, $endDate) . "'
            )";
            
            $result = DB_query($sql, $db);
            if ($row = DB_fetch_array($result)) {
                $p9Data[] = $row;
            }
        }
        
        return $p9Data;
    }
}

if (!function_exists('PrintP9All')) {
    function PrintP9All($year, $db) {
        $p9Data = array();
        
        $empSql = "SELECT pf_no FROM prlemployeemaster 
                   WHERE (dateterminated IS NULL OR dateterminated = '0000-00-00 00:00:00' OR dateterminated > NOW())";
        $empResult = DB_query($empSql, $db);
        
        while ($empRow = DB_fetch_array($empResult)) {
            $pfNo = $empRow['pf_no'];
            
            for ($month = 1; $month <= 12; $month++) {
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate = date('Y-m-t', strtotime($startDate));
                
                $sql = "SELECT 
                    prlemployeemaster.pf_no AS pfno,
                    " . (int)$month . " AS month,
                    prlemployeemaster.pin_no AS pinno,
                    CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
                    COALESCE(prlpayroltransfile.basicpay, 0) + COALESCE(prlpayroltransfile.allowances, 0) + COALESCE(prlpayroltransfile.overtime, 0) - COALESCE(prlpayroltransfile.lateness_absent, 0) AS basicPay,
                    COALESCE(prlpayroltransfile.non_cash_benefits, 0) AS noncash,
                    COALESCE(prlpayroltransfile.pension, 0) AS pension,
                    COALESCE(prlpayroltransfile.personalrelief, 0) AS perelief,
                    COALESCE(prlpayroltransfile.insurancerelief, 0) AS inrelief,
                    COALESCE((SELECT SUM(amount) FROM prlpaydetailstransfile WHERE pfno = prlemployeemaster.pf_no AND code = '1' AND payroll_id = prlpayroltransfile.payroll_id), 0) AS payetax
                FROM prlemployeemaster
                LEFT JOIN prlpayroltransfile ON prlemployeemaster.pf_no = prlpayroltransfile.pfno
                WHERE prlemployeemaster.pf_no = '" . db_escape($db, $pfNo) . "'
                AND prlpayroltransfile.payroll_id IN (
                    SELECT pkey FROM prlmrollperiods 
                    WHERE fromdate >= '" . db_escape($db, $startDate) . "' 
                    AND todate <= '" . db_escape($db, $endDate) . "'
                )";
                
                $result = DB_query($sql, $db);
                if ($row = DB_fetch_array($result)) {
                    $p9Data[] = $row;
                }
            }
        }
        
        return $p9Data;
    }
}

if (!function_exists('PrintP10')) {
    function PrintP10($year, $db) {
        $p10Data = array();
        
        for ($month = 1; $month <= 12; $month++) {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $sql = "SELECT 
                " . (int)$month . " AS month,
                COALESCE(SUM(prlpaydetailstransfile.amount), 0) AS netpaye
            FROM prlpaydetailstransfile
            WHERE prlpaydetailstransfile.code = '1' 
            AND prlpaydetailstransfile.payroll_id IN (
                SELECT pkey FROM prlmrollperiods 
                WHERE fromdate >= '" . db_escape($db, $startDate) . "' 
                AND todate <= '" . db_escape($db, $endDate) . "'
            )";
            
            $result = DB_query($sql, $db);
            if ($row = DB_fetch_array($result)) {
                $p10Data[] = $row;
            }
        }
        
        return $p10Data;
    }
}

if (!function_exists('GetPayrollPeriodByMonthYear')) {
    function GetPayrollPeriodByMonthYear($year, $month, $db) {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "SELECT pkey, type, fromdate, todate, open 
                FROM prlmrollperiods 
                WHERE fromdate <= '" . db_escape($db, $endDate) . "' 
                AND todate >= '" . db_escape($db, $startDate) . "'";
        
        $result = DB_query($sql, $db);
        if ($row = DB_fetch_array($result)) {
            return $row;
        }
        return null;
    }
}

function epayslip2($staffid){
    global $db;
   
    $pid =(int) $_GET['prid'];
    $filterStaff = " pt.pfno='".$staffid."'";
    
    
    // Create the SQL query
    $sql = "
    SELECT 
        pt.pfno,
        CONCAT('Names :', TRIM(pm.status), ' ', TRIM(pm.fname), ' ', TRIM(pm.mname), ' ', TRIM(pm.lname)) AS names,
        IFNULL(pt.basicpay, 0) AS basicpay,
        pt.overtime,
        pt.lateness_absent,
        (pt.basicpay + IFNULL(pt.overtime, 0) - IFNULL(pt.lateness_absent, 0) + IFNULL(pt.allowances, 0) - IFNULL(pt.pension, 0) + IFNULL(pt.non_cash_benefits, 0)) AS taxableincome,
        pm.idno,
        pm.pin_no,
        bk.bankbranch,
        pm.bankacno,
        prlpositions.name AS designation,
        lc.name AS branch,
        pm.insurancerelief,
        pm.mortagerelief
    FROM prlemployeemaster pm
    LEFT JOIN employeebnkbranch bk ON pm.bankcode = bk.parentcode AND pm.bankcode2 = bk.code
    LEFT JOIN prlpositions ON pm.position = prlpositions.code
    LEFT JOIN prlestablishment lc ON pm.branch = lc.code
    LEFT JOIN prlpayroltransfile pt ON pt.pfno = pm.pf_no
    LEFT JOIN prlmrollperiods p ON pt.payroll_id = p.pkey
    WHERE p.pkey = $pid AND ($filterStaff)
    ORDER BY pt.pfno";
    
    $result = mysqli_query($db, $sql);
    
    if ($result) {
        $auto = 1;
        $payslips = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $balance = $row['basicpay'];
            $names = $row['names'];
            $pfno = $row['pfno'];
            $overtime = $row['overtime'];
            $lates = $row['lateness_absent'];
            $allowances = 0;
            $deductions = 0;
            
            // Add to payslips
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Pay Slip For ' . $row['monthof'] . ' of ' . $row['yearof'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'PR No:' . $pfno, 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'ID Number : ' . $row['idno'] . ' |PIN :' . $row['pin_no'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Bank Code: ' . $row['bankcode'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Bank Account :' . $row['bankacno'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Branch: ' . $row['branch'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Position :' . $row['designation'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => $names, 'Pfno' => $pfno, 'Format' => 'N'];
            
            // Add basic pay and overtime
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Basic Pay', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $row['basicpay']];
            
            if ($overtime > 0) {
                $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Over Time', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $overtime];
                $balance += $overtime;
            }
            
            // Add allowances
            $allowances = 0; // Fetch allowances from another table if necessary
            $balance += $allowances;
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Total Allowances:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $allowances + $overtime];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Gross Pay:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $balance];
            
            // Deductions
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Less Deductions:', 'Pfno' => $pfno, 'Format' => 'N'];
            
            if ($lates > 0) {
                $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Man Hours lost:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $lates];
                $balance -= $lates;
                $deductions += $lates;
            }
            
            // Add loan deductions if any
            $loans = 0; // Fetch loan data from another table if necessary
            $balance -= $loans;
            $deductions += $loans;
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Total Deductions:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $deductions];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Net pay:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $balance];
        }

        // Return the payslip array to the caller
        return $payslips;
    } else {
        // Return an empty array if there is an error fetching results
        return [];
    }
}

function payslips() {
    global $db;
    $pid = rtrim($_GET['prid']);

    // Initialize variables
    $auto = 0;
    $balance = 0;

    // Get the month and year from the payroll period
    $sqlPeriod = "SELECT MONTH(todate) AS monthof, YEAR(todate) AS yearof FROM prlmrollperiods WHERE pkey = '$pid'";
    $resultPeriod = DB_query($sqlPeriod, $db);
    $period = DB_fetch_array($resultPeriod);
    $monthof = $period['monthof'];
    $yearof = $period['yearof'];

    // Prepare the payslips array
    $payslips = [];

    // Cursor-like loop for fetching employee data
    $sqlEmployees = "SELECT 
                        pm.pf_no,
                        CONCAT('Names: ', TRIM(ifnull(pm.status,'')), ' ', TRIM(pm.fname), ' ', TRIM(pm.mname), ' ', TRIM(pm.lname)) AS names,
                        COALESCE(pt.basicpay, 0) AS basicpay,
                        pt.overtime,
                        pt.lateness_absent,
                        (pt.basicpay + COALESCE(pt.overtime, 0) - COALESCE(pt.lateness_absent, 0) + COALESCE(pt.allowances, 0) - COALESCE(pt.pension, 0) + COALESCE(pt.non_cash_benefits, 0)) AS taxableincome,
                        pm.idno,
                        pm.pin_no,
                        bk.bankbranch AS bankcode,
                        pm.bankacno,
                        prlpositions.name AS designation,
                        lc.name AS branch,
                        pm.insurancerelief,
                        pm.mortagerelief
                    FROM prlemployeemaster pm
                    LEFT JOIN employeebnkbranch bk ON pm.bankcode = bk.parentcode AND pm.bankcode2 = bk.code
                    LEFT JOIN prlpositions ON pm.position = prlpositions.code
                    LEFT JOIN prlestablishment lc ON pm.branch = lc.code
                    LEFT JOIN prlpayroltransfile pt ON pt.pfno = pm.pf_no
                    JOIN prlmrollperiods ON pt.payroll_id = prlmrollperiods.pkey
                    WHERE prlmrollperiods.pkey = '$pid' 
                    ORDER BY pt.pfno";

    $resultEmployees = DB_query($sqlEmployees, $db);

    while ($employee = DB_fetch_array($resultEmployees)) {
        // Extract employee data
        $pfno = $employee['pf_no'];
        $names = $employee['names'];
        $basicpay = $employee['basicpay'];
        $overtime = $employee['overtime'];
        $lates = $employee['lateness_absent'];
        $taxableincome = $employee['taxableincome'];
        $idno = $employee['idno'];
        $pin_no = $employee['pin_no'];
        $bankcode = $employee['bankcode'];
        $bankac = $employee['bankacno'];
        $designation = $employee['designation'];
        $locationname = $employee['branch'];
        $insurancerelief = $employee['insurancerelief'];
        $mortgagerelief = $employee['mortagerelief'];

        $balance = $basicpay; // Initialize balance

        // Add payslip entries
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Pay Slip For $monthof of $yearof",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "PR No: $pfno",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "ID Number: $idno | PIN: $pin_no",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Bank Code: $bankcode",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Bank Account: $bankac",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Branch: $locationname",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Position: $designation",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => $names,
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'l'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Basic Pay",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $basicpay
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Add Allowances:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];

        // Overtime entry
        if ($overtime > 0) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => "Over Time",
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $overtime
            ];
            $balance += $overtime;
        }

        // Allowances
        $sqlAllowances = "SELECT description, amount FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 1 AND amount > 0";
        $resultAllowances = DB_query($sqlAllowances, $db);
        while ($allowance = DB_fetch_array($resultAllowances)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $allowance['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $allowance['amount']
            ];
        }

        $totalAllowances = 0;
        $sqlTotalAllowances = "SELECT SUM(amount) AS total FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 1";
        $resultTotalAllowances = DB_query($sqlTotalAllowances, $db);
        $totalAllowances = DB_fetch_array($resultTotalAllowances)['total'] ?? 0;

        $balance += $totalAllowances;
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Total Allowances:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $totalAllowances + $overtime
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Gross Pay:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $balance
        ];

        // Deductions section
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'l'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Less Deductions:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];

        $deductions = 0;

        // Lateness deductions
        if ($lates > 0) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => "Man Hours lost:",
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $lates
            ];
            $balance -= $lates;
            $deductions += $lates;
        }

        // Other deductions
        $sqlDeductions = "SELECT description, amount FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 0 AND amount > 0";
        $resultDeductions = DB_query($sqlDeductions, $db);
        while ($deduction = DB_fetch_array($resultDeductions)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $deduction['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $deduction['amount']
            ];
        }

        $totalDeductions = 0;
        $sqlTotalDeductions = "SELECT SUM(amount) AS total FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND (deduction = 0 OR deduction IS NULL)";
        $resultTotalDeductions = DB_query($sqlTotalDeductions, $db);
        $totalDeductions = DB_fetch_array($resultTotalDeductions)['total'] ?? 0;

        $balance -= $totalDeductions;
        $deductions += $totalDeductions;

        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Less Loans / Balances",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];

        // Loan deductions
        $sqlLoans = "SELECT Prlproducts.description, prlloantrans.amount, 
                            CASE 
                                WHEN prlstaffloans.saving = 1 THEN prlstaffloans.principal + SUM(prlloantrans.amount) 
                                ELSE prlstaffloans.principal - COALESCE(prlstaffloans.openbalance, 0) - SUM(prlloantrans.amount) 
                            END AS LoanBalance
                     FROM prlloantrans 
                     JOIN prlstaffloans ON prlloantrans.loanindex = prlstaffloans.loanindex 
                     JOIN Prlproducts ON prlstaffloans.deductcode = Prlproducts.code 
                     WHERE prlloantrans.pfno = '$pfno' AND prlloantrans.payroll_id = '$pid' AND prlloantrans.amount > 0 
                     GROUP BY Prlproducts.description, prlstaffloans.principal, prlloantrans.amount, prlstaffloans.saving, prlstaffloans.openbalance";

        $resultLoans = DB_query($sqlLoans, $db);
        while ($loan = DB_fetch_array($resultLoans)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $loan['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $loan['amount'],
                'LoanBalance' => $loan['LoanBalance']
            ];
        }

        // Loan interest
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Less Loan Interest:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $sqlLoanInterest = "SELECT CONCAT('% on ', TRIM(Prlproducts.description)) AS description, prlloantrans.interest 
                            FROM prlloantrans 
                            JOIN prlstaffloans ON prlloantrans.loanindex = prlstaffloans.loanindex 
                            JOIN Prlproducts ON prlstaffloans.deductcode = Prlproducts.code 
                            WHERE prlloantrans.pfno = '$pfno' AND prlloantrans.payroll_id = '$pid' AND prlloantrans.interest > 0";
        $resultLoanInterest = DB_query($sqlLoanInterest, $db);
        while ($interest = DB_fetch_array($resultLoanInterest)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $interest['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $interest['interest']
            ];
        }

        $sqlLoanTotals = "SELECT COALESCE(SUM(amount), 0) + COALESCE(SUM(interest), 0) AS total
                          FROM prlloantrans
                          WHERE pfno = '$pfno' AND payroll_id = '$pid'";
        $resultLoanTotals = DB_query($sqlLoanTotals, $db);
        $loanTotals = DB_fetch_array($resultLoanTotals);
        $loanDeductions = $loanTotals['total'] ?? 0;
        $deductions += $loanDeductions;
        $balance -= $loanDeductions;

        // Total deductions and net pay
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Total Deductions:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $deductions
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Net pay:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $balance
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'X'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'N',
            'Notes' => 'PAYSLIP SUMMARY'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];

        // Non-cash benefits
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "List of NON-CASH Benefits applied:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $sqlNonCashBenefits = "SELECT LOWER(description) AS description, amount 
                                FROM prlpaydetailstransfile 
                                WHERE pfno = '$pfno' AND payroll_id = '$pid' AND non_cash_benefits = 3";
        $resultNonCashBenefits = DB_query($sqlNonCashBenefits, $db);
        while ($benefit = DB_fetch_array($resultNonCashBenefits)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $benefit['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $benefit['amount']
            ];
        }

        // Gross taxable pay
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Gross Taxable Pay",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $taxableincome
        ];

        // Reliefs
        $sqlReliefs = "SELECT LOWER(prlreliefs.name) AS name, reliefdeducted 
                       FROM prlpaydetailstransfile 
                       JOIN prlreliefs ON prlpaydetailstransfile.code = prlreliefs.productcode 
                       WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 0 AND reliefdeducted > 0";
        $resultReliefs = DB_query($sqlReliefs, $db);
        while ($relief = DB_fetch_array($resultReliefs)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $relief['name'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $relief['reliefdeducted']
            ];
        }

        // Insurance relief
        $sqlInsuranceRelief = "SELECT insurancerelief FROM prlpayroltransfile WHERE pfno = '$pfno' AND payroll_id = '$pid'";
        $resultInsuranceRelief = DB_query($sqlInsuranceRelief, $db);
        $insuranceRelief = DB_fetch_array($resultInsuranceRelief)['insurancerelief'] ?? 0;
        if ($insuranceRelief > 0) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => 'Insurance Relief',
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $insuranceRelief
            ];
        }
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'K'
        ];

        // Reset balance, deductions, and allowances for the next employee
        $balance = 0;
        $deductions = 0;
        $allowances = 0;
    }
   // Output the payslips data (you can choose how to return or display this)
    return $payslips;
}

function epayslip($staffid) {
global $db;
    $pid = rtrim($_GET['prid']);
    $filterStaff = " pm.pf_no='" . $staffid . "'";
    
    // Initialize variables
    $auto = 0;
    $balance = 0;

    // Get the month and year from the payroll period
    $sqlPeriod = "SELECT MONTH(todate) AS monthof, YEAR(todate) AS yearof FROM prlmrollperiods WHERE pkey = '$pid'";
    $resultPeriod = DB_query($sqlPeriod, $db);
    $period = DB_fetch_array($resultPeriod);
    $monthof = $period['monthof'];
    $yearof = $period['yearof'];

    // Prepare the payslips array
    $payslips = [];

    // Cursor-like loop for fetching employee data
    $sqlEmployees = "SELECT 
                        pm.pf_no,
                        CONCAT('Names: ', TRIM(ifnull(pm.status,'')), ' ', TRIM(pm.fname), ' ', TRIM(pm.mname), ' ', TRIM(pm.lname)) AS names,
                        COALESCE(pt.basicpay, 0) AS basicpay,
                        pt.overtime,
                        pt.lateness_absent,
                        (pt.basicpay + COALESCE(pt.overtime, 0) - COALESCE(pt.lateness_absent, 0) + COALESCE(pt.allowances, 0) - COALESCE(pt.pension, 0) + COALESCE(pt.non_cash_benefits, 0)) AS taxableincome,
                        pm.idno,
                        pm.pin_no,
                        bk.bankbranch AS bankcode,
                        pm.bankacno,
                        prlpositions.name AS designation,
                        lc.name AS branch,
                        pm.insurancerelief,
                        pm.mortagerelief
                    FROM prlemployeemaster pm
                    LEFT JOIN employeebnkbranch bk ON pm.bankcode = bk.parentcode AND pm.bankcode2 = bk.code
                    LEFT JOIN prlpositions ON pm.position = prlpositions.code
                    LEFT JOIN prlestablishment lc ON pm.branch = lc.code
                    LEFT JOIN prlpayroltransfile pt ON pt.pfno = pm.pf_no
                    JOIN prlmrollperiods ON pt.payroll_id = prlmrollperiods.pkey
                    WHERE prlmrollperiods.pkey = '$pid' AND ($filterStaff)
                    ORDER BY pt.pfno";

    $resultEmployees = DB_query($sqlEmployees, $db);

    while ($employee = DB_fetch_array($resultEmployees)) {
        // Extract employee data
        $pfno = $employee['pf_no'];
        $names = $employee['names'];
        $basicpay = $employee['basicpay'];
        $overtime = $employee['overtime'];
        $lates = $employee['lateness_absent'];
        $taxableincome = $employee['taxableincome'];
        $idno = $employee['idno'];
        $pin_no = $employee['pin_no'];
        $bankcode = $employee['bankcode'];
        $bankac = $employee['bankacno'];
        $designation = $employee['designation'];
        $locationname = $employee['branch'];
        $insurancerelief = $employee['insurancerelief'];
        $mortgagerelief = $employee['mortagerelief'];

        $balance = $basicpay; // Initialize balance

        // Add payslip entries
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Pay Slip For $monthof of $yearof",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "PR No: $pfno",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "ID Number: $idno | PIN: $pin_no",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Bank Code: $bankcode",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Bank Account: $bankac",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Branch: $locationname",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Position: $designation",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => $names,
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'l'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Basic Pay",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $basicpay
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Add Allowances:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];

        // Overtime entry
        if ($overtime > 0) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => "Over Time",
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $overtime
            ];
            $balance += $overtime;
        }

        // Allowances
        $sqlAllowances = "SELECT description, amount FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 1 AND amount > 0";
        $resultAllowances = DB_query($sqlAllowances, $db);
        while ($allowance = DB_fetch_array($resultAllowances)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $allowance['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $allowance['amount']
            ];
        }

        $totalAllowances = 0;
        $sqlTotalAllowances = "SELECT SUM(amount) AS total FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 1";
        $resultTotalAllowances = DB_query($sqlTotalAllowances, $db);
        $totalAllowances = DB_fetch_array($resultTotalAllowances)['total'] ?? 0;

        $balance += $totalAllowances;
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Total Allowances:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $totalAllowances + $overtime
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Gross Pay:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $balance
        ];

        // Deductions section
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'l'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Less Deductions:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];

        $deductions = 0;

        // Lateness deductions
        if ($lates > 0) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => "Man Hours lost:",
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $lates
            ];
            $balance -= $lates;
            $deductions += $lates;
        }

        // Other deductions
        $sqlDeductions = "SELECT description, amount FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 0 AND amount > 0";
        $resultDeductions = DB_query($sqlDeductions, $db);
        while ($deduction = DB_fetch_array($resultDeductions)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $deduction['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $deduction['amount']
            ];
        }

        $totalDeductions = 0;
        $sqlTotalDeductions = "SELECT SUM(amount) AS total FROM prlpaydetailstransfile WHERE pfno = '$pfno' AND payroll_id = '$pid' AND (deduction = 0 OR deduction IS NULL)";
        $resultTotalDeductions = DB_query($sqlTotalDeductions, $db);
        $totalDeductions = DB_fetch_array($resultTotalDeductions)['total'] ?? 0;

        $balance -= $totalDeductions;
        $deductions += $totalDeductions;

        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Less Loans / Balances",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];

        // Loan deductions
        $sqlLoans = "SELECT Prlproducts.description, prlloantrans.amount, 
                            CASE 
                                WHEN prlstaffloans.saving = 1 THEN prlstaffloans.principal + SUM(prlloantrans.amount) 
                                ELSE prlstaffloans.principal - COALESCE(prlstaffloans.openbalance, 0) - SUM(prlloantrans.amount) 
                            END AS LoanBalance
                     FROM prlloantrans 
                     JOIN prlstaffloans ON prlloantrans.loanindex = prlstaffloans.loanindex 
                     JOIN Prlproducts ON prlstaffloans.deductcode = Prlproducts.code 
                     WHERE prlloantrans.pfno = '$pfno' AND prlloantrans.payroll_id = '$pid' AND prlloantrans.amount > 0 
                     GROUP BY Prlproducts.description, prlstaffloans.principal, prlloantrans.amount, prlstaffloans.saving, prlstaffloans.openbalance";

        $resultLoans = DB_query($sqlLoans, $db);
        while ($loan = DB_fetch_array($resultLoans)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $loan['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $loan['amount'],
                'LoanBalance' => $loan['LoanBalance']
            ];
        }

        // Loan interest
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Less Loan Interest:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $sqlLoanInterest = "SELECT CONCAT('% on ', TRIM(Prlproducts.description)) AS description, prlloantrans.interest 
                            FROM prlloantrans 
                            JOIN prlstaffloans ON prlloantrans.loanindex = prlstaffloans.loanindex 
                            JOIN Prlproducts ON prlstaffloans.deductcode = Prlproducts.code 
                            WHERE prlloantrans.pfno = '$pfno' AND prlloantrans.payroll_id = '$pid' AND prlloantrans.interest > 0";
        $resultLoanInterest = DB_query($sqlLoanInterest, $db);
        while ($interest = DB_fetch_array($resultLoanInterest)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $interest['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $interest['interest']
            ];
        }

        $sqlLoanTotals = "SELECT COALESCE(SUM(amount), 0) + COALESCE(SUM(interest), 0) AS total
                          FROM prlloantrans
                          WHERE pfno = '$pfno' AND payroll_id = '$pid'";
        $resultLoanTotals = DB_query($sqlLoanTotals, $db);
        $loanTotals = DB_fetch_array($resultLoanTotals);
        $loanDeductions = $loanTotals['total'] ?? 0;
        $deductions += $loanDeductions;
        $balance -= $loanDeductions;

        // Total deductions and net pay
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Total Deductions:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $deductions
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Net pay:",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $balance
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'X'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'N',
            'Notes' => 'PAYSLIP SUMMARY'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];

        // Non-cash benefits
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "List of NON-CASH Benefits applied:",
            'Pfno' => $pfno,
            'Format' => 'N'
        ];
        $sqlNonCashBenefits = "SELECT LOWER(description) AS description, amount 
                                FROM prlpaydetailstransfile 
                                WHERE pfno = '$pfno' AND payroll_id = '$pid' AND non_cash_benefits = 3";
        $resultNonCashBenefits = DB_query($sqlNonCashBenefits, $db);
        while ($benefit = DB_fetch_array($resultNonCashBenefits)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $benefit['description'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $benefit['amount']
            ];
        }

        // Gross taxable pay
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Notes' => "Gross Taxable Pay",
            'Pfno' => $pfno,
            'Format' => 'N',
            'Amount' => $taxableincome
        ];

        // Reliefs
        $sqlReliefs = "SELECT LOWER(prlreliefs.name) AS name, reliefdeducted 
                       FROM prlpaydetailstransfile 
                       JOIN prlreliefs ON prlpaydetailstransfile.code = prlreliefs.productcode 
                       WHERE pfno = '$pfno' AND payroll_id = '$pid' AND deduction = 0 AND reliefdeducted > 0";
        $resultReliefs = DB_query($sqlReliefs, $db);
        while ($relief = DB_fetch_array($resultReliefs)) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => $relief['name'],
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $relief['reliefdeducted']
            ];
        }

        // Insurance relief
        $sqlInsuranceRelief = "SELECT insurancerelief FROM prlpayroltransfile WHERE pfno = '$pfno' AND payroll_id = '$pid'";
        $resultInsuranceRelief = DB_query($sqlInsuranceRelief, $db);
        $insuranceRelief = DB_fetch_array($resultInsuranceRelief)['insurancerelief'] ?? 0;
        if ($insuranceRelief > 0) {
            $payslips[] = [
                'ItemId' => $auto++,
                'Notes' => 'Insurance Relief',
                'Pfno' => $pfno,
                'Format' => 'N',
                'Amount' => $insuranceRelief
            ];
        }
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'b'
        ];
        $payslips[] = [
            'ItemId' => $auto++,
            'Pfno' => $pfno,
            'Format' => 'K'
        ];

        // Reset balance, deductions, and allowances for the next employee
        $balance = 0;
        $deductions = 0;
        $allowances = 0;
    }
   // Output the payslips data (you can choose how to return or display this)
    return $payslips;
}

function onepayslip(){
global $db;

$pid =(int) $_GET['prid'];
    
  if(count($_POST['StaffID'])>1){
    foreach ($_POST['StaffID'] as $staffid) {
        $filterStaff .= "  pm.pf_no='".$staffid."'  or";
    }
   $str_len = strlen($filterStaff);
   $filterStaff = mb_strcut($filterStaff,0,$str_len-3);
  } else {
   $staffid = $_POST['StaffID'][0];
   $filterStaff = " pm.pf_no='".$staffid."'";
 }
 
 
    // Create the SQL query
    $sql = "
    SELECT 
        pt.pfno,
        CONCAT('Names :', TRIM(pm.fname), ' ', TRIM(pm.mname), ' ', TRIM(pm.lname)) AS names,
        IFNULL(pt.basicpay, 0) AS basicpay,
        pt.overtime,
        pt.lateness_absent,
        (pt.basicpay + IFNULL(pt.overtime, 0) - IFNULL(pt.lateness_absent, 0) + IFNULL(pt.allowances, 0) - IFNULL(pt.pension, 0) + IFNULL(pt.non_cash_benefits, 0)) AS taxableincome,
        pm.idno,
        pm.pin_no,
        bk.bankbranch,
        pm.bankacno,
        prlpositions.name AS designation,
        lc.name AS branch,
        pm.insurancerelief,
        pm.mortagerelief
    FROM prlemployeemaster pm
    LEFT JOIN employeebnkbranch bk ON pm.bankcode = bk.parentcode AND pm.bankcode2 = bk.code
    LEFT JOIN prlpositions ON pm.position = prlpositions.code
    LEFT JOIN prlestablishment lc ON pm.branch = lc.code
    LEFT JOIN prlpayroltransfile pt ON pt.pfno = pm.pf_no
    LEFT JOIN prlmrollperiods p ON pt.payroll_id = p.pkey
    WHERE p.pkey = $pid AND ($filterStaff)
    ORDER BY pt.pfno";
    
    $result = mysqli_query($db, $sql);
    
    if ($result) {
        $auto = 1;
        $payslips = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $balance = $row['basicpay'];
            $names = $row['names'];
            $pfno = $row['pfno'];
            $overtime = $row['overtime'];
            $lates = $row['lateness_absent'];
            $allowances = 0;
            $deductions = 0;
            
            // Add to payslips
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Pay Slip For ' . $row['monthof'] . ' of ' . $row['yearof'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'PR No:' . $pfno, 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'ID Number : ' . $row['idno'] . ' |PIN :' . $row['pin_no'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Bank Code: ' . $row['bankcode'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Bank Account :' . $row['bankacno'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Branch: ' . $row['branch'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Position :' . $row['designation'], 'Pfno' => $pfno, 'Format' => 'N'];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => $names, 'Pfno' => $pfno, 'Format' => 'N'];
            
            // Add basic pay and overtime
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Basic Pay', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $row['basicpay']];
            
            if ($overtime > 0) {
                $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Over Time', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $overtime];
                $balance += $overtime;
            }
            
            // Add allowances
            $allowances = 0; // Fetch allowances from another table if necessary
            $balance += $allowances;
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Total Allowances:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $allowances + $overtime];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Gross Pay:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $balance];
            
            // Deductions
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Less Deductions:', 'Pfno' => $pfno, 'Format' => 'N'];
            
            if ($lates > 0) {
                $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Man Hours lost:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $lates];
                $balance -= $lates;
                $deductions += $lates;
            }
            
            // Add loan deductions if any
            $loans = 0; // Fetch loan data from another table if necessary
            $balance -= $loans;
            $deductions += $loans;
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Total Deductions:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $deductions];
            $payslips[] = ['ItemId' => $auto++, 'Notes' => 'Net pay:', 'Pfno' => $pfno, 'Format' => 'N', 'Amount' => $balance];
        }

        // Return the payslip array to the caller
        return $payslips;
    } else {
        // Return an empty array if there is an error fetching results
        return [];
    }
}

// printLoans removed - functions consolidated
// printSacco removed - functions consolidated

function getmasterroll() {
    global $db; // Assuming $db is your MySQL database connection object

    $gid = isset($_GET['Mid']) && mb_strlen($_GET['Mid']) > 0 ? $_GET['Mid'] : 0;

    // Retrieve product codes and descriptions
    $SQLproducts = "SELECT code, description, deduction FROM prlproducts "
        . "WHERE code NOT IN (SELECT deductcode FROM prlstaffloans) "
        . "AND code IN (SELECT code FROM prlpaydetailstransfile WHERE payroll_id >= " . $gid . ") "
        . "ORDER BY deduction DESC";

    $ResultIndex = $db->query($SQLproducts);

    $masterrollColumns = ""; // Store dynamic column definitions

    while ($rows = $ResultIndex->fetch_assoc()) {
        $masterrollColumns .= ($rows['deduction'] == 1 ? 'A' : 'D') . '_' . rtrim($rows['code']) . " FLOAT, ";
    }

    // Create temporary table (MySQL syntax)
    $createTempTableSql = "CREATE TEMPORARY TABLE masterroll (
        " . $masterrollColumns . "
        pfno CHAR(20),
        names VARCHAR(50),
        basicpay FLOAT,
        Overtime FLOAT,
        Manhrlost FLOAT,
        Loans FLOAT
    ); ";

    $db->query($createTempTableSql);

    // Insert data into the temporary table
    $insertSql = "INSERT INTO masterroll SELECT ";

    $ResultIndex = $db->query($SQLproducts);

    $selectColumns = ""; // Store dynamic select columns

    while ($rows = $ResultIndex->fetch_assoc()) {
        $selectColumns .= "pfle_" . rtrim($rows['code']) . ".amount, ";
    }

    $insertSql .= $selectColumns . "
        prlpayroltransfile.pfno,
        CONCAT(TRIM(prlemployeemaster.fname), ' ', TRIM(prlemployeemaster.mname), ' ', TRIM(prlemployeemaster.lname)) AS names,
        IFNULL(prlpayroltransfile.basicpay, 0) AS basicpay,
        IFNULL(prlpayroltransfile.overtime, 0) AS Overtime,
        IFNULL(prlpayroltransfile.lateness_absent, 0) AS Manhrlost,
        (SELECT IFNULL(SUM(prlloantrans.amount), 0) + IFNULL(SUM(prlloantrans.interest), 0) 
         FROM prlloantrans 
         WHERE prlloantrans.pfno = prlpayroltransfile.pfno AND prlloantrans.payroll_id = '" . $gid . "') AS totalloan
    FROM prlpayroltransfile
    LEFT JOIN prlemployeemaster ON prlpayroltransfile.pfno = prlemployeemaster.pf_no ";

    $ResultIndex = $db->query($SQLproducts);

    while ($rows = $ResultIndex->fetch_assoc()) {
        $insertSql .= "
        LEFT JOIN prlpaydetailstransfile pfle_" . rtrim($rows['code']) . " ON 
            prlpayroltransfile.pfno = pfle_" . rtrim($rows['code']) . ".pfno AND 
            pfle_" . rtrim($rows['code']) . ".code = '" . $rows['code'] . "' AND 
            pfle_" . rtrim($rows['code']) . ".payroll_id = '" . $gid . "'";
    }

    $insertSql .= "
    WHERE prlpayroltransfile.payroll_id = '" . $gid . "'
    GROUP BY ";

    $ResultIndex = $db->query($SQLproducts);

    while ($rows = $ResultIndex->fetch_assoc()) {
        $insertSql .= "pfle_" . rtrim($rows['code']) . ".amount, ";
    }

    $insertSql .= "
    prlpayroltransfile.pfno,
    prlpayroltransfile.basicpay,
    prlpayroltransfile.overtime,
    prlpayroltransfile.lateness_absent,
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname;";

    $db->query($insertSql);

    // Select from the temporary table and return the result
    return sqlselect($gid);
}

function sqlselect($gid = 0) {
    global $db;

    $sum = "(basicpay + Overtime)";

    $SQLproducts = "SELECT code, description, deduction FROM prlproducts "
        . "WHERE code NOT IN (SELECT deductcode FROM prlstaffloans) "
        . "AND code IN (SELECT code FROM prlpaydetailstransfile WHERE payroll_id >= " . $gid . ") "
        . "ORDER BY deduction DESC";

    $ResultIndex = $db->query($SQLproducts);
    $numrows = $ResultIndex->num_rows;

    $sqlselect = "SELECT pfno, names, basicpay, Overtime, ";

    while ($rows = $ResultIndex->fetch_assoc()) {
        $sqlselect .= ($rows['deduction'] == 1 ? 'A' : 'D') . '_' . rtrim($rows['code']) . ", ";
        $sum .= ($rows['deduction'] == 1 ? '+' : '-') . 'IFNULL(' . ($rows['deduction'] == 1 ? 'A' : 'D') . '_' . rtrim($rows['code']) . ', 0)';
    }

    $sum .= " - Manhrlost - Loans) AS netpay";
    $sqlselect .= "Manhrlost, Loans, " . $sum . " FROM masterroll ORDER BY pfno ASC";

    $result = $db->query($sqlselect);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $db->query("DROP TEMPORARY TABLE masterroll");

    return $data;
}

function MakeTimedataReady($fromdate, $todate, $period) {
    global $db;

    // Step 1: Initialize variables
    $today = date('Y-m-d', strtotime($fromdate));
    $lastWorkingDay = date('Y-m-d', strtotime($todate));
    
    // Step 2: Create an empty table for working days
    $workingDays = [];

    // Step 3: Generate the list of working days between fromdate and todate
    $currentDate = strtotime($today);
    $endDate = strtotime($lastWorkingDay);

    while ($currentDate <= $endDate) {
        $workingDays[] = date('Y-m-d', $currentDate);
        $currentDate = strtotime('+1 day', $currentDate);  // Increment by 1 day
    }

    // Step 4: Insert data for prltimesheet
    foreach ($workingDays as $date) {
        // Step 4a: Insert for freqcode = 0 employees (those without leave)
        $SQL = "INSERT INTO prltimesheet (pfno, date, period, recommended, ShouldLogIn)
                SELECT pf_no, '$date', '$period', noofhrsperday, 1
                FROM prlemployeemaster
                WHERE freqcode = 0";
        mysqli_query($db, $SQL);

        // Step 4b: Check if the current date is a working day and not a holiday
        $dateName = date('l', strtotime($date));  // Get the day of the week (e.g., Monday, Tuesday)
        
        $query = "SELECT isworkingday FROM Dayoftheweeks WHERE Dayoftheweek = '$dateName'";
        $result = mysqli_query($db, $query);
        $row = mysqli_fetch_assoc($result);
        $isWorkingDay = $row['isworkingday'];

        if ($isWorkingDay == 1) {
            // Check if it's a holiday
            $query = "SELECT name FROM prlspecialdays WHERE DAY(date) = DAY('$date') AND MONTH(date) = MONTH('$date')";
            $result = mysqli_query($db, $query);
            $holiday = mysqli_fetch_assoc($result);

            if (!$holiday) {
                // Insert for freqcode > 0 employees (those with leave)
                $SQL = "INSERT INTO prltimesheet (pfno, date, period, recommended, ShouldLogIn)
                        SELECT pf_no, '$date', '$period', noofhrsperday, 1
                        FROM prlemployeemaster
                        WHERE pf_no NOT IN (
                            SELECT pfno FROM prlstaffleaveplanner
                            WHERE '$date' BETWEEN leavedue AND leavend
                        ) AND freqcode > 0";
                mysqli_query($db, $SQL);
            }
        }
    }
}

function GetDayOfWeeks($startDate='2025-01-01', $endDate='2025-01-07') {
    global $db;

    // Prepare the SQL query to get the days of the week
    $sql = "SELECT DAYNAME(dateValue) AS dayName
            FROM (
                SELECT DATE_ADD('$startDate', INTERVAL @i:=@i + 1 DAY) AS dateValue
                FROM (SELECT @i:=-1) AS init
                WHERE @i < DATEDIFF('$endDate', '$startDate')
            ) AS daysOfWeek";

    // Execute the query
    $result = DB_query($sql, $db);

    // Fetch and return the results
    $days = [];
    while ($row = DB_fetch_array($result)) {
        $days[] = $row['dayName'];
    }

    return $days;
}
 
function SetExpectedDateOfReturn($fromDate, $noOfDays = 0) {
    global $db;

    // Initialize variables
    $today = new DateTime($fromDate);
    $days = intval($noOfDays);
    $lastWorkingDay = clone $today;

    // Create an array to hold working days
    $workingDays = [];

    // Calculate the last working day
    $lastWorkingDay->modify("+$days days");

    // Loop through the days from today to the calculated last working day
    while ($today <= $lastWorkingDay) {
        $workingDays[] = $today->format('Y-m-d');
        $today->modify('+1 day');
    }

    // Check for holidays and weekends
    $finalReturnDate = $lastWorkingDay;
    foreach ($workingDays as $date) {
        $dayName = date('l', strtotime($date));

        // Check if it's a working day using a database query
        $isWorkingDayQuery = "SELECT isworkingday FROM Dayoftheweeks WHERE Dayoftheweek = '$dayName'";
        $result = DB_query($isWorkingDayQuery, $db);
        $isWorkingDay = DB_fetch_array($result)['isworkingday'];

        // Check if the date is a holiday
        $holidayQuery = "SELECT name FROM prlspecialdays WHERE day = DAY('$date') AND month = MONTH('$date')";
        $holidayResult = DB_query($holidayQuery, $db);
        $holiday = DB_fetch_array($holidayResult)['name'];

        // If it's not a working day or a holiday, extend the expected return date
        if ($isWorkingDay == 0 || $holiday) {
            $finalReturnDate->modify('+1 day');
        }
    }

    // Return the final expected date of return
    return $finalReturnDate->format('Y-m-d');
}

function GetDOWdateOfReturn($checkDate) {
    global $db;

    // Convert the checkDate to a DateTime object
    $today = new DateTime($checkDate);
    $lastWorkingDay = clone $today;

    // Set the range for checking dates (14 days ahead)
    $lastWorkingDay->modify('+14 days');

    // Prepare an array to hold working days
    $workingDays = [];
    
    // Generate the dates to check
    while ($today <= $lastWorkingDay) {
        $workingDays[] = $today->format('Y-m-d');
        $today->modify('+1 day');
    }

    // Check for the next working day
    foreach ($workingDays as $date) {
        $dayName = date('l', strtotime($date));

        // Check if it's a working day
        $isWorkingDayQuery = "SELECT isworkingday FROM Dayoftheweeks WHERE Dayoftheweek = '$dayName'";
        $result = DB_query($isWorkingDayQuery, $db);
        $isWorkingDay = DB_fetch_array($result)['isworkingday'];

        // Check if the date is a holiday
        $holidayQuery = "SELECT name FROM prlspecialdays WHERE day = DAY('$date') AND month = MONTH('$date')";
        $holidayResult = DB_query($holidayQuery, $db);
        $holiday = DB_fetch_array($holidayResult)['name'];

        // If it's a working day and not a holiday, return the date
        if ($isWorkingDay == 1 && !$holiday) {
            return $date; // Return the next working day
        }
    }

    // If no working day is found in the range, return null
    return null;
}




function generatepayroll() {
    global $db;
    $gid = $_GET['Gid'];
    $InputError = 0;

    mysqli_begin_transaction($db);

    if (!processLoanTransactions($db, $gid) ||
        !processPayrollTransactions($db, $gid) ||
        !processPayDetailsTransactions($db, $gid)) {
        $InputError = 1;
    }

    if ($InputError == 1) {
        // exited loop with errors so rollback
        echo "Failed to execute, Batch has been rolled back";
        mysqli_rollback($db);
    } else {
        // all good so commit data transaction
        mysqli_commit($db);
        echo "The payroll has been updated";
    }
}

function processLoanTransactions($db, $gid) {
    mysqli_query($db, "DELETE FROM prlloantrans WHERE payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'");
    if (mysqli_errno($db)) {
        return false;
    }

    $loans_query = "SELECT prlstaffloans.loanindex, prlstaffloans.instalment, prlstaffloans.interest, prlstaffloans.interesttype, prlstaffloans.pfno, prlstaffloans.saving, prlmrollperiods.pkey, prlstaffloans.principal FROM prlstaffloans JOIN prlemployeemaster ON prlemployeemaster.pf_no = prlstaffloans.pfno JOIN prlmrollperiods ON prlemployeemaster.freqcode = prlmrollperiods.type WHERE prlmrollperiods.pkey = '" . mysqli_real_escape_string($db, $gid) . "' AND prlstaffloans.startdate <= CURDATE()";
    $loans_result = mysqli_query($db, $loans_query);

    if ($loans_result) {
        while ($loan_row = mysqli_fetch_assoc($loans_result)) {
            $loanindex = $loan_row['loanindex'];
            $instalment = $loan_row['instalment'];
            $interest = $loan_row['interest'];
            $interesttype = $loan_row['interesttype'];
            $pfno = $loan_row['pfno'];
            $saving = $loan_row['saving'];
            $pkey = $loan_row['pkey'];
            $principal = $loan_row['principal'];

            $deducted_loan_amount = deductLoan($loanindex, $pkey, $db);

            if ($saving == 0) {
                $balance = ($deducted_loan_amount > 0) ? min($deducted_loan_amount, $instalment) : 0;
                $interest_amount = ($interest > 0) ? (($interesttype == 2) ? $principal * ($interest * 0.01) : $deducted_loan_amount * ($interest * 0.01)) : 0;

            } else {
                $balance = $instalment;
                $interest_amount = 0;
            }
            $insert_loan_trans = "INSERT INTO prlloantrans (loanindex, amount, interest, pfno, payroll_id) VALUES ('" . mysqli_real_escape_string($db, $loanindex) . "', '" . mysqli_real_escape_string($db, $balance) . "', '" . mysqli_real_escape_string($db, $interest_amount) . "', '" . mysqli_real_escape_string($db, $pfno) . "', '" . mysqli_real_escape_string($db, $gid) . "')";
            mysqli_query($db, $insert_loan_trans);
            if (mysqli_errno($db)) {
                return false;
            }
        }
    } else {
        return false;
    }
    return true;
}

function processPayrollTransactions($db, $gid) {
    mysqli_query($db, "DELETE FROM prlpayroltransfile WHERE payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'");
    if (mysqli_errno($db)) {
        return false;
    }


    $payroll_query = "SELECT prlemployeemaster.pf_no, prlemployeemaster.freqcode, prlemployeemaster.basicpay, prlemployeemaster.noofhrsperday, prlemployeemaster.insurancerelief, prlmrollperiods.fromdate, prlmrollperiods.todate FROM prlemployeemaster LEFT JOIN prlmrollperiods ON prlmrollperiods.pkey = '$gid' WHERE (prlemployeemaster.dateterminated IS NULL OR prlemployeemaster.dateterminated >= prlmrollperiods.todate) AND prlemployeemaster.dateemployed <= prlmrollperiods.fromdate";
    $payroll_result = mysqli_query($db, $payroll_query);

    if ($payroll_result) {
        while ($payroll_row = mysqli_fetch_assoc($payroll_result)) {
            $pfno = $payroll_row['pf_no'];
            $freqcode = $payroll_row['freqcode'];
            $basicpay = $payroll_row['basicpay'];
            $noofhrsperday = $payroll_row['noofhrsperday'];
            $insurancerelief = $payroll_row['insurancerelief'];
            $fromdate = $payroll_row['fromdate'];
            $todate = $payroll_row['todate'];

            $daystotal = date('d', strtotime($todate));

            $basicpay_calc = ($freqcode == 0) ? ((isset($basicpay) ? $basicpay : 0) * getLoginCount($db, $pfno, $fromdate, $todate)) : (isset($basicpay) ? $basicpay : 0);
            $overtime_calc = ($freqcode > 0) ? calculateOvertime($db, $pfno, $basicpay, $noofhrsperday, $daystotal, $fromdate, $todate) : 0;
            $lateness_absent_calc = ($freqcode > 0 && employeeShouldLogin($db, $pfno, $fromdate, $todate)) ? calculateLatenessAbsent($db, $pfno, $basicpay, $noofhrsperday, $daystotal, $fromdate, $todate) : 0;
            $allowances_calc = calculateAllowances($db, $pfno);
            $pension_calc = calculatePension($db, $pfno);
            $non_cash_calc = calculateNonCashBenefits($db, $pfno);
            $pensionable = calculatePensionable($db, $pfno, $gid);

             $insert_query = "INSERT INTO prlpayroltransfile (pfno, payroll_id, basicpay, overtime, lateness_absent, allowances, pension, non_cash_benefits, personalrelief, insurancerelief, rowid) VALUES ('" . mysqli_real_escape_string($db, $pfno) . "', '" . mysqli_real_escape_string($db, $gid) . "', " . floatval($basicpay_calc) . ", " . floatval($overtime_calc) . ", " . floatval($lateness_absent_calc) . ", " . floatval($allowances_calc) . ", " . floatval($pensionable) . ", " . floatval($non_cash_calc) . ", 0, " . floatval($insurancerelief) . ", 0)";
            $insert_result = mysqli_query($db, $insert_query);
            if (!$insert_result) {
                error_log("Insert error: " . mysqli_error($db) . " - PFNO: " . $pfno);
                return false;
            }

            $personal_relief_calc = getindividualrelief($pfno, $gid, 0, $db);
            $update_relief_query = "UPDATE prlpayroltransfile
                                    SET personalrelief = " . floatval($personal_relief_calc) . "
                                    WHERE payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'
                                      AND pfno = '" . mysqli_real_escape_string($db, $pfno) . "'";
            if (!mysqli_query($db, $update_relief_query)) {
                error_log("Relief update error: " . mysqli_error($db) . " - PFNO: " . $pfno);
                return false;
            }
        }
    } else {
        return false;
    }
    
    return true;
}

function calculateAllowances($db, $pfno) {
    $allowances_query = "SELECT IFNULL(SUM(prlmatrix.amount), 0) AS allowances FROM prlmatrix JOIN prlproducts ON prlmatrix.prodid = prlproducts.code WHERE prlmatrix.deduction = 1 AND prlmatrix.pfno = '$pfno' AND (prlproducts.pens_tax = 0 OR prlproducts.pens_tax = 1 OR prlproducts.pens_tax = 2)";
    $allowances_result = mysqli_query($db, $allowances_query);
    if ($allowances_result) {
        $allowances_row = mysqli_fetch_assoc($allowances_result);
        return $allowances_row['allowances'];
    } else {
        return 0; // Or handle the error as needed
    }
}

function calculatePension($db, $pfno) {
    $pension_query = "SELECT IFNULL(SUM(prlmatrix.amount), 0) AS pension FROM prlmatrix JOIN prlproducts ON prlmatrix.prodid = prlproducts.code WHERE prlmatrix.deduction = 0 AND prlmatrix.pfno = '$pfno' AND prlproducts.pens_tax = 2";
    $pension_result = mysqli_query($db, $pension_query);
    if ($pension_result) {
        $pension_row = mysqli_fetch_assoc($pension_result);
        return $pension_row['pension'];
    } else {
        return 0; // Or handle the error as needed
    }
}

function calculateNonCashBenefits($db, $pfno) {
    $non_cash_query = "SELECT IFNULL(SUM(prlmatrix.amount), 0) AS non_cash FROM prlmatrix JOIN prlproducts ON prlmatrix.prodid = prlproducts.code WHERE prlmatrix.deduction = 1 AND prlmatrix.pfno = '$pfno' AND prlproducts.pens_tax = 3";
    $non_cash_result = mysqli_query($db, $non_cash_query);
    if ($non_cash_result) {
        $non_cash_row = mysqli_fetch_assoc($non_cash_result);
        return $non_cash_row['non_cash'];
    } else {
        return 0; // Or handle the error as needed
    }
}

function deductLoan($loanindex, $payroll_id, $mysqli) {
    $loanbalance = 0.0;
    $instalment = 0.0;

     
    $query = "
        SELECT 
            (prlstaffloans.principal - IFNULL(SUM(prlloantrans.amount), 0)) AS loanbalance,
            prlstaffloans.instalment
        FROM prlstaffloans
        LEFT JOIN prlloantrans 
            ON prlstaffloans.loanindex = prlloantrans.loanindex 
            AND prlloantrans.payroll_id <= ?
        WHERE prlstaffloans.loanindex = ?
        AND prlstaffloans.startdate <= CURDATE()
        GROUP BY prlstaffloans.principal, prlstaffloans.instalment
    ";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $payroll_id, $loanindex);
    $stmt->execute();
    $stmt->bind_result($loanbalance, $instalment);
    $stmt->fetch();
    $stmt->close();

    return $loanbalance;
}

function getindividualrelief($pfno, $payrollid, $rtype, $mysqli) {
    $reliefvalue = 0;

    // Get product codes with pens_tax = 1
    $product_query = "SELECT code, hastable, pens_tax FROM prlproducts WHERE pens_tax = 1";
    $product_result = $mysqli->query($product_query);

    if ($product_result) {
        while ($product_row = $product_result->fetch_assoc()) {
            $prodcode = $product_row['code'];
            $hastable = $product_row['hastable'];
            $pens_tax = $product_row['pens_tax'];

            // Get basic pay and pension amount
            $pay_query = "SELECT COALESCE(basicpay, 0) + COALESCE(allowances, 0) + COALESCE(overtime, 0) AS basicpay, COALESCE(pension, 0) AS pension FROM prlpayroltransfile WHERE payroll_id = " . intval($payrollid) . " AND pfno = '" . $mysqli->real_escape_string($pfno) . "'";
            $pay_result = $mysqli->query($pay_query);
            
            if (!$pay_result) {
                continue;
            }
            
            $pay_row = $pay_result->fetch_assoc();

            if (!$pay_row) {
                continue;
            }

            $basicpay = $pay_row['basicpay'];
            $amountofpension = $pay_row['pension'];
            $Tbasicpay = $basicpay - $amountofpension;

            // Get relief amounts, percentages, and max relief
            $relief_query = "SELECT reliefamount, reliefaspercent, maxrelief FROM prlreliefs WHERE productcode = " . intval($prodcode) . " AND type = " . intval($rtype);
            $relief_result = $mysqli->query($relief_query);

            if (!$relief_result) {
                continue;
            }

            while ($relief_row = $relief_result->fetch_assoc()) {
                $reliefamount = $relief_row['reliefamount'];
                $reliefaspercent = $relief_row['reliefaspercent'];
                $maxrelief = $relief_row['maxrelief'];

                if ($reliefamount > 0 && $reliefamount !== null) {
                    $reliefvalue += $reliefamount;
                }

                if ($reliefaspercent !== null && $reliefaspercent > 0) {
                    $reliefvalue += ($Tbasicpay * ($reliefaspercent * 0.01));

                    if ($maxrelief !== null && $maxrelief > 0) {
                        if ($reliefvalue > $maxrelief) {
                            $reliefvalue += $maxrelief;
                        }
                    }
                }
            }
        }
    }
    if ($product_result) {
        $product_result->free_result();
    }
    return $reliefvalue;
}

function calculatePensionable($db, $pfno, $gid) {
    $pensionable_matrix_query = "SELECT IFNULL(SUM(prlmatrix.amount), 0) AS matrix_pension FROM prlmatrix JOIN prlproducts ON prlmatrix.prodid = prlproducts.code WHERE prlmatrix.deduction = 0 AND prlmatrix.pfno = '$pfno' AND prlproducts.pens_tax = 2";
    $pensionable_matrix_result = mysqli_query($db, $pensionable_matrix_query);
    $matrix_pension = 0;
    if ($pensionable_matrix_result) {
        $matrix_pension_row = mysqli_fetch_assoc($pensionable_matrix_result);
        $matrix_pension = $matrix_pension_row['matrix_pension'];
    }

    $pensionable_loan_query = "SELECT IFNULL(SUM(instalment), 0) AS loan_pension FROM prlstaffloans JOIN prlproducts ON prlstaffloans.deductcode = prlproducts.code WHERE prlproducts.deduction = 0 AND prlproducts.pens_tax = 2 AND prlstaffloans.pfno = '$pfno' AND prlstaffloans.saving = 1";
    $pensionable_loan_result = mysqli_query($db, $pensionable_loan_query);
    $loan_pension = 0;

    if ($pensionable_loan_result) {
        $loan_pension_row = mysqli_fetch_assoc($pensionable_loan_result);
        $loan_pension = $loan_pension_row['loan_pension'];
    }

    $total_pensionable = $matrix_pension + $loan_pension;

    return ($total_pensionable > 20000) ? 20000 : $total_pensionable;
}

function getLoginCount($db, $pfno, $fromdate, $todate) {
    $query = "SELECT COUNT(DISTINCT date(logdate)) as cnt FROM prllogin WHERE pfno = '$pfno' AND logdate BETWEEN '$fromdate' AND '$todate'";
    $result = mysqli_query($db, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['cnt'] > 0 ? $row['cnt'] : 1;
    }
    return 1;
}

function employeeShouldLogin($db, $pfno, $fromdate, $todate) {
    return true;
}

function calculateOvertime($db, $pfno, $basicpay, $noofhrsperday, $daystotal, $fromdate, $todate) {
    $query = "SELECT (SUM(t.noofhours) - SUM(t.recommended)) * (e.basicpay / e.noofhrsperday) AS total_ot
              FROM prltimesheet t
              INNER JOIN prlemployeemaster e ON t.pfno = e.pf_no
              WHERE t.pfno = '" . mysqli_real_escape_string($db, $pfno) . "'
                AND t.date BETWEEN '" . mysqli_real_escape_string($db, $fromdate) . "' AND '" . mysqli_real_escape_string($db, $todate) . "'
                AND e.basicpay > 0
                AND e.noofhrsperday > 0
              GROUP BY e.basicpay, e.noofhrsperday";
    $result = mysqli_query($db, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return floatval($row['total_ot']);
    }
    return 0;
}

function calculateLatenessAbsent($db, $pfno, $basicpay, $noofhrsperday, $daystotal, $fromdate, $todate) {
    $lateness = 0;
    $absent = 0;
    
    $lat_query = "SELECT SUM(minutes_late) as total_late FROM prllateness WHERE pfno = '$pfno' AND ldate BETWEEN '$fromdate' AND '$todate'";
    $lat_result = mysqli_query($db, $lat_query);
    if ($lat_result && $lat_row = mysqli_fetch_assoc($lat_result)) {
        $lateness = $lat_row['total_late'] ?: 0;
    }
    
    $abs_query = "SELECT COUNT(*) as cnt FROM prlabsent WHERE pfno = '$pfno' AND adate BETWEEN '$fromdate' AND '$todate'";
    $abs_result = mysqli_query($db, $abs_query);
    if ($abs_result && $abs_row = mysqli_fetch_assoc($abs_result)) {
        $absent = $abs_row['cnt'] ?: 0;
    }
    
    $hourly_rate = ($basicpay / ($noofhrsperday * $daystotal));
    $daily_rate = $hourly_rate * $noofhrsperday;
    
    $lateness_deduction = ($lateness / 60) * $hourly_rate;
    $absent_deduction = $absent * $daily_rate;
    
    return $lateness_deduction + $absent_deduction;
}



function getreliefvalue($db, $pfno, $prodcode, $payrollid) {
    $hastable = 0;
    $pens_tax = 0;
    
    $prod_result = mysqli_query($db, "SELECT hastable, pens_tax FROM prlproducts WHERE code = " . intval($prodcode));
    if ($prod_row = mysqli_fetch_assoc($prod_result)) {
        $hastable = $prod_row['hastable'];
        $pens_tax = $prod_row['pens_tax'];
    }
    
    $basicpay = 0;
    $amountofpension = 0;
    $pt_result = mysqli_query($db, "SELECT (COALESCE(basicpay, 0) + COALESCE(allowances, 0) + COALESCE(overtime, 0)) as bp, COALESCE(pension, 0) as pen FROM prlpayroltransfile WHERE payroll_id = " . intval($payrollid) . " AND pfno = '$pfno'");
    if ($pt_row = mysqli_fetch_assoc($pt_result)) {
        $basicpay = $pt_row['bp'] ?: 0;
        $amountofpension = $pt_row['pen'] ?: 0;
    }
    
    if ($pens_tax == 1) {
        $Tbasicpay = $basicpay - $amountofpension;
    } else {
        $Tbasicpay = $basicpay;
    }
    
    $reliefvalue = 0;
    
    $relief_result = mysqli_query($db, "SELECT reliefamount, reliefaspercent, maxrelief FROM prlreliefs WHERE productcode = " . intval($prodcode));
    while ($relief = mysqli_fetch_assoc($relief_result)) {
        if ($relief['reliefamount'] > 0 && $relief['reliefamount'] !== null) {
            $reliefvalue = $relief['reliefamount'];
        }
        if ($relief['reliefaspercent'] !== null && $relief['reliefaspercent'] > 0) {
            $reliefvalue = $Tbasicpay * ($relief['reliefaspercent'] * 0.01);
        }
        if ($relief['maxrelief'] !== null && $relief['maxrelief'] > 0 && $reliefvalue > $relief['maxrelief']) {
            $reliefvalue = $relief['maxrelief'];
        }
    }
    
    return $reliefvalue;
}

function processPayDetailsTransactions($db, $gid) {
    mysqli_query($db, "DELETE FROM prlpaydetailstransfile WHERE payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'");
    if (mysqli_errno($db)) {
        return false;
    }
    
    $employees_query = "SELECT pf_no FROM prlemployeemaster WHERE (dateterminated IS NULL OR dateterminated >= (SELECT todate FROM prlmrollperiods WHERE pkey = '$gid')) AND dateemployed <= (SELECT fromdate FROM prlmrollperiods WHERE pkey = '$gid')";
    $employees_result = mysqli_query($db, $employees_query);
    
    if ($employees_result) {
        while ($emp_row = mysqli_fetch_assoc($employees_result)) {
            $pfno = $emp_row['pf_no'];
            
            $matrix_query = "SELECT prlmatrix.prodid, prlmatrix.amount, prlmatrix.employeramount, prlmatrix.name, prlproducts.deduction, prlproducts.pens_tax, prlproducts.hastable 
                            FROM prlmatrix 
                            JOIN prlproducts ON prlmatrix.prodid = prlproducts.code 
                            WHERE prlmatrix.pfno = '$pfno' AND (prlmatrix.nonrecuring = 0 OR prlmatrix.nonrecuring IS NULL OR (prlmatrix.nonrecuring = 1 AND prlmatrix.posted IS NULL))";
            $matrix_result = mysqli_query($db, $matrix_query);
            
            if ($matrix_result) {
                while ($matrix_row = mysqli_fetch_assoc($matrix_result)) {
                    $prodid = $matrix_row['prodid'];
                    $amount = $matrix_row['amount'];
                    $employeramount = $matrix_row['employeramount'];
                    $deduction = $matrix_row['deduction'];
                    $pens_tax = $matrix_row['pens_tax'];
                    $hastable = $matrix_row['hastable'];
                    $name = $matrix_row['name'];
                    
                    if ($amount > 0 && $hastable == 0) {
                        $calculated_amount = $amount;
                    } else {
                        $calculated_amount = GetPayrollValue($pfno, $prodid, $gid);
                    }
                    
                    $reliefdeducted = getreliefvalue($db, $pfno, $prodid, $gid);
                    
                    $insert_detail = "INSERT INTO prlpaydetailstransfile (payroll_id, pfno, code, description, amount, employercontribution, deduction, reliefdeducted, non_cash_benefits) 
                                     VALUES ('$gid', '$pfno', '$prodid', '" . mysqli_real_escape_string($db, $name) . "', " . floatval($calculated_amount) . ", " . floatval($employeramount) . ", " . intval($deduction) . ", " . floatval($reliefdeducted) . ", " . intval($pens_tax) . ")";
                    mysqli_query($db, $insert_detail);
                    if (mysqli_errno($db)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
    } else {
        return false;
    }

    $summary_sql = "UPDATE prlpayroltransfile pt
                    LEFT JOIN (
                        SELECT payroll_id,
                               pfno,
                               SUM(CASE WHEN deduction = 1 THEN amount ELSE 0 END) AS allowance_total,
                               SUM(CASE WHEN deduction = 0 THEN amount ELSE 0 END) AS deduction_total
                        FROM prlpaydetailstransfile
                        WHERE payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'
                        GROUP BY payroll_id, pfno
                    ) pd ON pt.payroll_id = pd.payroll_id AND pt.pfno = pd.pfno
                    LEFT JOIN (
                        SELECT payroll_id,
                               pfno,
                               SUM(amount + IFNULL(interest, 0)) AS loan_total
                        FROM prlloantrans
                        WHERE payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'
                        GROUP BY payroll_id, pfno
                    ) lt ON pt.payroll_id = lt.payroll_id AND pt.pfno = lt.pfno
                    SET pt.allowances = IFNULL(pd.allowance_total, 0),
                        pt.deductions = IFNULL(pd.deduction_total, 0) + IFNULL(lt.loan_total, 0)
                    WHERE pt.payroll_id = '" . mysqli_real_escape_string($db, $gid) . "'";
    mysqli_query($db, $summary_sql);
    if (mysqli_errno($db)) {
        return false;
    }

    return true;
}


function GetPayrollValue($pfno, $prodcode, $pid) {
    global $db;
    
    $product_sql = "SELECT hastable, pens_tax FROM prlproducts WHERE code = " . intval($prodcode);
    $product_result = DB_query($product_sql, $db);
    $product = DB_fetch_array($product_result);
    
    if(!$product) return 0;
    
    $hastable = intval($product['hastable']);
    $pens_tax = intval($product['pens_tax']);
    
    $emp_sql = "SELECT basicpay, insurancerelief FROM prlemployeemaster WHERE pf_no = '" . DB_escape_string($pfno) . "'";
    $emp_result = DB_query($emp_sql, $db);
    $emp = DB_fetch_array($emp_result);
    
    if(!$emp) return 0;
    
    $basicpay = floatval($emp['basicpay']);
    
    $noncash_sql = "SELECT SUM(m.amount) as total FROM prlmatrix m 
                    INNER JOIN prlproducts p ON m.prodid = p.code 
                    WHERE m.pfno = '" . DB_escape_string($pfno) . "' AND p.pens_tax = 3";
    $noncash_result = DB_query($noncash_sql, $db);
    $noncash = DB_fetch_array($noncash_result);
    $noncashbenefits = floatval($noncash['total']);
    
    $pension_sql = "SELECT SUM(m.amount) as total FROM prlmatrix m 
                    INNER JOIN prlproducts p ON m.prodid = p.code 
                    WHERE m.pfno = '" . DB_escape_string($pfno) . "' AND p.pens_tax = 2";
    $pension_result = DB_query($pension_sql, $db);
    $pension = DB_fetch_array($pension_result);
    $amountofpension = floatval($pension['total']);
    
    $loan_sql = "SELECT SUM(sl.instalment) as total FROM prlstaffloans sl 
                 INNER JOIN prlproducts p ON sl.deductcode = p.code 
                 WHERE sl.pfno = '" . DB_escape_string($pfno) . "' AND p.deduction = 0 AND p.pens_tax = 2 AND sl.saving = 1";
    $loan_result = DB_query($loan_sql, $db);
    $loan = DB_fetch_array($loan_result);
    $amountofpension += floatval($loan['total']);
    
    $minimunPension = min($amountofpension, 20000);
    
    $allowance_sql = "SELECT SUM(m.amount) as total FROM prlmatrix m 
                      INNER JOIN prlproducts p ON m.prodid = p.code 
                      WHERE m.pfno = '" . DB_escape_string($pfno) . "' AND p.pens_tax = 0 AND p.deduction = 1 AND m.posted IS NULL";
    $allowance_result = DB_query($allowance_sql, $db);
    $allowance = DB_fetch_array($allowance_result);
    $totalallowances = floatval($allowance['total']);
    
    $overtime = 0;
    $overtime_sql = "SELECT SUM((t.noofhours - t.recommended) * (e.basicpay / e.noofhrsperday)) as total
                      FROM prltimesheet t
                      INNER JOIN prlemployeemaster e ON t.pfno = e.pf_no
                      INNER JOIN prlmrollperiods rp ON t.date BETWEEN rp.fromdate AND rp.todate AND rp.pkey = " . intval($pid) . "
                      WHERE t.pfno = '" . DB_escape_string($pfno) . "' AND e.basicpay > 0 AND e.noofhrsperday > 0";
    $overtime_result = DB_query($overtime_sql, $db);
    if($overtime_row = DB_fetch_array($overtime_result)) {
        $overtime = floatval($overtime_row['total']);
    }
    
    if($pens_tax == 1) {
        $Tbasicpay = ($basicpay + $totalallowances + $noncashbenefits + $overtime) - $minimunPension;
    } else {
        $Tbasicpay = $basicpay + $totalallowances + $overtime;
    }
    
    $sum = 0;
    
    if($hastable > 0) {
        if($hastable == 2) {
            $table_sql = "SELECT lowerlimit, upperlimit, percentageamount, ceiling 
                          FROM prlitemaintenace 
                          WHERE code = " . intval($prodcode) . " 
                          ORDER BY taxband ASC";
            $table_result = DB_query($table_sql, $db);
            
            while($table_row = DB_fetch_array($table_result)) {
                $lowerlimit = floatval($table_row['lowerlimit']);
                $upperlimit = floatval($table_row['upperlimit']);
                $percentageamount = floatval($table_row['percentageamount']);
                
                if($Tbasicpay >= $lowerlimit && $Tbasicpay <= $upperlimit) {
                    $sum = $sum + ($Tbasicpay - ($lowerlimit - 1)) * ($percentageamount * 0.01);
                    break;
                } elseif($Tbasicpay > $upperlimit) {
                    $sum = $sum + ($upperlimit - ($lowerlimit - 1)) * ($percentageamount * 0.01);
                }
            }
        } elseif($hastable == 1) {
            $table_sql = "SELECT lowerlimit, upperlimit, ceiling 
                          FROM prlitemaintenace 
                          WHERE code = " . intval($prodcode) . " 
                          AND (" . floatval($Tbasicpay) . " BETWEEN lowerlimit AND upperlimit)
                          ORDER BY taxband ASC";
            $table_result = DB_query($table_sql, $db);
            
            if($table_row = DB_fetch_array($table_result)) {
                $sum = floatval($table_row['ceiling']);
            }
        }
    } else {
        $table_sql = "SELECT taxableamount, ceiling, percentageamount
                      FROM prlitemaintenace
                      WHERE code = " . intval($prodcode);
        $table_result = DB_query($table_sql, $db);

        while($table_row = DB_fetch_array($table_result)) {
            $taxableamount = floatval($table_row['taxableamount']);
            $ceiling = floatval($table_row['ceiling']);
            $percentageamount = floatval($table_row['percentageamount']);

            if($percentageamount > 0) {
                $sum = $Tbasicpay * round(($percentageamount * 0.01), 2);
                if($ceiling > 0 && $sum > $ceiling) {
                    $sum = $ceiling;
                }
            } else {
                $sum = $taxableamount;
            }
        }
    }
    
    return $sum;
}
