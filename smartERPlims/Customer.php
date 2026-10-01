<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');

$Title = _('Customer Maintenance');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Customer') .'" alt="" />' . ' ' . _('Customer Maintenance') . '</p>';

// Import button
echo '<p><a class="btn btn-info" href="import_excel.php?type=customer">' . _('Import from Excel (CSV)') . '</a></p>';

$i=0;
if(isset($_POST['submit'])){
    if(!isset($_POST['customerposting'])){
       prnMsg('You have to select a customer posting group','error');
       $i++;
    }
    
    if(!isset($_POST['curr_cod'])){
       prnMsg('You have to select a customer currency','error');
       $i++;
    }
    
    if($i==0){
        if($_POST['submit']=='Add New Customer'){
                       
            $result = DB_query("Select customer from debtors where customer like '".$_POST['customer']."'", $db);
            if(DB_num_rows($result)>0){
                prnMsg('The customer name '.$_POST['customer'].' already exists','warn');
            } else {
                               
             $ID= Triger_debtors($descript);
                
            $sql=sprintf("INSERT INTO `debtors`
           (itemcode,`contact` ,`creditlimit` ,`customer`  ,`middlen`
           ,`phone` ,`fax` ,`company`,`altcontact`,`email` ,`city` ,`country` 
           ,`inactive` ,`postcode` ,`curr_cod`,`customerposting`,`salesman`) 
           values ('%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s','%s'  ,'%s'
           ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' )",$ID,
            $_POST['contact'] ,$_POST['creditlimit'] ,
            $_POST['customer']  ,$_POST['middlen'],
            $_POST['phone'] ,$_POST['fax'] ,$_POST['company'],
            $_POST['altcontact'],$_POST['email'] ,
            $_POST['city'] ,$_POST['country'], 
            $_POST['inactive'] ,$_POST['postcode'] ,$_POST['curr_cod'],
            $_POST['customerposting'],$_POST['salesman']);
           DB_query($sql, $db);
            }
       }
     // end of create new
       
        if($_POST['submit']=='Update Customer'){
            
          $SQL="UPDATE `debtors`
          SET `contact` = '". $_POST['contact']."'
             ,`creditlimit` = '". $_POST['creditlimit']."'
             ,`customer` = '".$_POST['customer']."'
             ,`middlen` = '".$_POST['middlen']."'
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
             ,`username` = '".$_POST['username']."'
             ,`customerposting` = '".$_POST['customerposting']."'
             ,`salesman` = '".$_POST['salesman']."'
             WHERE `itemcode` ='".$_POST['editcode']."'";
                    DB_query($SQL,$db);
    }
 }
 // end of validation
}

if($_POST['delete']=='Delete Customer'){
    $result = DB_query("Select * from debtors_trans where acctfolio='".$_POST['editcode']."'", $db);
     if(DB_num_rows($result)>0){
         prnMsg('This account has transactions and cannot be deleted','error');
     }else{
         $result = DB_query("delete from debtors where itemcode='".$_POST['editcode']."'", $db);
         if(DB_num_rows($result)>0){
              prnMsg('This customer has been deleted','info');
         }
     }
}

if(isset($_GET['Modify']) ){
    
    $result = DB_query("Select * from debtors where itemcode='".$_GET['Modify']."'", $db);
    if(DB_num_rows($result)==0){
        Die('This code is not correct');
       }else{
        $myrows= DB_fetch_array($result);
        $_POST['customer']=$myrows['customer'];
        $_POST['company']=$myrows['company'];
        $_POST['postcode']=$myrows['postcode'];
        $_POST['city']=$myrows['city'];
        $_POST['country']=trim($myrows['country']);
        $_POST['phone']=$myrows['phone'];
        $_POST['fax']=$myrows['fax'];
        $_POST['altcontact']=$myrows['altcontact'];
        $_POST['email']=trim($myrows['email']);
        $_POST['creditlimit']=$myrows['creditlimit'];
        $_POST['curr_cod']=trim($myrows['curr_cod']);
        $_POST['inactive']=$myrows['inactive'];
        $_POST['customerposting']=$myrows['customerposting'];
        $_POST['salesman']=trim($myrows['salesman']);
        $_POST['middlen']=$myrows['middlen'];// Vat PIN
    }
    
    
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">'
        . '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="editcode" value="' . $_GET['Modify'] . '" />';
echo '<table class="table" cellspacing="4"><tr><td valign="top"><table class="table table-bordered"><caption>Corporate Details</caption>';

echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" size="30" required="required" value="'.$_POST['customer'].'"/></td></tr>';
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  value="'.$_POST['company'].'"/></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  value="'.$_POST['postcode'].'"/></td></tr>';
echo '<tr><td>' . _('city').'</td><td><input type="text" name="city" maxlength="50" value="'.$_POST['city'].'"  /></td></tr>';

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
echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  value="'.$_POST['phone'].'"/></td></tr>';
echo '<tr><td>' . _('Contact Name').'</td><td><input type="text" name="fax" maxlength="50"  value="'.$_POST['fax'].'"/></td></tr>';
echo '<tr><td>' . _('Alt Contact').'</td><td><input type="text" name="altcontact" maxlength="100"  value="'.$_POST['altcontact'].'"/></td></tr>';
echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100"  value="'.$_POST['email'].'"' ;
        ?> pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"  <?php echo '/></td></tr>';

echo '</table></td><td  valign="top"><table class="table table-bordered">';
echo '<tr><td>' . _('Credit Period(Days)').'</td><td><input type="text" class="integer" name="creditlimit" maxlength="4" required="required"  value="'.($_POST['creditlimit'] ?? '30').'"/></td></tr>';


        $result=DB_query("SELECT currency, currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2"><p>'._('There are no currencies currently defined - go to the setup tab of the main menu and set at least one up first').'</p></td></tr>';
	} else {
		if (!isset($_POST['curr_cod'])){
			$CurrResult = DB_query("SELECT currencydefault FROM companies WHERE coycode=1",$db);
			$myrow = DB_fetch_row($CurrResult);
			$_POST['curr_cod'] = $myrow[0];
		}
		echo '<tr><td>' . _('Customer Currency') . ':</td>
				<td><select name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
			if ($_POST['curr_cod']==$myrow['currabrev']){
				echo '<option selected="selected" value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
			} else {
				echo '<option value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
			}
		} //end while loop
		DB_data_seek($result,0);

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
        echo '<tr><td>'._('Customer Posting Group').'</td><td>';
        echo '<select name="customerposting">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option '. ($myrow['code']==$_POST['customerposting']?'selected="selected"':''). '>' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
     
        echo '<tr><td>'._('Sales Person').'</td><td>';
           echo '<select name="salesman"><option value="">not selected</option>';
     
        $result=DB_query("select code,salesman from salesrepsinfo",$db);    
        while ($myrow = DB_fetch_array($result)) {
		echo '<option  value="'.$myrow['code'].'"'. ($myrow['code']==$_POST['salesman']?' selected="selected"':''). '>' . $myrow['salesman'] . '</option>';
		} //end while loop
        echo '</select>' ;
         echo  '</td></tr>';
          echo '<tr><td>' . _('PIN').'</td><td><input type="text" name="middlen" maxlength="15" value="'.$_POST['middlen'].'"/>' ;
        
         
echo '</table></td></tr></table>';
    
} else {

echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">'
        . '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top"><table class="table table-bordered"><caption>Corporate Details</caption>';

echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" size="30" required="required" /></td></tr>';
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  /></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  /></td></tr>';
echo '<tr><td>' . _('city').'</td><td><input type="text" name="city" maxlength="50" /></td></tr>';
echo '<tr><td>' . _('Country').'</td><td><select name="country">';
        
foreach ($CountriesArray as $CountryEntry => $CountryName){
    echo '<option value="' . $CountryName . '">' . $CountryName  . '</option>';
}

echo '</select></td></tr>';
echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  /></td></tr>';
echo '<tr><td>' . _('Contact Name').'</td><td><input type="text" name="fax" maxlength="50"  /></td></tr>';
echo '<tr><td>' . _('Alt Contact').'</td><td><input type="text" name="altcontact" maxlength="100"  /></td></tr>';
echo '<tr><td>' . _('email').'</td><td><input type="text" name="email" maxlength="100" ' ;
        ?>   pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"   <?php echo '/></td></tr>';

echo '</table></td><td  valign="top"><table class="table table-bordered">';
echo '<tr><td>' . _('Credit Period(days)').'</td><td><input type="text" class="integer" name="creditlimit" maxlength="4" required="required"  /></td></tr>';


$result=DB_query("SELECT currency, currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2">'
                . '<p>' . _('There are no currencies currently defined ').
                 '<br />'. _(' go to the setup tab of the main menu and set at least one up first') . '</p></td>
                </tr>';
	} else {
		echo '<tr><td>' . _('Customer Currency') . ':</td><td><select tabindex="17" name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
                    echo '<option value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
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
        echo '<tr><td>'._('Customer Posting Group').'</td><td>';
        echo '<select name="customerposting">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option >' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
     
        echo '<tr><td>'._('Sales Person').'</td><td>';
        $result=DB_query("select code,salesman from salesrepsinfo",$db);    
        echo '<select name="salesman"><option value="">not selected</option>';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option value="'.$myrow['code'].'">' . $myrow['salesman'] . '</option>';
		} //end while loop
        echo '</select>' ;
        echo  '</td></tr>';
        
        echo '<tr><td>' . _('PIN').'</td><td><input type="text" name="middlen" maxlength="15" />' ;

        echo '</table></td></tr></table>';

}

if (!isset($_GET['Modify'])) {
		echo '<div class="centre">
                        <input type="submit" name="submit" value="' . _('Add New Customer') . '" />&nbsp;
                        <input type="Reset" name="Reset" value="' . _('Reset') . '" />
                    </div>';
	} else {
	   echo '<br />
		<div class="centre">
		<input type="submit" name="submit" value="' . _('Update Customer') . '" />&nbsp;
		<input type="submit" name="delete" value="' . _('Delete Customer') . '" onclick="return confirm(\'' . _('Are You Sure You Want To Delete?') . '\');" />
            </div>';
	}
        
echo '</div></form>' ;


include('includes/footer.inc');


?>
