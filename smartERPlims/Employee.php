<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Employee Maintenance');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if(isset($_POST['submit'])){
    if(!isset($_POST['supplierposting'])){
       prnMsg('You have to select a Employee posting group','error');
       $i++;
    }
    
    if(!isset($_POST['curr_cod'])){
       prnMsg('You have to select a Employee currency','error');
       $i++;
    }
    
    if($i==0){
        if($_POST['submit']=='Add New Employee'){
                       
            $result = DB_query("Select customer from creditors where customer like '".$_POST['customer']."'", $db);
            if(DB_num_rows($result)>0){
                prnMsg('The Employee name '.$_POST['customer'].' already exists','warn');
            } else {
                               
            $sql=sprintf("INSERT INTO `creditors`
           (IsEmployee,`contact` ,`vatregno` ,`customer`  ,`middlen`
           ,`phone` ,`fax` ,`company`,`altcontact`,`email` ,`city` ,`country` 
           ,`inactive` ,`postcode` ,`curr_cod`,`supplierposting`,`firstn`) 
           values (1,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s','%s'  ,'%s'
           ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' ,'%s' )",
            $_POST['contact'] ,$_POST['vatregno'] ,
            $_POST['customer']  ,$_POST['middlen'],
            $_POST['phone'] ,$_POST['fax'] ,$_POST['company'],
            $_POST['altcontact'],$_POST['email'] ,
            $_POST['city'] ,$_POST['country'], 
            $_POST['inactive'] ,$_POST['postcode'] ,$_POST['curr_cod'],
            $_POST['supplierposting'],$_POST['firstn']);
           DB_query($sql, $db);
            }
       }
     // end of create new
       
        if($_POST['submit']=='Update Employee'){
            
          $SQL="UPDATE `creditors`
          SET `contact` = '". $_POST['contact']."'
             ,`vatregno` = '". $_POST['vatregno']."'
             ,`customer` = '".$_POST['customer']."'
             ,`firstn` = '".$_POST['firstn']."'
             ,`middlen` = '".$_POST['middlen']."'
             ,`lastn` = '".$_POST['lastn']."'
             ,`status` = '".$_POST['status']."'
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
             ,`supplierposting` = '".$_POST['supplierposting']."'  
               WHERE `itemcode` ='".$_POST['editcode']."'";
            DB_query($SQL,$db);
            
    }
 }
 // end of validation
}

if($_POST['delete']=='Delete Employee'){
    $result = DB_query("Select * from creditor_trans where acctfolio='".$_POST['editcode']."'", $db);
     if(DB_num_rows($result)>0){
         prnMsg('This account has transactions and cannot be deleted','error');
     }else{
         $result = DB_query("delete from creditors where itemcode='".$_POST['editcode']."'", $db);
         if(DB_num_rows($result)>0){
              prnMsg('This vendor has been deleted','info');
         }
     }
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Employee Maintenance') .'" alt="" />' . ' ' . _('Employee Maintenance') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">'
. '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

If(Isset($_GET['Modify'])){
    echo '<input type="hidden" name="editcode" value="' .$_GET['Modify']. '" />';

    $results = DB_query("Select * from creditors where IsEmployee=1 and  itemcode='".$_GET['Modify']."'", $db);
    if(DB_num_rows($results)==0){
        Die('You have selected an incorrect code');
    } else {
        $myrows= DB_fetch_array($results);
        $_POST['customer']=trim($myrows['customer']);
        $_POST['company']=trim($myrows['company']);
        $_POST['postcode']=$myrows['postcode'];
        $_POST['city']=$myrows['city'];
        $_POST['country']=trim($myrows['country']);
        $_POST['phone']=$myrows['phone'];
        $_POST['fax']=$myrows['fax'];
        $_POST['altcontact']=$myrows['altcontact'];
        $_POST['email']=trim($myrows['email']);
        $_POST['vatregno']=trim($myrows['vatregno']);
        $_POST['curr_cod']=trim($myrows['curr_cod']);
        $_POST['inactive']=$myrows['inactive'];
        $_POST['supplierposting']=$myrows['supplierposting'];
        $_POST['firstn']=$myrows['firstn'];
        $_POST['middlen']=$myrows['middlen'];
        $_POST['status']=$myrows['status'];
        $_POST['lastn']=$myrows['lastn'];
    }
    
echo '<table class="table-bordered" cellspacing="4"><tr><td valign="top" rowspan="2"><table class="table-bordered">'
    . '<caption>Corporate Details</caption>';
echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" maxlength="30" required="required" value="'.$_POST['customer'].'" /></td></tr>';
echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10" value="'.$_POST['phone'].'"  /></td></tr>';
echo '<tr><td>' . _('Email').'</td><td><input type="text" name="email" maxlength="100" ' ;
        ?>   pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"   <?php echo ' value="'.$_POST['email'].'" /></td></tr>';
echo '</table></td><td  valign="top"><table class="table-bordered"><caption>Contact Details</caption>';
echo '<tr><td>' . _('PIN').'</td><td><input type="text" name="vatregno" maxlength="15" value="'.$_POST['vatregno'].'" />' ;
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50" value="'.$_POST['company'].'"  /></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100" value="'.$_POST['postcode'].'"  /></td></tr>';
echo '</table></td></tr></table><table class="table-bordered">'
. '<caption>Posting Details</caption>';
$result=DB_query("SELECT currency,currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2">'
                . '<p>' . _('There are no currencies currently defined ').
                 '<br />'. _(' go to the setup tab of the main menu and set at least one up first') . '</p></td>
                </tr>';
	} else {
		echo '<tr><td>' . _('Vendor Currency') . ':</td><td><select tabindex="17" name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
                    echo '<option '.($_POST['curr_cod']==TRIM($myrow['currabrev'])?'selected="selected"':'').' value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
		}  

		echo '</select></td></tr>';
	}
        
        echo '<tr><td>' . _('Block Account').'</td><td>';
        echo '<select name="inactive">'
                . '<option '.($_POST['inactive']=='0'?'selected="selected"':'').' value="0">No</option>'
                . '<option  '.($_POST['inactive']=='1'?'selected="selected"':'').' value="1">Yes</option>'
                . '</select>';
        echo  '</td></tr>';
        $result=DB_query("SELECT code FROM arpostinggroups",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2"><p>' .
                         _('There are no posting currently defined') 
                        .'<br />'. _('go to the setup tab of the main menu and set at least one up first'). '</p></td>
			</tr>';
	} else {
        echo '<tr><td>'._('Vendor Posting Group').'</td><td>';
        echo '<select name="supplierposting">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option '.($_POST['supplierposting']==$myrow['code']?'selected="selected"':'').' >' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
         
        echo '</table>';
 echo '<div class="centre">
           <input type="submit" name="submit" value="' . _('Update Employee') . '" />&nbsp;
           <input type="submit" name="delete" value="' . _('Delete Employee') . '" onclick="return confirm(\'' . _('Are You Sure You Want To Delete?') . '\');" />
    </div>';

} else {
    
echo '<table class="table-bordered" cellspacing="4"><tr><td valign="top" rowspan="2"><table class="table-bordered">'
    . '<caption>Corporate Details</caption>';
echo '<tr><td>' . _('Name').'</td><td><input type="text" name="customer" maxlength="30" required="required" /></td></tr>';
echo '<tr><td>' . _('Telephone No').'</td><td><input type="text" name="phone" maxlength="10"  /></td></tr>';
echo '<tr><td>' . _('Email').'</td><td><input type="text" name="email" maxlength="100" ' ;
        ?>   pattern="[a-z0-9!#$%&'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"   <?php echo '/></td></tr>';

echo '</table></td><td  valign="top"><table class="table-bordered"><caption>Personal Details</caption>';
echo '<tr><td>' . _('PIN').'</td><td><input type="text" name="vatregno" maxlength="15" />' ;
echo '<tr><td>' . _('Address').'</td><td><input type="text" name="company" maxlength="50"  /></td></tr>';
echo '<tr><td>' . _('Address 2').'</td><td><input type="text" name="postcode" maxlength="100"  /></td></tr>';

echo '</table></td></tr></table><table class="table-bordered">'
. '<caption>Posting Details</caption>';
$result=DB_query("SELECT currency, currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2">'
                . '<p>' . _('There are no currencies currently defined ').
                 '<br />'. _(' go to the setup tab of the main menu and set at least one up first') . '</p></td>
                </tr>';
	} else {
		echo '<tr><td>' . _('Vendor Currency') . ':</td><td><select tabindex="17" name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
                    echo '<option value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
		}  

		echo '</select></td></tr>';
	}
        
        echo '<tr><td>' . _('Block Account').'</td><td>';
        echo '<select name="inactive">'
                . '<option  value="0">No</option>'
                . '<option  value="1">Yes</option>'
                . '</select>';
        echo  '</td></tr>';
       
        
        $result=DB_query("SELECT code FROM arpostinggroups",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2"><p>' .
                         _('There are no posting currently defined') 
                        .'<br />'. _('go to the setup tab of the main menu and set at least one up first'). '</p></td>
			</tr>';
	} else {
        echo '<tr><td>'._('Vendor Posting Group').'</td><td>';
        echo '<select name="supplierposting">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option >' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';
         
        echo '</table>';
   echo '<div class="centre">
            <input type="submit" name="submit" value="' . _('Add New Employee') . '" />&nbsp;
            <input type="Reset" name="Reset" value="' . _('Reset') . '" />
        </div>';
}

        
echo '</div></form>' ;
include('includes/footer.inc');

?>