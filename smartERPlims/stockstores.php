<?php
include('includes/session.inc');
$Title = _('Stores');
include('includes/header.inc');

if(isset($_GET['code'])){
    $sql="SELECT `code`,`Storename` FROM `Stores` where `code`='".$_GET['code']."'";
    
        $results=DB_query($sql,$db);
        $rowse=DB_fetch_row($results);
        $_POST['categoryid'] = $rowse[0];
        $_POST['categorydescription'] = $rowse[1];
}

if(isset($_POST['save'])){
    DB_query(sprintf("Insert into `Stores` (`code`,`Storename`) values ('%s','%s')",
            $_POST['categoryid'],$_POST['categorydescription']), $db);
}

if(isset($_POST['edit'])){
    if($_POST['edit']=='Edit'){
        DB_query(sprintf("update `Stores` set `Storename`='%s' where `code`='%s' ",
                $_POST['categorydescription'],$_POST['editcode']), $db);
    }
    
    if($_POST['edit']=='Delete'){
        $sql="Select * from `stockledger` where `store`='".$_POST['editcode']."'";
        $Result=DB_query($sql,$db);
        if(DB_num_rows($Result)==0){
            DB_query("Delete from `Stores` where `code`='".$_POST['editcode']."'", $db);
        }else{
            prnMsg('This Store can not be deleted because its in use');
        }
        
    }
    
}

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Inventory Categories') . '" alt="" />' . ' ' . _('Inventory Categories') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_GET['code'])){
    echo '<input type="hidden" name="editcode" value="' . $_GET['code'] . '" />';
}

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top"><table class="table table-bordered"><caption>General</caption>';
echo '<tr><td>Code</td><td><input type="text" maxlength="6" size="6" name="categoryid" value="'.$_POST['categoryid'].'"/></td></tr>';
echo '<tr><td>Store Name</td><td><input type="text" maxlength="20" size="20" name="categorydescription" value="'.$_POST['categorydescription'].'"/></td></tr>';
echo '</table></td></tr></table>';

if(isset($_GET['code'])){
    echo '<div><input type="submit" name="edit" value="'._('Edit').'"/>';
    echo '<input type="submit" name="edit" value="'._('Delete').'"/></div>';
}else{
    echo '<div><input type="submit" name="save" value="'._('Add New').'"/>';
    echo '</div>';
}

echo '</div></form>' ;

$sql="SELECT `code`,`Storename` FROM `Stores`";
$results=DB_query($sql,$db);

echo '<br /><table class="table table-bordered"><tr><th>Code</th><th>STORE</th></tr>';

while($rows=DB_fetch_array($results)){
echo sprintf('<tr><td><a href="%s?code=%s">%s</a></td><td>%s</td></tr>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') ,
        $rows['code'],$rows['code'],$rows['Storename']);
}
echo '</table>';


include('includes/footer.inc');
?>