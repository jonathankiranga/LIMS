<?php

   Function GetProductOption(){
    global $db;
    
    $ResultIndex=DB_query("select itemcode,descrip FROM stockmaster "
    . " where inactive=0 "
    . "and (isstock_1=1 or isstock_2=1 or isstock_4=1 or isstock_5=1) Order by descrip", $db);
    while($value=DB_fetch_array($ResultIndex)){
        if($_POST['Prod_itemcode']==trim($value['itemcode'])){
            echo '<option value="'.trim($value['itemcode']).'"  selected="selected">'. trim($value['descrip']).'</option>';
        }else{
           echo '<option value="'.trim($value['itemcode']).'" >'.trim($value['descrip']).'</option>';
       
        }
        
    }
}   
     
   function createstores($code){
       global $db;
       $line="";
       
       
       $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
       $line .='<select name="location['.$code.']" required="required" onchange="CalculateForm(salesform.submit);">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   trim($rows['code']),$_POST['location'][$code]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
       }
       $line .= '</select>';
       
       
        return $line;
    }
    
   function CreateUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM stockmaster left join unit f on stockmaster.units=f.code 
  left join unit l on stockmaster.units=l.code where itemcode= '".$stockcode."'", $db);
       
       
       $line .='<select name="UOM['.$pkey.']" required="required" onchange="CalculateForm(salesform.submit)">';
              
       $rows=  DB_fetch_row($REsults);
        $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       
       
       
       $line .= '</select>';
       
       
        return $line;
   }
    
function getbalance($stcode,$location,$UOM=''){
         global $db;
      
       $tankClass=new tankClass() ;
       $tankClass->update_tank_balance();
       
       $sql="SELECT IFNULL(sum(fulqty * partperunit),0) as fulqty,IFNULL(sum(loosqty),0) as loosqty  from stockledger 
             where stockledger.itemcode='".$stcode."' and stockledger.store='".$location."'"; 
             $ResultIndex=DB_query($sql,$db);
            while($rows = DB_fetch_array($ResultIndex)){
                $StockBalance[$stcode]=array('fulqty'=> (int) $rows['fulqty'],'loosqty'=>(int) $rows['loosqty']);
            }
      
         $value = $StockBalance[$stcode];
         $fullqty = $value['fulqty'] + $value['loosqty'];
        
        return $fullqty;
     }
     
    function getbalanceByTypes($stcode, $location, $fromDate = '', $toDate = '', $doctyp = '') {
        global $db;
        
        $tankClass = new tankClass();
        $tankClass->update_tank_balance();
        
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
     
   Function GetwoTank(){
    global $db;
    
    $ResultIndex=DB_query("select tankname, stockmaster.descrip from ProductionUnit "
            . "join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode  ", $db);
    while($value=DB_fetch_array($ResultIndex)){
        if($_POST['wotank']==trim($value['tankname'])){
            echo '<option value="'.trim($value['tankname']).'"  selected="selected">'.$value['tankname'].'-'. $value['descrip'].'</option>';
        }else{
           echo '<option value="'.trim($value['tankname']).'" >'.$value['tankname'].'-'. $value['descrip'].'</option>';
       
        }
        
    }
}

   function CreatepageUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM stockmaster left join unit f on stockmaster.units=f.code 
  left join unit l on stockmaster.units=l.code where itemcode= '".$stockcode."'", $db);
       
       
       $line .='<select name="UOM['.$pkey.']" required="required" onchange="ReloadForm(salesform.submit)">';
              
       $rows=  DB_fetch_row($REsults);
        $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                      . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       
       
       
       $line .= '</select>';
       
       
        return $line;
   }
    
   function CreateQuoteUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
        $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
        FROM stockmaster left join unit f on stockmaster.units=f.code 
        left join unit l on stockmaster.units=l.code where itemcode= '".$stockcode."'", $db);
    
       $line .='<select name="UOM['.$pkey.']" required="required" onchange="CalculateForm(salesform.submit)">';
              
       $rows  = DB_fetch_row($REsults);
       $rowIDF = trim($rows[0]);
       $rowIDL = trim($rows[1]);
       
        $line .= sprintf('<option value="F_'.$rowIDF.'" %s >'.$rowIDF.'</option>'
                       . '<option value="L_'.$rowIDL.'" %s >'.$rowIDL.'</option>'
                   ,$_POST['UOM'][$pkey]=='F_'.$rowIDF?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='L_'.$rowIDL?'selected="selected"':'');
      
       $line .= '</select>';
       
       
        return $line;
   }
   
   Function getGriduom($pkey,$codeX){
       Global $db;
       unset($_SESSION['units']);
       
       if(!isset($_SESSION['units'])){
            $ResultIndex=DB_query("select code, descrip from unit",$db);
             while($row = DB_fetch_array($ResultIndex)){
                $code = trim($row['code']);
                $_SESSION['units'][$code]=$row;
            }
       }
       
       $code =isset($_POST["units"][$pkey])? trim($_POST["units"][$pkey]):'';
       $line ='<select name="units['.$pkey.']" required="required" onchange="ReloadForm(prodform.refresh)">';
       foreach ($_SESSION['units'] as $key => $rows) {
         $selected =((trim($rows['code'])==$codeX)?'selected="selected"':"");
         $line .=sprintf('<option value="%s" %s>%s</option>',trim($rows['code']),$selected,trim($rows['descrip']));
       }
       
       $line .= '</select>';
    return $line;
   }
   
   
   
   Function getCostType($pkey){
       
      
$itemArray=array(
    ""=>'',
    "1"=>'Raw Material',
    "2"=>'Direct Consumable Material');
       
       $codeX =isset($_POST["CostType"][$pkey])? trim($_POST["CostType"][$pkey]):'';
       $line ='<select name="CostType['.$pkey.']" onchange="ReloadForm(prodform.refresh)">';
       foreach ($itemArray as $key => $rows) {
         $selected =(($key==$codeX)?'selected="selected"':"");
         $line .=sprintf('<option value="%s" %s>%s</option>',$key,$selected,trim($rows));
       }
       
       $line .= '</select>';
    return $line;
   }
   
   Function getTankbalance($tank,$itemcode){
         global $db;
         
         $tankClass=new tankClass() ;
         $tankClass->update_tank_balance();
         
         $sql=sprintf("SELECT IFNULL(units,0) FROM ProductionUnit 
              where tankname='%s' and itemcode='%s'",$tank,$itemcode);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $number =$row[0];
             
       return     $number;
     }
     
     
     Function getTankCapacity($tank,$itemcode){
         global $db;
         
         $tankClass=new tankClass() ;
         $tankClass->update_tank_balance();
         
         $sql=sprintf("SELECT balance FROM ProductionUnit 
              where tankname='%s' and itemcode='%s'",$tank,$itemcode);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $number =($row[0]);
        return     $number;
     }
     
   class tankClass{
         var $rows1;
         var $rowsunits;
                 
                function update_tank_balance(){
                    global $db;

                    $sql="SELECT tankname FROM ProductionUnit";
                       $ResultIndex = DB_query($sql,$db);
                       while($dbrows= DB_fetch_array($ResultIndex)){
                           $this->rows1[]=$dbrows;
                            
                       }
                        
                       foreach ($this->rows1 as $key => $tankcode) {
                           $myTankCode=$tankcode['tankname'];
                           if(mb_strlen($myTankCode)>0){
                           $sql=sprintf("select sum(units) as units from tanktrans "
                                   . " where tankname='%s'",$myTankCode);
                           
                            $ResultIndex = DB_query($sql,$db);
                            while($dbrows= DB_fetch_array($ResultIndex)){
                                    $this->rowsunits[$myTankCode]=$dbrows['units'];
                                }
                           }
                       }
                       
                        foreach ($this->rowsunits as $tankcode => $balance) {
                          $sql = sprintf("update ProductionUnit set units=(%f) 
                          where ProductionUnit.tankname= '%s'",$balance,$tankcode); 
                          
                          $ResultIndex = DB_query($sql,$db);
                        }

                }
                
                
     
     }
   
?>