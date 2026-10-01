<?php



class timmer {
    var $db;
    var $pfno;
    var $timein;
    var $timeout;
    var $date;
    var $period;
    var $hour;
    Var $minute;
    var $Date_Array = array();
    var $Msg ='You have sucessfully clocked in';
    var $Msg2 ='This account has already been clocked in';
    var $Msg3 ='You have sucessfully clocked Out';
    var $Msg4 ='This account has not yet been clocked in <br /> The proceedure requires that you clock in before clocking out.';
    var $m;
    var $y;
    var $d;
    var $recommended;
    var $appdate;
    var $ERPdate;

    function __construct() {
       global $db;
       $this->db = $db;
       $this->date =  Date('Y-m-d',Mktime(0,0,0,date('m'),date('d'),date('Y')));
       $this->appdate = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,date('m'),date('d'),date('Y')));
       $this->period = GetPeriod($this->appdate,$this->db);
     }

    function explodedate(){
         $DateEntry = $this->date ;

         if (mb_strpos($DateEntry,'/')) {
            $this->Date_Array = explode('/',$DateEntry);
	 } elseif (mb_strpos ($DateEntry,'-')) {
            $this->Date_Array = explode('-',$DateEntry);
	 } elseif (mb_strpos ($DateEntry,'.')) {
            $this->Date_Array = explode('.',$DateEntry);
	 }
             $this->y = $this->Date_Array[0];
             $this->m = $this->Date_Array[1];
             $this->d = $this->Date_Array[2];
           
     }

    function compiletime(){
        $this->explodedate();
        $this->hour = $_POST['hours'];
        $this->minute = $_POST['minutes'];
        $this->appdate=  Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,$this->m,$this->d,$this->y));
        $this->period = GetPeriod($this->appdate,$this->db);
   }

    function clockin(){
       $this->pfno = $_POST['pfno'];
       $this->compiletime();
       $this->timein = Date('Y-m-d h:i:sa',Mktime($this->hour,$this->minute,0,$this->m,$this->d,$this->y));
       $FromDate = str_replace('/','-',  substr($this->ERPdate,0,10).' 00:00:00');
       $ToDate = str_replace('/','-', substr($this->ERPdate,0,10).' 23:59:59');
     
        if($this->checkifclockedin() == 0){
            $sql= sprintf("update prltimesheet set timein='%s',DidtheylogIn=1 where  pfno='%s' and
                 date between '". $FromDate."' and '".$ToDate."'  and  period='%s' ",$this->timein,$this->pfno,$this->period);
             DB_query($sql,$this->db);
             prnMsg($this->Msg);
        } else {
             prnMsg($this->Msg2);
        }

    }

    function clockout(){
       $this->pfno = $_POST['pfno'];
       $this->compiletime();
       $this->timeout = Date('Y-m-d h:i:sa',Mktime($this->hour,$this->minute,0,$this->m,$this->d,$this->y));
       $FromDate = str_replace('/','-',  substr($this->ERPdate,0,10).' 00:00:00');
       $ToDate = str_replace('/','-', substr($this->ERPdate,0,10).' 23:59:59');
      
        $sql= sprintf("update prltimesheet set timeout='%s',DidtheylogIn=1 where  pfno='%s' and
                date between '". $FromDate."' and '".$ToDate."' and  period='%s' ",
                $this->timeout,$this->pfno,$this->period);
            
        if($this->checkifclockedin() == 1){
           DB_query($sql,$this->db);
            prnMsg($this->Msg3);
        } else {
             prnMsg($this->Msg4);
        }
    }

    function checkifclockedin(){
        $FromDate = str_replace('/','-',  substr($this->ERPdate,0,10).' 00:00:00');
	$ToDate = str_replace('/','-', substr($this->ERPdate,0,10).' 23:59:59');
        
        $sql = "select timein from prltimesheet  where pfno='".$this->pfno."' "
            . " and period='".$this->period."'  and date between '". $FromDate."' and '".$ToDate."'";
        
        $results = DB_query($sql,$this->db);
        $timein  = DB_fetch_row($results);
        if(isset($timein[0])){
           return 1;
        } else {
           return 0;
        }

    }

    FUNCTION GETALLEMPLOYEES(){

    $sql="SELECT  pm.[pf_no] , pm.[status] ,
       pm.[fname] , pm.[mname] , pm.[lname] , pm.[idno],
       pm.[pin_no] , pm.[nssf_no] , pm.[nhif_no],
       pm.[telno] , pm.[email] , pm.[freqcode] ,
       pm.[basicpay] , pm.[noofhrsperday] ,
       ts.[timein] , ts.[timeout] , ts.[noofhours], ts.[date]
       FROM [prlemployeemaster] pm
       join [prltimesheet] ts on  pm.pf_no = ts.pfno
       and ts.period='". $this->period ."' and ts.date='".$this->date."'";

        $ResultIndex = DB_query($sql,$this->db);
        return $ResultIndex ;
     }

    FUNCTION GEToneEMPLOYEE($pfno=''){
    $this->pfno = $pfno ;

    $sql="SELECT  
       pm.[pf_no] ,
       pm.[status] ,
       pm.[fname] ,
       pm.[mname] ,
       pm.[lname] ,
       pm.[idno],
       pm.[pin_no] ,
       pm.[nssf_no] ,
       pm.[nhif_no],
       pm.[telno] ,
       pm.[email] ,
       pm.[freqcode] ,
       pm.[basicpay] ,
       pm.[noofhrsperday] ,
       ts.[timein] ,
       ts.[timeout],
       ts.[noofhours], 
       ts.[date]
 FROM [prlemployeemaster] pm
      join [prltimesheet] ts on pm.pf_no=ts.pfno
      and ts.period='". $this->period ."'"
      . " and ts.date='".$this->date."' "
      . "  where ts.pfno='".$this->pfno."'";
    
    $ResultIndex = DB_query($sql,$this->db);
    return $ResultIndex ;
}

    function getmydate($s){

        if($s==null){
            $date = null;
        }else {
            $dt = new DateTime($s);
            $date = $dt->format('Y/m/d');
        }

        return $date;
   }

    function getmytime($s){

    if($s==null){
        $time = null;
    }else {
        $dt = new DateTime($s);
        $time = $dt->format('h:i:sa');
    }

    return $time;
}

    function autoclock($pfno,$date,$timeIn,$timeOut){
        $period = GetPeriod($date,$this->db);
        $FromDate = str_replace('/','-',  substr($date,0,10).' 00:00:00');
	$ToDate = str_replace('/','-', substr($date,0,10).' 23:59:59');
        
         $sql=sprintf("Update prltimesheet set timein='%s',timeout='%s' ,DidtheylogIn=1
             where pfno='%s' and date between '%s' and %s and period='%s' ",
             $timeIn,$timeOut,$pfno,$FromDate,$ToDate,$this->period);

         DB_query($sql,$this->db);
           
    }

}

?>
