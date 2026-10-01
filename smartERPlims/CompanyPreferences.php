<?php
include('includes/session.inc');

$Title = _('Company Preferences');
/* webERP manual links before header.inc */
$ViewTopic= 'CreatingNewSystem';
$BookMark = 'CompanyParameters';
include('includes/header.inc');

if (isset($Errors)) {
    unset($Errors);
}

//initialise no input errors assumed initially before we test
$InputError = 0;
$Errors = array();
$i=1;

$Da=array();

$ResultIndex=DB_query('SELECT `id`,`Dimension_type` FROM `DimensionSetUp`', $db);
while($rows=DB_fetch_array($ResultIndex)){
    $Da[$rows['id']]= $rows['Dimension_type'];
}



if (isset($_POST['submit'])) {


	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (mb_strlen($_POST['CoyName']) > 50 OR mb_strlen($_POST['CoyName'])==0) {
		$InputError = 1;
		prnMsg(_('The company name must be entered and be fifty characters or less long'), 'error');
		$Errors[$i] = 'CoyName';
		$i++;
	}

	if (mb_strlen($_POST['Email'])>0 and !IsEmailAddress($_POST['Email'])) {
		$InputError = 1;
		prnMsg(_('The email address is not correctly formed'),'error');
		$Errors[$i] = 'Email';
		$i++;
	}

	if ($InputError !=1){
		$sql = "UPDATE companies SET coyname='" . $_POST['CoyName'] . "',
                        PIN = '" . $_POST['PIN'] . "',
                        vat = '" . $_POST['VAT'] . "',
                        CoyAuthorisedBy ='" . $_POST['CoyAuthorisedBy'] . "',
                        regoffice1='" . $_POST['RegOffice1'] . "',
                        regoffice2='" . $_POST['RegOffice2'] . "',
                        regoffice3='" . $_POST['RegOffice3'] . "',
                        regoffice4='" . $_POST['RegOffice4'] . "',
                        regoffice5='" . $_POST['RegOffice5'] . "',
                        regoffice6='" . $_POST['RegOffice6'] . "',
                        telephone='" . $_POST['Telephone'] . "',
                        " . ($_POST['DefaultDimension_1'] !== '' ? "`DefaultDimension_1`='" . (int)$_POST['DefaultDimension_1'] . "'," : "") . "
                        " . ($_POST['DefaultDimension_2'] !== '' ? "`DefaultDimension_2`='" . (int)$_POST['DefaultDimension_2'] . "'," : "") . "
                        `Commision`='" .$_POST['Commision'] . "',
                        `CommissionRetention`='" .$_POST['CommissionRetention'] . "',
                        `ReduceCommissionRetention`='" .$_POST['ReduceCommissionRetention'] . "',
                        fax='" . $_POST['Fax'] . "',
                        email='" . $_POST['Email'] . "'
                        WHERE coycode=1";

			$ErrMsg =  _('The company preferences could not be updated because');
			$result = DB_query($sql,$db,$ErrMsg);
			prnMsg( _('Company preferences updated'),'success');
			/* End of update currencies */

			$ForceConfigReload = True; // Required to force a load even if stored in the session vars
			include('includes/GetConfig.php');
			$ForceConfigReload = False;

	} else {
		prnMsg( _('Validation failed') . ', ' . _('no updates or deletes took place'),'warn');
	}

} /* end of if submit */

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') .
		'" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if ($InputError != 1) {
	$sql = "SELECT CoyAuthorisedBy,coyname,
                        `PIN`,
                        `vat`,
                        regoffice1,
                        regoffice2,
                        regoffice3,
                        regoffice4,
                        regoffice5,
                        regoffice6,
                        telephone,
                        fax,
                        email,
                        DefaultDimension_1,
                        DefaultDimension_2,
                        `Commision` ,
                        `CommissionRetention` ,
                        `ReduceCommissionRetention` 
                        FROM companies
                WHERE coycode=1";

	$ErrMsg =  _('The company preferences could not be retrieved because');
	$result = DB_query($sql, $db,$ErrMsg);

	$myrow = DB_fetch_array($result);
        $_POST['CoyAuthorisedBy']= $myrow['CoyAuthorisedBy'];
	$_POST['CoyName'] = $myrow['coyname'];
	$_POST['PIN']  = $myrow['PIN'];
        $_POST['VAT']  = $myrow['vat'];
	$_POST['RegOffice1']  = $myrow['regoffice1'];
	$_POST['RegOffice2']  = $myrow['regoffice2'];
	$_POST['RegOffice3']  = $myrow['regoffice3'];
	$_POST['RegOffice4']  = $myrow['regoffice4'];
	$_POST['RegOffice5']  = $myrow['regoffice5'];
	$_POST['RegOffice6']  = $myrow['regoffice6'];
	$_POST['Telephone']   = $myrow['telephone'];
	$_POST['Fax']         = $myrow['fax'];
	$_POST['Email']       = $myrow['email'];
        $_POST['DefaultDimension_1']  =$myrow['DefaultDimension_1'];
	$_POST['DefaultDimension_2']  =$myrow['DefaultDimension_2'];
        $_POST['Commision']           =$myrow['Commision'];
	$_POST['CommissionRetention']  =$myrow['CommissionRetention'];
        $_POST['ReduceCommissionRetention']  =$myrow['ReduceCommissionRetention'];
	
}
echo '<table class="table table-bordered"><tr><td><table class="table table-bordered">';
echo '<tr>
		<td>' . _('Authorise Invoices') . ' (' . _('to appear on Invoices') . '):</td>
		<td><input '.(in_array('CoyAuthorisedBy',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="1" type="text" autofocus="autofocus" required="required" name="CoyAuthorisedBy" value="' . stripslashes($_POST['CoyAuthorisedBy']) . '"  pattern="?!^ +$"  title="' . _('Enter the name of the business. This will appear on all reports and at the top of each screen. ') . '" size="52" maxlength="50" /></td>
	</tr>';
echo '<tr>
		<td>' . _('Name') . ' (' . _('to appear on reports') . '):</td>
		<td><input '.(in_array('CoyName',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="1" type="text" autofocus="autofocus" required="required" name="CoyName" value="' . stripslashes($_POST['CoyName']) . '"  pattern="?!^ +$"  title="' . _('Enter the name of the business. This will appear on all reports and at the top of each screen. ') . '" size="52" maxlength="50" /></td>
	</tr>';

echo '<tr>
		<td>' . _('PIN') . ':</td>
		<td><input '.(in_array('PIN',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="2" type="text" required="required"  name="PIN" value="' . $_POST['PIN'] . '" size="22" maxlength="20" /></td>
	</tr>';
echo '<tr>
		<td>' . _('VAT') . ':</td>
		<td><input '.(in_array('VAT',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="3" type="text" required="required"  name="VAT" value="' . $_POST['VAT'] . '" size="22" maxlength="20" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 1') . ':</td>
		<td><input '.(in_array('RegOffice1',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="4" type="text" name="RegOffice1" title="' . _('Enter the first line of the company registered office. This will appear on invoices and statements.') . '" required="required" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice1']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 2') . ':</td>'
        . ' <td><input '.(in_array('RegOffice2',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="5" type="text" name="RegOffice2" title="' . _('Enter the second line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice2']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 3') . ':</td>
		<td><input '.(in_array('RegOffice3',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="6" type="text" name="RegOffice3" title="' . _('Enter the third line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice3']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 4') . ':</td>
		<td><input '.(in_array('RegOffice4',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="7" type="text" name="RegOffice4" title="' . _('Enter the fourth line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice4']) . '" /></td>
</tr>';

echo '<tr>
		<td>' . _('Address Line 5') . ':</td>
		<td><input '.(in_array('RegOffice5',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="8" type="text" name="RegOffice5" size="22" maxlength="20" value="' . stripslashes($_POST['RegOffice5']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 6') . ':</td>
		<td><input '.(in_array('RegOffice6',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="9" type="text" name="RegOffice6" size="17" maxlength="15" value="' . stripslashes($_POST['RegOffice6']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Telephone Number') . ':</td>
		<td><input '.(in_array('Telephone',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="10" type="tel" name="Telephone" required="required" title="' . _('Enter the main telephone number of the company registered office. This will appear on invoices and statements.') . '" size="26" maxlength="25" value="' . $_POST['Telephone'] . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Facsimile Number') . ':</td>
		<td><input '.(in_array('Fax',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="11" type="text" name="Fax" size="26" maxlength="25" value="' . $_POST['Fax'] . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Email Address') . ':</td>
		<td><input '.(in_array('Email',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="12" type="email" name="Email" title="' . _('Enter the main company email address. This will appear on invoices and statements.') . '" required="required" placeholder="accounts@example.com" size="50" maxlength="55" value="' . $_POST['Email'] . '" /></td>
	</tr></table></td><td>';

echo '<table class="table table-bordered">';
echo '<tr><td><B>Sales Comission Info</B></td></tr>';

echo '<tr>
        <td>' . _('Commision % on Excess Price') . ':</td>
        <td><input '.(in_array('Commision',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="13" type="text" class="number" name="Commision"  placeholder="0.5 for 50 %" size="50" maxlength="4" value="' . $_POST['Commision'] . '" /></td>
	</tr>';

echo '<tr>
       <td>' . _('Commission Retention %') . ':</td>
       <td><input '.(in_array('CommissionRetention',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="14" type="text" class="number" name="CommissionRetention"  placeholder="0.5 for 50% to be retained by the company" size="50" maxlength="4" value="' . $_POST['CommissionRetention'] . '" /></td>
    </tr>';

echo '<tr>
       <td>' . _('Reduce Commission Retention after each Shilling by') . ':</td>
       <td><input '.(in_array('ReduceCommissionRetention',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="15" type="text" class="number"  name="ReduceCommissionRetention"  placeholder="0.1 for after 1% retention is reduced" size="50" maxlength="4" value="' . $_POST['ReduceCommissionRetention'] . '" /></td>
    </tr>';


echo '<tr><td><B>Project Management Info</B></td></tr>';
echo '<tr><td><i>' . _('Dimension Type One') . ':</i></td><td><select name="DefaultDimension_1" tabindex="16"><option></option>';
foreach ($Da as $key => $value) {
    echo '<option value="'.$key.'"  '.(($_POST['DefaultDimension_1']==trim($key))?'selected="selected"':'').'>'.$value.'</option>';
}
echo  '</select></td></tr>';

echo '<tr><td><i>' . _('Dimension Type Two') . ':</i></td><td><select name="DefaultDimension_2" tabindex="17"><option></option>';
foreach ($Da as $key => $value) {     
    echo '<option value="'.$key.'"  '.(($_POST['DefaultDimension_2']==trim($key))?'selected="selected"':'').'>'.$value.'</option>';
       
}
echo '</select></td></tr>';


echo '</table><br /><div class="centre"><input tabindex="26" type="submit" name="submit" value="' . _('Update') . '" /></div>';
echo '</div></form>';

include('includes/footer.inc');
?>
