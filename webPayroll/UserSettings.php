<?php

/* $Id: UserSettings.php 6519 2013-12-26 18:45:22Z rchacon $*/

include('includes/session.inc');
$Title = _('User Settings');
include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/user.png" title="' .
	_('User Settings') . '" alt="" />' . ' ' . _('User Settings') . '</p>';

$PDFLanguages = array(_('Latin Western Languages - Times'),
					_('Eastern European Russian Japanese Korean Hebrew Arabic Thai'),
					_('Chinese'),
					_('Free Serif'));


if (isset($_POST['Modify'])) {
	// no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */


	//!!!for the demo only - enable this check so password is not changed
	if ($AllowDemoMode AND $_POST['Password'] != ''){
		$InputError = 1;
		prnMsg(_('Cannot change password in the demo or others would be locked out!'),'warn');
	}

 	$UpdatePassword = 'N';

	if ($_POST['PasswordCheck'] != ''){
		if (mb_strlen($_POST['Password'])<5){
			$InputError = 1;
			prnMsg(_('The password entered must be at least 5 characters long'),'error');
		} elseif (mb_strstr($_POST['Password'],$_SESSION['UserID'])!= False){
			$InputError = 1;
			prnMsg(_('The password cannot contain the user id'),'error');
		}
		if ($_POST['Password'] != $_POST['PasswordCheck']){
			$InputError = 1;
			prnMsg(_('The password and password confirmation fields entered do not match'),'error');
		}else{
			$UpdatePassword = 'Y';
		}
	}


	if ($InputError != 1) {
		// no errors
		if ($UpdatePassword != 'Y'){
			$sql = "UPDATE www_users
					SET displayrecordsmax='" . $_POST['DisplayRecordsMax'] . "',
						theme='" . $_POST['Theme'] . "',
						language='" . $_POST['Language'] . "',
						email='". $_POST['email'] ."',
						pdflanguage='" . $_POST['PDFLanguage'] . "'
					WHERE userid = '" . $_SESSION['UserID'] . "'";

			$ErrMsg =  _('The user alterations could not be processed because');
			$DbgMsg = _('The SQL that was used to update the user and failed was');

			$result = DB_query($sql,$db, $ErrMsg, $DbgMsg);

			prnMsg( _('The user settings have been updated') . '. ' . _('Be sure to remember your password for the next time you login'),'success');
		} else {
			$sql = "UPDATE www_users
				SET displayrecordsmax='" . $_POST['DisplayRecordsMax'] . "',
					theme='" . $_POST['Theme'] . "',
					language='" . $_POST['Language'] . "',
					email='". $_POST['email'] ."',
					pdflanguage='" . $_POST['PDFLanguage'] . "',
					password='" . CryptPass($_POST['Password']) . "'
				WHERE userid = '" . $_SESSION['UserID'] . "'";

			$ErrMsg =  _('The user alterations could not be processed because');
			$DbgMsg = _('The SQL that was used to update the user and failed was');

			$result = DB_query($sql,$db, $ErrMsg, $DbgMsg);

			prnMsg(_('The user settings have been updated'),'success');
		}
	  // update the session variables to reflect user changes on-the-fly
		$_SESSION['DisplayRecordsMax'] = $_POST['DisplayRecordsMax'];
		$_SESSION['Theme'] = trim($_POST['Theme']); /*already set by session.inc but for completeness */
		$Theme = $_SESSION['Theme'];
		$_SESSION['Language'] = trim($_POST['Language']);
		$_SESSION['PDFLanguage'] = $_POST['PDFLanguage'];
		include ('includes/LanguageSetup.php'); // After last changes in LanguageSetup.php, is it required to update?
	}
}

echo '<div class="container">'
    . '<form class="form-signin" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table class="table-bordered">
		<tr>
			<td><label>' . _('User ID') . ':</label></td>
			<td><label>' . $_SESSION['UserID'] . '</label></td>
		</tr>';

echo '<tr>
		<td><label>' . _('User Name') . ':</lebel></td>
		<td><label>' . $_SESSION['UsersRealName'] . '
		<input type="hidden" name="RealName" value="'.$_SESSION['UsersRealName'].'" /></label></td></tr>';

echo '<tr>
	<td><label>' . _('Language') . ':</label></td>
	<td><select name="Language" class="form-control">';

if (!isset($_POST['Language'])){
	$_POST['Language']=$_SESSION['Language'];
}

foreach ($LanguagesArray as $LanguageEntry => $LanguageName){
	if (isset($_POST['Language']) AND $_POST['Language'] == $LanguageEntry){
		echo '<option selected="selected" value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	} elseif (!isset($_POST['Language']) AND $LanguageEntry == $DefaultLanguage) {
		echo '<option selected="selected" value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	} else {
		echo '<option value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	}
}
echo '</select></td></tr>';


if (!isset($_POST['PasswordCheck'])) {
	$_POST['PasswordCheck']='';
}
if (!isset($_POST['Password'])) {
	$_POST['Password']='';
}
echo '<tr><td><label>' . _('New Password') . ':</label></td>
		<td><input class="form-control" type="password" name="Password" pattern="(?!^'.$_SESSION['UserID'].'$).{5,}" title="'._('Must be more than 5 characters and cannot be as same as userid').'" placeholder="'._('More than 5 characters').'" size="20" value="' .  $_POST['Password'] . '" /></td>
	</tr>
	<tr><td><label>' . _('Confirm Password') . ':</label></td>
		<td><input class="form-control" type="password" name="PasswordCheck" pattern="(?!^'.$_SESSION['UserID'].'$).{5,}" title="'._('Must be more than 5 characters and cannot be as same as userid').'" placeholder="'._('More than 5 characters').'" size="20"  value="' . $_POST['PasswordCheck'] . '" /></td>
	</tr>
	<tr><td colspan="2" align="center"><i>' . _('If you leave the password boxes empty your password will not change') . '</i></td>
	</tr>
	<tr><td><label>' . _('Email') . ':</label></td>';

$sql = "SELECT email from www_users WHERE userid = '" . $_SESSION['UserID'] . "'";
$result = DB_query($sql,$db);
$myrow = DB_fetch_array($result);
if(!isset($_POST['email'])){
	$_POST['email'] = $myrow['email'];
}

echo '<td><input class="form-control" type="email" name="email" size="40" value="' . $_POST['email'] . '" /></td>
	</tr>';

if (!isset($_POST['PDFLanguage'])){
	$_POST['PDFLanguage']=$_SESSION['PDFLanguage'];
}

echo '<tr><td><label>' . _('PDF Language Support') . ':</label></td>
		<td><select name="PDFLanguage" class="form-control">';

for($i=0;$i<count($PDFLanguages);$i++){
	if ($_POST['PDFLanguage']==$i){
		echo '<option selected="selected" value="' . $i .'">' . $PDFLanguages[$i] . '</option>';
	} else {
		echo '<option value="' . $i .'">' . $PDFLanguages[$i]. '</option>';
	}
}
echo '</select></td>
	</tr>
	</table>
	<br />
	<div class="centre"><input class="btn" type="submit" name="Modify" value="' . _('Modify') . '" /></div>
   	</form></div>';

include('includes/footer.inc');
?>
