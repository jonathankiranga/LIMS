<?php

class ComboBox {
    var  $CustomerArray = array();
    var  $BranchArray = array();
    var  $DepartArray = array();

    function getCustomers(){
        global $db;
        $results = DB_query("SELECT pf_no as [debtorno],fname+' '+mname+' '+lname as [name] FROM prlemployeemaster",$db);
        while($row = DB_fetch_array($results)){
             $this->CustomerArray[] = $row;
        }
    }

    function getBranches(){
        global $db;
        $results = db_query("SELECT  code as [branchcode],name as [brname] FROM [prlestablishment]",$db);
        while($row = DB_fetch_array($results)){
              $this->BranchArray[] = $row;
        }
     }
     
     function GetDepartments(){
         global $db;
        $results = db_query("SELECT code as [code],name as [name] FROM [prldepartments]",$db);
        while($row = DB_fetch_array($results)){
              $this->DepartArray[] = $row;
        }
     }

    function __construct() {
       $this->getCustomers() ;
       $this->getBranches() ;
       $this->GetDepartments() ;
    }
    
    function GetDepartName($code){
         global $db;
        $results = db_query("SELECT code as [code],name as [name] FROM [prldepartments] where code='".$code."'",$db);
        $row = DB_fetch_row($results);
        return $row[1];
    }
    
    
    function GetBranchName($code){
        global $db;
        $results = db_query("SELECT code as [branchcode],name as [brname] FROM [prlestablishment] where code='".$code."'",$db);
        $row = DB_fetch_row($results);
        return $row[1];
    }

}

$COMBOCLASS = NEW ComboBox;


?>
