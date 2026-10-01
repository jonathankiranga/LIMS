<?php
   /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define('LIKE','LIKE');

session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
session_start();
include('../config.php');

Global $db,$PriorityArray,$wwwusers,$TaskstatusArray,$NewContacts;
 
// Make sure it IS global, regardless of our context
$database = $_SESSION['DatabaseName'];
 //DB wrapper functions to change only once for whole application
  $db = new mysqli($host, $DBUser, $DBPassword, $database);
 

$CRMArray = Array(
    '0'=>'New Lead',
    '1'=>'Scheduling for a Meeting',
    '2'=>'Beginning Negotiations',
    '3'=>'Advanced Negotiations',
    '4'=>'Closing Deal',
    '5'=>'Lost Deal',
    '6'=>'Now A Customer');

$PriorityArray=array(
    "0"=>'High',
    "1"=>'Moderate',
    "2"=>'low');

$frequencyArray=array(
    "0"=>'Once',
    "1"=>'Daily',
    "7"=>'Weekly',
    "30"=>'Monthly',
    "365"=>'Annualy');

$TaskstatusArray=array(
    "0"=>'Not yet begun',
    "1"=>'In progress',
    "2"=>'Almost Done',
    "3"=>'Taking Longer than expected',
    "4"=>'Complete');

/*These arrays are for the CRM*/
$result = DB_query("SELECT userid,realname FROM www_users where `blocked`=0 ",$db);
While($row= DB_fetch_array($result)){
    $user=trim($row['userid']);
    $wwwusers[$user]=$row['realname'];
}

$result = DB_query("SELECT `Company`,`pkey` FROM `NewContacts`",$db);
While($row= DB_fetch_array($result)){
    $pkey=(int)$row['pkey'];
    $NewContacts[$pkey]=$row['Company'];
}

 if(isset($_GET['TaskName'])){
    $Function=$_GET['TaskName'];
 }elseif(isset($_POST['TaskName'])){
    $Function=$_POST['TaskName'];
 }
 
 if(isset($_GET['TASKFORM'])){
    $TASKFORM=$_GET['TASKFORM'];
 }elseif(isset($_POST['TASKFORM'])){
    $TASKFORM=$_POST['TASKFORM'];
 }
 
if(isset($Function)){
     $PriodnoArray = GetReportDates($TASKFORM);
    switch ($Function) {
        case 'ACTIVITY':
            CompletedActivity();
            break;
       default:
            CompletedTasks();
            break;
    }
}

/* Begin of functions*/

Function CompletedTasks(){
    global $db,$CRMArray;
    $FinalArray = GetTasks();
    
    $html = '';

    if(is_array($FinalArray)){
        foreach ($FinalArray as $key => $value) {
           $html .= ShowTasksHtml($value);
        }
    }else{
        $html ='No Data';
    } 
  
   echo $html;
}

function ShowTasksHtml($value){
    Global $db,$PriorityArray,$wwwusers,$TaskstatusArray;
      
    $sp = trim($value["Status"]);
    $Taskstatus=$TaskstatusArray[$sp];
    
    if($value["Future"]>0){
        $style='style="background-color:pink"';
    }
    if($value["Future"]==0){
        $style='style="background-color:white;"';
    }
    if($value["Future"]<0){
        $style='style="background-color:lightcyan;"';
    }
      
    if($sp==4){
       $style='style="background-color:white;"';
    }
    
    $sp =(int) $value["Priority"];
    $Priority=$PriorityArray[$sp];

    $sp = trim($value["TaskOwner"]);
    $TaskOwner=$wwwusers[$sp];
    
   
       
    $taskdetails = html_entity_decode($value["taskdetails"]);
    $taskdetails = str_replace('<p>','<li>',$taskdetails);
    $taskdetails = str_replace('</p>','</li>',$taskdetails);
    return  sprintf('<tr %s>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><ul>%s</ul></td>'
              . '</tr>',$style,$TaskOwner,$value["Taskname"],
            miniConvertSQLDate($value["datedue"]),$Priority,$taskdetails,$Taskstatus);

}

function GetTasks(){
     global $db,$PriodnoArray ;
   
     $SQL=sprintf("SELECT `TaskOwner`,`Taskname`,`datedue`,`Status`,`Priority`,`frequency`,`taskdetails` 
      ,DATEDIFF(`datedue`,NOW()) as Future  FROM `Tasks` where datedue between '%s' and '%s' 
      order by `TaskOwner`,`Priority`,`Status` asc", $PriodnoArray['fromDate'],$PriodnoArray['toDate']);
     
     $Activities=array();
     $result=DB_query($SQL,$db);
     while($myrow = DB_fetch_array($result)){
         $Activities[]=$myrow;
     }
     
    
 return  $Activities;
}

/* Ene of functions*/

Function ShowActivityHtml($value){
     Global $db,$NewContacts,$wwwusers,$CRMArray;

        $sp = trim($value["Status"]);
        $Taskstatus =$CRMArray[$sp];
        
        if($value["valueofbusiness"]==''){
            $antipatedBusines='Not yet Known';
        }else{
           $antipatedBusines= number_format($value["valueofbusiness"]);
        }
        if($value["Future"]>0){
            $style='style="background-color:pink"';
        }
        if($value["Future"]==0){
            $style='style="background-color:white;"';
        }
        if($value["Future"]<0){
            $style='style="background-color:lightcyan;"';
        }

        if($sp>=4){
            $style='style="background-color:white;"';
        }
        
        $co=(int)$value["Contact"];
        $contact =$NewContacts[$co];
        
        $sp= $value["ActivityOwner"];
        $salesperson =$wwwusers[$sp];
        
     
        $taskdetails = html_entity_decode($value["taskdetails"]);
        $taskdetails = str_replace('<p>','<li>',$taskdetails);
        $taskdetails = str_replace('</p>','</li>',$taskdetails);
     
       return  sprintf('<tr %s>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div style="width:100px;">%s</div></td>'
                  . '<td><ul>%s</ul></td>'
                  . '<td><div>%s</div></td>'
                  . '</tr>',$style,$value["Activityname"],
                  miniConvertSQLDate($value["fromdue"]),miniConvertSQLDate($value["todue"]),
                $contact,$antipatedBusines,$Taskstatus,$taskdetails,$salesperson);

}

Function CompletedActivity(){
     global $db,$CRMArray;
   
     $FinalArray = GetActivities();
     $HTML='';

     if(is_array($FinalArray)){
        foreach ($FinalArray as $value) {
          $HTML  .=   ShowActivityHtml($value);
        }
     }else{
         $HTML= 'No data';
     }
    echo $HTML;
}

function GetActivities(){
       global $db,$PriodnoArray ;
 
       $SQL=sprintf("select `pkey`,`ActivityOwner`,`Activityname`,`fromdue`,`todue`,`Contact`,`Status`
      ,`valueofbusiness`,`taskdetails`,`createdby`,`createdon`,`lastactivity`,DATEDIFF(`fromdue`,NOW()) as Future
       FROM `NewActivity` where `todue` between '%s' and '%s'  order by `ActivityOwner`,`Status` desc ",$PriodnoArray['fromDate'],$PriodnoArray['toDate']);

      $Activities=array();
      $result=DB_query($SQL,$db);
     while($myrow = DB_fetch_array($result)){
         $Activities[]=$myrow;
     }

 return  $Activities;
}

/* Ene of functions*/

function GetReportDates($dates){
    global $db;
    
   $ResultIndex=DB_query("Select DATE_ADD(DATE_ADD(`lastdate_in_period`, INTERVAL 1 DAY), INTERVAL -1 MONTH),DATE_ADD(`lastdate_in_period`, INTERVAL 1439 MINUTE)
             from `periods` where `periodno`='".$dates."'", $db);
   $rowdate = DB_fetch_row($ResultIndex);
   $FromDate = $rowdate[0];
   $ToDate = $rowdate[1];
 
return array("fromDate"=>$FromDate,"toDate"=>$ToDate);
}

/* Ene of functions*/


 if(isset($_GET['Stockfind'])){
    Stockfind($_GET['offset'],$_GET['top'],$_GET['Stockfind']);
 }elseif(isset($_POST['Stockfind'])){
    Stockfind($_POST['offset'],$_POST['top'],$_POST['Stockfind']);
 }
   
function Stockfind($offset,$height,$value){
    Global $db;
    $_SESSION['work_orders']=array();
    unset($_SESSION['work_orders']);
    
    $top  = 50;
    $left = $offset['left'];
   
     $ResultIndex = DB_query("SELECT itemcode,upper(descrip) as descrip from stockmaster 
               where inactive=0 and (isstock_4 =1 or isstock_1 =1)  order by descrip", $db);
    
       $return= '<div class="finderheader" id="findStock" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>What do you want to Produce</b><div class="finder"><table id="multStockTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
           $return .= sprintf('<tr onclick="prodInventory(\'%s\',\'%s\');ReloadForm(prodform.refresh);"><td>%s</td></tr>',trim($row['itemcode']),trim($row['descrip']),trim($row['descrip'])) ;
           }
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="multStockInput" onkeyup="multStockFunction();"  autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button" onclick="prodInventory(\'\',\'\')" value="Cancel" />
          </div>';
    
 
   echo $return;
 }
 
 
 if(isset($_GET['stockname'])){
    stockname($_GET['offset'],$_GET['top'],$_GET['stockname']);
 }elseif(isset($_POST['stockname'])){
    stockname($_POST['offset'],$_POST['top'],$_POST['stockname']);
 }
   
function stockname($offset,$height,$value){
    Global $db;
    
     $ResultIndex=DB_query("select code, descrip from unit",$db);
             while($row = DB_fetch_array($ResultIndex)){
                $code = trim($row['code']);
                $_SESSION['units'][$code]=$row;
            }
            
    $top  = 50;
    $left = $offset['left'];
   
     $ResultIndex = DB_query("SELECT itemcode,upper(descrip) as descrip,`units` from stockmaster 
               where inactive=0 and (isstock_4 =1 or isstock_2 =1)
               order by descrip", $db);
    
       $return= '<div class="finderheader" id="findStock" style="top:'. $top .'px; left:'.$left.'px;">'
               . '<b>What do you want to Produce</b><div class="finder">'
               . '<table id="multStockTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
                $code = trim($row['units']);
                $description=trim($row['descrip']).' '.$_SESSION['units'][$code]['descrip'];
                $return .= sprintf('<tr onclick="ItemInventory(\'%s\',\'%s\');ReloadForm(prodform.refresh);">'
                      . '<td>%s</td></tr>',trim($row['itemcode']),$description,trim($row['descrip'])) ;
           }
              $return .= sprintf('<tr onclick="ItemInventory(\'%s\',\'%s\');ReloadForm(prodform.refresh);">'
                      . '<td>%s</td></tr>',trim('H2O'),trim('WATER ltrs'),trim('WATER')) ;
           
           
           
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="multStockInput" onkeyup="multStockFunction();"  autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button" onclick="ItemInventory(\'\',\'\')" value="Cancel" />
          </div>';
    
 
   echo $return;
 }
 
 
 
 
 
 

function DB_query($SQL,$Conn,$ErrorMessage='',$DebugMessage= '',$Transaction=false,$TrapErrors=true){
    global $debug;
	
	$result = $Conn->query($SQL);
    
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



   

function miniConvertSQLDate($DateEntry) {
    	
//for MySQL dates are in the format YYYY-mm-dd

	if (mb_strpos($DateEntry,'/')) {
		$Date_Array = explode('/',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'-')) {
		$Date_Array = explode('-',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'.')) {
		$Date_Array = explode('.',$DateEntry);
	} else {
		switch ($_SESSION['DefaultDateFormat']) {
			case 'd/m/Y':
				return '0/0/000';
				break;
			case 'd.m.Y':
				return '0.0.000';
				break;
			case 'm/d/Y':
				return '0/0/0000';
				break;
			case 'Y/m/d':
				return '0000/0/0';
				break;
			case 'Y-m-d':
				return '0000-0-0';
				break;
		}
	}

	if (mb_strlen($Date_Array[2])>4) {  /*chop off the time stuff */
		$Date_Array[2]= mb_substr($Date_Array[2],0,2);
	}

	if ($_SESSION['DefaultDateFormat']=='d/m/Y'){
		return $Date_Array[2].'/'.$Date_Array[1].'/'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='d.m.Y'){
		return $Date_Array[2].'.'.$Date_Array[1].'.'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='m/d/Y'){
		return $Date_Array[1].'/'.$Date_Array[2].'/'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='Y/m/d'){
		return $Date_Array[0].'/'.$Date_Array[1].'/'.$Date_Array[2];
	} elseif ($_SESSION['DefaultDateFormat']=='Y-m-d'){
		return $Date_Array[0].'-'.$Date_Array[1].'-'.$Date_Array[2];
	}
} // end function ConvertSQLDate

