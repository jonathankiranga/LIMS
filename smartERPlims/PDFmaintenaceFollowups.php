<?php
/* $Id: MaintenanceTasks.php 5231 2012-04-07 18:10:09Z daitnree $*/
include('includes/session.inc');
$Title = _('Customer Routine Maintenace Schedule');
include('includes/header.inc');
 
$Role = array();
$Role['Ta'] = 'New Customer Lead';
$Role['Tb'] = 'Collect Debts from Sales';
$Role['Tc'] = 'Routine Maintenace';

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/group_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p><br />';

if(!isset($_POST['customerfollowup'])){
     
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table class="table table-bordered"><tr><td>Dates</td></tr>';
        
  echo '<tr><td>From</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
  echo '<td>Date TO</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10"   required="required"  value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
 
  echo '<tr><td colspan="2"><input type="submit" name="customerfollowup" value="Print Customer Followup"/>'
       . '</td></tr></table>';
  echo '</div></form>';
  

}else{
    
$sql="SELECT taskid,
            `CustomerVisits`.`itemcode`,
            `Customer`,
            `taskdescription`,
            `lastcompleted`,
            `today`,
            `userresponsible`,
            `realname`,
            `manager`,
            `userid`,
            `comments`
            FROM `CustomerVisits`
            INNER JOIN debtors ON `CustomerVisits`.itemcode=debtors.itemcode
            left JOIN www_users ON `CustomerVisits`.userresponsible=www_users.`salesman`
            WHERE `today` between '".ConvertSQLDate($_POST['date'])."' and '".ConvertSQLDate($_POST['datedue'])."'
            ORDER BY itemcode DESC";


$ErrMsg = _('The maintenance schedule cannot be retrieved because');
$Result=DB_query($sql,$db,$ErrMsg);

echo '<table class="table table-bordered" id="GL">
     <tr><th>' . _('Task ID') . '</th>
        <th>' . _('Customer Name') . '</th>
        <th>' . _('Task Description') . '</th>
        <th>' . _('Was Scheduled on ') . '</th>
        <th>' . _('Completed on ') . '</td>
        <th>' . _('Person in Charge') . '</th>
        <th>' . _('Manager/Supervisor') . '</th>
        <th>' . _('Comments') . '</th>
    </tr>';

while ($myrow=DB_fetch_array($Result)) {

	if ($myrow['manager']!=''){
		$ManagerResult = DB_query("SELECT realname FROM www_users WHERE userid='" . $myrow['manager'] . "'",$db);
		$ManagerRow = DB_fetch_array($ManagerResult);
		$ManagerName = $ManagerRow['realname'];
	} else {
		$ManagerName = _('No Manager Set');
	}

	echo '<tr>
			<td>' . $myrow['taskid'] . '</td>
			<td>' . $myrow['Customer'] . '</td>
			<td>' . $myrow['taskdescription'] . '</td>
			<td>' . ConvertSQLDate($myrow['lastcompleted']) . '</td>
			<td>' . ConvertSQLDate($myrow['today']) . '</td>
			<td>' . $myrow['realname'] . '</td>
			<td>' . $ManagerName . '</td>
			<td>' . $myrow['comments'] . '</td>
		</tr>';
}

echo '</table><br /><br />';

echo '<input type="button" onclick="tableToExcel(\'GL\',\'P$L\')" value="Export to Excel">';

}

include('includes/footer.inc');

?>