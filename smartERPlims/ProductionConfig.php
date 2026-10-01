<?php

include('includes/session.inc');
$Title = _('Production Bill of Materials');
include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/group_add.png" title="' .
		_('Search') . '" alt="" />' . ' ' . $Title . '</p>';

if (isset($_GET['SelectedRole'])){
	$SelectedRole = $_GET['SelectedRole'];
} elseif (isset($_POST['SelectedRole'])){
	$SelectedRole = $_POST['SelectedRole'];
}

if (isset($_POST['submit']) OR isset($_GET['remove']) OR isset($_GET['add']) ) {
	// if $_POST['SecRoleName'] then it is a modifications on a SecRole
	// else it is either an add or remove of a page token
	unset($sql);
	if (isset($SelectedRole) ) {
		$PageTokenId = $_GET['PageToken'];
		if( isset($_GET['add']) ) { // updating Security Groups add a page token
			$sql = "INSERT INTO productionconfig (categoryid,rawmatid)"
                                . "VALUES ('".$SelectedRole."','".$PageTokenId."' )";
			$ErrMsg = _('The addition of the group access failed because');
			$ResMsg = _('The group access was added.');
		} elseif ( isset($_GET['remove']) ) { // updating Security Groups remove a page token
			$sql = "DELETE FROM productionconfig
					WHERE categoryid = '".$SelectedRole."'
					AND rawmatid = '".$PageTokenId . "'";
			$ErrMsg = _('The removal of this group access failed because');
			$ResMsg = _('This group access was removed.');
		}
		unset($_GET['add']);
		unset($_GET['remove']);
		unset($_GET['PageToken']);
	}
	// Need to exec the query
	if (isset($sql) AND $InputError != 1 ) {
		$result = DB_query($sql,$db,$ErrMsg);
		if( $result ) {
			prnMsg( $ResMsg,'success');
		}
	}
} 

if (!isset($SelectedRole)) {

/* If its the first time the page has been displayed with no parameters then none of the above are true and the list of Users will be displayed with links to delete or edit each. These will call the same page again and allow update/input or deletion of the records*/

	$result=DB_query("SELECT `categoryid`,`categorydescription` FROM stockcategory",$db);
    
	echo '<div class="container"><table class="table-bordered">';
	echo '<tr><th colspan="2">' . _('Stock Categories') . '</th></tr>';

	$k=0; //row colour counter

	while ($myrow = DB_fetch_array($result)) {
		
		/*The SecurityHeadings array is defined in config.php */

		printf('<tr><td>%s</td>
			<td><a href="%s&amp;SelectedRole=%s">' . _('View or Modify') . '</a></td>
			</tr>',
			$myrow['categorydescription'],
			htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8')  . '?',
			$myrow['categoryid'],
			htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?',
			$myrow['categoryid'],
			urlencode($myrow['categorydescription']));

	} //END WHILE LIST LOOP
	echo '</table></div>';
} //end of ifs and buts!


if (isset($SelectedRole)) {
	echo '<br /><div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">' . _('Review Existing Roles') . '</a></div>';
}

if (isset($SelectedRole)) {
	//editing an existing role
          $result=DB_query("SELECT `categoryid`,`categorydescription` "
                    . "FROM stockcategory WHERE categoryid='" . $SelectedRole . "'",$db);
    
	if ( DB_num_rows($result) == 0 ) {
		prnMsg( _('The selected role is no longer available.'),'warn');
	} else {
		$myrow = DB_fetch_array($result);
		$_POST['SelectedRole'] = $myrow['categoryid'];
		$_POST['SecRoleName'] = $myrow['categorydescription'];
	}
}
echo '<br />';
echo '<form autocomplete="off"method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if( isset($_POST['SelectedRole'])) {
    echo '<input type="hidden" name="SelectedRole" value="' . $_POST['SelectedRole'] . '" />';
}
echo '<table class="table-bordered">';
if (!isset($_POST['SecRoleName'])) {
	$_POST['SecRoleName']='';
}else{
    echo '<tr><td>' . _('Category') . ':</td><td>' . $_POST['SecRoleName'] . '</td></tr>';
    echo '</table></form>';
}
if (isset($SelectedRole)) {

	$sqlUsed = "SELECT rawmatid FROM productionconfig WHERE categoryid='". $SelectedRole . "'";
	/*Make an array of the used tokens */
	$UsedResult = DB_query($sqlUsed, $db);
	$TokensUsed = array();
	$i=0;
	while ($myrow=DB_fetch_row($UsedResult)){
		$TokensUsed[$i] =trim($myrow[0]);
		$i++;
	}

	echo '<br /><table class="table-bordered"><tr>';
	//if (DB_num_rows($Result)>0 ) {
		echo '<th colspan="3"><div class="centre">' . _('Assigned Product Groups') . '</div></th>';
		echo '<th colspan="3"><div class="centre">' . _('Available Product Groups') . '</div></th>';
	//}
	echo '</tr>';

	$k=0; //row colour counter
     
        foreach ($ProductionCategory as $key => $namevalue) {
       if(mb_strlen($key)>0){
		if (in_array($key,$TokensUsed)){
			printf('<tr><td>%s</td>
					<td>%s</td>
					<td><a href="%sSelectedRole=%s&amp;remove=1&amp;PageToken=%s" onclick="return confirm(\'' . _('Are you sure you wish to delete this security token from this role?') . '\');">' . _('Remove') . '</a></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>',
					$key,
					$namevalue,
					htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8')  . '?',
					$SelectedRole,
					$key);
		} else {
			printf('<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>%s</td>
					<td>%s</td>
					<td><a href="%sSelectedRole=%s&amp;add=1&amp;PageToken=%s">' . _('Add') . '</a></td>',
					$key,
					$namevalue,
					htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8')  . '?',
					$SelectedRole,
					$key);
		}
       }     
		echo '</tr>';
	}
	echo '</table>';
}

include('includes/footer.inc');

?>
