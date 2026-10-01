<?php

define ('LIKE','LIKE');

if (!isset($mysqlport)){
	$mysqlport = 3306;
}
global $db,$mysqli;
// Make sure it IS global, regardless of our context
       
$db = mysqli_connect($host , $DBUser, $DBPassword,$_SESSION['DatabaseName'], $mysqlport);

$mysqli = new mysqli($host , $DBUser, $DBPassword,$_SESSION['DatabaseName']);

$result=DB_query('SET sql_mode = ANSI', $db);
//this statement sets the charset to be used for sending data to and from the db server
//if not set, both mysqli server and mysqli client/library may assume otherwise
mysqli_set_charset($db, 'utf8');

/* check connection */
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	session_unset();
	session_destroy();
	echo '<p>' . _('Click') . ' ' . '<a href="index.php">' . _('here') . '</a>' . ' '  ._('to try logging in again') . '</p>';
	exit();
}

if ( !$db ) {
	echo '<br />' . _('The configuration in the file config.php for the database user name and password do not provide the information required to connect to the database server');
	exit;
}

/* Update to allow RecurringSalesOrdersProcess.php to run via cron */
if (isset($DatabaseName)) {
	if (!mysqli_select_db($db,$DatabaseName)) {
		echo '<br />' . _('The company name entered does not correspond to a database on the database server specified in the config.php configuration file. Try logging in with a different company name');
		echo '<br /><a href="index.php">' . _('Back to login page') . '</A>';
		unset ($DatabaseName);
		exit;
	}
} else {
	if (!mysqli_select_db($db,$_SESSION['DatabaseName'])) {
		echo '<br />' . _('The company name entered does not correspond to a database on the database server specified in the config.php configuration file. Try logging in with a different company name');
		echo '<br /><a href="index.php">' . _('Back to login page') . '</A>';

		unset ($_SESSION['DatabaseName']);
		exit;
        }
}

require_once ($PathPrefix .'includes/MiscFunctions.php');

//DB wrapper functions to change only once for whole application

function DB_query ($SQL, $Conn, $ErrorMessage='', $DebugMessage= '', $Transaction=false, $TrapErrors=true){

	global $debug;
	global $PathPrefix;
        
        $SQL = RemoveBox($SQL);
    //  $filename= 'sql/'.basename($_SERVER['SCRIPT_NAME'],'.php').'.SQL';
    //  file_put_contents($filename,$SQL."\r\n\r\n",FILE_APPEND);

	$result= mysqli_query($Conn,$SQL);

	$_SESSION['LastInsertId'] = mysqli_insert_id($Conn);

	if ($DebugMessage == '') {
		$DebugMessage = _('The SQL that failed was');
	}

	if (DB_error_no($Conn) != 0 AND $TrapErrors==true){
		if ($TrapErrors){
			require_once($PathPrefix . 'includes/header.inc');
		}
		prnMsg($ErrorMessage . '<br />' . DB_error_msg($Conn),'error', _('Database Error'). ' ' .DB_error_no($Conn));
		if ($debug==1){
			prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
		}
		if ($Transaction){
			$SQL = 'rollback';
			$Result = DB_query($SQL,$Conn);
			if (DB_error_no($Conn) !=0){
				prnMsg(_('Error Rolling Back Transaction'), 'error', _('Database Rollback Error'). ' ' .DB_error_no($Conn) );
			}else{
				prnMsg(_('Rolling Back Transaction OK'), 'error', _('Database Rollback Due to Error Above'));
			}
		}
		if ($TrapErrors){
			include($PathPrefix . 'includes/footer.inc');
			exit;
		}
	} elseif (isset($_SESSION['Monthsaudittrail']) and (DB_error_no($Conn)==0 AND $_SESSION['Monthsaudittrail']>0) AND (DB_affected_rows($result)>0)){

		$SQLArray = explode(' ', $SQL);

		if (($SQLArray[0] == 'INSERT')
			OR ($SQLArray[0] == 'UPDATE')
			OR ($SQLArray[0] == 'DELETE')) {

			if ($SQLArray[2]!='audittrail'){ // to ensure the auto delete of audit trail history is not logged
				$AuditSQL = "INSERT INTO audittrail (transactiondate,userid,querystring)
						VALUES('" . Date('Y-m-d H:i:s') . "',
							'" . trim($_SESSION['UserID']) . "',
							'" . htmlentities($SQL) . "')";

				$AuditResult = mysqli_query($Conn, $AuditSQL);
			}
		}
	}
	return $result;

}

function DB_System ($SQL, $Conn, $ErrorMessage='', $DebugMessage= '', $Transaction=false, $TrapErrors=true){
    global $debug;
    global $PathPrefix;
        
        $SQL = RemoveBox($SQL);
        
    //      $filename= 'sql/'.basename($_SERVER['SCRIPT_NAME'],'.php').'.SQL';
    //      file_put_contents($filename,$SQL."\r\n\r\n",FILE_APPEND);

        
	$result= mysqli_query($Conn,$SQL);

	$_SESSION['LastInsertId'] = mysqli_insert_id($Conn);

	if ($DebugMessage == '') {
	    $DebugMessage = _('The SQL that failed was');
	}

        if (DB_error_no($Conn) != 0 AND $TrapErrors==true){
		if ($TrapErrors){
			require_once($PathPrefix . 'includes/header.inc');
		}
		prnMsg($ErrorMessage . '<br />' . DB_error_msg($Conn),'error', _('Database Error'). ' ' .DB_error_no($Conn));
		if ($debug==1){
			prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
		}
		if ($Transaction){
			$SQL = 'rollback';
			$Result = DB_query($SQL,$Conn);
			if (DB_error_no($Conn) !=0){
				prnMsg(_('Error Rolling Back Transaction'), 'error', _('Database Rollback Error'). ' ' .DB_error_no($Conn) );
			}else{
				prnMsg(_('Rolling Back Transaction OK'), 'error', _('Database Rollback Due to Error Above'));
			}
		}
		if ($TrapErrors){
			include($PathPrefix . 'includes/footer.inc');
			exit;
		}
	}
	
  return $result;

}

function DB_prepare($SQL, $Conn){
    $SQL = RemoveBox($SQL);
    $stmt = $Conn -> prepare($SQL);
  return $stmt -> execute();

}

function DB_fetch_row ($ResultIndex) {

	$RowPointer=mysqli_fetch_row($ResultIndex);
	Return $RowPointer;
}

function DB_fetch_assoc ($ResultIndex) {

	$RowPointer=mysqli_fetch_assoc($ResultIndex);
	Return $RowPointer;
}

function DB_fetch_array ($ResultIndex) {

	$RowPointer=mysqli_fetch_array($ResultIndex);
	Return $RowPointer;
}

function DB_data_seek ($ResultIndex,$Record) {
	mysqli_data_seek($ResultIndex,$Record);
}

function DB_free_result ($ResultIndex){
    if (is_resource($ResultIndex)) {
    	mysqli_free_result($ResultIndex);
    }
}

function DB_num_rows ($ResultIndex){
	return mysqli_num_rows($ResultIndex);
}

function DB_affected_rows($ResultIndex){

	global $db;
	return mysqli_affected_rows($db);

}

function DB_error_no ($Conn){
	return mysqli_errno($Conn);
}

function DB_error_msg($Conn){
	return mysqli_error($Conn);
}

function DB_Last_Insert_ID($Conn, $Table, $FieldName){
 
	if (isset($_SESSION['LastInsertId'])) {
		$Last_Insert_ID = $_SESSION['LastInsertId'];
	} else {
		$Last_Insert_ID = 0;
	}
 
	return $Last_Insert_ID;
}

function DB_escape_string($String){
    global $db;
    return  mysqli_escape_string($db,$String);;
}

function DB_show_tables($Conn){
    $Result = DB_query('SHOW TABLES',$Conn);
    Return $Result;
}

function DB_show_fields($TableName, $Conn){
    $Result = DB_query("DESCRIBE $TableName",$Conn);
    Return $Result;
}

function interval( $val, $Inter ){
    global $dbtype;
    return "\n".'interval ' . $val . ' '. $Inter."\n";
}

function DB_Maintenance($Conn){

	prnMsg(_('The system has just run the regular database administration and optimisation routine.'),'info');

	$TablesResult = DB_query('SHOW TABLES',$Conn);
	while ($myrow = DB_fetch_row($TablesResult)){
		$Result = DB_query('OPTIMIZE TABLE ' . $myrow[0],$Conn);
	}

	$Result = DB_query("UPDATE config SET confvalue='" . Date('Y-m-d') . "' WHERE confname='DB_Maintenance_LastRun'",$Conn);
}

function DB_Txn_Begin($Conn){
	mysqli_query($Conn,'SET autocommit=0');
	mysqli_query($Conn,'START TRANSACTION');
}

function DB_Txn_Commit($Conn){
	mysqli_query($Conn,'COMMIT');
	mysqli_query($Conn,'SET autocommit=1');
}

function DB_Txn_Rollback($Conn){
	mysqli_query($Conn,'ROLLBACK');
}

function DB_IgnoreForeignKeys($Conn){
	mysqli_query($Conn,'SET FOREIGN_KEY_CHECKS=0');
}

function DB_ReinstateForeignKeys($Conn){
	mysqli_query($Conn, 'SET FOREIGN_KEY_CHECKS=1');
}

Function RemoveBox($sql){
    $leftbrace = str_replace('[','`',$sql);
    $allbraces = str_replace(']','`',$leftbrace);

    return $allbraces;
}
 

FUNCTION IssqldateGreater($PriodClosedDate,$CurrentDate){
    Global $db;
   
    $sql= sprintf("TO_DAYS('%s') - TO_DAYS('%s')",$CurrentDate,$PriodClosedDate);
    $ResultIndex = DB_System($sql,$db); 
    $rows = DB_fetch_row($ResultIndex);
   	
    if($rows[0] <0){ $return =0; }else{ $return =1; }
    
    return $return;
}


 $data = array();
 $index = array();
 $DIMENSIONARRY = array();
 
function mysqlclass($id){
       global $db,$data,$index,$DIMENSIONARRY;
        
        $sql=sprintf("select PARENT,LEVEL,Code,Dimension FROM dimensions where id='%s'",$id);
        $ResultIndex = DB_System($sql,$db); 
        while ($row = DB_fetch_array($ResultIndex)) {
            $id =$row["Code"];
            $parent_id =$row["PARENT"];
            $data[$id]= $row;
            $index[$parent_id][]=$id;
        }
        
        display_tree("",0);
        return $DIMENSIONARRY;
}

function display_tree($parent_id, $level){
    global $data, $index,$DIMENSIONARRY;
    if (isset($index[$parent_id])) {
        foreach ($index[$parent_id] as $id) {
            $DIMENSIONARRY[]=array(
                "sort_seq"=>($level*2),
                "levels"=>$level,
                "Code"=>$data[$id]["Code"],
                "Dimension"=>$data[$id]["Dimension"]);
            display_tree($id, $level + 1);
        }
    }
    
    return $DIMENSIONARRY;
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

