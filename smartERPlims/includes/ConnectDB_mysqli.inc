<?php
/* MySQLi Database Connection Wrapper */
define('LIKE','LIKE');

global $db;
// Get database name from session
$database = $_SESSION['DatabaseName'];

// Create MySQLi connection
$db = new mysqli($host, $DBUser, $DBPassword, $database);

// Check connection
if ($db->connect_error) {
    testconnect();
}

// Set charset to utf8mb4
$db->set_charset("utf8mb4");

require_once ($PathPrefix .'includes/MiscFunctions.php');

function testconnect(){
    
    if (!isset($RootPath)){
        $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
        if ($RootPath == '/' OR $RootPath == "\\") {
            $RootPath = '';
        }
    }
    echo '<html><head><link href="'.$RootPath.'/javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" /><link href="'. $RootPath .'/css/homepage.css" rel="stylesheet" type="text/css"/></head>';
    echo '<body><div class="container">';
    $error= "Connect failed: Database connection error. Contact your administrator";
    prnMsg($error,'warn');

    prnMsg('<a href="index.php">Click here... to try logging in again</a>','info');
    echo '</div></body></html>';
    session_unset();
     exit();
}

function DB_query($SQL,$Conn,$ErrorMessage='',$DebugMessage= '',$Transaction=false,$TrapErrors=true){
	global $debug;
	global $PathPrefix;

	$result = $Conn->query($SQL);
        $SQLArray = explode(' ',$SQL);
       
	if ($DebugMessage == '') {
        $DebugMessage = _('The SQL that failed was');
	}

	if ($Conn->errno != 0 AND $TrapErrors==true){

		if ($TrapErrors){
                    require_once($PathPrefix . 'includes/header.inc');
		}

		prnMsg($ErrorMessage . '<br />' . $Conn->error,'error', _('Database Error'). ' ' .$Conn->errno);
		if ($debug==1){
            prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
		}

		if ($Transaction){
			$Conn->rollback();
			prnMsg(_('Rolling Back Transaction OK'), 'error', _('Database Rollback Due to Error Above'));
		}

		if ($TrapErrors){
			include($PathPrefix . 'includes/footer.inc');
			exit;
		}

	} elseif (isset($_SESSION['MonthsAuditTrail']) and ($Conn->errno == 0 AND $_SESSION['MonthsAuditTrail']>0)
                AND  ($Conn->affected_rows>0)){

		$SQLArray = explode(' ', $SQL);

		if (($SQLArray[0] == 'INSERT')  OR ($SQLArray[0] == 'UPDATE')  OR ($SQLArray[0] == 'DELETE')) {

			if ($SQLArray[2]!='audittrail' and $SQLArray[2]!='systypes_1' ){
                // to ensure the auto delete of audit trail history is not logged
                $AuditSQL = sprintf("INSERT INTO audittrail (transactiondate,userid,querystring) VALUES ('%s','%s','%s')", 
                    Date('Y-m-d H:i:s'),$_SESSION['UserID'],DB_escape_string($SQL));

                $AuditResult = $Conn->query($AuditSQL);
                if ($Conn->errno != 0 AND $TrapErrors==true){
                    prnMsg($ErrorMessage . '<br />' . $Conn->error,'error', _('Database Error'). ' ' .$Conn->errno);
                    if ($debug==1){
                        prnMsg($DebugMessage. '<br />' . $AuditSQL . '<br />','error',_('Database SQL Failure'));
                    }
                }
			}
		}
	}

    return $result;
}

function DB_System($SQL,$Conn,$ErrorMessage='',$DebugMessage= '',$Transaction=false,$TrapErrors=true){
    global $debug;
	global $PathPrefix;

	$result = $Conn->query($SQL);
    
	if ($DebugMessage == '') {
        $DebugMessage = _('The SQL that failed was');
	}

	if ($Conn->errno != 0 AND $TrapErrors==true){

		if ($TrapErrors){
            require_once($PathPrefix . 'includes/header.inc');
		}

		prnMsg($ErrorMessage . '<br />' . $Conn->error,'error', _('Database Error'). ' ' .$Conn->errno);
		if ($debug==1){
            prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
		}

		if ($Transaction){
			$Conn->rollback();
			prnMsg(_('Error Rolling Back Transaction'), 'error', _('Database Rollback Error'). ' ' .$Conn->errno );
		}

		if ($TrapErrors){
			include($PathPrefix . 'includes/footer.inc');
			exit;
		}

	}

    return $result;
}

function DB_Find_Table($SelectedTable){
    global $db;
    $rows = 0;
    $result = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$db->real_escape_string($SelectedTable)."'");
    
    if($result && $result->num_rows > 0) {
        $rows = 1;
    }
    
    return $rows;
}

function DB_Table_rename($tablefrom,$tableto){
    global $db;
    $result = DB_query("RENAME TABLE `".$db->real_escape_string($tablefrom)."` TO `".$db->real_escape_string($tableto)."`",$db);
}

function Db_Drop_Table($tableName){
    global $db;
    $sql = "DROP TABLE IF EXISTS `".$db->real_escape_string($tableName)."`";
    $result = DB_query($sql,$db);
}

function DB_html_decode($String){
    return htmlspecialchars($String);
}

function DB_escape_string($String){
    global $db;
    return $db->real_escape_string($String);
}

function DB_backup_string($String){
    $addedslashes = addslashes(htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
    return str_replace("/","\\", $addedslashes);
}

function Table_fetch_row ($ResultIndex) {
    return $ResultIndex->fetch_row();
}

function Table_name($ResultIndex) {
    // This function gets TABLE_NAME from query results
    $row = $ResultIndex->fetch_assoc();
    return $row['TABLE_NAME'] ?? '';
}

function DB_show_fields($TableName, $Conn){
    return $Conn->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$Conn->real_escape_string($TableName)."'");
}

function DB_show_tables($Conn){
    return $Conn->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
}

function DB_fetch_row($ResultIndex) {
    if($ResultIndex instanceof mysqli_result) {
        return $ResultIndex->fetch_row();
    }
    return array();
}

function DB_fetch_assoc ($ResultIndex) {
    if($ResultIndex instanceof mysqli_result) {
        return $ResultIndex->fetch_assoc();
    }
    return array();
}

function DB_fetch_array ($ResultIndex) {
    if($ResultIndex instanceof mysqli_result) {
        return $ResultIndex->fetch_assoc();
    }
    return array();
}

function DB_data_seek ($ResultIndex,$Record) {
    if($ResultIndex instanceof mysqli_result) {
        return $ResultIndex->data_seek($Record);
    }
    return false;
}

function DB_free_result ($ResultIndex){
    if ($ResultIndex instanceof mysqli_result) {
        $ResultIndex->free();
    }
}

function DB_num_rows ($ResultIndex){
    if($ResultIndex instanceof mysqli_result) {
        return $ResultIndex->num_rows;
    }
    return 0;
}

function DB_affected_rows($ResultIndex){
    global $db;
    return $db->affected_rows;
}

function DB_error_no ($Conn){
    return $Conn->errno;
}

function DB_error_msg($Conn){
    return $Conn->error;
}

function DB_Last_Insert_ID($Conn, $Table, $FieldName){
    return $Conn->insert_id;
}

function interval( $val, $Inter ){
    global $dbtype;
    return "\n".'INTERVAL ' . $val . ' '. strtoupper($Inter)."\n";
}

function DB_Maintenance($Conn){
    prnMsg(_('The system has just run the regular database administration and optimisation routine.'),'info');
    $Result = $Conn->query("UPDATE config SET confvalue='" . Date('Y-m-d') . "' WHERE confname='DB_Maintenance_LastRun'");
}

function DB_Txn_Begin($Conn){
    $Conn->autocommit(false);
    $Conn->begin_transaction();
}

function DB_Txn_Commit($Conn){
    $Conn->commit();
    $Conn->autocommit(true);
}

function DB_Txn_Rollback($Conn){
    $Conn->rollback();
    $Conn->autocommit(true);
}

function DB_IgnoreForeignKeys($Conn){
    $Conn->query("SET FOREIGN_KEY_CHECKS=0");
}

function DB_ReinstateForeignKeys($Conn){
    $Conn->query("SET FOREIGN_KEY_CHECKS=1");
}

function fetch_array($id_res){
    if($id_res instanceof mysqli_result) {
        return $id_res->fetch_assoc();
    }
    return array();
}

 
Function Triger_getaccountno($accdesc){
    global $db;
   
   $ResultIndex = DB_System('Select count(*) from acct',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (6-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = substr($accdesc,1,2) . $replicate .$int;
 
 return $code;
}

function Triger_bankaccounts(){
    global $db;
     
   $ResultIndex = DB_System('Select count(*) from bankaccounts',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (6-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'BK' . $replicate .$int;
   
 return $code;
}

Function Triger_creditors($descript){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from creditors',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (4-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'cr' .substr($descript, 0, 2) . $replicate .$int;
 
 return $code;
}

Function Triger_debtors($descript){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from debtors',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (4-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'Dr' .substr($descript, 0, 2) . $replicate .$int;
 
 return $code;
}

Function Triger_stockmaster($descript){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from stockmaster',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (6-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = substr($descript, 0, 2) . $replicate .$int;
 
 return $code;
}

Function Triger_unit(){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from unit',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (2-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'u' . $replicate .$int;
 
 return $code;
}

