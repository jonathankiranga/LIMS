<?php

include('includes/session.inc');
$Title = _('Customer Posting Groups');
include('includes/header.inc');

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Customer') .'" alt="" />' . ' ' . _('Customer Posting Groups') . '</p>';


if(isset($_POST['submit'])){
    $errors=0;
    
    if(mb_strlen($_POST['code'])==0 and !isset($_POST['codeedit'])){
        prnMsg('You have not selected a code','warn');
        $errors++;
    }
    
    if(mb_strlen($_POST['debtorsaccount'])==0){
        prnMsg('You have not selected a debtors account','warn');
        $errors++;
    }
    
   
    
    if(mb_strlen($_POST['salesaccount'])==0){
        prnMsg('You have not selected a Sales account','warn');
        $errors++;
    }
    
    
    
    if($errors==0 and $_POST['submit']=='New Posting group'){
        $SQL="INSERT INTO `postinggroups`
           (`code`
           ,`salesaccount`
           ,`debtorsaccount`
           ,`IsTaxed`
           ,`VATinclusive`)
     VALUES
           ('".$_POST['code']."'
           ,'".$_POST['salesaccount']."'
           ,'".$_POST['debtorsaccount']."'
           ,'".$_POST['IsTaxed']."'
           ,'".$_POST['VATinclusive']."')";
        
        $ResultIndex=DB_query($SQL, $db);
        if(DB_num_rows($ResultIndex)>0){
            prnMsg('You have succeeded in creating a posting group');
        }
    }
    
    
    if($errors==0 and $_POST['submit']=='Edit Posting group'){
        $SQL="UPDATE `postinggroups`
                SET `salesaccount` = '".$_POST['salesaccount']."'
                   ,`debtorsaccount` = '".$_POST['debtorsaccount']."'
                   ,`IsTaxed` = '".$_POST['IsTaxed']."'
                   ,`VATinclusive` ='".$_POST['VATinclusive']."'
                 WHERE `code` ='".$_POST['codeedit']."'";
        $ResultIndex=DB_query($SQL, $db);
        if(DB_num_rows($ResultIndex)>0){
            prnMsg('You have succeeded in editing a posting group');
        }
    }
    
      unset($_POST['code']);
      unset($_POST['salesaccount']);
      unset($_POST['debtorsaccount']);
      unset($_POST['IsTaxed']);
      unset($_POST['VATinclusive']);
}


if(isset($_POST['delete'])){
    
   $result= DB_query("Select `customerposting` from debtors where `customerposting`='".$_POST['codeedit']."'", $db);
   if(DB_num_rows($result)==0){
       DB_query("Delete from `postinggroups` where code='". $_POST['codeedit'] . "'", $db);
   } else {
       prnMsg('This posting group is in use, so it cannot be deleted');
   }
    
}



if(isset($_GET['EDIT'])){
      $result=DB_query("SELECT `code`
      ,`salesaccount`
      ,`debtorsaccount`
      ,`IsTaxed`
      ,`VATinclusive`
       FROM `postinggroups` 
       where code='".$_GET['EDIT']."'",$db);
      
      $rows =DB_fetch_array($result);
      $_POST['code']=$rows['code'];
      $_POST['salesaccount']=$rows['salesaccount'];
      $_POST['debtorsaccount']=$rows['debtorsaccount'];
      $_POST['IsTaxed']=$rows['IsTaxed'];
      $_POST['VATinclusive']=$rows['VATinclusive'];
}


echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';   
echo '<input type="hidden" name="codeedit" value="' . $_GET['EDIT'] . '" />';   
echo '<table class="table table-bordered"><tr>';

if(!isset($_GET['EDIT'])){
    echo '<td>Enter Code</td><td><input type="text" name="code"  maxlength="10" required="required" /></td></tr>';
}

echo '<tr><td>Select Sales Account</td><td><select name="salesaccount"><option></option>';
     $result=DB_query("Select accno,accdesc from acct where balance_income=1 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['salesaccount']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    
echo '<tr><td>Select Debtors Account</td><td><select name="debtorsaccount"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=0 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['debtorsaccount']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    echo '<tr><td>VAT Is Taxed</td><td><select name="IsTaxed">';
    $VAToptionArray =array('0','1');
    foreach ($VAToptionArray as $VAToptionEntry => $option) {
        if ($_POST['IsTaxed']==$option){
            echo '<option selected="selected" value="'.$option .'">' . ($option==0?'NO':'YES') . '</option>';
        } else {
            echo '<option value="'. $option .'">' . ($option==0?'NO':'YES') . '</option>';
        }
    }

    echo '</select></td></tr>';
    echo '<tr><td>Is VAT Inclusive</td><td><select name="VATinclusive">';
    $VAToptionArray =array('0','1');
    foreach ($VAToptionArray as $VAToptionEntry => $option) {
        if ($_POST['VATinclusive']==$option){
            echo '<option selected="selected" value="'.$option .'">' . ($option==0?'NO':'YES') . '</option>';
        } else {
            echo '<option value="'. $option .'">' . ($option==0?'NO':'YES') . '</option>';
        }
    }
 
echo '</select></td></tr></table>';
echo '<div class="centre">';
        if(isset($_GET['EDIT'])){
            echo '<input type="submit" name="submit" value="Edit Posting group"/>';
            echo '<input type="submit" name="delete" value="Delete Posting group"/>';
        }else{
            echo '<input type="submit" name="submit" value="New Posting group"/>';
        }
echo '</div></form>';


echo '</td></tr><tr><td valign="top"></td></tr><table class="table table-bordered">';

      $result=DB_query('SELECT `code`
      ,`salesaccount`
      ,`debtorsaccount`
      ,`IsTaxed`
      ,`VATinclusive`
       FROM `postinggroups`', $db);

echo '<TR><TH>CODE</TH>'
      . '<TH>SALES ACCOUNT</TH>'
        . '<TH>DEBTORS ACCOUNT</TH>'
        . '<TH>Is Taxed</TH>'
        . '<TH>VAT INCLUSIVE</TH></TR>';

while($rows=DB_fetch_array($result)){
    
    echo sprintf('<tr>'
            . '<td><a href="?EDIT=%s">%s</a></td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td></tr>',
          $rows['code'],$rows['code'],
          getaccount($rows['salesaccount']),
          getaccount($rows['debtorsaccount']),
         ($rows['IsTaxed']==0?'NO':'YES') ,
         ($rows['VATinclusive']==0?'NO':'YES') );
    
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
