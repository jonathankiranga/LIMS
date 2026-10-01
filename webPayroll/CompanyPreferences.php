<?php

/* $Id: CompanyPreferences.php 6494 2013-12-11 20:33:46Z rchacon $*/

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

	

	if ($InputError !=1){

		$sql = "UPDATE companies SET coyname='" . $_POST['CoyName'] . "',
                        companynumber = '" . $_POST['CompanyNumber'] . "',
                        gstno='" . $_POST['GSTNo'] . "',
                        regoffice1='" . $_POST['RegOffice1'] . "',
                        regoffice2='" . $_POST['RegOffice2'] . "',
                        regoffice3='" . $_POST['RegOffice3'] . "',
                        regoffice4='" . $_POST['RegOffice4'] . "',
                        regoffice5='" . $_POST['RegOffice5'] . "',
                        regoffice6='" . $_POST['RegOffice6'] . "',
                        telephone='" . $_POST['Telephone'] . "',
                        fax='" . $_POST['Fax'] . "',
                        email='" . $_POST['Email'] . "',
                        currencydefault='" . $_POST['CurrencyDefault'] . "',
                        debtorsact='" . $_POST['DebtorsAct'] . "',
                        pytdiscountact='" . $_POST['PytDiscountAct'] . "',
                        creditorsact='" . $_POST['CreditorsAct'] . "',
                        payrollact='" . $_POST['PayrollAct'] . "',
                        grnact='" . $_POST['GRNAct'] . "',
                        exchangediffact='" . $_POST['ExchangeDiffAct'] . "',
                        purchasesexchangediffact='" . $_POST['PurchasesExchangeDiffAct'] . "',
                        retainedearnings='" . $_POST['RetainedEarnings'] . "',
                        gllink_debtors='" . $_POST['GLLink_Debtors'] . "',
                        gllink_creditors='" . $_POST['GLLink_Creditors'] . "',
                        gllink_stock='" . $_POST['GLLink_Stock'] ."',
                        freightact='" . $_POST['FreightAct'] . "'
                WHERE coycode=1";

			$ErrMsg =  _('The company preferences could not be updated because');
			$result = DB_query($sql,$db,$ErrMsg);
			prnMsg( _('Company preferences updated'),'success');

			/* Alter the exchange rates in the currencies table */

			/* Get default currency rate */
			$sql="SELECT rate from currencies WHERE currabrev='" . $_POST['CurrencyDefault'] . "'";
			$result = DB_query($sql,$db);
			$myrow = DB_fetch_row($result);
			$NewCurrencyRate=$myrow[0];

			/* Set new rates */
			$sql="UPDATE currencies SET rate=rate/" . $NewCurrencyRate;
			$ErrMsg =  _('Could not update the currency rates');
			$result = DB_query($sql,$db,$ErrMsg);

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

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table-bordered">';

if ($InputError != 1) {
	$sql = "SELECT coyname,
                        gstno,
                        companynumber,
                        regoffice1,
                        regoffice2,
                        regoffice3,
                        regoffice4,
                        regoffice5,
                        regoffice6,
                        telephone,
                        fax,
                        email,
                        currencydefault,
                        debtorsact,
                        pytdiscountact,
                        creditorsact,
                        payrollact,
                        grnact,
                        exchangediffact,
                        purchasesexchangediffact,
                        retainedearnings,
                        gllink_debtors,
                        gllink_creditors,
                        gllink_stock,
                        freightact
                FROM companies
                WHERE coycode=1";

	$ErrMsg =  _('The company preferences could not be retrieved because');
	$result = DB_query($sql, $db,$ErrMsg);


	$myrow = DB_fetch_array($result);

	$_POST['CoyName'] = $myrow['coyname'];
	$_POST['GSTNo'] = $myrow['gstno'];
	$_POST['CompanyNumber']  = $myrow['companynumber'];
	$_POST['RegOffice1']  = $myrow['regoffice1'];
	$_POST['RegOffice2']  = $myrow['regoffice2'];
	$_POST['RegOffice3']  = $myrow['regoffice3'];
	$_POST['RegOffice4']  = $myrow['regoffice4'];
	$_POST['RegOffice5']  = $myrow['regoffice5'];
	$_POST['RegOffice6']  = $myrow['regoffice6'];
	$_POST['Telephone']  = $myrow['telephone'];
	$_POST['Fax']  = $myrow['fax'];
	$_POST['Email']  = $myrow['email'];
	$_POST['CurrencyDefault']  = $myrow['currencydefault'];
	$_POST['DebtorsAct']  = $myrow['debtorsact'];
	$_POST['PytDiscountAct']  = $myrow['pytdiscountact'];
	$_POST['CreditorsAct']  = $myrow['creditorsact'];
	$_POST['PayrollAct']  = $myrow['payrollact'];
	$_POST['GRNAct'] = $myrow['grnact'];
	$_POST['ExchangeDiffAct']  = $myrow['exchangediffact'];
	$_POST['PurchasesExchangeDiffAct']  = $myrow['purchasesexchangediffact'];
	$_POST['RetainedEarnings'] = $myrow['retainedearnings'];
	$_POST['GLLink_Debtors'] = $myrow['gllink_debtors'];
	$_POST['GLLink_Creditors'] = $myrow['gllink_creditors'];
	$_POST['GLLink_Stock'] = $myrow['gllink_stock'];
	$_POST['FreightAct'] = $myrow['freightact'];
}

echo '<tr>
		<td>' . _('Name') . ' (' . _('to appear on reports') . '):</td>
		<td><input '.(in_array('CoyName',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="1" type="text" autofocus="autofocus" required="required" name="CoyName" value="' . stripslashes($_POST['CoyName']) . '"  pattern="?!^ +$"  title="' . _('Enter the name of the business. This will appear on all reports and at the top of each screen. ') . '" size="52" maxlength="50" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Official Company Number') . ':</td>
		<td><input '.(in_array('CoyNumber',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="2" type="text" name="CompanyNumber" value="' . $_POST['CompanyNumber'] . '" size="22" maxlength="20" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Tax Authority Reference') . ':</td>
		<td><input '.(in_array('TaxRef',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="3" type="text" name="GSTNo" value="' . $_POST['GSTNo'] . '" size="22" maxlength="20" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 1') . ':</td>
		<td><input '.(in_array('RegOffice1',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="4" type="text" name="RegOffice1" title="' . _('Enter the first line of the company registered office. This will appear on invoices and statements.') . '" required="required" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice1']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 2') . ':</td>
		<td><input '.(in_array('RegOffice2',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="5" type="text" name="RegOffice2" title="' . _('Enter the second line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice2']) . '" /></td>
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
	</tr>';


$result=DB_query("SELECT currabrev, currency FROM currencies",$db);
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.

echo '<tr>
		<td>' . _('Home Currency') . ':</td>
		<td><select tabindex="13" name="CurrencyDefault">';

while ($myrow = DB_fetch_array($result)) {
	if ($_POST['CurrencyDefault']==$myrow['currabrev']){
		echo '<option selected="selected" value="'. $myrow['currabrev'] . '">' . $CurrencyName[$myrow['currabrev']] . '</option>';
	} else {
		echo '<option value="' . $myrow['currabrev'] . '">' . $CurrencyName[$myrow['currabrev']] . '</option>';
	}
} //end while loop

DB_free_result($result);

echo '</select></td>
	</tr>';
echo '</table><br /><div class="centre"><input tabindex="26" class="btn" type="submit" name="submit" value="' . _('Update') . '" /></div>';

echo '</div></form>';

include('includes/footer.inc');
?>
