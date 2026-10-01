<?php
include('includes/session.inc');
$Title = _('VALUE ADDED TAX Categories');
include('includes/header.inc');

if(isset($_GET['code'])){
    $sql="SELECT `vatc`,`vat`,`vatdecrip`,`taxcatid` FROM `vatcategory`  where `taxcatid`='".$_GET['code']."'";
    
        $results=DB_query($sql,$db);
        $rowse=DB_fetch_row($results);
        $_POST['vatc'] = $rowse[0];
        $_POST['vat'] = $rowse[1];
        $_POST['vatdecrip'] = $rowse[2];
}

if(isset($_POST['save'])){
    DB_query(sprintf("Insert into `vatcategory` (`vatc`,`vat`,`vatdecrip`) values ('%s',%f,'%s')",
            $_POST['vatc'],$_POST['vat'],$_POST['vatdecrip']), $db);
}

if(isset($_POST['edit'])){
    if($_POST['edit']=='Edit'){
        DB_query(sprintf("update "
                . "vatcategory set `vatc`='%s'"
                . ",`vat`=%f"
                . ",`vatdecrip`='%s' "
                . "where `taxcatid`='%s' ",
               $_POST['vatc'],$_POST['vat'],$_POST['vatdecrip'],$_POST['editcode']), $db);
    }
    
    if($_POST['edit']=='Delete'){
        $sql="Select * from inventorypostinggroup where `vatcategory`='".$_POST['vatc']."'";
        $Result=DB_query($sql,$db);
        if(DB_num_rows($Result)==0){
            DB_query("Delete from vatcategory where taxcatid='".$_POST['editcode']."'", $db);
        }else{
            prnMsg('This category can not be deleted because its in use');
        }
        
    }
    
}

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('VALUE ADDED TAX') . '" alt="" />' . ' ' . _('VALUE ADDED TAX') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_GET['code'])){
    echo '<input type="hidden" name="editcode" value="' . $_GET['code'] . '" />';
}

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
    . '<table class="table table-bordered"><caption>General</caption>';
echo '<tr><td>Code</td>'
   . '<td><input type="text" maxlength="6" size="6" name="vatc" value="'.$_POST['vatc'].'"/></td>'
   . '</tr>';
echo '<tr><td>Rate</td>'
   . '<td><input type="text" maxlength="2" size="3" name="vat" value="'.$_POST['vat'].'"/></td></tr>';
echo '<tr><td>Category</td>'
   . '<td><input type="text" maxlength="20" size="20" name="vatdecrip" value="'.$_POST['vatdecrip'].'"/></td></tr>';
echo '</table></td></tr></table>';

if(isset($_GET['code'])){
    echo '<div><input type="submit" name="edit" value="'._('Edit').'"/>';
    echo '<input type="submit" name="edit" value="'._('Delete').'"/></div>';
}else{
    echo '<div><input type="submit" name="save" value="'._('Add New').'"/>';
    echo '</div>';
}

echo '</div></form>' ;

$sql="SELECT `vatc`,`vat`,`vatdecrip`,`taxcatid` FROM `vatcategory`";
$results=DB_query($sql,$db);
echo '<br /><table class="table table-bordered"><tr><th>Code</th><th>Category</th><th>Description</th></tr>';

while($rows=DB_fetch_array($results)){
echo sprintf('<tr><td><a href="%s?code=%s">%s</a></td><td>%s</td><td>%s</td></tr>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') ,
        $rows['taxcatid'],$rows['vatc'],$rows['vat'],$rows['vatdecrip']);
}
echo '</table>';


include('includes/footer.inc');
?>