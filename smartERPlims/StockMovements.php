<?php
include('includes/session.inc');
$Title = _('Stock Movement');
include('includes/header.inc');
include('production/poscart.inc');

$thispage=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$POSclass = new NewAssemble();

 if(!isset($_POST['date'])){ 
    $ResultIndex = DB_query('Select NOW()-30 as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $date = $rowdate[0];
}else{
    $date = FormatDateForSQL($_POST['date']);
}

if(isset($_GET['StockID'])){
    $StockID=$_GET['StockID'];
}

if(isset($_POST['ProdStore'])){
   $Storecode=trim($_POST['ProdStore']);
}

if(isset($_POST['StockID'])){
    $StockID=$_POST['StockID'];
}


echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Stock Movement') . '" alt="" />' . ' ' . _('Stock Movement') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="inventory">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="StockID" value="' . $StockID . '" />';
  
if(isset($StockID)){
  $SQL=sprintf("Select 
            `stockmaster`.`itemcode`,
            `stockmaster`.`descrip`
            FROM `stockmaster`  
             where `stockmaster`.itemcode = '%s'",$StockID);
    
    $results=DB_query($SQL,$db);
    $rows = DB_fetch_row($results);
    $string =filter_var($rows[1],FILTER_SANITIZE_SPECIAL_CHARS);
    
    echo '<table class="table-bordered"><caption><h3>'. $string .'</h3></caption><tr><td>Date</td><td>'
    . '<input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" '
    . 'maxlength="10" readonly="readonly" value="' .$_POST['date']. '" '
    . 'onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/>'
    . '<select name="ProdStore" onchange="ReloadForm(inventory.Refresh)">'.$POSclass->ToStoreTankForItem($StockID).'</select></td></tr></table>';
    
    echo '<table class="table-bordered" id="testTable">'
            . '<thead>'
             . '<tr>'
            . '<th>Date</th>'
            . '<th>Document Type</th>'
             . '<th>Document No</th>'
            . '<th>Stock In</th>'
            . '<th>Stock Out</th>'
            . '<th>Stock Balance</th>'
            . '</tr></thead>';
   
  
      if(IsTankOrStore(trim($StockID))=="S"){
            $sql="Select sum(fulqty * `stockledger`.`partperunit`)+IFNULL(sum(loosqty),0) as Total,
                    `lunit`.`descrip` 
                    from `stockledger` 
                    join `stockmaster` on `stockledger`.itemcode=`stockmaster`.itemcode 
                    left join `unit` lunit on stockmaster.`units`=lunit.`code` 
                    where `stockledger`.itemcode='".$StockID."' and `stockledger`.store='".$Storecode."' 
                    and  date<'". $date."'  group by `lunit`.`descrip`";
            $ResultIndex = DB_query($sql, $db);
            $row = DB_fetch_row($ResultIndex);
            $Balance=$row[0];
      }else{
             $sql="Select sum(`tanktrans`.units) as Total,
                    `lunit`.`descrip` 
                    from `tanktrans` 
                    join `stockmaster` on `tanktrans`.itemcode=`stockmaster`.itemcode 
                    left join `unit` lunit on stockmaster.`units`=lunit.`code` 
                    where `tanktrans`.itemcode='".$StockID."'  and `tanktrans`.tankname='".$Storecode."' 
                    and  date<'". $date."'  group by `lunit`.`descrip`";
            $ResultIndex = DB_query($sql, $db);
            $row = DB_fetch_row($ResultIndex);
            $Balance=$row[0];
      }
     echo sprintf('<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td></td><td></td>'
                . '<td>%s</td>'
                . '</tr>',  $_POST['date'], 'Bal', 'Bal cfwd',  number_format($Balance,0).' '.$row['LOUM']
                
            );
     
       if(IsTankOrStore(trim($StockID))=="S"){
           $sql="Select
            date,
            invref,
            `systypes_1`.`typename`,
            `funit`.`descrip` as FUOM,
            fulqty,
            `lunit`.`descrip` as LOUM,
            loosqty,
            (fulqty * `stockledger`.`partperunit`)+IFNULL(loosqty,0) as Total
             from `stockledger` 
             join `systypes_1` on `systypes_1`.`typeid`=`stockledger`.`doctyp` 
             join `stockmaster` on `stockledger`.itemcode=`stockmaster`.itemcode 
             and (stockmaster.isstock_1=1 or stockmaster.isstock_2 =1 or stockmaster.isstock_4 =1 
             or stockmaster.isstock_5 =1 or stockmaster.isstock_6 =1)
             left join `unit` funit on stockmaster.`units`=funit.`code` 
             left join `unit` lunit on stockmaster.`subunits`=lunit.`code` 
             where (`stockledger`.itemcode='".$StockID."' 
             and `stockledger`.store='".$Storecode."') and date>='".$date."' order by date";
       } else {
   
           $sql="Select
            `tanktrans`.date,
            `tanktrans`.batchno as invref,
            `systypes_1`.`typename`,
            `funit`.`descrip` as FUOM,
            0 as fulqty,
            `lunit`.`descrip` as LOUM,
            `tanktrans`.units as loosqty,
            `tanktrans`.units as Total
             from `tanktrans` 
             join `systypes_1` on `systypes_1`.`typeid`=`tanktrans`.`doctype` 
             join `stockmaster` on `tanktrans`.itemcode=`stockmaster`.itemcode 
             and (stockmaster.isstock_1=1 or stockmaster.isstock_2 =1 or stockmaster.isstock_4 =1 
             or stockmaster.isstock_5 =1 or stockmaster.isstock_6 =1)
            left join `unit` funit on stockmaster.`units`=funit.`code` 
            left join `unit` lunit on stockmaster.`subunits`=lunit.`code` 
             where (`tanktrans`.itemcode='".$StockID."' 
              and `tanktrans`.tankname='".$Storecode."') and  date>='".$date."' order by date";
           
       }
       
    $ResultIndex = DB_query($sql, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $Balance+=$row['Total'];
        echo sprintf('<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '%s'
                . '<td>%s</td>'
                . '</tr>',
        ConvertSQLDate($row['date']),
                $row['typename'],
                $row['invref'],
                Formatdata($row), 
                number_format($Balance,0).' '.$row['FUOM']
                
            );
    }
    
    echo '</table>';
       
echo '<input type="submit" id="Refresh"  name="Refresh"  value="Refresh"/><input type="button" onclick="tableToExcel(\'testTable\', \'Stock Movement\')" value="Export to Excel">';

echo '<input type="submit"  value="Cancel"/>';

}else{
    
?>
    <table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - Stock Name</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
           
            
         </tbody>
    </table>
<?php
echo '<table class="register display" style="width:100%">'
        . '<thead><TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Package Size</th>'
        . '</tr></thead><tbody>';
      
       $results=DB_query("Select `itemcode`,`descrip`,`inactive`,`partperunit`,`averagestock`,`postinggroup`,`isstock` 
              FROM `stockmaster` where (stockmaster.isstock_1=1 or stockmaster.isstock_2 =1 or stockmaster.isstock_4 =1 
             or stockmaster.isstock_5 =1 or stockmaster.isstock_6 =1) order by `descrip` asc", $db);
       
       $k=0;
       while($rows=DB_fetch_array($results)){
           $isstock= trim($rows['isstock']);
           echo sprintf('<tr><td><a href="%s?StockID=%s">Click to view %s</a></td>', $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td>'. $rows['descrip'] .'</td>';
           echo '<td>'.($rows['inactive']==true?'YES':"NO") .'</td>';
           echo '<td>'. number_format($rows['partperunit'],0) .'</td>';
           echo '</tr>';
            
       }     
       
       echo '<tfoot><TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Package Size</th>'
        . '</tr></tfoot></table>';
}

echo '</div></form>' ;
include('includes/footer.inc');


Function Formatdata($row){
    if($row['fulqty']>0 or $row['loosqty'] > 0){
       $line='<td>'.(($row['fulqty']>0)?(trim(number_format($row['fulqty'],0)).' '.$row['FUOM']):'');
       $line .=(($row['loosqty'] >0)?  trim(number_format($row['loosqty'],0)).' '.$row['LOUM']:'')
               .'</td><td></td>';
    }else{
        
        $line= '<td></td><td>'.(($row['fulqty']<0)?( trim(number_format($row['fulqty']*-1)).' '.$row['FUOM']):'');
        $line .=(($row['loosqty']<0)?(trim(number_format($row['loosqty']*-1)).' '.$row['LOUM']):'').'</td>';
   }      
      
   return $line;
      
}


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
    