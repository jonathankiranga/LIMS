<?php

include('includes/session.inc');
$Title = _('GL Posting Groups');
include('includes/header.inc');

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' .$Title.'" alt="" />' .$Title. '</p>';


if(isset($_POST['submit'])){
    $errors=0;
    
    if(mb_strlen($_POST['code'])==0 and !isset($_POST['codeedit'])){
        prnMsg('You have not selected a code','warn');
        $errors++;
    }
    
    if(mb_strlen($_POST['vataccount'])==0){
        prnMsg('You have not selected a VAT account','warn');
        $errors++;
    }
    
    if($errors==0 and $_POST['submit']=='New Posting group'){
        $SQL="INSERT INTO `GLpostinggroup`
           (`code`
           ,`defaultgl_vat`
           ,`vatcategory`)
     VALUES
           ('".$_POST['code']."'
            ,'".$_POST['vataccount']."'
           ,'".$_POST['VATinclusive']."')";
        
        $ResultIndex=DB_query($SQL, $db);
        if(DB_num_rows($ResultIndex)>0){
            prnMsg('You have succeeded in creating a posting group');
        }
    }
    
    
    if($errors==0 and $_POST['submit']=='Edit Posting group'){
        $SQL="UPDATE `GLpostinggroup`
                SET `defaultgl_vat` = '".$_POST['vataccount']."'
                    ,`vatcategory` ='".$_POST['VATinclusive']."'
                WHERE `code` ='".$_POST['codeedit']."'";
        $ResultIndex=DB_query($SQL, $db);
        if(DB_num_rows($ResultIndex)>0){
            prnMsg('You have succeeded in editing a posting group');
        }
    }
    
      unset($_POST);
}


if(isset($_POST['delete'])){
    
   $result= DB_query("Select `postinggroup` from acct where `postinggroup`='".$_POST['codeedit']."'", $db);
   if(DB_num_rows($result)==0){
       DB_query("Delete from `GLpostinggroup` where code='". $_POST['codeedit'] . "'", $db);
   } else {
       prnMsg('This posting group is in use, so it cannot be deleted');
   }
    
}


if(isset($_GET['EDIT'])){
      $result=DB_query("SELECT `code`,`defaultgl_vat`,`vatcategory`
      FROM `GLpostinggroup`  where code='".$_GET['EDIT']."'",$db);
      
      $rows =DB_fetch_array($result);
      $_POST['code']=$rows['code'];
      $_POST['vataccount']=$rows['defaultgl_vat'];
      $_POST['VATinclusive']=$rows['vatcategory'];
      
}


echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';   
echo '<input type="hidden" name="codeedit" value="' . $_GET['EDIT'] . '" />';   
echo '<table class="table table-bordered"><tr>';

if(!isset($_GET['EDIT'])){
    echo '<td>Enter Code</td><td><input type="text" name="code" maxlength="10" required="required" /></td></tr>';
}


echo '<tr><td>VAT Account</td><td><select name="vataccount"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=0 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['vataccount']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    
 
    
    
echo '<tr><td>Select VAT Category</td><td><select name="VATinclusive"><option></option>';
    $result=DB_query("Select vatc,vat from vatcategory",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['VATinclusive']==trim($myrow['vatc'])){
                echo '<option selected="selected" value="'. $myrow['vatc'] .'">' . $myrow['vatc'] .'-'. $myrow['vat'] . '</option>';
        } else {
                echo '<option value="'. $myrow['vatc'] .'">' . $myrow['vatc'] .'-'. $myrow['vat'] . '</option>';
        }
    } //end while loop
    echo '</select></td></tr>';
   

     
echo '</table>';
echo '<div class="centre">';
        if(isset($_GET['EDIT'])){
            echo '<input type="submit" name="submit" value="Edit Posting group"/>';
            echo '<input type="submit" name="delete" value="Delete" onclick="return confirm(\''._('Are you sure you wish to Delete ?').'\');"/>';
        }else{
            echo '<input type="submit" name="submit" value="New Posting group"/>';
        }
echo '</div></form>';


echo '</td></tr><tr><td valign="top"></td></tr><table class="table table-bordered">';

      $result=DB_query('SELECT `code`,`defaultgl_vat`,`vatcategory` FROM `GLpostinggroup`', $db);

echo '<TR><TH>CODE</TH><TH>VAT<br/> Account</TH><TH>VAT Rate<br/> Category(%)</TH></TR>';

while($rows=DB_fetch_array($result)){
      echo sprintf('<tr><td><a href="?EDIT=%s">%s</a></td><td>%s</td><td>%s</td></tr>',
      $rows['code'],$rows['code'], getaccount($rows['defaultgl_vat']), ($rows['vatcategory']));
}
       
echo '</table></td></tr></table>';

include('includes/footer.inc');

function getaccount($code){
    global $db;
    $result=DB_query("Select accno,accdesc from acct where accno='".$code."'",$db);
    $rows= DB_fetch_row($result);
    
    return $rows[1];
}

?>
