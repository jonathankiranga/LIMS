<?php

   function createstores($code){
       global $db;
       $line="";
       
       
       $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
       $line .='<select name="location['.$code.']" required="required">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$_POST['location'][$code]==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
       $line .= '</select>';
       
       
        return $line;
    }
     
   Function StoreTankCMB($ItemCode){
        global $db;

        $line='';
        $line .='<select name="location['.$ItemCode.']" required="required">';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $_SESSION['productionTankStore']="S";
           $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
           while($rows= DB_fetch_array($REsults)){
                $line .= sprintf('<option value="%s" %s>%s</option>',trim($rows['code']),$_POST['location']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
            }
       }else{
          $_SESSION['productionTankStore']="T";
           $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                   $line .= sprintf('<option value="%s" %s>%s</option>',trim($value['tankname']),$_POST['location']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
       $line .= '</select>';
        return $line; 
    }
    
    function getbalance($stcode,$location,$UOM=''){
         global $db;
         
        $sql="SELECT sum(fulqty*partperunit) + sum(loosqty) as lunits 
             from stockledger where stockledger.itemcode='".$stcode."' 
             and stockledger.store='".$location."'";
        
        $ResultIndex=DB_query($sql,$db);
        $rows = DB_fetch_row($ResultIndex);
        $ans = $rows[0];
        
         return $ans;
         
     }
     
    function getbalanceByTypes($stcode, $location, $fromDate = '', $toDate = '', $doctyp = '') {
        global $db;
        
        $conditions = array();
        $conditions[] = "itemcode='" . $stcode . "'";
        
        if (!empty($location)) {
            $conditions[] = "store='" . $location . "'";
        }
        
        if (!empty($doctyp)) {
            $conditions[] = "doctyp='" . $doctyp . "'";
        }
        
        if (!empty($fromDate) && !empty($toDate)) {
            $conditions[] = "`date` BETWEEN '" . $fromDate . "' AND '" . $toDate . "'";
        } elseif (!empty($fromDate)) {
            $conditions[] = "`date` >= '" . $fromDate . "'";
        } elseif (!empty($toDate)) {
            $conditions[] = "`date` <= '" . $toDate . "'";
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT IFNULL(SUM(fulqty * partperunit),0) + IFNULL(SUM(loosqty),0) as lunits 
                FROM stockledger 
                WHERE " . $whereClause;
        
        $ResultIndex = DB_query($sql, $db);
        $rows = DB_fetch_row($ResultIndex);
        
        return $rows[0] ? $rows[0] : 0;
    }
    
   function CreateUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM stockmaster left join unit f on stockmaster.units=f.code 
  left join unit l on stockmaster.units=l.code 
  where itemcode= '".$stockcode."'", $db);
       
       
       $line .='<select name="UOM['.$pkey.']" required="required">';
              
       $rows=  DB_fetch_row($REsults);
           $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       
       
       
       $line .= '</select>';
       
       
        return $line;
   }
   
   function GetUnits($stockcode){
       Global $db;
       $list=array();
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM stockmaster left join unit f on stockmaster.units=f.code 
  left join unit l on stockmaster.units=l.code where itemcode= '".$stockcode."'", $db);
       
       $rows= DB_fetch_row($REsults);
       $list['fulqty']=$rows[0];
       $list['loosqty']=$rows[1];
       
       return $list;
   }
   
   function createlocations($code){
       global $db;
       $line="";
       
       
       $REsults=DB_query('SELECT locationid,locationdescription FROM fixedassetlocations', $db);
       $line .='<select name="location['.$code.']" required="required">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
           $rows['locationid'],$_POST['location'][$code]==$rows['locationid']?'selected="selected"':'',$rows['locationdescription']);
       }
       $line .= '</select>';
       
       
        return $line;
   }
   
?>