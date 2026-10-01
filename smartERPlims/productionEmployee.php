<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Staff Member Maintenance');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' 
  . _('Staff Members') .'" alt="" />' . ' ' . _('Staff Members') . '</p>';


if(isset($_POST['submit'])){
    
    if($_POST['submit']=='Add New'){

        $result = DB_query("Select salesman from productionEmployee where salesman like '".$_POST['customer']."'", $db);
        if(DB_num_rows($result)>0){
            prnMsg('The name '.$_POST['customer'].' already exists','warn');
        } else {

        $sql=sprintf("INSERT INTO `productionEmployee`
       (`salesman`,`pinno`,`phone`,`fax`,`company`,`altcontact`,`email` ,`city` ,`country` 
       ,`inactive`,`postcode`,`curr_cod`,`commissionposting`,`manager`) values
       ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
         $_POST['customer']  ,$_POST['middlen'],$_POST['phone'] ,$_POST['fax'] ,$_POST['company'],
        $_POST['altcontact'],$_POST['email'] , $_POST['city'] ,$_POST['country'], 
        $_POST['inactive'] ,$_POST['postcode'] ,$_POST['curr_cod'],
        $_POST['customerposting'],$_POST['productionManager']);

         DB_query($sql, $db);
        }
    }

    if($_POST['submit']=='Update'){

      $SQL="UPDATE `productionEmployee`
         SET `manager` = '". $_POST['productionManager']."'
            ,`pinno` = '".$_POST['middlen']."'
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

if($_POST['delete']=='Delete'){
    $result = DB_query("Select * from debtors where salesman='".$_POST['editcode']."'", $db);
     if(DB_num_rows($result)>0){
         prnMsg('This account has transactions and cannot be deleted','error');
     }else{
         $result = DB_query("delete from productionEmployee where code='".$_POST['editcode']."'", $db);
         if(DB_num_rows($result)>0){
              prnMsg('This customer has been deleted','info');
         }
     }
}

if(isset($_GET['Modify']) ){
    $result = DB_query("Select * from productionEmployee where code='".$_GET['Modify']."'", $db);
    if(DB_num_rows($result)==0){
        Die('This code is not correct');
    }else{
        $myrows= DB_fetch_array($result);
        $_POST['customer']=trim($myrows['salesman']);
        $_POST['company']=trim($myrows['company']);
        $_POST['postcode']=trim($myrows['postcode']);
        $_POST['city']=trim($myrows['city']);
        $_POST['country']=trim($myrows['country']);
        $_POST['phone']=trim($myrows['phone']);
        $_POST['fax']=trim($myrows['fax']);
        $_POST['altcontact']=trim($myrows['altcontact']);
        $_POST['email']=trim($myrows['email']);
        $_POST['productionManager']=trim($myrows['manager']);
        $_POST['curr_cod']=trim($myrows['curr_cod']);
        $_POST['inactive']=trim($myrows['inactive']);
        $_POST['customerposting']=trim($myrows['commissionposting']);
        $_POST['middlen']=trim($myrows['pinno']);
      
    }
    
    
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="editcode" value="' . $_GET['Modify'] . '" />';
echo '<table class="table"><tr><td valign="top"><table class="table table-bordered"><caption>Personal Data</caption>';

echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" maxlength="30" required="required" value="'.$_POST['customer'].'"/></td></tr>';
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  value="'.$_POST['company'].'"/></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  value="'.$_POST['postcode'].'"/></td></tr>';
echo '<tr><td>' . _('city').'</td><td><input type="text" name="city" maxlength="50" value="'.$_POST['city'].'"  /></td></tr>';

echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  value="'.$_POST['phone'].'"/></td></tr>';
echo '<tr><td>' . _('Fax No').'</td><td><input type="text" name="fax" maxlength="50"  value="'.$_POST['fax'].'"/></td></tr>';
echo '<tr><td>' . _('Alt Contact').'</td><td><input type="text" name="altcontact" maxlength="100"  value="'.$_POST['altcontact'].'"/></td></tr>';
echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100"  value="'.$_POST['email'].'"' ;
        ?> pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"  <?php echo '/></td></tr>';

echo '</table></td><td  valign="top"><table class="table table-bordered"><caption>Other Details</caption>';

$result=DB_query("SELECT currency, currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2">'
                . '<p>' . _('There are no currencies currently defined ').
                 '<br />'. _(' go to the setup tab of the main menu and set at least one up first') . '</p></td>
                </tr>';
	} else {
		echo '<tr><td>' . _('Currency') . ':</td><td><select tabindex="17" name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
                    echo '<option '.($_POST['curr_cod']==$myrow['currabrev']?'selected="selected"':'').' value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
		}  

		echo '</select></td></tr>';
	}
       
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
		echo '<option '. (trim($myrow['code'])==$_POST['customerposting']?'selected="selected"':''). '>' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
     echo '<tr><td>' . _('PIN').'</td><td><input type="text" name="middlen" maxlength="10" value="'.$_POST['middlen'].'"/></td></tr>' ;
     echo '</table></td></tr></table>';
    
} else {

echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table"><tr><td valign="top"><table class="table table-bordered"><caption>Personal data</caption>';

echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" maxlength="30" required="required" /></td></tr>';
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  /></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  /></td></tr>';
echo '<tr><td>' . _('city').'</td><td><input type="text" name="city" maxlength="50" /></td></tr>';

echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  /></td></tr>';
echo '<tr><td>' . _('Fax No').'</td><td><input type="text" name="fax" maxlength="50"  /></td></tr>';
echo '<tr><td>' . _('Alt Contact').'</td><td><input type="text" name="altcontact" maxlength="100"  /></td></tr>';
echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100" ' ;
        ?>   pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"   <?php echo '/></td></tr>';

echo '</table></td><td  valign="top"><table class="table table-bordered"><caption>Other Details</caption>';
$result=DB_query("SELECT currency, currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2">'
                . '<p>' . _('There are no currencies currently defined ').
                 '<br />'. _(' go to the setup tab of the main menu and set at least one up first') . '</p></td>
                </tr>';
	} else {
		echo '<tr><td>' . _('Currency') . ':</td><td><select tabindex="17" name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
                    echo '<option '.($_POST['curr_cod']==$myrow['currabrev']?'selected="selected"':'').' value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
		}  

		echo '</select></td></tr>';
	}
        
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
    echo '<tr><td>' . _('PIN').'</td><td><input type="text" name="middlen" maxlength="10" /></td></tr>' ;

       
        echo '</table></td></tr></table>';
}

if (!isset($_GET['Modify'])) {
        echo '<div class="centre">
                <input type="submit" name="submit" value="' . _('Add New') . '" />&nbsp;
                <input type="Reset" name="Reset" value="' . _('Reset') . '" />
            </div>';
	} else {
	   echo '<br />
		<div class="centre">
		<input type="submit" name="submit" value="' . _('Update') . '" />&nbsp;
		<input type="submit" name="cancel" value="' . _('Delete') . '" onclick="return confirm(\'' . _('Are You Sure You Want To Delete?') . '\');" />
            </div>';
	}
        
echo '</div></form>' ;



echo '<div class="container"><table class="table table-bordered">'
        . '<thead><TR>'
        . '<th>Account Code</th>'
        . '<th>Name</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '</tr></thead><tbody>';
      
       $results=DB_query('Select code,salesman,phone,email from productionEmployee ', $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
            echo sprintf('<tr><td><a href="productionEmployee.php?Modify=%s">%s</a></td>',trim($rows['code']),trim($rows['code']));
            echo '<td>'.$rows['salesman'].'</td>';
            echo '<td>'.trim($rows['phone']).'</td>';
            echo '<td>'.trim($rows['email']).'</td>';
            echo '</tr>';
            
       }        

echo '</tbody></table></div>';

include('includes/footer.inc');


?>
