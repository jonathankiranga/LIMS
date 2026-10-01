<?php

class ComboBox {
    var  $CustomerArray = array();
    var  $SupplierArray = array();
    var  $accountno ;

    function getCustomers(){
        global $db;
        $results = DB_query('SELECT `itemcode` AS debtorno, `customer` AS name FROM `debtors`',$db);
        while($row = DB_fetch_array($results)){
             $this->CustomerArray[] = $row;
        }
    }

    function getSuppliers(){
        global $db;
        $results = DB_query('SELECT `itemcode` AS supplierid, `customer` AS suppname FROM `creditors`',$db);
        while($row = DB_fetch_array($results)){
              $this->SupplierArray[] = $row;
         }
    }

    function __construct() {
       $this->getCustomers() ;
       $this->getSuppliers() ;
    }

    


}

$COMBOCLASS = NEW ComboBox;


?>
