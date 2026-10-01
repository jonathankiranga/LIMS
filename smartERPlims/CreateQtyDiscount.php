<?php
include('includes/session.inc');
$Title = _('Inventory Categories/Classification');
include('includes/header.inc');
/*
SELECT `Rate`,`QTY` FROM `Discouts`
 *  */
if(isset($_GET['code'])){
        $sql="SELECT `Rate`,`QTY` FROM `Discouts`  where `rowid`='".$_GET['code']."'";
    
        $results=DB_query($sql,$db);
        $rowse=DB_fetch_row($results);
        $_POST['categoryid'] = $rowse[0];
        $_POST['categorydescription'] = $rowse[1];
}

    if(isset($_POST['save'])){
       DB_query(sprintf("Insert into `Discouts` (`Rate`,`QTY`) values ('%s','%s')",
                $_POST['categoryid'],$_POST['categorydescription']), $db);
        unset($_POST);
    }

   if(isset($_POST['edit'])){
        DB_query(sprintf("update `Discouts` set `Rate`='%s' ,`QTY`='%s' "
                . " where `rowid`='%s'",$_POST['categoryid'],$_POST['categorydescription'],$_POST['editcode']), $db);
       unset($_POST);
    }
    
    if(isset($_POST['delete'])){
        DB_query("Delete from `Discouts` where `rowid`='".$_POST['editcode']."'", $db);
        unset($_POST);
    }
 
echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Trade Discounts') . '" alt="" />' . ' ' . _('Trade Discounts') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_GET['code'])){
    echo '<input type="hidden" name="editcode" value="' . $_GET['code'] . '" />';
}

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top"><table class="table table-bordered"><caption>General</caption>';
echo '<tr><td>Discount Rate(eg 0.5 for 50%)</td><td><input type="text" maxlength="6" class="number" size="5" name="categoryid" value="'.$_POST['categoryid'].'"  required="required"/></td></tr>';
echo '<tr><td>Discount for how many Units</td><td><input type="text" class="number" size="20" name="categorydescription" value="'.$_POST['categorydescription'].'"  required="required"/></td></tr>';
echo '<tr>';

if(isset($_GET['code'])){
    echo '<td colspan="2"><input type="submit" name="edit" value="'._('Edit Discount').'"/>';
    echo '<input type="submit" name="delete" value="'._('Delete Discount').'"/></td>';
}else{
    echo '<td colspan="2"><input type="submit" name="save" value="'._('Add New Discount Rate').'"/>';
    echo '</td>';
}
echo '</tr>';
echo '</table></td></tr></table>';
echo '</div></form>' ;

$sql="SELECT `rowid`,`Rate`,`QTY` FROM `Discouts`  ";
$results=DB_query($sql,$db);

echo '<br /><table class="table table-bordered"><tr><th>Discount Rate</th><th>Discount Category(Units)</th></tr>';

while($rows=DB_fetch_array($results)){
echo sprintf('<tr><td><a href="%s?code=%s">%f</a></td><td>%s</td></tr>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') ,$rows['rowid'],$rows['Rate'],$rows['QTY']);
}
echo '</table>';


include('includes/footer.inc');
?>