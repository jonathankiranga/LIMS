<?php
include('includes/session.inc');
$Title = _('Stock Adjustments');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('transactions/stockbalance.inc');   

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$results = DB_query('Select `itemcode`,`descrip`,`container`,`averagestock`
FROM `stockmaster` where isstock_5=1 or isstock_4=1 order by `descrip` asc', $db);
 while($rows=DB_fetch_array($results)){
     $stockmaster[trim($rows['itemcode'])]=$rows;
 }
   
if(isset($_GET['StockID'])){
  $StockID =trim($_GET['StockID']);
}elseif(isset($_POST['StockID'])){
  $StockID =trim($_POST['StockID']);
}  

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Stock Adjustments') . '" alt="" />' . ' ' . _('Stock Adjustments') . '</p>';
if(isset($_POST['saveadjustment'])){
     $SQL=array();
     $SQL[]="Delete from Containers where itemcode='".trim($_POST['StockID'])."'";
   
    foreach ($_POST['count'] as $itemcode => $qty){
        if($qty>0){
            $SQL[]= sprintf("INSERT INTO `Containers` (`itemcode` ,`ContainerCode` ,`ContainerQTY`)
            VALUES ('%s' ,'%s','%s')",$StockID,$itemcode,$qty);
        }
    }
    
    foreach ($SQL as $value) {
        DB_query($value,$db);
    }
    
}

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />'
   . '<input type="hidden" name="StockID" value="' . $StockID  . '" />';

if(isset($StockID)){
       
        $DESCRIPTION = $stockmaster[$StockID]['descrip'];
        $DRUM = trim($stockmaster[$StockID]['container']);
        
        $results = DB_query(sprintf("Select `itemcode`,`descrip`,`averagestock`  FROM `stockmaster`
         where isstock_6=1 and `itemcode` not in ('%s') order by `descrip` asc",$DRUM), $db);
         while($rows=DB_fetch_array($results)){
             $containerarray[]=$rows;
         }
 
       $stockmasterline ='<div>';
       $stockmasterline .= '<table>';
       $stockmasterline .= '<tr><td><label>Select The Seal</label></td><td><label>Enter <br>The Number of Seals<br> used in a package</label></td></tr>';
   
       foreach ($containerarray as $key => $rows) {
          $itemcode = trim($rows['itemcode']);
          $descrip  = trim($rows['descrip']);
          $checked  = getifsaved($StockID,$itemcode);
          
          $stockmasterline .= sprintf('<tr><td><input type="checkbox" id="%s" name="container[%s]" value="%s"  %s>'
                  . '<label for="%s">%s</label></td><td><input type="text" name="count[%s]" size="5" value="%s" maxlenth="1" /></td></tr>',
                  $itemcode, $itemcode,$descrip,(($checked==0)?'':'checked'),$itemcode,$descrip,$itemcode,$checked);
       }
       $stockmasterline .= '</table>';
    
     
    echo '<p class="page_title_text"><a href="' . $RootPath . '/StockContainer.php">List all</a></p>'
            . '<p class="page_title_text"><h3>Select lids or seals for '. $DESCRIPTION. '</h3></p>';
   
    echo  $stockmasterline;
    
    echo '<table class="table-bordered">'
    . '<tr><td><input type="submit" name="refresh" value="Refresh"/>'
    . '</td><td><input type="submit" name="saveadjustment" value="Add Selection to container list"/></td></tr>'
            . '</table></div>';

}else{
    
   echo '<table class="table-bordered">'
        . '<thead><TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Last LPO Cost</th>'
        . '<th>STOCK TYPE</th>';
    echo  '</tr></thead><tbody>';
        
       $k=0;
       foreach ($stockmaster as $rows){
           echo sprintf('<tr><td><a href="%s?StockID=%s">%s</a></td>', 
                   $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td>'.$rows['descrip'].'</td>';
           echo '<td>'.($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td>'. number_format($rows['averagestock'],2).'</td>';
           echo '<td>'.$mbflag[$rows['isstock']].'</td></tr>';
           
       }     
       echo '</table>';
       
       
}

echo '</div></form>' ;
include('includes/footer.inc');

function getifsaved($Parent,$cont){
    global $db;
    
    $SQL ="select ContainerQTY from Containers where itemcode='".trim($Parent)."' 
            and ContainerCode='".trim($cont)."' " ;
    $results=DB_query($SQL ,$db);
    $rows=DB_fetch_row($results);
   return $rows[0];
}

?>