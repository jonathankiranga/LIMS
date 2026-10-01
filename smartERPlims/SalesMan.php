<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Sales Representative Maintenance');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

$i=0;
if(isset($_POST['submit'])){
    
    
    if($i==0){
        if($_POST['submit']=='Add New Sales Agent'){
                       
            $result = DB_query("Select salesman from salesrepsinfo where salesman like '".$_POST['customer']."'", $db);
            if(DB_num_rows($result)>0){
                prnMsg('The Sales Man name '.$_POST['customer'].' already exists','warn');
            } else {
                               
            $sql=sprintf("INSERT INTO `salesrepsinfo`
           (`salesman`,`middlen`,`phone`,`fax`,`company`,`altcontact`,`email` ,`city` ,`country` 
           ,`inactive`,`postcode`,`curr_cod`,`commissionposting`) values
           ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
            $_POST['customer']  ,$_POST['middlen'],$_POST['phone'] ,$_POST['fax'] ,$_POST['company'],
            $_POST['altcontact'],$_POST['email'] , $_POST['city'] ,$_POST['country'], 
            $_POST['inactive'] ,$_POST['postcode'] ,$_POST['curr_cod'],$_POST['customerposting']);
                        
             DB_query($sql, $db);
            }
       }
     // end of create new
       
        if($_POST['submit']=='Update Sales Agent'){
            
          $SQL="UPDATE `salesrepsinfo`
             SET `middlen` = '".$_POST['middlen']."'
                ,`phone` = '".$_POST['phone']."'
                ,`fax` = '".$_POST['fax']."'
                ,`company` = '".$_POST['company']."'
                ,`altcontact` = '".$_POST['altcontact']."'
                ,`email` = '".$_POST['email']."'
                ,`city` = '".$_POST['city']."'
                ,`country` = '".$_POST['country']."'
                ,`inactive` = '".$_POST['inactive']."'
                ,`postcode` = '".$_POST['postcode']."'
                ,`curr_cod` = '".$_POST['curr_cod']."'
                ,`commissionposting` = '".$_POST['customerposting']."'
                ,`salesman` = '".$_POST['customer']."'
             WHERE `code` ='".$_POST['editcode']."'";
                    DB_query($SQL,$db);
    }
 }
 // end of validation
}

if($_POST['delete']=='Delete Sales Agent'){
    $result = DB_query("Select * from debtors where salesman='".$_POST['editcode']."'", $db);
     if(DB_num_rows($result)>0){
         prnMsg('This account has transactions and cannot be deleted','error');
     }else{
         $result = DB_query("delete from salesrepsinfo where itemcode='".$_POST['editcode']."'", $db);
         if(DB_num_rows($result)>0){
              prnMsg('This customer has been deleted','info');
         }
     }
}

if(isset($_GET['Modify']) ){
    
    $result = DB_query("Select * from salesrepsinfo where code='".$_GET['Modify']."'", $db);
    if(DB_num_rows($result)==0){
        Die('This code is not correct');
       }else{
        $myrows= DB_fetch_array($result);
        $_POST['customer']=$myrows['salesman'];
        $_POST['company']=$myrows['company'];
        $_POST['postcode']=$myrows['postcode'];
        $_POST['city']=$myrows['city'];
        $_POST['country']=trim($myrows['country']);
        $_POST['phone']=$myrows['phone'];
        $_POST['fax']=$myrows['fax'];
        $_POST['altcontact']=$myrows['altcontact'];
        $_POST['email']=trim($myrows['email']);
        $_POST['creditlimit']=$myrows['commission'];
        $_POST['curr_cod']=trim($myrows['curr_cod']);
        $_POST['inactive']=$myrows['inactive'];
        $_POST['customerposting']=$myrows['commissionposting'];
        $_POST['middlen']=$myrows['middlen'];
        $_POST['target']=$myrows['target']; 
    }
    
    
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="editcode" value="' . $_GET['Modify'] . '" />';
echo '<table class="table-condensed table-responsive-small"><tr><td valign="top">'
. '<table class="table-condensed table-responsive-small"><caption>General Details</caption>';
echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" maxlength="30" required="required" value="'.$_POST['customer'].'"/></td></tr>';
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  value="'.$_POST['company'].'"/></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  value="'.$_POST['postcode'].'"/></td></tr>';
echo '<tr><td>' . _('city').'</td><td><input type="text" name="city" maxlength="50" value="'.$_POST['city'].'"  /></td></tr>';
echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  value="'.$_POST['phone'].'"/></td></tr>';
echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100"  value="'.$_POST['email'].'"' ;
        ?> pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"  <?php echo '/></td></tr>';
echo '</table></td><td valign="top"><table class="table-condensed table-responsive-small"><caption>Settings</caption>';
      echo '<tr><td>' . _('Block Account').'</td><td>';
        echo '<select name="inactive">'
                . '<option  '.($_POST['inactive']==0?'selected="selected"':'').' value="0">No</option>'
                . '<option  '.($_POST['inactive']==1?'selected="selected"':'').' value="1">Yes</option>'
                . '</select>';
        echo  '</td></tr>';
       
        
        $result=DB_query("SELECT code FROM postinggroups",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2">' .
                        '<p>'. _('There are no posting currently defined - go to the setup tab of the main menu and set at least one up first').'</p></td>
			</tr>';
	} else {
        echo '<tr><td>'._('Posting Group').'</td><td>';
        echo '<select name="customerposting">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option '. ($myrow['code']==$_POST['customerposting']?'selected="selected"':''). '>' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
     echo '<tr><td>' . _('KRA PIN').'</td><td><input type="text" name="middlen" maxlength="10" value="'.$_POST['middlen'].'"/>' ;
        
         
echo '</table></td></tr></table>';
    
} else {

echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table-condensed table-responsive-small"><tr><td valign="top">'
. '<table class="table-condensed table-responsive-small"><caption>General Details</caption>';

echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" maxlength="30" required="required" /></td></tr>';
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  /></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  /></td></tr>';
echo '<tr><td>' . _('city').'</td><td><input type="text" name="city" maxlength="50" /></td></tr>';

echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  /></td></tr>';

echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100" ' ;
        ?>   pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"   <?php echo '/></td></tr>';

echo '</table></td><td valign="top"><table class="table-condensed table-responsive-small"><caption>Settings</caption>';

echo '<tr><td>' . _('Block Account').'</td><td>';
        echo '<select name="inactive">'
                . '<option  value="0">No</option>'
                . '<option   value="1">Yes</option>'
                . '</select>';
        echo  '</td></tr>';
       
        
        $result=DB_query("SELECT code FROM postinggroups",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2"><p>' .
                         _('There are no posting currently defined') 
                        .'<br />'. _('go to the setup tab of the main menu and set at least one up first'). '</p></td>
			</tr>';
	} else {
        echo '<tr><td>'._('Posting Group').'</td><td>';
        echo '<select name="customerposting">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option >' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
        echo '<tr><td>' . _('KRA PIN').'</td><td><input type="text" name="middlen" maxlength="10" />' ;

        echo '</table></td></tr></table>';

}

if (!isset($_GET['Modify'])) {
		echo '<div class="centre">
                        <input type="submit" name="submit" value="' . _('Add New Sales Agent') . '" />
                    </div>';
	} else {
	   echo '<br />
		<div class="centre">
		<input type="submit" name="submit" value="' . _('Update Sales Agent') . '" />&nbsp;
		<input type="submit" name="delete" value="' . _('Delete Sales Agent') . '" onclick="return confirm(\'' . _('Are You Sure You Want To Delete?') . '\');" />
            </div>';
	}
        
echo '</div></form>' ;
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Sales Representative Maintenance') .'" alt="" />' . ' ' . _('Sales Representative Maintenance') . '</p>';

echo '<p><table class="table table-bordered"><tr><th>Code</th><th>Sales Reps Name</th><th>Telephone No</th><th>Email</th></tr>';
      $results=DB_query('Select code,salesman,commission,phone,email,target from salesrepsinfo', $db);
      
       while($rows=DB_fetch_array($results)){
           echo sprintf('<tr><td><a href="SalesMan.php?Modify=%s">%s</a></td>',
                   trim($rows['code']),trim($rows['code']));
                    echo '<td>'.$rows['salesman'].'</td>';
                    echo '<td>'.trim($rows['phone']).'</td>';
                    echo '<td>'.trim($rows['email']).'</td>';
                echo '</tr>';
            
       }        

echo '</table></p>';

include('includes/footer.inc');


?>
