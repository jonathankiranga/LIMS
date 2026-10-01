<?php
include('includes/session.inc');
$Title = _('Tanks and storage Units');
include('includes/header.inc');

if(isset($_POST['submit'])){
    if(mb_strlen($_POST['CapacityUOM'])==0){
        prnMsg('Please setup UOM for sub units','warn');
    }
}


if(mb_strlen($_POST['CapacityUOM'])>0){
    if(!isset($_POST['editline'])){
      $sql=sprintf("INSERT INTO `ProductionUnit`
           (`itemcode`
           ,`capacity`
           ,`tankname`
           ,`UOM`
           ,`CapacityUOM`
           ,status)
      VALUES
           ('%s'
           ,%f
           ,'%s'
           ,'loosqty'
           ,'%s'
           ,'%s')"
            ,$_POST['itemcode']
            ,$_POST['capacity']
            ,$_POST['tankname']
            ,$_POST['CapacityUOM']
            ,$_POST['status']);
    }else{
        $sql=sprintf("Update `ProductionUnit` set `itemcode`='%s'
                            ,`capacity`=%f ,`tankname`='%s' ,`UOM`='loosqty' 
                            ,`CapacityUOM`='%s' ,status='%s' where `tankname`='%s'"
                            ,$_POST['itemcode']
                            ,$_POST['capacity']
                            ,$_POST['tankname']
                            ,$_POST['CapacityUOM']
                            ,$_POST['status']
                            ,$_POST['tankname']);
    }
    
    DB_query($sql,$db);
    unset($_POST);
    

}

echo '<p class="page_title_text">'. '<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . _('Tanks Defination') .'" alt="" />' .' ' . _('Tanks Defination') . '</p>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_GET['edit'])){
    echo '<input type="hidden" name="editline" value="' .$_GET['edit']. '" />';

    $results = DB_query("SELECT `itemcode`,`capacity`,`tankname`,`CapacityUOM`,`status` "
            . "FROM `ProductionUnit` where `tankname`=".$_GET['edit'],$db);
    
    $rows=DB_fetch_row($results);
    $_POST['itemcode']=$rows[0];
    $_POST['capacity']=$rows[1];
    $_POST['tankname']=$rows[2];
    $_POST['CapacityUOM']=$rows[3];
    $_POST['status']=$rows[4];
}

echo '<table class="table">';
echo '<tr><td>NAME OF TANK</td><td><input type="text" name="tankname" required="required" size="30" maxlength="30" value="'.$_POST['tankname'].'"/></td></tr>';
echo '<tr><td>PRODUCT For(in BOM)</td><td><select name="itemcode" required="required" >'; 
echo Getstocknames($_POST['itemcode']);
echo '</td></tr>';

$uomselected = selectUOM($_POST['itemcode'],'loosqty');
echo '<tr><td>UOM</td><td><input type="text" name="CapacityUOM" readonly="readonly" value="'.$uomselected.'"/>'; 
echo '<tr><td>TANK CAPACITY</td><td><input type="text" class="number" name="capacity"'
.((mb_strlen($uomselected)>0)? ' required="required" ':''). 'size="10" maxlength="10" value="'.$_POST['capacity'].'"/></td></tr>';
echo '<tr><td>TANK STATUS</td><td><select name="status"><option value="1" '.($_POST["status"]==1?'selected="selected"':'').'>Active</option>'
. '<option value="0" '.($_POST["status"]==0?'selected="selected"':'').'>Inactive</option></select></td></tr>';

echo '</table>';
echo '<div><input type="submit" name="submit" value="Continue"/></div>';
echo '</div></form><P>';

$sql="select `itemcode`
            ,`capacity`
            ,`tankname`
            ,`UOM`
            ,`CapacityUOM`
            ,`status`
            from `ProductionUnit`";

$URL=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
  
echo '<div class="container">'
. '<table class="table"><tr><th>For Product</th><th>Tank <BR />Name</th><th>Tank<BR /> Capacity</th>'
. '<th>Status</th></tr>';

$ResultIndex=DB_query($sql,$db);
while($row=DB_fetch_array($ResultIndex)){
    echo sprintf('<tr><td><a href="%s?edit=%s">%s</a></td>',$URL,$row['tankname'],Findstockdetails($row['itemcode']));
    echo '<td>'.$row['tankname'].'</td>';
    echo '<td>'.number_format($row['capacity'],0).'</td>';
    echo '<td>'.(($row['status']==true)?'ACTIVE':'INACTIVE').'</td></tr>';
    
  
}

echo '</table></div>';

include('includes/footer.inc');

function Getstocknames($select='empty'){
    global $db;
    $option='';

    $sql="Select itemcode,descrip from stockmaster where isstock_4=1";
    $ResultIndex=DB_query($sql,$db);
    while($row=DB_fetch_array($ResultIndex)){
        $option .= sprintf('<option value="%s" %s>%s</option>',$row['itemcode'],
                (($select==$row['itemcode'])?'selected="selected"':""), trim($row['descrip']));
    }
      return $option;
}
        
function selectUOM($stockcode,$OUM){
    Global $db;

    $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
    FROM `stockmaster` left join `unit` f on `stockmaster`.`units`=f.code 
    left join `unit` l on `stockmaster`.`units`=l.code 
    where itemcode= '".$stockcode."'", $db);
    $rows=DB_fetch_row($REsults);

    return ($OUM=='fulqty')?$rows[0]:$rows[1];
}   
        
function Findstockdetails($stockcode){
    global $db;

    $sql ="SELECT `stockmaster`.`itemcode`, `stockmaster`.`descrip` as `stockname`
    FROM `stockmaster`  where  itemcode ='".$stockcode."'";
    
   $ResultIndex=DB_query( $sql, $db);
   $rows = DB_fetch_row($ResultIndex);

   return $rows[1];
}   
        
?>