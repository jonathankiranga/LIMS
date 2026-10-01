<?php

include('includes/session.inc');
$Title = _('Inventory Posting Groups');
include('includes/header.inc');

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . _('Inventory') .'" alt="" />' . ' ' . _('Inventory Posting Groups') . '</p>';


if(isset($_POST['submit'])){
    $errors=0;
    
    if(mb_strlen($_POST['code'])==0 and !isset($_POST['codeedit'])){
        prnMsg('You have not selected a code','warn');
        $errors++;
    }
    
    if(mb_strlen($_POST['salesaccount'])==0){
        prnMsg('You have not selected a Sales Account','warn');
        $errors++;
    }
    
    if(mb_strlen($_POST['vataccount'])==0){
        prnMsg('You have not selected a VAT account','warn');
        $errors++;
    }
    
    if(mb_strlen($_POST['balancesheetStock'])==0){
        prnMsg('You have not selected a Balance Sheet Account','warn');
        $errors++;
    }
    
    if(mb_strlen($_POST['CostOfSales'])==0){
        prnMsg('You have not selected a Cost of Sales Account','warn');
        $errors++;
    }
    
    
    if($errors==0 and $_POST['submit']=='New Posting group'){
        $SQL="INSERT INTO `inventorypostinggroup`
           (`code`
           ,`defaultgl_sales`
           ,`defaultgl_purch`
           ,`defaultgl_vat`
           ,`balancesheet`
           ,`vatcategory`
           ,`stockvariance`
           ,`CostOfSales`
           ,`spoilage`)
    VALUES
           ('".$_POST['code']."'
           ,'".$_POST['salesaccount']."'
           ,'".$_POST['debtorsaccount']."'
           ,'".$_POST['vataccount']."'
           ,'".$_POST['balancesheetStock']."'
           ,'".$_POST['VATinclusive']."'
           ,'".$_POST['stockvariance']."'
           ,'".$_POST['CostOfSales']."'
           ,'".$_POST['spoilage']."')";
        
        $ResultIndex=DB_query($SQL, $db);
        if(DB_num_rows($ResultIndex)>0){
            prnMsg('You have succeeded in creating a posting group');
        }
    }
    
    
    if($errors==0 and $_POST['submit']=='Edit Posting group'){
        $SQL="UPDATE `inventorypostinggroup`
                SET `defaultgl_sales` = '".$_POST['salesaccount']."'
                   ,`defaultgl_purch` = '".$_POST['debtorsaccount']."'
                   ,`defaultgl_vat` = '".$_POST['vataccount']."'
                   ,`balancesheet` = '".$_POST['balancesheetStock']."'
                   ,`vatcategory` = '".$_POST['VATinclusive']."'
                   ,`stockvariance` = '".$_POST['stockvariance']."'
                   ,`CostOfSales` = '".$_POST['CostOfSales']."'
                   ,spoilage= '".$_POST['spoilage']."'
              WHERE `code` ='".$_POST['codeedit']."'";
        $ResultIndex=DB_query($SQL, $db);
        if(DB_num_rows($ResultIndex)>0){
            prnMsg('You have succeeded in editing a posting group');
        }
    }
    
      unset($_POST);
}


if(isset($_POST['delete'])){
    
   $result= DB_query("Select `postinggroup` from stockmaster where `postinggroup`='".$_POST['codeedit']."'", $db);
   if(DB_num_rows($result)==0){
       DB_query("Delete from `inventorypostinggroup` where code='". $_POST['codeedit'] . "'", $db);
   } else {
       prnMsg('This posting group is in use, so it cannot be deleted');
   }
    
}


if(isset($_GET['EDIT'])){
      $result=DB_query("SELECT `code`
      ,`defaultgl_sales`
      ,`defaultgl_purch`
      ,`defaultgl_vat`
      ,`balancesheet`
      ,`vatcategory`
      ,`stockvariance`
      ,`CostOfSales`
      ,`spoilage`
  FROM `inventorypostinggroup` 
       where code='".$_GET['EDIT']."'",$db);
       
      $rows =DB_fetch_array($result);
      $_POST['code']=$rows['code'];
      $_POST['salesaccount']=$rows['defaultgl_sales'];
      $_POST['debtorsaccount']=$rows['defaultgl_purch'];
      $_POST['vataccount']=$rows['defaultgl_vat'];
      $_POST['balancesheetStock']=$rows['balancesheet'];
      $_POST['VATinclusive']=$rows['vatcategory'];
      $_POST['stockvariance']=$rows['stockvariance'];
      $_POST['CostOfSales']=$rows['CostOfSales'];
      $_POST['spoilage']=$rows['spoilage'];
}

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';   
echo '<input type="hidden" name="codeedit" value="' . $_GET['EDIT'] . '" />';   
echo '<table class="table table-bordered"><tr>';

if(!isset($_GET['EDIT'])){
    echo '<td>Enter Code</td><td><input type="text" name="code" maxlength="10" required="required" /></td></tr>';
}

echo '<tr><td>Sales Account <span style="color:red">*</span></td><td><select name="salesaccount"><option></option>';
     $result=DB_query("Select accno,accdesc from acct where balance_income=1 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['salesaccount']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    
echo '<tr><td>Purchase Account [optional]</td><td><select name="debtorsaccount"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=1 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['debtorsaccount']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    
echo '<tr><td>VAT Account <span style="color:red">*</span></td><td><select name="vataccount"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=0 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['vataccount']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    
    
    echo '<tr><td>Balance Sheet Account <span style="color:red">*</span></td><td><select name="balancesheetStock"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=0 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['balancesheetStock']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

echo '</select></td></tr>';
    
    
    echo '<tr><td>Cost of Sales Account <span style="color:red">*</span></td><td><select name="CostOfSales"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=1 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['CostOfSales']==$myrow['accno']){
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
   
echo '<tr><td>Stock Movement Account</td><td><select name="stockvariance"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['stockvariance']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
    
    
echo '<tr><td>Sales Spoilage [optional]</td><td><select name="spoilage"><option></option>';
    $result=DB_query("Select accno,accdesc from acct where balance_income=1 and `ReportStyle`=0",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['spoilage']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    } //end while loop

    echo '</select></td></tr>';
     
echo '</table>';
echo '<div class="centre">';

        if(isset($_GET['EDIT'])){
            echo '<input type="submit" name="submit" value="Edit Posting group"/>';
            echo '<input type="submit" name="delete" value="Delete" onclick="return confirm(\''._('Are you sure you wish to Delete ?').'\');"/>';
        } else {
            echo '<input type="submit" name="submit" value="New Posting group"/>';
        }
        
echo '</div></form>';
echo '</td></tr><tr><td valign="top"></td></tr><table class="table table-bordered">';

      $result=DB_query('SELECT `code`
      ,`defaultgl_sales`
      ,`defaultgl_purch`
      ,`defaultgl_vat`
      ,`balancesheet`
      ,`vatcategory`
      ,`stockvariance`
      ,`CostOfSales`
      ,`spoilage`
  FROM `inventorypostinggroup`', $db);

echo '<TR><TH>CODE</TH>'
        . '<TH>Sales<br/> Account</TH>'
        . '<TH>Purchase<br/> Account</TH>'
        . '<TH>VAT<br/> Account</TH>'
        . '<TH>Balance Sheet<br/> Account</TH>'
        . '<TH>VAT Rate<br/> Category(%)</TH>'
        . '<TH>Stock Movement<br/>Account</TH>'
        . '<TH>Cost of Sales<br/> Account</TH>'
        . '<TH>Sales Spoilage<br/> [optional]</TH>'
        . '</TR>';

while($rows=DB_fetch_array($result)){
    
    echo sprintf('<tr>'
            . '<td><a href="?EDIT=%s">%s</a></td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td></tr>',
            $rows['code'],$rows['code'],
            getaccount($rows['defaultgl_sales']),
            getaccount($rows['defaultgl_purch']),
            getaccount($rows['defaultgl_vat']),
            getaccount($rows['balancesheet']),
            ($rows['vatcategory']),
            getaccount($rows['stockvariance']),
            getaccount($rows['CostOfSales']),
            getaccount($rows['spoilage'])
            );
    }
       
echo '</table></td></tr></table>';

include('includes/footer.inc');

function getaccount($code){
    global $db;
    
    $result = DB_query("Select accno,accdesc from acct where accno='".$code."'",$db);
    $rows = DB_fetch_row($result);
    
    return $rows[1];
}

?>