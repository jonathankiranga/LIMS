<?php
/* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
global $db;
// Make sure it IS global, regardless of our context

$database = $_SESSION['DatabaseName'];

// Dont change the sql driver to sql native or sql native 10.0 coz password encrytion will not work
// SQL Server Native Client 11.0 (32 bit)
// odbc_connect("Driver={SQL Server Native Client 11.0};Server=$host;Database=$database;"

// must turn off all system messages

$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",trim($DBUser), trim($DBPassword));

if (odbc_error()) {testconnect();}

require_once ($PathPrefix .'includes/MiscFunctions.php');

function testconnect(){
    
    if (!isset($RootPath)){
        $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
        if ($RootPath == '/' OR $RootPath == "\\") {
            $RootPath = '';
        }
    }
        
    echo '<html><head><link href="'.$RootPath.'/Javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />
      <link href="'. $RootPath .'/css/homepage.css" rel="stylesheet" type="text/css"/></head>';
    echo '<body><div class="container">';
    $error= sprintf("Connect failed: %s  , Contact your administrator", odbc_errormsg() );
    prnMsg($error,'warn');

    prnMsg('Click <a href="index.php">here... to try logging in again</a>','info');
    echo '</div></body></html>';
    session_unset();
    session_destroy();
    exit();
}

function DB_query ($SQL,$Conn,$ErrorMessage='',$DebugMessage= '',$Transaction=false,$TrapErrors=true){

	global $debug;
	global $PathPrefix;

	$result = odbc_exec($Conn,$SQL);

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

	} elseif (isset($_SESSION['MonthsAuditTrail']) and (DB_error_no($Conn)==0 AND $_SESSION['MonthsAuditTrail']>0) AND  (DB_affected_rows($result)>0)){

		$SQLArray = explode(' ', $SQL);

		if (($SQLArray[0] == 'INSERT')  OR ($SQLArray[0] == 'UPDATE')  OR ($SQLArray[0] == 'DELETE')) {

			if ($SQLArray[2]!='audittrail'){ // to ensure the auto delete of audit trail history is not logged

                                $AuditSQL = sprintf("INSERT INTO audittrail (transactiondate,userid,querystring)
                                VALUES ('%s','%s','%s')", Date('Y-m-d H:i:s'),$_SESSION['UserID'],DB_escape_string($SQL));

                                $AuditResult = odbc_exec($Conn,$AuditSQL);
                                if (DB_error_no($Conn) != 0 AND $TrapErrors==true){
                                          prnMsg($ErrorMessage . '<br />' . DB_error_msg($Conn),'error', _('Database Error'). ' ' .DB_error_no($Conn));
                                 if ($debug==1){
                			prnMsg($DebugMessage. '<br />' . $AuditSQL . '<br />','error',_('Database SQL Failure'));
                            	}

                                }
			}
		}
	}

  return $result;

}

Function DB_Find_Table($SelectedTable){
    global $db;
    $rows = 0;
    $TableResult = DB_show_tables($db);

    while(Table_fetch_row($TableResult)) {
        if(Table_name($TableResult) == $SelectedTable) {
            $rows=1;
         }
    }

return $rows;
}

Function DB_Table_rename($tablefrom,$tableto){
    global $db;
    $result = DB_query("sp_rename 'dbo.".$tablefrom."', '".$tableto."' ",$db);
}

Function Db_Drop_Table($tableName){
    global $db;

    $sql = "IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[".$tableName."]')
            AND type in (N'U'))
            DROP TABLE [dbo].[".$tableName."]";
	$result = DB_query($sql,$db);
}

function DB_html_decode($String){
        return  htmlspecialchars($String);
}

function DB_escape_string($String){
    $addedslashes = addslashes(htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
   return str_replace("\\","'", $addedslashes );
}

function DB_backup_string($String){
    $addedslashes = addslashes(htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
   return str_replace("/","\\", $addedslashes );
}

function Table_fetch_row ($ResultIndex) {
  return odbc_fetch_row($ResultIndex);
}

function Table_name($ResultIndex) {
  return odbc_result($ResultIndex,"TABLE_NAME");
}

function DB_show_fields($TableName, $Conn){
   Return odbc_columns($Conn,'','',$TableName);
}

function DB_show_tables($Conn){
   return odbc_tables($Conn);
 }

function DB_fetch_row($ResultIndex) {
   $ARRY = array();  $r = 0;
    if (odbc_fetch_row($ResultIndex)){
        for ($i=1; $i <= odbc_num_fields($ResultIndex); $i++) {
             $ARRY[$r] = odbc_result($ResultIndex,$i);
             $r++;
           }
      }
    return $ARRY;
}

function DB_fetch_assoc ($ResultIndex) {
    Return odbc_fetch_assoc($ResultIndex);
}

function DB_fetch_array ($ResultIndex) {
  return  odbc_fetch_array($ResultIndex,$rownumber=null);

}

function DB_data_seek ($ResultIndex,$Record) {
    return  odbc_fetch_row($ResultIndex,$Record);
}

function DB_free_result ($ResultIndex){
    if (is_resource($ResultIndex)) {
    	odbc_free_result($ResultIndex);
    }
}

function DB_num_rows ($ResultIndex){
   return odbc_num_rows($ResultIndex);
}

function DB_affected_rows($ResultIndex){
    return odbc_num_rows($ResultIndex);

}

function DB_error_no ($Conn){
   return odbc_error();
}

function DB_error_msg($Conn){
   return odbc_errormsg();
}

function DB_Last_Insert_ID($Conn, $Table, $FieldName){
    $SQL = "SELECT IDENT_CURRENT('".$Table."')";
    $resuts = DB_query($SQL,$Conn);
    $row = DB_fetch_row($resuts);
    return $row[0];
}

function interval( $val, $Inter ){
    global $dbtype;
    return "\n".'interval ' . $val . ' '. $Inter."\n";
}

function DB_Maintenance($Conn){
   prnMsg(_('The system has just run the regular database administration and optimisation routine.'),'info');
   $Result = DB_query("UPDATE config SET confvalue='" . Date('Y-m-d') . "' WHERE confname='DB_Maintenance_LastRun'", $Conn);
}

function DB_Txn_Begin($Conn){
     odbc_autocommit($Conn,false) ;
}

function DB_Txn_Commit($Conn){
   odbc_commit($Conn);
   odbc_autocommit($Conn,true) ;
}

function DB_Txn_Rollback($Conn){
    odbc_rollback($Conn);
}

function DB_IgnoreForeignKeys($Conn){
   odbc_exec($Conn, 'sp_MSforeachtable @command1="ALTER TABLE ? NOCHECK CONSTRAINT ALL"');
}

function DB_ReinstateForeignKeys($Conn){
    odbc_exec($Conn, 'sp_MSforeachtable @command1="ALTER TABLE ? CHECK CONSTRAINT ALL"');
}

function fetch_array($id_res){
  $ARRY = array();

     if (odbc_fetch_row($id_res)){
         for ($i = 1; $i <= odbc_num_fields($id_res); $i++) {
              $field_name = odbc_field_name($id_res, $i);
              $ARRY[$field_name] = odbc_result($id_res,$field_name);
            }
       }
           return $ARRY;
   }

?>
