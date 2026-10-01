<?php
include('includes/session.inc');
$Title = _('Merge Stock Codes');
include('includes/header.inc');
   
if(isset($_POST['save'])){
    $SQLARRAY[]="Update `stockledger` set `itemcode`='".$_POST['Changeto']."' where `itemcode`='".$_POST['ChangeFrom']."'";
    $SQLARRAY[]="Update `tanktrans` set `itemcode`='".$_POST['Changeto']."' where `itemcode`='".$_POST['ChangeFrom']."'";
    $SQLARRAY[]="Update `PurchaseLine` set `code`='".$_POST['Changeto']."' where `code` ='".$_POST['ChangeFrom']."'";
    $SQLARRAY[]="Update `SalesLine` set `code`='".$_POST['Changeto']."' where `code`='".$_POST['ChangeFrom']."'";
    $SQLARRAY[]="Update `ProductionMaster` set `itemcode`='".$_POST['Changeto']."' where `itemcode`='".$_POST['ChangeFrom']."'";
    $SQLARRAY[]="Update `ProductionUnit` set `itemcode`='".$_POST['Changeto']."' where `itemcode`='".$_POST['ChangeFrom']."'";
    $SQLARRAY[]="Update `ProdcutionMasterLine` set `itemcode`='".$_POST['Changeto']."' where `itemcode`='".$_POST['ChangeFrom']."'";
 
     $rowcount=0;
    DB_Txn_Begin($db);
    foreach ($SQLARRAY as $SQL) {
      $ResultIndex=DB_query($SQL, $db);  
      $rowcount += DB_num_rows($ResultIndex);
    }
    
    if(DB_error_no($db)==0){
       
        DB_Txn_Commit($db);
        prnMsg('You have Merged Stock Item  in '.$rowcount.' places');
    }else{
        DB_Txn_Rollback($db);
    }
    
}

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Delete Entry Window') . '" alt="" />' . ' ' . _('Delete Entry Window') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
   . '<table class="table table-bordered"><caption>Enter System Stock Code To Change From</caption>';
echo '<tr><td>Change from</td>'
       . '<td><select required="required" name="ChangeFrom">';
    $SQL = "SELECT itemcode,descrip from stockmaster  where `inactive`=1 order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($roe = DB_fetch_array($ResultIndex)){
        $selected=(trim($roe['itemcode'])==trim($_POST['ChangeFrom']))?'selected':'';
        echo sprintf('<option value="%s" %s>%s</option>',trim($roe['itemcode']),$selected,trim($roe['itemcode']).' - '.$roe['descrip']); 
    }
    
echo '</select></td></tr>';
echo '<tr><td>Merge Stock With Change to</td>'
       . '<td><select required="required" name="Changeto">';
    $SQL = "SELECT itemcode,descrip from stockmaster where `inactive`=0 order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($roe = DB_fetch_array($ResultIndex)){
        $selected=(trim($roe['itemcode'])==trim($_POST['Changeto']))?'selected':'';
        echo sprintf('<option value="%s" %s>%s</option>',trim($roe['itemcode']),$selected,trim($roe['itemcode']).' - '.$roe['descrip']); 
    }
    
echo '</select></td></tr></table>';
echo '<div><input type="submit" name="save" value="'._('Merge Stock').'"/>';
echo '</div>';
echo '</div></form>' ;


include('includes/footer.inc');
?>