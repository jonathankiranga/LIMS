<?php

ini_set('session.gc_maxlifetime', 3600);
session_name('ErpWithCRM');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($PathPrefix)) {
    $PathPrefix='';
}

if (!file_exists($PathPrefix . 'config.php')){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));
    if ($RootPath == '/' OR $RootPath == "\\") {
        $RootPath = '';
    }
    header('Location:' . $RootPath . '/installer/index.php');
    exit;
}

include($PathPrefix . 'config.php');

if (isset($dbuser)) {
    $DBUser=$dbuser;
    $DBPassword=$dbpassword;
    $DBType=$dbType;
}

// Ensure a database is selected even when the login form submits without a company value.
if (empty($_SESSION['DatabaseName']) && !isset($DatabaseName) && !empty($DefaultDatabase)) {
    $DatabaseName = $DefaultDatabase;
}



if (!isset($SysAdminEmail)) {
    $SysAdminEmail='';
}


include($PathPrefix . 'includes/ConnectDB.inc');
include($PathPrefix . 'includes/DateFunctions.inc');

// Ensure FormID is always set for CSRF protection
if (!isset($_SESSION['FormID'])) {
	$_SESSION['FormID'] = sha1(uniqid(mt_rand(), true));
}



if (isset($_SESSION['DatabaseName'])){
    
	foreach ($_POST as $PostVariableName => $PostVariableValue) {
		// Skip FormID - it's a generated hash and should not be escaped
		if ($PostVariableName === 'FormID') {
			continue;
		}
		if (gettype($PostVariableValue) != 'array') {
			$_POST[$PostVariableName] = $PostVariableValue;
		} else {
			foreach ($PostVariableValue as $PostArrayKey => $PostArrayValue) {
				$PostVariableValue[$PostArrayKey] = $PostArrayValue;
			}
		}
	}

	/* iterate through all elements of the $_GET array and DB_escape_string them
	to limit possibility for SQL injection attacks and cross scripting attacks
	*/
	foreach ($_GET as $GetKey => $GetValue) {
		if (gettype($GetValue) != 'array') {
			$_GET[$GetKey] = DB_escape_string($GetValue);
		}
	}
        
} else { //set SESSION['FormID'] before the a user has even logged in
	// FormID is now initialized earlier in the file
}


include($PathPrefix . 'includes/LanguageSetup.php');

if (!isset($AllowAnyone)){ /* only do security checks if AllowAnyone is not true */

	include $PathPrefix . 'includes/UserLogin.php';	/* Login checking and setup */

	if (isset($_POST['UserNameEntryField']) AND isset($_POST['Password'])) {
            $rc = userLogin($_POST['UserNameEntryField'], $_POST['Password'], $SysAdminEmail, $db);
	} elseif (empty($_SESSION['DatabaseName'])) {
            $rc = UL_SHOWLOGIN;
	} else {
            $rc = UL_OK;
	}

        $_SESSION['Theme'] = (isset($_SESSION['Theme'])) ? $_SESSION['Theme'] :'xenos';
	/*  Need to set the theme to make login screen nice */
	$Theme =  $_SESSION['Theme'] ;
	switch ($rc) {
	case  UL_OK; //user logged in successfully
		include($PathPrefix . 'includes/LanguageSetup.php'); //set up the language
		break;

	case UL_SHOWLOGIN:
         
		include($PathPrefix . 'includes/Login.php');
		exit;

	case UL_BLOCKED:
		die(include($PathPrefix . 'includes/FailedLogin.php'));

	case  UL_CONFIGERR:
		$Title = _('Account Error Report');
		include($PathPrefix . 'includes/header.inc');
		echo '<br /><br /><br />';
		prnMsg(_('Your user role does not have any access defined. There is an error in the security setup for this user account'),'error');
		include($PathPrefix . 'includes/footer.inc');
		exit;

	case  UL_NOTVALID:
		$demo_text = '<font size="3" color="red"><b>' .  _('incorrect password') . '</b></font><br /><b>' . _('The user/password combination') . '<br />' . _('is not a valid user of the system') . '</b>';
		die(include($PathPrefix . 'includes/Login.php'));

	case  UL_MAINTENANCE:
		$demo_text = '<font size="3" color="red"><b>' .  _('system maintenance') . '</b></font><br /><b>' . _('webERP is not available right now') . '<br />' . _('during maintenance of the system') . '</b>';
		die(include($PathPrefix . 'includes/Login.php'));

	}
        /* only do security checks if AllowAnyone is not true */
}elseif(basename($_SERVER['SCRIPT_NAME'])=='Logout.php'){
	header('Location: index.php');
}

/*User is logged in so get configuration parameters  - save in session*/
include($PathPrefix . 'includes/GetConfig.php');

/*If the Code $Version - held in ConnectDB.inc is > than the Database VersionNumber held in config table then do upgrades */


//purge the audit trail if necessary
if (isset($_SESSION['MonthsAuditTrail'])){
	 $sql = "DELETE FROM audittrail	WHERE  transactiondate <= '" . Date('Y-m-d', mktime(0,0,0, Date('m')-$_SESSION['MonthsAuditTrail'])) . "'";
	$ErrMsg = _('There was a problem deleting expired audit-trail history');
//	$result = DB_System($sql,$db);
}
/*Check to see if currency rates need to be updated */
$_SESSION['UpdateCurrencyRatesDaily']=0;
if (isset($_SESSION['UpdateCurrencyRatesDaily'])){
	if ($_SESSION['UpdateCurrencyRatesDaily']!=0)  {
		if (DateDiff(Date($_SESSION['DefaultDateFormat']),
			ConvertSQLDate($_SESSION['UpdateCurrencyRatesDaily']),'d')> 0){

			if ($_SESSION['ExchangeRateFeed']=='ECB') {
				$CurrencyRates = GetECBCurrencyRates(); // gets rates from ECB see includes/MiscFunctions.php
				/*Loop around the defined currencies and get the rate from ECB */
				if ($CurrencyRates!=false) {
					$CurrenciesResult = DB_System("SELECT currabrev FROM currencies",$db);
					while ($CurrencyRow = DB_fetch_array($CurrenciesResult)){
						if ($CurrencyRow['currabrev']!=$_SESSION['CompanyRecord']['currencydefault']){
							$UpdateCurrRateResult = DB_System("UPDATE currencies SET rate='" . GetCurrencyRate($CurrencyRow['currabrev'],$CurrencyRates) . "'
                                                            WHERE currabrev='" . $CurrencyRow['currabrev'] . "'",$db);
						}
					}
				}
			} else {
				$CurrenciesResult = DB_System("SELECT currabrev FROM currencies",$db);
				while ($CurrencyRow = DB_fetch_row($CurrenciesResult)){
					if ($CurrencyRow['currabrev']!=$_SESSION['CompanyRecord']['currencydefault']){
						$UpdateCurrRateResult = DB_System("UPDATE currencies SET rate='" . google_currency_rate($CurrencyRow['currabrev']) . "'
															WHERE currabrev='" . $CurrencyRow['currabrev'] . "'",$db);
					}
				}
			}
			$_SESSION['UpdateCurrencyRatesDaily'] = Date('Y-m-d');
			$UpdateConfigResult = DB_System("UPDATE config SET confvalue = '" . Date('Y-m-d') . "' WHERE confname='UpdateCurrencyRatesDaily'",$db);
		}
	}
}




If (isset($_POST['Theme']) AND ($_SESSION['UsersRealName'] == $_POST['RealName'])) {
	$_SESSION['Theme'] = $_POST['Theme'];
	$Theme = $_POST['Theme'];
} elseif (!isset($_SESSION['Theme'])) {
	$Theme = $_SESSION['DefaultTheme'];
	$_SESSION['Theme'] = $_SESSION['DefaultTheme'];
} else {
	$Theme = $_SESSION['Theme'];
}

/* Set the logo if not yet set.
 * will be done only once per session and each time
 * we are not in session (i.e. before login)
 */
if (empty($_SESSION['LogoFile'])) {
	/* find a logo in companies/$CompanyDir
	 * (nice side effect of function:
	 * variables are local, so we will never
	 * cause name clashes)
	 */

	function findLogoFile($CompanyDir, $PathPrefix) {
		$dir = rtrim($PathPrefix,'/\\') . '/companies/' . $CompanyDir . '/';
		if (!is_dir($dir) || !is_readable($dir)) {
			return null;
		}
		$DirHandle = dir($dir);
		$InCompanyDir = array();
		while (($DirEntry = $DirHandle->read()) !== false ){
			if ($DirEntry != '.' AND $DirEntry !='..'){
				$InCompanyDir[] = $DirEntry; //make an array of all files under company directory
			}
		} //loop through list of files in the company directory
		$DirHandle->close();
		if (!empty($InCompanyDir)) {
			foreach($InCompanyDir as $logofilename) {
				if (strncasecmp($logofilename,'logo.png',8) === 0 AND
					is_readable($dir . $logofilename) AND
					is_file($dir . $logofilename)) {
					$logo = $logofilename;
					break;
				}
			}
			if (!isset($logo)) {
				foreach($InCompanyDir as $logofilename) {
					if (strncasecmp($logofilename,'logo.jpg',8) === 0 AND
						is_readable($dir . $logofilename) AND
						is_file($dir . $logofilename)) {
						$logo = $logofilename;
						break;
					}
				}
			}
			if (empty($logo)) {
				return null;
			} else {
				return 'companies/' .$CompanyDir .'/'. $logo;
			}
		} //end listing of files under company directory is not empty
	}

	/* Find a logo in companies/<company of this session> */
	if (!empty($_SESSION['DatabaseName'])) {
		$_SESSION['LogoFile'] = findLogoFile($_SESSION['DatabaseName'], $PathPrefix);
	}
}

// Now check that the user as logged in has access to the page being called. $SecurityGroups is an array of
// arrays defining access for each group of users. These definitions can be modified by a system admin under setup

if (!isset($_SESSION['AllowedPageSecurityTokens'])) {
	$_SESSION['AllowedPageSecurityTokens'] = array();
}

// If this page declares PageSecurity = 0, allow it for any logged-in user
if (isset($PageSecurity) && $PageSecurity === 0 && !in_array(0, $_SESSION['AllowedPageSecurityTokens'])) {
	$_SESSION['AllowedPageSecurityTokens'][] = 0;
}

if ((!is_array($_SESSION['AllowedPageSecurityTokens']) || empty($_SESSION['AllowedPageSecurityTokens'])) && !isset($AllowAnyone)) {
	// Session exists but no security tokens; most likely needs to re-login
	include($PathPrefix . 'includes/Login.php');
	exit;
}

/*The page security variable is now retrieved from the database in GetConfig.php and stored in the $SESSION['PageSecurityArray'] array
 * the key for the array is the script name - the script name is retrieved from the basename ($_SERVER['SCRIPT_NAME'])
 */
if (!isset($PageSecurity)){
//only hardcoded in the UpgradeDatabase script - so old versions that don't have the scripts.pagesecurity field do not choke
	$PageSecurity = $_SESSION['PageSecurityArray'][basename($_SERVER['SCRIPT_NAME'])];
}

if (!isset($AllowAnyone)){
	if (isset($_SESSION['AllowedPageSecurityTokens']) AND ((!in_array($PageSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity)))) {
		$Title = _('Security Permissions Problem');
		include($PathPrefix . 'includes/header.inc');
		echo '<tr><td class="menu_group_items">'
                . '<table width="100%" class="table_index"><tr><td class="menu_group_item">';
		echo '<b><font style="size:+1; text-align:center;">' . _('The security settings on your account do not permit you to access this function') . '</font></b>';
		echo '</td></tr></table></td></tr>';

		include($PathPrefix . 'includes/footer.inc');
		exit;
	}
}


/*System administrator login */
if (in_array($_SESSION['PageSecurityArray']['WWW_Users.php'], $_SESSION['AllowedPageSecurityTokens'])) {
	$debug = 1; //allow debug messages
} else {
	$debug = 0; //don't allow debug messages
}

function CryptPass( $Password ) {
        global $CryptFunction;
        if ($CryptFunction == 'sha1' ) {
            return sha1($Password);
        } elseif ($CryptFunction == 'md5' ) {
           return md5($Password);
        } else {
          return $Password;
        }
 }


/*
 * Security check to ensure that the form submitted is 
 * originally sourced from webERP with the FormID = $_SESSION['FormID'] - which is set before the first login
 */
if (sizeof($_POST) > 0 AND !isset($AllowAnyone)) {
    if (!isset($_POST['FormID']) OR ($_POST['FormID'] != $_SESSION['FormID'])) {
        $Title = _('Error in form verification');
        include('includes/header.inc');
        echo 'FormID:'.($_POST['FormID']).'<br/>SESSION:'.($_SESSION['FormID']);
        prnMsg(_('This form was not submitted with a correct ID') , 'error');
        include('includes/footer.inc');
        exit;
    }
}


include($PathPrefix . 'includes/DimensionClass.inc');


if(!isset($_POST['curr_cod'])){
    $_POST['curr_cod']=$_SESSION['CompanyRecord']['currencydefault'];
}
