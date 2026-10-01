<?php
include('includes/session.inc');
require_once 'vendor/autoload.php';
$Title = _('New Activity');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
include('calendar/myGooglesettings.php');

$mypage=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

 if(isset($_GET['new'])){
   $_SESSION[$mypage] = date("U");
   prnMsg(sprintf('Your calendar is set on email account %s',$_SESSION['UserEmail']),'info');
 }
 
 
$result = DB_query("SELECT userid,realname FROM www_users where userid='".$_SESSION['UserID']."'",$db);
While($row= DB_fetch_array($result)){
    $wwwusers[]=$row;
}

$result = DB_query("SELECT `Company`,`pkey`,`createdby`,`Contact_email` FROM `NewContacts`",$db);
While($row= DB_fetch_array($result)){
    $key=trim($row['pkey']);
    $NewContacts[$key]=$row;
}

if(isset($_POST['saveActivity'])){
    $fromdue = FormatDateForSQL($_POST["fromdue"]);
    $todue = FormatDateForSQL($_POST["todue"]);
    
    if(isset($_SESSION[$mypage])){
        if($_SESSION[$mypage]==$_POST['TransID']){
            $fromTime =  $_POST['time_from'];
            $totime   =  $_POST['time_to'];
            $start    =  $fromdue .'T'.trim($_POST['time_from']).':00+03:00';
            $enddate  =  $todue .'T'.trim($_POST['time_to']).':00+03:00';
            //2022-04-21T15:55:52+03:00"
            //'2015-12-01T10:00:00.000-05:00'
            $customerinvite = getuseremail($NewContacts,trim($_POST['Contact']));
            if((mb_strlen($customerinvite)>0) and $_POST['invite']=='1'){
              $attendee[] = array('email' => $_SESSION['UserEmail'],'organizer' => true);
              $attendee[] = array('email' =>$customerinvite);
            }else{
              $attendee[] = array('email' => $_SESSION['UserEmail'],'organizer' => true);
            }
            
            $status = (int) $_POST["Status"];
            if($status<5){
            $service = new Google_Service_Calendar($client);
            $event = new Google_Service_Calendar_Event(array(
                        'summary' => $_POST['Activityname'],
                        'location' => $_POST["location"],
                        'description' => $_POST["taskdetails"],
                        'start' => array(
                        'dateTime' =>  $start,
                        'timeZone' => 'Africa/Nairobi',
                        ),
                        'end' => array(
                        'dateTime' => $enddate,
                        'timeZone' => 'Africa/Nairobi',
                        ),
                        'attendees' => $attendee,
                        "creator" => array("email" => $_SESSION['UserEmail'],
                        "displayName" => $_SESSION['UsersRealName'] ,
                        "self" => true
                        ),
                        "guestsCanInviteOthers" => false,
                        "guestsCanModify" => false,
                        "guestsCanSeeOtherGuests" => false,
                        'reminders' => array(
                        'useDefault' => FALSE,
                        'overrides' => array(
                          array('method' => 'email', 'minutes' => 24 * 60),
                          array('method' => 'popup', 'minutes' => 120),
                        ),
                        ),
                ));  
            
            
                 $service->events->insert('primary', $event);
           }
             
        $SQL=sprintf("INSERT INTO `NewActivity`(`ActivityOwner`,`Activityname`,
            `fromdue`,`todue`,`Contact`,`Status`,`valueofbusiness`,`taskdetails`,
            `createdby`,`createdon`,`lastactivity`,`Sart_time_from`,`End_time_to`,`location`)
         VALUES ('%s','%s','%s','%s','%s','%s',%f,'%s','%s',NOW(),NOW(),'%s','%s','%s')",
                $_POST['ActivityOwner'] ,$_POST['Activityname'],$fromdue,$todue,
                $_POST['Contact'],$_POST['Status'],$_POST['valueofbusiness'] ,
                $_POST['taskdetails'],$_SESSION['UserID'],$start,$enddate,$_POST["location"]);

           DB_query($SQL,$db);
           prnMsg('Activity has been saved','info');
           $_SESSION[$mypage] = date("U");
        }else{
              prnMsg('You cannot post twice','info');
        }
    
    }else{
          prnMsg('You cannot post twice','info');
    }
   
    
}

if(isset($_POST['editactivity'])){
 $fromdue = FormatDateForSQL($_POST["fromdue"]);
 $todue = FormatDateForSQL($_POST["todue"]);
 $Today = FormatDateForSQL(Today());
  
 $TaskDetails = $_POST['taskdetails'].htmlspecialchars('<p><u>Previous Status on '.Date('Y-m-d H:i:s').'</u></p>').$_POST["Currenttaskdetails"];

  $SQL = "update `NewActivity` set `Status`=".$_POST['Status'];
  $SQL.= sprintf(",`lastactivity`=NOW(),`taskdetails`='%s' ", $TaskDetails);
  $SQL.= ",`fromdue`='".$fromdue."' ,`todue`='".$todue."'  where pkey=".$_POST['activityID'];

DB_query($SQL,$db);
prnMsg('Activity has been Updated','info');
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . $Title .'" alt="" />' . ' ' . $Title . '</p>';
echo '<form autocomplete="off" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new Activity session click here</a>';

echo '<div class="container"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>'
 . '<input type="hidden" name="TransID" value="' . $_SESSION[$mypage]  . '"/>';

if(isset($_GET['id'])){
    $SQL=sprintf("SELECT `ActivityOwner`,`Activityname`,`fromdue`,`todue`,`Contact`,`Status`,`valueofbusiness`,"
            . "`taskdetails`,`Sart_time_from` ,`End_time_to`,`location` FROM `NewActivity` where `pkey`='%s'",$_GET['id']);
    $ResultIndex = DB_query($SQL,$db);
    $ActivityOwne = DB_fetch_row($ResultIndex);
    
    $_POST['ActivityOwner'] = trim($ActivityOwne[0]);
    $_POST['Activityname'] = $ActivityOwne[1];
    $_POST["fromdue"] = ConvertSQLDate($ActivityOwne[2]);
    $_POST["todue"] = ConvertSQLDate($ActivityOwne[3]);
    $_POST['Contact'] = trim($ActivityOwne[4]);
    $_POST['Status'] = (int)$ActivityOwne[5];
    $_POST['valueofbusiness'] = $ActivityOwne[6];
    $_POST['taskdetails'] = $ActivityOwne[7];
    $_POST['time_from'] = $ActivityOwne[8];
    $_POST['time_to'] = $ActivityOwne[9];
    $_POST["location"] = $ActivityOwne[10];

 echo '<div class="container"><input type="hidden" name="Currenttaskdetails" value="' .$_POST['taskdetails']. '"/>';
 echo '<div class="container"><input type="hidden" name="activityID" value="' . $_GET['id']. '"/>';
  
 echo '<div style="width:60%;"><table class="table-bordered table-condensed">
        <tr><td>Activity Owner (who will perform this activity)</td><td><select name="ActivityOwner" required="required">';
                 foreach ($wwwusers as $Contacts){
                    $selection=(trim($Contacts['userid'])==$_POST['ActivityOwner'])?'selected="selected"':'';
                    echo sprintf('<option value="%s" %s>%s</option>',$Contacts['userid'],$selection,$Contacts['realname']);
                } 
 echo '</select></td></tr>
    <tr><td>Activity Name (What is the task called)</td><td><input type="text" name="Activityname" required="required" value="'.$_POST['Activityname'].'"/></td></tr>';

 ?>
 
  <tr><td><label>Location</label></td><td>
            <input type="text" name="location"  value="<?php echo $_POST['location'];?>"> </td></tr>
    <tr><td>Duration</td>
        <td>From Date:<input type="text" class="date" required="required"  value="<?php echo $_POST['fromdue'];?>"  name="fromdue" alt="<?php echo $_SESSION['DefaultDateFormat'];?>" size="11" maxlength="10" readonly="readonly"  onchange="isDate(this, this.value, '<?php echo "'".$_SESSION['DefaultDateFormat']."'"?>')"/>
       <span>TIME</span><input type="time" name="time_from"  value="<?php echo $_POST['time_from'];?>">To Date:<input type="text" class="date" name="todue" required="required"  value="<?php echo $_POST['todue'];?>"  alt="<?php echo $_SESSION['DefaultDateFormat'];?>" size="11" maxlength="10" readonly="readonly"  onchange="isDate(this, this.value, '<?php echo "'".$_SESSION['DefaultDateFormat']."'"?>')"/><span>TIME</span>
            <input type="time" name="time_to"  value="<?php echo $_POST['time_to'];?>"></td></tr>
    <?php  
  
 
 
echo '<tr><td>Contact</td><td><select name="Contact" required="required"><option></option>';
        foreach ($NewContacts as $Contacts){
           $selection=(trim($Contacts['pkey'])==$_POST['Contact'])?'selected="selected"':'';
           echo sprintf('<option value="%s" %s>%s</option>',trim($Contacts['pkey']),$selection,$Contacts['Company'].' "Owner :'.$Contacts['createdby'].'"');
       } 
echo  '</select></td></tr>
    <tr><td> Next Activity Status</td><td><select name="Status" required="required"><option></option>';
    foreach ($CRMArray as $key => $CRM){
         $selection=(trim($key)==$_POST['Status'])?'selected="selected"':'';
       echo sprintf('<option value="%s" %s>%s</option>',$key,$selection,$CRM);
   } 
echo '</select></td></tr>
    <tr><td>Estimated Value of business your pushing</td><td><input type="text" class="number" name="valueofbusiness"  value="'.$_POST['valueofbusiness'].'"  required="required"/></td></tr>
      <tr><td colspan="2">Details Of the Activity<br>
           <textarea rows="4" cols="150" name="taskdetails" required="required"></textarea>
           <details><summary>Histotoy</summary><p>'.$_POST['taskdetails'].'</p></details>
         </tr>
       <tr><td colspan="2"><input type="submit" name="editactivity" value="Update Activity Status"  onclick="return confirm('. _('Do you want to Update this Activity ?').');"></td></tr>
    </table></div>';

    }else{

?>
<div style="width:60%;"><table class="table-bordered table-condensed">
        <tr><td>Activity Owner (who will perform this activity)</td><td><select name="ActivityOwner" required="required">
                <?php foreach ($wwwusers as $Contacts){
                    $selection=(trim($Contacts['userid'])==trim($_SESSION['UserID']))?'selected="selected"':'';
                    echo sprintf('<option value="%s" %s>%s</option>',$Contacts['userid'],$selection,$Contacts['realname']);
                }  ?>
    </select></td></tr>
    <tr><td>Activity Name (What is the task called)</td><td><input type="text" name="Activityname" required="required"/></td></tr>
   <tr><td><label>Location</label></td><td>
            <input type="text" name="location"  value="">
     </td></tr>
    <tr><td>Duration</td>
        <td>From Date:<input type="text" class="date" required="required" name="fromdue" alt="<?php echo $_SESSION['DefaultDateFormat'];?>" size="11" maxlength="10" readonly="readonly"  onchange="isDate(this, this.value, '<?php echo "'".$_SESSION['DefaultDateFormat']."'"?>')"/>
       <span>TIME</span><input type="time" name="time_from"  required="required" value="">To Date:<input type="text" class="date" name="todue" required="required" alt="<?php echo $_SESSION['DefaultDateFormat'];?>" size="11" maxlength="10" readonly="readonly"  onchange="isDate(this, this.value, '<?php echo "'".$_SESSION['DefaultDateFormat']."'"?>')"/><span>TIME</span>
            <input type="time" name="time_to"  required="required" value=""></td></tr>
      
    <tr><td>Contact</td><td><select name="Contact" required="required"><option></option>
                <?php foreach ($NewContacts as $Contacts){
                    echo sprintf('<option value="%s">%s</option>',trim($Contacts['pkey']),$Contacts['Company'].' "Owner :'.$Contacts['createdby'].'"');
                } ?>
    </select></td></tr>
    <tr><td>Contact's Next Activity Status</td><td><select name="Status" required="required"><option></option>
                <?php foreach ($CRMArray as $key => $CRM){
                    echo sprintf('<option value="%s">%s</option>',$key,$CRM);
                } ?>
            </select> <br>Do you want to invite the client using google calendar ?<input type="checkbox" name="invite" value="1" checked="checked"></td></tr>
    <tr><td>Contact's Estimated. Value of business</td><td><input type="text" class="number" name="valueofbusiness"  required="required"/></td></tr>
   
    <tr><td colspan="2">Details Of the Activity<br>
            <textarea  rows="4" cols="150" name="taskdetails" required="required"></textarea>
        </tr>
       <tr><td colspan="2"><input type="submit" name="saveActivity" value="Save Activity"  onclick="return confirm('<?php echo _('Do you want to Save this Activity ?');?>');"></td></tr>
    </table></div>

<?php
}
echo '</div></form>';

include('includes/footer.inc');

function getuseremail($row,$id){
    $return='';
    
    $email = trim($row[$id]['Contact_email']);
    if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $return = $email;
    }
    
    return $return;
}