<?php
include('includes/session.inc');
$Title = _('Production Lab Results');
include('includes/header.inc');

if(isset($_POST['Batchno'])){
   $BatchNo = $_POST['Batchno'];
} elseif(isset($_GET['Batchno'])){
   $BatchNo = $_GET['Batchno'];
}

if(isset($_POST['update'])){
 $SQL=sprintf("update `ProductionMaster` "
                 . " set `labparam1`='%s',"
                 . "`labparam2`='%s',"
                 . "`labparam3`='%s',"
                 . "`labparam4`='%s',"
                 . "`labparam5`='%s'  where `Batchno`='%s' ",
            $_POST['P_one'],$_POST['P_two'],$_POST['P_three'],
            $_POST['P_four'],$_POST['P_five'],$_POST['Batchno']);
 
         DB_query($SQL,$db);
}

echo '<p class="page_title_text">'
   . '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" '
   . ' title="' . _('Production Lab Results') . '" alt="" />' . ' ' . $Title. (isset($BatchNo)?'  For '.$BatchNo:'' ).'</p>';
  
echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container"><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>'
   . '<input type="hidden" name="Batchno" value="'. $BatchNo .'"/>';

if(isset($BatchNo)){
         
    $sql="select "
    . "`Batchno`,"
    . "`date`,"
    . "`userid`,
       `labparam1` as Parameter_1,
       `labparam2` as Parameter_2,
       `labparam3` as Parameter_3,
       `labparam4` as Parameter_4,
       `labparam5` as Parameter_5 "
    . " from `ProductionMaster` "
    . " where `Batchno`='".$BatchNo."' ";
    $ResultIndex = DB_query($sql,$db);
    $rowlab = DB_fetch_row($ResultIndex);
    
    echo '<table class="table table-bordered"><tr><th></th><th></th><th></th><th></th></tr>';
    echo '<tr><td>Batch No</td><td>'.$rowlab[0].'</td></tr>';
    echo '<tr><td>Date Posted</td><td>'.ConvertSQLDate($rowlab[1]).'</td></tr>';
    echo '<tr><td>Posted By</td><td>'.$rowlab[2].'</td></tr>';
    echo '<tr><td>First Test</td><td><input type="text" name="P_one" value="'.$rowlab[3].'"  maxlength="50" /></td>';
    echo '<td>Second Test</td><td><input type="text" name="P_two" value="'.$rowlab[4].'"   maxlength="50" /></td></tr>';
    echo '<tr><td>Third Test</td><td><input type="text" name="P_three" value="'.$rowlab[5].'"   maxlength="50" /></td>';
    echo '<td>Fourth test</td><td><input type="text" name="P_four" value="'.$rowlab[6].'"   maxlength="50" /></td></tr>';
    echo '<tr><td>Fiveth test</td><td><input type="text" name="P_five" value="'.$rowlab[7].'"   maxlength="50" /></td></tr>';
    
     echo '<tr><td></td><td>Update Test Results</td>'
    . '<td><input type="submit" name="update" value="Post Test Results"/></td></tr>';
    echo '<tr><th>Production</th><th>Summary</th><th>For Batch No '.$BatchNo.'</th></tr>';
    
   $sql="SELECT 
      `ProdcutionMasterLine`.`itemcode`,
      `stockmaster`.`descrip` ,
      unit.descrip as fqty, 
      unitL.descrip as loseqty ,
      `ProdcutionMasterLine`.`UOM`,
      `ProdcutionMasterLine`.`qty`
  FROM `ProdcutionMasterLine`
  join stockmaster on ProdcutionMasterLine.itemcode=stockmaster.itemcode
  left join unit on stockmaster.units=unit.code
  left join unit unitL on `stockmaster`.`units`=unitL.code
  where `ProdcutionMasterLine`.`Batchno`='".$BatchNo."'" ;
    
   $ResultIndex = DB_query($sql,$db);
     while($row= DB_fetch_array($ResultIndex)){
            
     echo '<tr><td>'.$row['descrip'].'</td>'
        . '<td>'.($row['UOM']=='fulqty'?$row['fqty']:$row['loseqty']).'</td>'
        . '<td>'.$row['qty'].'</td></tr>';
     
    }
    
    echo '</table>';

    
    
} else {
       
    $sql="SELECT 
        `Batchno`,`date`,`userid`,
       `labparam1` as Parameter_1,
       `labparam2` as Parameter_2,
       `labparam3` as Parameter_3,
       `labparam4` as Parameter_4,
       `labparam5` as Parameter_5 
       from `ProductionMaster` order by `date` desc limit 100 ";
    $ResultIndex = DB_query($sql,$db);
    
    echo '<table class="table table-bordered"><tr>'
       . '<th>Batch No</th>'
       . '<th>Date</th>'
       . '<th>Created by</th>'
       . '<th>Test 1</th>' . 
            '<th>Test 2</th>' .
            '<th>Test 3</th>' . 
            '<th>Test 4</th>' . 
            '<th>Test 5</th>'
       . '</tr>';
    
    $thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    while($row = DB_fetch_array($ResultIndex)){
           echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td>'
                   . '<td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                   '<a href="'.$thispage.'?Batchno='.$row['Batchno'].'">'. $row['Batchno'] .'</a>',
                ConvertSQLDate($row['date']), 
                $row['userid'],
                 $row['Parameter_1'],
                 $row['Parameter_2'],
                 $row['Parameter_3'],
                 $row['Parameter_4'],
                 $row['Parameter_5']  );
    }
    
    echo '</table>';
    
}

echo '</div></form>';

include('includes/footer.inc');
    
?>
