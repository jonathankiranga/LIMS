<?php
include('includes/session.inc');
$Title = _('New Contact');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');

$salesArray=array();
$result=DB_query("select code,salesman from salesrepsinfo",$db);    
while ($myrow = DB_fetch_array($result)) {
   $code=trim($myrow['code']); $salesArray[$code]=$myrow['salesman'];
}
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('New Contact') .'" alt="" />' . ' ' . _('New Contact') . '</p>';

$i=0;
if(isset($_POST['AddNew'])){
    
    if(!isset($_POST['company'])){
       prnMsg('You have to select a Contact currency','error');
       $i++;
    }
    
    if($_POST['newrecord']!='yes'){
       $i++;
    }
    
    if($i==0){
        $sql=sprintf("INSERT INTO `NewContacts`
       (`company`,`postcode`,`city`,`country`,`Physical_Address`,`PIN_VAT`,`phone`,`email`
       ,`salesman` ,`Contact_Name` ,`Contact_Designation` ,`Contact_Telephone`
       ,`Contact_email`,`Alt_Contact_Name`,`Alt_Contact_Designation` ,`Alt_Contact_Telephone`
       ,`Alt_Contact_email` ,`createdby` ,`Date_Created` ,`Last_Activity`) VALUES ('%s' ,'%s' ,'%s' ,'%s'  ,'%s' ,'%s' ,'%s'  ,'%s' ,'%s' ,'%s' ,'%s'  ,'%s'  ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s')",
       $_POST['company'],$_POST['postcode'],$_POST['city'],$_POST['country'],$_POST['Physical_Address'],
       $_POST['PIN_VAT'],$_POST['phone'],$_POST['email'],$_POST['salesman'],$_POST['Contact_Name'] ,
       $_POST['Contact_Designation'] ,$_POST['Contact_Telephone'] , $_POST['Contact_email'] ,$_POST['Alt_Contact_Name'] ,$_POST['Alt_Contact_Designation'],
       $_POST['Alt_Contact_Telephone'],$_POST['Alt_Contact_email'],$_SESSION['UserID'],Date('Y-m-d H:i:s'),Date('Y-m-d H:i:s'));

      DB_query($sql, $db);
       prnMsg('You have saved a new contact','success');
        unset($_POST);
     }
}

if(isset($_POST['Edit'])){
    if(mb_stristr($_SESSION['UserID'],$_POST['username'])==$_POST['username']){
            
       $sql=sprintf("UPDATE `NewContacts` SET `company` = '%s',`postcode` = '%s' ,`city` = '%s' ,`country` = '%s'
      ,`Physical_Address` = '%s',`PIN_VAT` = '%s' ,`phone` = '%s',`email` = '%s',`salesman` = '%s'
      ,`Contact_Name` = '%s',`Contact_Designation` = '%s' ,`Contact_Telephone` = '%s',`Contact_email` = '%s'
      ,`Alt_Contact_Name` = '%s',`Alt_Contact_Designation` = '%s' ,`Alt_Contact_Telephone` = '%s' ,`Alt_Contact_email` = '%s'
      ,`Last_Activity` = '%s' where pkey=%f",$_POST['company'],$_POST['postcode'],$_POST['city'],$_POST['country'],
      $_POST['Physical_Address'],$_POST['PIN_VAT'],$_POST['phone'], $_POST['email'],$_POST['salesman'],
      $_POST['Contact_Name'] , $_POST['Contact_Designation'] ,$_POST['Contact_Telephone'] ,$_POST['Contact_email'] ,
      $_POST['Alt_Contact_Name'] ,$_POST['Alt_Contact_Designation'],$_POST['Alt_Contact_Telephone'],
      $_POST['Alt_Contact_email'],Date('Y-m-d H:i:s'),$_POST['editcode']);
  
      DB_query($sql,$db);
      unset($_POST);
    }else{
        prnMsg('You cannot alter this contact Because '.$_POST['username'].' is the owner', 'info');
    }
}

if(isset($_GET['itemcode'])){
    $result=DB_query("SELECT `company`
      ,`postcode` ,`city` ,`country`,`Physical_Address`,`PIN_VAT` ,`phone` ,`email` ,`salesman`
      ,`Contact_Name` ,`Contact_Designation`,`Contact_Telephone`,`Contact_email`
      ,`Alt_Contact_Name` ,`Alt_Contact_Designation`,`Alt_Contact_Telephone`,`Alt_Contact_email`
      ,`createdby`  FROM `NewContacts` where pkey=".$_GET['itemcode'],$db);
    $myrow = DB_fetch_row($result);
      $_POST['company']=trim($myrow[0]);
      $_POST['postcode']=trim($myrow[1]);
      $_POST['city']=trim($myrow[2]);
      $_POST['country']=trim($myrow[3]);
      $_POST['Physical_Address']=trim($myrow[4]);
      $_POST['PIN_VAT']=trim($myrow[5]);
      $_POST['phone']=trim($myrow[6]);
      $_POST['email']=trim($myrow[7]);
      $_POST['salesman']=trim($myrow[8]);
      $_POST['Contact_Name']=trim($myrow[9]);
      $_POST['Contact_Designation']=trim($myrow[10]);
      $_POST['Contact_Telephone']=trim($myrow[11]);
      $_POST['Contact_email']=trim($myrow[12]);
      $_POST['Alt_Contact_Name']=trim($myrow[13]);
      $_POST['Alt_Contact_Designation']=trim($myrow[14]);
      $_POST['Alt_Contact_Telephone']=trim($myrow[15]);
      $_POST['Alt_Contact_email']=trim($myrow[16]);
      $username = trim($myrow[17]);
}

echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="editcode" value="' . $_GET['itemcode'] . '" />'
   . '<input type="hidden" name="username" value="' .$username. '" />'
   . '<input type="hidden" name="newrecord" value="' .$_GET['new']. '" />';

echo '<table class="table" cellspacing="4"><tr><td valign="top" rowspan="2"><table class="table table-bordered"><caption>Primary Details</caption>';
echo '<tr><td>' . _('Business Name').'</td><td><input type="text"  required="required" name="company" maxlength="100"  value="'.$_POST['company'].'"/></td></tr>';
echo '<tr><td>' . _('Postal Code').'</td><td><input type="text" name="postcode" maxlength="50"  value="'.$_POST['postcode'].'"/></td></tr>';
echo '<tr><td>' . _('City').'</td><td><input type="text" name="city" maxlength="50"  value="'.$_POST['city'].'"/></td></tr>';
echo '<tr><td>' . _('Country').'</td><td><select name="country">';
        foreach ($CountriesArray as $CountryEntry => $CountryName){
                if (isset($_POST['country']) AND (strtoupper($_POST['country']) == strtoupper($CountryName))){
                        echo '<option selected="selected" value="' . $CountryName . '">' . $CountryName  . '</option>';
                }elseif (!isset($_POST['country']) AND $CountryName == "") {
                        echo '<option selected="selected" value="' . $CountryName . '">' . $CountryName  . '</option>';
                } else {
                        echo '<option value="' . $CountryName . '">' . $CountryName  . '</option>';
                }
        }
echo '</select></td></tr>';
echo '<tr><td>Physical Address</td><td><textarea id="Standard" rows="4" cols="40" name="Physical_Address">'.$_POST['Physical_Address'].'</textarea></td></tr>';
echo '<tr><td>' . _('PIN/VAT').'</td><td><input type="text" name="PIN_VAT" maxlength="50" required="required" value="'.$_POST['PIN_VAT'].'"/></td></tr>';
echo '<tr><td>' . _('Telephone No (office) ').'</td><td><input type="tel" name="phone" maxlength="10"  value="'.$_POST['phone'].'"/></td></tr>';
echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100"  value="'.$_POST['email'].'"' ;
        ?> pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"  <?php echo '/></td></tr>';
echo '<tr><td>'._('Sales Person').'</td><td>';

$result=DB_query("select code,salesman from salesrepsinfo",$db);    
echo '<select name="salesman"><option value="">not selected</option>';
  while($myrow = DB_fetch_array($result)) {
        $code=trim($myrow['code']);
        echo '<option  value="'.$code.'"'. ($code==$_POST['salesman']?' selected="selected"':''). '>' . $myrow['salesman'] . '</option>';
    } //end while loop
echo '</select>' ;
echo  '</td></tr>';
           
echo '</table></td><td  valign="top"><table class="table table-bordered"><caption>Contact Details</caption>';
echo '<tr><td>' . _('Contact Name').'</td><td><input type="text" name="Contact_Name" maxlength="50"  value="'.$_POST['Contact_Name'].'"/></td></tr>';
echo '<tr><td>' . _('Contact Designation').'</td><td><input type="text" name="Contact_Designation" maxlength="50" value="'.$_POST['Contact_Designation'].'"  /></td></tr>';
echo '<tr><td>' . _('Contact Telephone No').'</td><td><input type="tel" name="Contact_Telephone" maxlength="10"  value="'.$_POST['Contact_Telephone'].'"/></td></tr>';
echo '<tr><td>' . _('Contact email').'</td><td><input type="text" name="Contact_email" maxlength="100"  value="'.$_POST['Contact_email'].'"' ;
        ?> pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"  <?php echo '/></td></tr></table></td></tr>';

echo '<tr><td><table class="table table-bordered"><caption>Alternative Contact</caption><tr><td>' . _('Alt Contact Name').'</td><td><input type="text" name="Alt_Contact_Name" maxlength="50"  value="'.$_POST['Alt_Contact_Name'].'"/></td></tr>';
echo '<tr><td>' . _('Alt Contact Designation').'</td><td><input type="text" name="Alt_Contact_Designation" maxlength="50"  value="'.$_POST['Alt_Contact_Designation'].'"/></td></tr>';
echo '<tr><td>' . _('Alt Contact Telephone No').'</td><td><input type="tel" name="Alt_Contact_Telephone" maxlength="10"  value="'.$_POST['Alt_Contact_Telephone'].'"/></td></tr>';
echo '<tr><td>' . _('Alt Contact email').'</td><td><input type="text" name="Alt_Contact_email" maxlength="100"  value="'.$_POST['Alt_Contact_email'].'"' ;
        ?> pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"  <?php echo '/></td></tr>';
                
echo '</table></td></tr></table>';
 
if(isset($_GET['itemcode'])){
    echo '<div class="centre">
        <input type="submit" name="Edit" value="' . _('Edit Contact') . '" />
    </div>';
}elseif(isset($_GET['new'])){
    echo '<div class="centre">
        <input type="submit" name="AddNew" value="' . _('Add New Contact') . '" />
    </div>';
}
echo '</div></form>' ;

echo '<form><div class="container">
    <table class="table table-striped table-bordered">
    <thead>'
        . '<TR>'
        . '<th class="ascending">Business Name</th>'
        . '<th>Postal Code</th>'
        . '<th>Physical Address</th>'
        . '<th>PIN/VAT</th>'
        . '<th>Office Phone</th>'
        . '<th>Office email</th>'
        . '<th class="ascending">Sales Rep</th>'
        . '<th>Contact Name,Contact Designation,Contact Telephone,Contact email</th>'
        . '<th>Alt Contact Name,Alt Contact Designation,Alt Contact Telephone No,Alt Contact email</th>'
        . '<th class="ascending">Created By</th>'
        . '<th class="ascending">Date Created</th>'
        . '<th class="ascending">Last Activity</th>'
        . '</tr></thead>';
      
       $results=DB_query('SELECT `company`
      ,`postcode` ,`Physical_Address`,`PIN_VAT` ,`phone` ,`email` ,`salesman`
      ,`Contact_Name` ,`Contact_Designation`,`Contact_Telephone`,`Contact_email`
      ,`Alt_Contact_Name` ,`Alt_Contact_Designation`,`Alt_Contact_Telephone`,`Alt_Contact_email`
      ,`createdby`,`Date_Created`,`Last_Activity` ,pkey FROM `NewContacts`', $db);
       
       
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo '<tr>';
           echo sprintf('<td><a href="%s?itemcode=%s">%s</a></td>',
           $thispage, trim($rows['pkey']),trim($rows['company']));
           echo '<td>'. $rows['postcode'].'</td>';
           echo '<td>'. html_entity_decode($rows['Physical_Address']) .'</td>';
           echo '<td>'.$rows['PIN_VAT'].'</td>';
           echo '<td>'.trim($rows['phone']).'</td>'
                   . '<td>'.trim($rows['email']).'</td>';
           echo '<td>'.trim($salesArray[$rows['salesman']]).'</td>';
           echo '<td>'.trim($rows['Contact_Name']).'<br/>'.trim($rows['Contact_Designation']).'<br/>'.trim($rows['Contact_Telephone']).'<br/>'.trim($rows['Contact_email']).'</td>';
           echo '<td>'.trim($rows['Alt_Contact_Name']).'<br/>'.trim($rows['Alt_Contact_Designation']).'<br/>'.trim($rows['Alt_Contact_Telephone']).'<br/>'.trim($rows['Alt_Contact_email']).'</td>';
           echo '<td>'.$rows['createdby'].'</td>'
              . '<td>'. ConvertSQLDate($rows['Date_Created']).'</td>'
              . '<td>'. ConvertSQLDate($rows['Last_Activity']).'</td>'
             . '</tr>';
            
       }        

echo '</tbody></table></div></form>';

include('includes/footer.inc');
?>