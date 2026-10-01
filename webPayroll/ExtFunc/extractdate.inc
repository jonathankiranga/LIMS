<?php

class extractdate {
    //put your code here
var $Date_Array = array();
var $d,$m,$y;
var $employeeclass = Array();

function explodedate($DateEntry){
// mktime(hour,minute,second,month,day,year);

         if (mb_strpos($DateEntry,'/')) {
		$this->Date_Array = explode('/',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'-')) {
		$this->Date_Array = explode('-',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'.')) {
		$this->Date_Array = explode('.',$DateEntry);
	}

        if($_SESSION['DefaultDateFormat']=='d/m/Y' or $_SESSION['DefaultDateFormat']== 'd.m.Y' or $_SESSION['DefaultDateFormat']== 'd-m-Y'){
            $this->d = $this->Date_Array[0];
            $this->m = $this->Date_Array[1];
            $this->y = $this->Date_Array[2];
         } elseif($_SESSION['DefaultDateFormat']=='m/d/Y' or  $_SESSION['DefaultDateFormat']== 'm.d.Y' or  $_SESSION['DefaultDateFormat']== 'm-d-Y'){
            $this->m = $this->Date_Array[0];
            $this->d = $this->Date_Array[1];
            $this->y = $this->Date_Array[2];
          } elseif($_SESSION['DefaultDateFormat']=='Y/m/d' or  $_SESSION['DefaultDateFormat']== 'Y.m.d' or  $_SESSION['DefaultDateFormat']== 'Y-m-d'){
             $this->y = $this->Date_Array[0];
             $this->m = $this->Date_Array[1];
             $this->d = $this->Date_Array[2];
           } else {
               die('<h1>Please set a date format for the system</h1>');
          }


     }


function returndate($month,$year){
    return Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,$month,1,$year));
}

function getemployeeheader(){
global $db;
$i=0;
$this->employeeclass = array();

    $sql="select * from prlemployeemaster ";
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $this->employeeclass['pfno'][$i] = $row['pf_no'];
        $this->employeeclass['fname'][$i] = $row['fname'];
        $this->employeeclass['mname'][$i] = $row['mname'];
        $this->employeeclass['lname'][$i] = $row['lname'];
        $this->employeeclass['pinno'][$i] = $row['pin_no'];
        $i++;
    }

return $this->employeeclass;
}

function filteremployeeheader($code){
global $db;
$i=0;
$this->employeeclass = array();

    $sql="select * from prlemployeemaster where pf_no='".$code."'";
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $this->employeeclass['pfno'][$i] = $row['pf_no'];
        $this->employeeclass['fname'][$i] = $row['fname'];
        $this->employeeclass['mname'][$i] = $row['mname'];
        $this->employeeclass['lname'][$i] = $row['lname'];
        $this->employeeclass['pinno'][$i] = $row['pin_no'];
        $i++;
    }

return $this->employeeclass;
}
}

$Extrdate = new extractdate();

?>
