<?php
include('includes/session.inc');
$Title = _('Stock Adjustments');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('transactions/stockbalance.inc');   

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
 
 if(!isset($_POST['date'])){ 
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
}
    
$StoreArray=array();
$REsults=DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
while($rows=  DB_fetch_array($REsults)){
    $StoreArray[]=$rows;
}

$results=DB_query("Select `itemcode`,`descrip`,`inactive`,`averagestock`,`postinggroup`  
        FROM `stockmaster` where (isstock_3=0 or isstock_3 is null) and
        (`inactive`=0 or `inactive` is null) order by `descrip` asc", $db);
 while($rows=DB_fetch_array($results)){
     $stockmaster[]=$rows;
 }
   
 
if(isset($_POST['location'])){
  $Location =trim($_POST['location']);
}


if(isset($_GET['StockID'])){
  $StockID =trim($_GET['StockID']);
}elseif(isset($_POST['StockID'])){
  $StockID =trim($_POST['StockID']);
}  

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Stock Adjustments') . '" alt="" />' . ' ' . _('Stock Adjustments') . '</p>';


if(isset($_POST['savecost'])){
    if($_POST['averagecost']>0){
     $value="Update stockmaster set `averagestock`='".$_POST['averagecost']."' where itemcode='".trim($_POST['StockID'])."'";
     DB_query($value,$db);
  }
}


if(isset($_POST['saveadjustment'])){

 $SQL=array();

  if($_POST['averagecost']>0){
    $value="Update stockmaster set `averagestock`='".$_POST['averagecost']."' where itemcode='".trim($_POST['StockID'])."'";
     DB_query($value,$db);
  }
            
 if(mb_strlen($_POST['stockbalance'])>0){
     
      $_POST['DocNo'] = GetNextTransNo(17, $db);
      $journalno = GetNextTransNo(0, $db);
      $Periodno = GetPeriod($_POST['date'], $db,true);
      $StockBalLose = (float) $_POST['StockBalanceInloose'];
      $StockBalance = (float) $_POST['stockbalance'];
      if($StockBalLose>=0){
          $Adjustby = (float) $StockBalance - $StockBalLose;
      }elseif($StockBalLose<0){
          $Adjustby = (float) $StockBalance - $StockBalLose  ;
      }
      $_POST['stockbalance'] = abs($Adjustby) ;
      $Cost=(float) $_POST['averagecost'];
      $date = FormatDateForSQL($_POST['date']);
      
if(IsTankOrStore(trim($StockID))=="S"){
         $SQL[]="INSERT INTO `stockledger`
            (`date`,`stname`,`doctyp`,`itemcode`,`invref`,`fulqty`,`loosqty`,`price`,`store`,`journal`,`period`,partperunit)
          VALUES ('".$date ."','Stock Adjust','17','".$StockID ."','".$_POST['DocNo']."',0,'".$Adjustby."',".$Cost.",'".$_POST['location']."','".$journalno."','".$Periodno."',1)";
         
         $SQL[]="INSERT INTO StockRegister (itemcode, StockIn, StockOut, cost, journal, GRN)
            VALUES ('$StockID', " . ($Adjustby > 0 ? $Adjustby : 0) . ", " . ($Adjustby < 0 ? abs($Adjustby) : 0) . ", $Cost, '$journalno', '" . $_POST['DocNo'] . "')";
         
       }else{
          
$sql="SELECT IFNULL(sum(fulqty * partperunit),0) + IFNULL(sum(loosqty),0) as loosqty from stockledger 
             where stockledger.itemcode='".$_POST['StockID']."'"; 
             $ResultIndex=DB_query($sql,$db);
              $StockinStore= DB_fetch_row($ResultIndex);
             
             if($StockinStore[0]!=0){
               $SQL[]="INSERT INTO `stockledger` (`date`,`stname`,`doctyp`,`itemcode`,`invref`,`fulqty`,`loosqty`,`price`,`store`,`journal`,`period`,partperunit)
                 VALUES ('".$date ."','Stock Adjust','17','".$StockID ."','".$_POST['DocNo']."',0,'".($StockinStore[0] * -1)."',".$Cost.",'".$StoreArray[0]['code']."','".$journalno."','".$Periodno."',1)";
               
               $SQL[]="INSERT INTO StockRegister (itemcode, StockIn, StockOut, cost, journal, GRN)
                  VALUES ('$StockID', 0, " . abs($StockinStore[0]) . ", $Cost, '$journalno', '" . $_POST['DocNo'] . "')";
               
               $_POST['stockbalance']=(isset($StockinStore[0])?$_POST['stockbalance']+$StockinStore[0]:$_POST['stockbalance']);
            }
          
            $SQL[]= sprintf("INSERT INTO `tanktrans` (`tankname`,`units`,`uom`,`date`,`batchno`,`doctype`,`itemcode`)
             VALUES  ('%s',%f ,'%s' ,'".$date ."','%s',17,'%s')",$_POST['location'],$Adjustby,'loosqty',$_POST['DocNo'],$_POST['StockID']);
          
    
   }
      
                
if($_POST['stockbalance']>=0){
        
         $SQL[]=SPRINTF("Insert into `Generalledger`
         (`journalno`,`Docdate` ,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`VATaccountcode`
         ,`amount`,`VATamount`,`currencycode`,`ExchangeRate`,`narration`)
         (select
          '%s'
         ,'".$date ."'
         ,'%s'
         ,'".$_POST['DocNo']."'
         ,17
         ,`inventorypostinggroup`.`balancesheet`
         ,`inventorypostinggroup`.`stockvariance`
         ,' '
         ,IFNULL(`stockmaster`.`averagestock` * ".$_POST['stockbalance'].",0)
         ,0
         ,'".$_SESSION['CompanyRecord']['currencydefault']."'
         ,1
         ,(rtrim(`stockmaster`.`descrip`) +' X ".$_POST['stockbalance']."' )  as narration
         from `stockmaster` 
         join `inventorypostinggroup` on `inventorypostinggroup`.`code`=`stockmaster`.`postinggroup`
         where `stockmaster`.itemcode='%s' 
         AND `inventorypostinggroup`.`balancesheet` IS NOT NULL
         AND `inventorypostinggroup`.`stockvariance` IS NOT NULL
         )",$journalno,$Periodno,trim($_POST['StockID']));
                       
                       
      }elseif($_POST['stockbalance']<0){
          
            $SQL[]=SPRINTF("Insert into `Generalledger`
             (`journalno`,`Docdate` ,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`VATaccountcode`
             ,`amount`,`VATamount`,`currencycode`,`ExchangeRate`,`narration`)
             (select
              '%s'
             ,'".$date ."'
             ,'%s'
             ,'".$_POST['DocNo']."'
             ,17
             ,`inventorypostinggroup`.`stockvariance`
             ,`inventorypostinggroup`.`balancesheet`
             ,' '
             ,IFNULL(`stockmaster`.`averagestock` * ".($_POST['stockbalance'] * -1).",0)
             ,0
             ,'".$_SESSION['CompanyRecord']['currencydefault']."'
             ,1
            ,(rtrim(`stockmaster`.`descrip`) +' X ".($_POST['stockbalance'] * -1)."' )  as narration
          from `stockmaster` 
             join `inventorypostinggroup` on `inventorypostinggroup`.`code`=`stockmaster`.`postinggroup`
             where `stockmaster`.itemcode='%s' 
             AND `inventorypostinggroup`.`balancesheet` IS NOT NULL
             AND `inventorypostinggroup`.`stockvariance` IS NOT NULL
             )",$journalno,$Periodno,trim($_POST['StockID']));
}



 }
 

    DB_Txn_Begin($db);
    foreach ($SQL as $value) {
        DB_query($value,$db);
    }

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    }else{
        DB_Txn_Commit($db);
        prnMsg('This transaction has been posted');
    }
     
}

    
echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post" id="inventory">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($StockID)){
        $results=DB_query("Select `itemcode`,`descrip`,`inactive`,`averagestock`,`postinggroup` FROM `stockmaster` where itemcode='".$StockID."'", $db);
        $rowsstock=DB_fetch_row($results);
        $DESCRIPTION=$rowsstock[1];
        $AVERAGESTOCK=$rowsstock[3];        
        $date = FormatDateForSQL($_POST['date']);
      
    if(IsTankOrStore(trim($StockID))=="S"){
        $StockBalanceInloose = getbalanceBYDATE(trim($StockID),$Location,$date,'loosqty');
       
       $REsults=DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
       $STOREline ='<select name="location" required="required" onchange="ReloadForm(inventory.refresh)"><option></option>';
       while($rows=DB_fetch_array($REsults)){
          $CODE = trim($rows['code']);
          $STOREline .= sprintf('<option value="%s" %s >%s</option>',$CODE,
          (($Location==$CODE)?'selected="selected"':''),$rows['Storename']);
       }
        $STOREline .= '</select>';
     }else{
         
        $StockBalanceInloose = getTankbalanceBYDATE($Location,trim($StockID),$date);
        
        $ResultIndex=DB_query(sprintf("select tankname from `ProductionUnit` where itemcode='%s'",trim($StockID)), $db);
         $STOREline ='<select name="location" required="required" onchange="ReloadForm(inventory.refresh)"><option></option>';
         while($value=DB_fetch_array($ResultIndex)){
               $CODE = trim($value['tankname']);
               $STOREline .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),
               (($Location==$CODE)?'selected="selected"':''),$value['tankname']);
           }
        $STOREline .= '</select>';
   }
         
       $stockmasterline ='<select name="StockID" onchange="ReloadForm(inventory.refresh)">';
       foreach ($stockmaster as $key => $rows) {
          $itemcode=trim($rows['itemcode']);
          $stockmasterline .= sprintf('<option value="%s" %s>%s</option>',
          $itemcode,(($StockID==$itemcode)?'selected="selected"':''),$rows['descrip']);
       }
        $stockmasterline .= '</select>';
   
    $_POST['DocNo'] = GetTempNextNo(17, $db);
    
    echo '<a href="' . $RootPath . '/StockAdjustments.php">List all</a>'
            . '<p class="page_title_text">'. $DESCRIPTION. '</p>';
    echo '<input type="hidden" name="Selectedlocation" value="' .$Location .'"/>'
       . '<input type="hidden" name="StockBalanceInloose" value="' .$StockBalanceInloose .'"/>';
    echo '<table class="table-bordered">'
      . '<tr><td>Document No</td><td><input type="text" name="DocNo" value="'.$_POST['DocNo'].'" size="10" readonly="readonly"/></td></tr>'
      .'<tr><td>Stock Item</td><td>';
    echo  $stockmasterline;
    echo '</td></tr>';
    
    echo '<tr><td>Date</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
    echo '<tr><td>Average Cost in loose</td><td><input type="text" name="averagecost" class="number" maxlength="10" size="10" value="'.$AVERAGESTOCK.'"/></td></tr>';
    echo '<tr><td>Adjustment description</td><td><input type="text" name="Memo"  maxlength="50" size="50" /></td></tr>';
    echo '<tr><td>Store</td><td>';
    echo  $STOREline;
    echo '</td></tr>';
    echo '<tr><td class="number">Adjust To Quantity</td>'
    . '<td><input type="text" name="stockbalance" class="number" maxlength="10" size="10"/> '
            . 'Current Balance: '.(($StockBalanceInloose<0)?'NEGATIVE '.$StockBalanceInloose:$StockBalanceInloose) .'</td></tr>';
    echo '<tr><td colspan="3"><input type="submit" name="refresh" value="Refresh"/>'
    . '<input type="submit" name="saveadjustment" value="Save Adjustment"/>'
    . '<input type="submit" name="savecost" value="Save Cost Only"/></td></tr></table>';

}else{
    
   echo '<table class="table-bordered">'
        . '<thead><TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Last LPO Cost</th>'
        . '<th>Posting Group</th>';
        foreach ($StoreArray as $store){
            echo '<th>'.$store['Storename'].'</th>';
        }
echo '<th>STOCK VALUE</th>'
        . '</tr></thead><tbody>';
        
       $k=0;
       foreach ($stockmaster as $rows){
            $cost = getlaststco(trim($rows['itemcode']));
            if($cost>0){
              $value="Update stockmaster set `averagestock`='".$cost."' where itemcode='".trim($rows['itemcode'])."'";
               DB_query($value,$db);
            }
            
           echo sprintf('<tr><td><a href="%s?StockID=%s">%s</a></td>',  $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td>'.$rows['descrip'].'</td>';
           echo '<td>'.($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td>'. number_format($rows['averagestock'],2).'</td>';
           echo '<td>'.trim($rows['postinggroup']).'</td>';
           $Value=0;
            foreach ($StoreArray as $store){
                $StockBalanceInloose =(int) getbalance(trim($rows['itemcode']),$store['code']);
                $Value += $StockBalanceInloose;
                echo '<td>'. number_format($StockBalanceInloose) .'</td>';
            }
             echo '<td>'.number_format($Value*$rows['averagestock'],0).'</td>';
       }     
       echo '</tr></table>';
       
       
}

echo '</div></form>' ;
include('includes/footer.inc');

Function IsTankOrStore($ItemCode){
   global $db;
        $sql=sprintf("select count(*) from `ProductionUnit` where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
        
       if($Stores[0]==0){
          $tankStore="S";
       }else{
             $ResultIndex=DB_query(sprintf("select tankname from `ProductionUnit` where itemcode='%s'",$ItemCode), $db);
             $Tanks = DB_fetch_row($ResultIndex);
             $tankStore=$Tanks[0];
       }
       return $tankStore;
}
    

Function getlaststco($code){
  global $db;
  
    $Slaqry ="SELECT 
        `PurchaseLine`.`UnitPrice`*(`PurchaseLine`.`vatrate`+100)/100,
        `PurchaseLine`.`UOM`,
        `PurchaseLine`.`partperunit`
FROM  PurchaseLine Where `PurchaseLine`.`code` ='".$code."' and `PurchaseLine`.`documenttype`='18' 
   order by `PurchaseLine`.`docdate` desc limit 1";
 $ResultIndex = DB_query($Slaqry,$db);
        $Stores = DB_fetch_row($ResultIndex);
        if($Stores[1]=='fulqty'){
           $AVCOST = $Stores[0]/$Stores[2];
        }else{
           $AVCOST =$Stores[0];
        }
        
  return  $AVCOST;
}
    

function getbalanceBYDATE($stcode,$location,$DATE,$UOM=''){
        global $db;
     
      
      $sql="SELECT IFNULL(sum(fulqty * partperunit),0) as fulqty,IFNULL(sum(loosqty),0) as loosqty  from stockledger 
            where stockledger.itemcode='".$stcode."' and `date`<='".$DATE."' and stockledger.store='".$location."'"; 
            $ResultIndex=DB_query($sql,$db);
           while($rows = DB_fetch_array($ResultIndex)){
               $StockBalance[$stcode]=array('fulqty'=>  $rows['fulqty'],'loosqty'=> $rows['loosqty']);
           }
     
        $value = $StockBalance[$stcode];
        $fullqty = $value['fulqty'] + $value['loosqty'];
       
       return $fullqty;
    }
    
     
   Function getTankbalanceBYDATE($tank,$itemcode,$DATE){
         global $db;
         $rowsunits="";
     
        $sql=sprintf("select sum(`units`) as units 
        from `tanktrans`  where `date`<='%s' AND `tankname`='%s'",$DATE,$tank);
        $ResultIndex = DB_query($sql,$db);
         $dbrows= DB_fetch_row($ResultIndex);
         $rowsunits=$dbrows[0];

              
       return  $rowsunits;
     }
  
?>
