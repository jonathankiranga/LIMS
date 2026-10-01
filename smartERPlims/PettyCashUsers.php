<?php
include('includes/session.inc');
$Title = _('Petty Cash');

/* webERP manual links before header.inc */
$ViewTopic= 'GettingStarted';
$BookMark = 'UserMaintenance';
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/petteycash.inc');

$ClsPettey = new  Petteycash();
$ClsPettey->Calculate();

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/group_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p><br />';
// Make an array of the security roles

if (isset($_GET['SelectedUser'])){
	$SelectedUser = $_GET['SelectedUser'];
} elseif (isset($_POST['SelectedUser'])){
	$SelectedUser = $_POST['SelectedUser'];
}

if(isset($_POST['newshift'])){
    $sql=array();
    // Adjust the defaultlocation to 20 chars from 5 chars wwwusers 
    $sql[]="Update `companies` set `shiftno`=`shiftno`+1 where coycode=1";
    
    $sql[]=sprintf("Update `www_users` "
            . " set `Currentshiftno`=(select `shiftno` from `companies` where coycode=1) ,"
            . " defaultlocation='%s' "
            . " where `userid`='%s'",$_POST['Bank_Code'],$SelectedUser);
  
    DB_Txn_Begin($db);
    foreach ($sql as $sqlcmd) {
      DB_query($sqlcmd,$db);
   }
            
   if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    } else {
        DB_Txn_Commit($db);
    }
}

$sql = "SELECT secroleid,secrolename FROM securityroles where secroleid <=".$_SESSION['AccessLevel']."  ORDER BY secrolename";
$Sec_Result = DB_query($sql, $db);
$SecurityRoles = array();

// Now load it into an a ray using Key/Value pairs
while($Sec_row = DB_fetch_row($Sec_Result) ) {
    $SecurityRoles[$Sec_row[0]] = $Sec_row[1];
}

DB_free_result($Sec_Result);

	$sql = "SELECT userid,
                    realname,
                    phone,
                    email,
                    lastvisitdate,
                    fullaccess,
                    defaultlocation
                    FROM www_users where fullaccess <='".$_SESSION['AccessLevel']."'";
	$result = DB_query($sql,$db);

	echo '<div class="container"><table class="table table-bordered">';
	echo '<tr><th>' . _('User Login') . '</th>
                <th>' . _('Full Name') . '</th>
                <th>' . _('Telephone') . '</th>
                <th>' . _('Email') . '</th>
                <th>' . _('Last Visit') . '</th>
                <th>' . _('Security Role')  . '</th>
                <th>' . _('Last Petty Cash No')  . '</th>
                <th>' . _('PettyCash Account')  . '</th>
                <th></th>
              </tr>';


	while ($myrow = DB_fetch_array($result)) {

	if ($myrow[8]=='') {
		$LastVisitDate = Date($_SESSION['DefaultDateFormat']);
	} else {
		$LastVisitDate = ConvertSQLDate($myrow['lastvisitdate']);
	}

		printf('<tr><td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td><a href="%sSelectedUser=%s">' . _('Select') . '</a></td>
                            </tr>',
                            $myrow['userid'],
                            $myrow['realname'],
                            $myrow['phone'],
                            $myrow['email'],
                            $LastVisitDate,
                            $SecurityRoles[($myrow['fullaccess'])],
                            $ClsPettey->GetShiftno($myrow['userid']),
                            GetBank($myrow['defaultlocation']),
                            htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?',
                            $myrow['userid']);
               
	} //END WHILE LIST LOOP
	echo '</table></div><br />';




echo '<form autocomplete="off"method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($SelectedUser)) {

	$sql = "SELECT userid,
			realname,
			phone,
			email,
			customerid,
			password,
			supplierid,
			salesman,
			pagesize,
			fullaccess,
			cancreatetender,
			defaultlocation,
			modulesallowed,
			blocked,
			theme,
			language,
			pdflanguage
		FROM www_users
		WHERE userid='" . $SelectedUser . "'";

	$result = DB_query($sql, $db);
	$myrow = DB_fetch_array($result);

	$_POST['UserID'] = $myrow['userid'];
	$_POST['RealName'] = $myrow['realname'];
	$_POST['Bank_Code']= $myrow['defaultlocation'];
                
	echo '<input type="hidden" name="SelectedUser" value="' . $SelectedUser . '" />';
	echo '<input type="hidden" name="UserID" value="' . $_POST['UserID'] . '" />';
        echo '<table class="table-bordered">
            <tr><th colspan="2">Close Petty cash for the selected user</th></tr>
              <tr>
                    <td>' . _('User code') . ':</td>
                    <td>' . $_POST['UserID'] . '</td>
                </tr><tr>
                    <td>' . _('User Real Name') . ':</td>
                    <td>' . $_POST['RealName'] . '</td>
                </tr>';
       
        $BankObject='<tr><td>Default Bank:</td><td><Select name="Bank_Code"><option></option>';
        $resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency` FROM `BankAccounts`", $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $BankObject .= '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                $BankObject .= '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
       }
        echo $BankObject;
        echo '</td></tr>';

        echo '<tr><td colspan="2"><input type="submit" name="newshift" value="' . _('Close shift or Day End') . '" onclick="return confirm(\''._('Do you want to Open (or close) this Day ?').'\');" /></td></tr>';
        echo '</table></form>';

} 

 
include('includes/footer.inc');



Function GetBank($Bank_Code){
    Global $db;
    
    $resultindex = DB_query("SELECT `accountcode`,`bankName`,`currency` FROM `BankAccounts` where `accountcode`='".$Bank_Code."'", $db);
    $row = DB_fetch_row($resultindex);
    return $row[1];
  
}

?>
