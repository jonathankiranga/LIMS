<?php
include('includes/session.inc');
$Title = _('System Parameters');
$ViewTopic= 'GettingStarted';
$BookMark = 'SystemConfiguration';
include('includes/header.inc');
include('includes/CountriesArray.php');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Supplier Types')
	. '" alt="" />' . $Title. '</p>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
$InputError = 0;

if($InputError !=1){
    $sql = array();
    

    if ($_SESSION['ManualNumber'] != $_POST['X_ManualNumber'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_ManualNumber']."' WHERE confname = 'ManualNumber'";
    }
if ($_SESSION['WithholdingTaxGlAccount'] != $_POST['X_WithholdingTaxGlAccount'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_WithholdingTaxGlAccount']."' WHERE confname = 'WithholdingTaxGlAccount'";
    } 
    
if ($_SESSION['SINGLEUSER'] != $_POST['X_SINGLEUSER'] ) {
        $sql[] = "UPDATE config SET confvalue = '".$_POST['X_SINGLEUSER']."' WHERE confname = 'SINGLEUSER'";
} 

if ($_SESSION['WithholdingTaxRate'] != $_POST['X_WithholdingTaxRate'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_WithholdingTaxRate']."' WHERE confname = 'WithholdingTaxRate'";
    } 
    
    if ($_SESSION['ProhibitPostingsBefore'] != $_POST['X_ProhibitPostingsBefore'] ) {
			$sql[] = "UPDATE config SET confvalue = '" . $_POST['X_ProhibitPostingsBefore']."' WHERE confname = 'ProhibitPostingsBefore'";
		}
                
    if ($_SESSION['DB_Maintenance'] != $_POST['X_DB_Maintenance'] ) {
			$sql[] = "UPDATE config SET confvalue = '". ($_POST['X_DB_Maintenance'])."' WHERE confname = 'DB_Maintenance'";
		}
    if ($_SESSION['DefaultDateFormat'] != $_POST['X_DefaultDateFormat'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultDateFormat']."' WHERE confname = 'DefaultDateFormat'";
    }
    if ($_SESSION['DefaultTheme'] != $_POST['X_DefaultTheme'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultTheme']."' WHERE confname = 'DefaultTheme'";
    }
    if ($_SESSION['part_pics_dir'] != $_POST['X_part_pics_dir'] ) {
			$sql[] = "UPDATE config SET confvalue = 'companies/" . $_SESSION['DatabaseName'] . '/' . $_POST['X_part_pics_dir']."' WHERE confname = 'part_pics_dir'";
		}
    if ($_SESSION['SmtpSetting'] != $_POST['X_SmtpSetting']){
            $sql[] = "UPDATE config SET confvalue = '" . $_POST['X_SmtpSetting'] . "' WHERE confname='SmtpSetting'";
     }
    if ($_SESSION['PageLength'] != $_POST['X_PageLength'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_PageLength']."' WHERE confname = 'PageLength'";
    }
    
    if ($_SESSION['PageLeftLogo'] != $_POST['X_PageLeftLogo'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_PageLeftLogo']."' WHERE confname = 'PageLeftLogo'";
    }if ($_SESSION['PageRightLogo'] != $_POST['X_PageRightLogo'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_PageRightLogo']."' WHERE confname = 'PageRightLogo'";
    }
    if ($_SESSION['reports_dir'] != $_POST['X_reports_dir'] ) {
			$sql[] = "UPDATE config SET confvalue = 'companies/" . $_SESSION['DatabaseName'] . '/' . $_POST['X_reports_dir']."' WHERE confname = 'reports_dir'";
		} 
    if ($_SESSION['DefaultDisplayRecordsMax'] != $_POST['X_DefaultDisplayRecordsMax'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultDisplayRecordsMax']."' WHERE confname = 'DefaultDisplayRecordsMax'";
    } 
    
             
    if ($_SESSION['RomalpaClause'] != $_POST['X_RomalpaClause'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_RomalpaClause']."' WHERE confname = 'RomalpaClause'";
    } 
    
    
    /*$sql = "SELECT confname, confvalue FROM config where confname='paymentterms'";
                    $ConfigResult = DB_query($sql,$db);
                    while($myrow = DB_fetch_array($ConfigResult) ) {
                         $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
                    } */
    
     if ($_SESSION['paymentterms'] != $_POST['X_paymentterms'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_paymentterms']."' WHERE confname = 'paymentterms'";
    } 
   
    
      if (isset($_POST['X_ItemDescriptionLanguages'])) {
            $ItemDescriptionLanguages = '';
            foreach ($_POST['X_ItemDescriptionLanguages'] as $ItemLanguage){
                    $ItemDescriptionLanguages .= $ItemLanguage .',';
            }

            if ($_SESSION['ItemDescriptionLanguages'] != $ItemDescriptionLanguages){
                    $sql[] = "UPDATE config SET confvalue='" . $ItemDescriptionLanguages . "' WHERE confname='ItemDescriptionLanguages'";
            }
	}          
        
    $ErrMsg =  _('The system configuration could not be updated because');
		if (sizeof($sql) > 1 ) {
			$result = DB_Txn_Begin($db);
			foreach ($sql as $line) {
				$result = DB_query($line,$db,$ErrMsg);
			}
			$result = DB_Txn_Commit($db);
		} elseif(sizeof($sql)==1) {
			$result = DB_query($sql,$db,$ErrMsg);
		}

		prnMsg( _('System configuration updated'),'success');

		$ForceConfigReload = True; // Required to force a load even if stored in the session vars
		include('includes/GetConfig.php');
		$ForceConfigReload = False;
}else {
		prnMsg( _('Validation failed') . ', ' . _('no updates or deletes took place'),'warn');
	}


}

echo '<form autocomplete="off"method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered">';

$TableHeader = '<tr>
                    <th>' . _('System Variable Name') . '</th>
                    <th>' . _('Value') . '</th>
                    <th>' . _('Notes') . '</th>
                </tr>';

echo '<tr><th colspan="3">' . _('General Settings') . '</th></tr>';
echo $TableHeader;

//DefaultDisplayRecordsMax
echo '<tr style="outline: 1px solid">
		<td colspan="2">' . _('Payment Terms') . ':</td>
		<td><textarea  rows="2" cols="100"  required="required"  id="ParameterName"  title="'._('This is the Quote footer').'" name="X_paymentterms">' . $_SESSION['paymentterms'] . '</textarea></td>
	</tr>';
// DefaultDateFormat
echo '<tr style="outline: 1px solid"><td>' . _('Default Date Format') . ':</td>
	<td><select name="X_DefaultDateFormat">
	<option '.(($_SESSION['DefaultDateFormat']=='d/m/Y')?'selected="selected" ':'').'value="d/m/Y">' . _('d/m/Y') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='d.m.Y')?'selected="selected" ':'').'value="d.m.Y">' . _('d.m.Y') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='m/d/Y')?'selected="selected" ':'').'value="m/d/Y">' . _('m/d/Y') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='Y/m/d')?'selected="selected" ':'').'value="Y/m/d">' . _('Y/m/d') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='Y-m-d')?'selected="selected" ':'').'value="Y-m-d">' . _('Y-m-d') . '</option>
	</select></td>
	<td>' . _('The default date format for entry of dates and display.') . '</td></tr>';

// DefaultTheme
echo '<tr style="outline: 1px solid"><td>' . _('New Users Default Theme') . ':</td>
	 <td><select name="X_DefaultTheme">';
$ThemeDirectory = dir('css/');
while (false != ($ThemeName = $ThemeDirectory->read())){
	if (is_dir("css/$ThemeName") AND $ThemeName != '.' AND $ThemeName != '..' AND $ThemeName != '.svn'){
		if ($_SESSION['DefaultTheme'] == $ThemeName) {
			echo '<option selected="selected" value="' . $ThemeName . '">' . $ThemeName . '</option>';
		} else {
			echo '<option value="' . $ThemeName . '">' . $ThemeName . '</option>';
		}
	}
}
echo '</select></td>
	<td>' . _('The default theme is used for new users who have not yet defined the display colour scheme theme of their choice') . '</td></tr>';


//ItemDescriptionLanguages
if (!isset($_POST['X_ItemDescriptionLanguages'])){
	$_POST['X_ItemDescriptionLanguages'] = explode(',',$_SESSION['ItemDescriptionLanguages']);
}
echo '<tr style="outline: 1px solid">
		<td>' . _('Languages to Maintain Translations for Item Descriptions') . ':</td>
		<td><select name="X_ItemDescriptionLanguages[]" size="3" multiple="multiple" >';
		echo '<option value="">' . _('None')  . '</option>';
foreach ($LanguagesArray as $LanguageEntry => $LanguageName){
	if (isset($_POST['X_ItemDescriptionLanguages']) AND in_array($LanguageEntry,$_POST['X_ItemDescriptionLanguages'])){
		echo '<option selected="selected" value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	} elseif ($LanguageEntry != $DefaultLanguage) {
		echo '<option value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	}
}
echo '</select></td>
		<td>' . _('Select the languages in which translations of the item description will be maintained. The default language is excluded.') . '</td>
	</tr>';
//$part_pics_dir
echo '<tr style="outline: 1px solid"><td>' . _('The directory where images are stored') . ':</td><td><select name="X_part_pics_dir">';


$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);

while ($DirEntry = $DirHandle->read() ){
    if (is_dir($CompanyDirectory . $DirEntry)
        AND $DirEntry != '..'
        AND $DirEntry!='.'
        AND $DirEntry!='.svn'
        AND $DirEntry != 'CVS'
        AND $DirEntry != 'reports'
        AND $DirEntry != 'locale'
        AND $DirEntry != 'fonts'   ){

        if ($_SESSION['part_pics_dir'] == $CompanyDirectory . $DirEntry){
                echo '<option selected="selected" value="' . $DirEntry . '">' . $DirEntry . '</option>';
        } else {
                echo '<option value="' . $DirEntry . '">' . $DirEntry  . '</option>';
        }
    }
}

echo '</select></td>
	<td>' . _('The directory under which all image files should be stored. Image files take the format of ItemCode.jpg - they must all be .jpg files and the part code will be the name of the image file. This is named automatically on upload. The system will check to ensure that the image is a .jpg file') . '</td>
	</tr>';

echo '<tr style="outline: 1px solid">
	<td>' . _('Using Smtp Mail'). '</td>
	<td>
		<select type="text" name="X_SmtpSetting" >';
		if ($_SESSION['SmtpSetting'] == 0){
			echo '<option select="selected" value="0">' . _('No') . '</option>';
			echo '<option value="1">' . _('Yes') . '</option>';
		} elseif ($_SESSION['SmtpSetting'] == 1){
			echo '<option select="selected" value="1">' . _('Yes') . '</option>';
			echo '<option value="0">' . _('No') . '</option>';
		}

echo '</select>
         </td>
	 <td>' .  _('The default setting is using mail in default php.ini, if you choose Yes for this table table-bordered, you can use the SMTP set in the setup section.').'
	 </td>
     </tr>';


/*Perform Database maintenance DB_Maintenance*/
echo '<tr style="outline: 1px solid"><td>' . _('Perform Database Maintenance At Logon') . ':</td><td><select name="X_DB_Maintenance">';
	
	if ($_SESSION['DB_Maintenance']=='0'){
		echo '<option selected="selected" value="0">' . _('Un-Restricted') . '</option>';
	} else {
		echo '<option value="0">' . _('Un-Restricted') . '</option>';
	}
	
        if ($_SESSION['DB_Maintenance']=='-1'){
		echo '<option selected="selected" value="-1">' . _('Allow SysAdmin Access Only') . '</option>';
	} else {
		echo '<option value="-1">' . _('Allow SysAdmin Access Only') . '</option>';
	}

echo '</select></td>
	<td>' . _('Uses the function DB_Maintenance defined in ConnectDB.inc to perform database maintenance tasks, to run at regular intervals - checked at each and every user login') . '</td>
	</tr>';

/*/PageLength
echo '<tr style="outline: 1px solid"><td>' . _('Report Page Length') . ':</td>
	<td><input type="text" class="integer" pattern="(?!^0\d*$)[\d]{1,3}" title="'._('The input should be between 1 and 999').'" placeholder="'._('1 to 999').'" name="X_PageLength" size="4" maxlength="6" value="' . $_SESSION['PageLength'] . '" /></td><td>&nbsp;</td>
</tr>';*/

//PageLogo
echo '<tr style="outline: 1px solid"><td>' . _('Report Page Left LOGO') . ':</td>
	<td><select name="X_PageLeftLogo">';

$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);

while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['PageLeftLogo'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}

echo '</select></td><td>'._('This is left Report logo').'</td></tr>';

//PageLogo
echo '<tr style="outline: 1px solid"><td>' . _('Report Page Right LOGO') . ':</td><td><select name="X_PageRightLogo">';

$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);

while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['PageRightLogo'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}
echo '</select></td><td>'._('This is right Report logo').'</td></tr>';


 //$reports_dir
echo '<tr style="outline: 1px solid"><td>' . _('The directory where reports are stored') . ':</td>
	<td><select name="X_reports_dir">';

$DirHandle = dir($CompanyDirectory);

while (false != ($DirEntry = $DirHandle->read())){

	if (is_dir($CompanyDirectory . $DirEntry)
		AND $DirEntry != '..'
		AND $DirEntry != 'includes'
		AND $DirEntry!='.'
		AND $DirEntry!='.svn'
		AND $DirEntry != 'doc'
		AND $DirEntry != 'css'
		AND $DirEntry != 'CVS'
		AND $DirEntry != 'sql'
		AND $DirEntry != 'part_pics'
		AND $DirEntry != 'locale'
		AND $DirEntry != 'fonts'      ){

		if ($_SESSION['reports_dir'] == $CompanyDirectory . $DirEntry){
			echo '<option selected="selected" value="' . $DirEntry . '">' . $DirEntry . '</option>';
		} else {
			echo '<option value="' . $DirEntry . '">' . $DirEntry  . '</option>';
		}
	}
}

echo '</select></td>
	<td>' . _('The directory under which all report pdf files should be created in. A separate directory is recommended') . '</td>
	</tr>';

//DefaultDisplayRecordsMax
echo '<tr style="outline: 1px solid">
		<td>' . _('Default Maximum Number of Records to Show') . ':</td>
		<td><input type="text" class="integer" pattern="(?!^0\d*$)[\d]{1,3}" required="required" title="'._('The records should be between 1 and 999').'" name="X_DefaultDisplayRecordsMax" size="4" maxlength="3" value="' . $_SESSION['DefaultDisplayRecordsMax'] . '" /></td>
		<td>' . _('When pages have code to limit the number of returned records - such as select customer, select supplier and select item, then this will be the default number of records to show for a user who has not changed this for themselves in user settings.') . '</td>
	</tr>';

//DefaultDisplayRecordsMax
echo '<tr style="outline: 1px solid">
		<td colspan="2">' . _('This is the invoice footer') . ':</td>
		<td><textarea  rows="2" cols="100"  required="required" title="'._('This is the invoice footer').'" name="X_RomalpaClause">' . $_SESSION['RomalpaClause'] . '</textarea></td>
	</tr>';

//DefaultDisplayRecordsMax
echo '<tr style="outline: 1px solid">
    <td>' . _('Withholding Tax GL account') . ':</td>
    <td><select name="X_WithholdingTaxGlAccount"   required="required">';
     
$REsults=DB_query("SELECT `accno`,`accdesc` FROM `acct`  "
        . " where `balance_income`='0' and `inactive`='0' and `ReportStyle`=0", $db);
        while($row=  DB_fetch_array($REsults)){
            echo sprintf('<option value="%s" %s>%s</option>',
                    $row['accno'],($_SESSION['WithholdingTaxGlAccount']==$row['accno']?'selected="selected"':''),
                    $row['accdesc']);
        }

 echo  '</select></td><td>Select the Balance sheet account for withholding Tax </td></tr>';
//$_POST['X_SINGLEUSER']
 
 $singleuser=array('Singleuser'=>'Single User','Multiuser'=>'Multi User');
echo '<tr style="outline: 1px solid">
    <td>' . _('Single User') . ':</td>
    <td><select name="X_SINGLEUSER" required="required">';
        foreach ($singleuser as $key => $value) {
         echo sprintf('<option value="%s" %s>%s</option>', $key,($_SESSION['SINGLEUSER']==$key?'selected="selected"':''),$value);
        }

 echo  '</select></td><td>To Remove or activate LPO approvals</td></tr>';
//DefaultDisplayRecordsMax
echo '<tr style="outline: 1px solid">
		<td>' . _('Withholding Tax Rate') . ':</td>
		<td><input type="text" class="number"  required="required" title="'._('With holding Tax Rate').'" name="X_WithholdingTaxRate" value="' . $_SESSION['WithholdingTaxRate'] . '" /></td>
	<td>Enter the Withholding Tax rate as a number. Ignore the % sign</td></tr>';


// Forusing manual numbers
echo '<tr style="outline: 1px solid"><td>' . _('Use Manual Numbers') . ':</td>
	 <td><select name="X_ManualNumber">';
    if ($_SESSION['ManualNumber'] == 0){
            echo '<option select="selected" value="0">' . _('No') . '</option>';
            echo '<option value="1">' . _('Yes') . '</option>';
    } elseif ($_SESSION['ManualNumber'] == 1){
            echo '<option select="selected" value="1">' . _('Yes') . '</option>';
            echo '<option value="0">' . _('No') . '</option>';
    }
echo '</select></td>
	<td>' . _('Make the system to allow editing of numbers') . '</td></tr>';

    
    
    
echo '</table>
	<br /><div class="centre"><input type="submit" name="submit" value="' . _('Update system settings') . '" /></div>
    </div></form>';

include('includes/footer.inc');
?>