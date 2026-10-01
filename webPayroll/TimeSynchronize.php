<?php
/* $Id: session.inc 6338 2013-09-28 05:10:46Z daintree $*/
if(!isset($PathPrefix)) {
   $PathPrefix='';
}

if (!file_exists($PathPrefix . 'config.php')){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));
    if ($RootPath == '/' OR $RootPath == "\\") {
        $RootPath = '';
    }
    header('Location:' . $RootPath . '/install/index.php');
    exit;
}

include( 'config.php'); 

if (isset($SessionSavePath)){
    session_save_path($SessionSavePath);
}

if (!isset($SysAdminEmail)) {
    $SysAdminEmail='';
}

ini_set('session.gc_maxlifetime',$SessionLifeTime);

if( !ini_get('safe_mode') ){
    set_time_limit($MaximumExecutionTime);
    ini_set('max_execution_time',$MaximumExecutionTime);
}
session_write_close(); //in case a previous session is not closed
session_name('Smartpayrollsystem');
session_start();

$_SESSION['DatabaseName']=$DefaultDatabase ;

include('includes/PHPobjects.inc');
include('includes/DateFunctions.inc');
include('includes/ConnectDB_mssql.inc');

    $sql = "SELECT confname, confvalue FROM config";
    $ConfigResult = DB_query($sql,$db);
    while( $myrow = DB_fetch_array($ConfigResult) ) {
            if (is_numeric($myrow['confvalue']) AND $myrow['confname']!='DefaultPriceList' AND $myrow['confname']!='VersionNumber'){
                    //the variable name is given by $myrow[0]
                    $_SESSION[$myrow['confname']] = (double) $myrow['confvalue'];
            } else {
                    $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
            }
    } //end loop through all config variables
    
include('includes/LanguageSetup.php');
include('ExtFunc/gensalary.inc') ;


$ResultIndex = DB_query('Select 
    datepart(DAY,getdate()) as date,
      datepart(MONTH,getdate()) as month,
	  datepart(YEAR,getdate()) as year',$db);

 $rowdate = DB_fetch_row($ResultIndex);
 $Converteddate = date($_SESSION['DefaultDateFormat'], mktime(0,0,0,$rowdate[1],  $rowdate[0],$rowdate[2]));
 $period = GetPeriod($Converteddate,$db);
 $date   = "cast('".$rowdate[2].'-'.$rowdate[1].'-'.$rowdate[0]."' as smalldatetime)";
 $dateto = "cast('".$rowdate[2].'-'.$rowdate[1].'-'.($rowdate[0]+1)."' as smalldatetime)";
   
$ResultIndex = DB_query("SELECT count(*) FROM [prltimesheet] where date between ".$date." and ".$dateto,$db);
$Check_for_data = DB_fetch_row($ResultIndex);

if($Check_for_data[0]==0){
    $DATE = FormatDateForSQL($Converteddate);
    MakeTimedataReady($DATE,$DATE,$period);
    DB_query('EXEC [dbo].[MakeTimedataReady]',$db);
    prnMsg('No data exits, now creating ');
}else{
    prnMsg('Data Exists :'.$Check_for_data[0].' records');
}

?>