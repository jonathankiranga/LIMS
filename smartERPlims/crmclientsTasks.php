<?php
include('includes/session.inc');
require_once 'vendor/autoload.php';
$Title = _('New Task');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
include('calendar/myGooglesettings.php');
$mypage=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . $Title .'" alt="" />' . ' ' . $Title . '</p>';

 if(isset($_GET['new'])){
    $_SESSION[$mypage] = date("U");
    prnMsg(sprintf('Your calendar is set on email account %s',$_SESSION['UserEmail']),'info');
  }
 
$result = DB_query("SELECT userid,realname FROM www_users where userid='".$_SESSION['UserID']."'",$db);
While($row= DB_fetch_array($result)){
    $wwwusers[]=$row;
}



if(isset($_POST['savetask'])){
      if(isset($_SESSION[$mypage])){
        if($_SESSION[$mypage]==$_POST['TransID']){
            $datecoverted = FormatDateForSQL($_POST['datedue']);
            $fromTime =  $_POST['time_from'];
            $totime   =  $_POST['time_to'];
            //'2015-12-01T10:00:00.000-05:00'
            $start = $datecoverted .'T'.trim($_POST['time_from']).':00+03:00';
            $enddate = $datecoverted .'T'.trim($_POST['time_to']).':00+03:00';
            //2022-04-21T15:55:52+03:00"
            $status = (int) $_POST["Status"];
           
            if($status<4){
            $service = new Google_Service_Calendar($client);
            $event = new Google_Service_Calendar_Event(array(
                        'summary' => $_POST["Taskname"],
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
                        'attendees' => array(
                        array('email' => $_SESSION['UserEmail'],'organizer' => true)
                        ),
                        "creator" => array("email" => $_SESSION['UserEmail'],
                        "displayName" =>$_SESSION['UsersRealName'] ,
                        "self" => true
                        ),
                        "guestsCanInviteOthers" => false,
                        "guestsCanModify" => false,
                        "guestsCanSeeOtherGuests" => false,
                        'reminders' => array(
                        'useDefault' => FALSE,
                        'overrides' => array(
                          array('method' => 'email', 'minutes' => 24 * 60),
                          array('method' => 'popup', 'minutes' => 20),
                        ),
                        ),
                ));  
            
                 $service->events->insert('primary', $event);
           }
            //Alter table Tasks add time_from varchar(10),time_to varchar(10),location varchar(max)
            
             $SQL=sprintf("INSERT INTO `Tasks`(
                `userid`,`datecreated`,lastactivity,`TaskOwner`,`Taskname`,`datedue`,`Status`,
                `Priority`,`frequency`,`taskdetails`,time_from,time_to,location)
             VALUES ('%s',NOW(),NOW(),'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                    $_SESSION['UserID'], $_POST["TaskOwner"],$_POST["Taskname"],
                     FormatDateForSQL($_POST["datedue"]),$_POST["Status"],$_POST["Priority"],
                    $_POST["frequency"],$_POST["taskdetails"],$start,$enddate,$_POST["location"]);
             
              DB_query($SQL,$db);
              prnMsg('Task has been saved','info');
              
                $_SESSION[$mypage] = date("U");
            }else{
                prnMsg('You cannot post twice','info');
            }
      
        }else{
              prnMsg('You cannot post twice','info');
        }
}

if(isset($_POST['edittask'])){

$TaskDetails=$_POST['taskdetails'].htmlspecialchars('<p><u>Previous Status on '.Date('Y-m-d H:i:s').'</u></p>').$_POST["Currenttaskdetails"];
$status = (int) $_POST["Status"];
$frequencyint = (int) $_POST["frequency"];
$date = FormatDateForSQL($_POST["datedue"]);
 
$SQL = array();
    if($status=='4'){
        $SQL1 = sprintf("Update `Tasks` set `Status`='%s',`taskdetails`='%s',lastactivity=NOW(),`frequency`='%s'"
                . ",`datedue`='%s' where pkey='%s' ",$_POST["Status"],$_POST['taskdetails'],$_POST["Priority"],$date,$_POST["TaskID"]);
        DB_query($SQL1,$db);

            if($frequencyint>0){
                  $dateR = GetNextRoutine($_POST["datedue"],$frequencyint);
                  $SQL2 = sprintf("INSERT INTO `Tasks`(`userid`,`datecreated`,lastactivity,`TaskOwner`,`Taskname`,`datedue`,`Status`,
                   `Priority`,`frequency`,`taskdetails`) VALUES ('%s',NOW(),NOW(),'%s','%s','%s','%s','%s','%s','%s')",
                  $_SESSION['UserID'],$_POST["TaskOwner"],$_POST["Taskname"],$dateR,0,$_POST["Priority"],$_POST["frequency"], $_POST["taskdetails"]);

                   DB_query($SQL2,$db);
            }   
            
       }else{
        $SQL3 = sprintf("Update `Tasks` set `Status`='%s',`datedue`='%s',`taskdetails`='%s' where pkey='%s' ",
                $_POST["Status"],$date,$TaskDetails,$_POST["TaskID"]);
        DB_query($SQL3,$db);
     }
  prnMsg('Task has been Updated','info');
}

echo '<form autocomplete="off" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
     <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new Task session click here</a>'

        . '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container"><input type="hidden" name="TransID" value="' . $_SESSION[$mypage]  . '"/>'
        . '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';

if(isset($_GET['id'])){
$SQL=sprintf("SELECT "
        . "`TaskOwner`,"
        . "`Taskname`,"
        . "`datedue`,"
        . "`Status`,"
        . "`Priority`,"
        . "`frequency`,"
        . "`time_from`,"
        . "`time_to`,"
        . "`location`,"
        . "`taskdetails` "
        . "FROM `Tasks` where `pkey`='%s'",$_GET['id']);
$ResultIndex = DB_query($SQL,$db);
$TaskOwne = DB_fetch_row($ResultIndex);

$_POST["TaskOwner"] = trim($TaskOwne[0]);
$_POST["Taskname"]  = $TaskOwne[1];
$_POST["datedue"]   = ConvertSQLDate($TaskOwne[2]);
$_POST["Status"]    = (int)$TaskOwne[3];
$_POST["Priority"]  = (int)$TaskOwne[4];
$_POST["frequency"] = (int)$TaskOwne[5];
$_POST["time_from"] = (int)$TaskOwne[6];
$_POST["time_to"]   = (int)$TaskOwne[7];
$_POST["location"]  = (int)$TaskOwne[8];
$_POST["taskdetails"] = $TaskOwne[9];
    
    echo '<div class="container"><input type="hidden" name="Currenttaskdetails" value="' .$_POST['taskdetails']. '"/>';
    echo '<div class="container"><input type="hidden" name="TaskID" value="' .$_GET['id']. '"/>';
    echo '<div style="width:60%;"><table class="table-bordered table-condensed">
    <tr><td>TASK Owner (who will perform this task)</td><td><select name="TaskOwner">';
                 foreach ($wwwusers as $Contacts){
                   $selection=(trim($Contacts['userid'])==$_POST["TaskOwner"])?'selected="selected"':'';
                   echo sprintf('<option value="%s" %s>%s</option>',$Contacts['userid'],$selection,$Contacts['realname']);
                } 
    echo '</select></td></tr>
    <tr><td>TASK Name (What is the task called)</td><td><input type="text" name="Taskname" required="required" value="'.$_POST["Taskname"].'"/></td></tr>
    <tr><td>DUE Date</td><td><input type="text" class="date" name="datedue"  value="'.$_POST["datedue"].'" alt="'. $_SESSION['DefaultDateFormat'].'" size="11" maxlength="10" required="required"  onchange="isDate(this, this.value, '. "'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>
    <tr><td>Status</td><td><select name="Status">';
                foreach ($TaskstatusArray as $key => $CRM){
                      $selection=(trim($key)==$_POST['Status'])?'selected="selected"':'';
                    echo sprintf('<option value="%s" %s>%s</option>',$key,$selection,$CRM);
                } 
            echo '</select> </td></tr>
    <tr><td>Priority</td><td><select name="Priority">';
                foreach ($PriorityArray as $key => $CRM){
                      $selection=(trim($key)==$_POST['Priority'])?'selected="selected"':'';
                    echo sprintf('<option value="%s" %s>%s</option>',$key,$selection,$CRM);
                } 
        echo '</select> </td></tr>
         <tr><td>Due To Repeat ?</td>
         <td><select name="frequency">';
                foreach ($frequencyArray as $key => $CRM){
                      $selection=(trim($key)==$_POST['frequency'])?'selected="selected"':'';
                    echo sprintf('<option value="%s" %s>%s</option>',$key,$selection,$CRM);
                } 
         echo   '</select></td></tr>
        <tr><td><label>Location</label></td><td><input type="text" name="location"  value="'.$_POST['location'].'"></td></tr>
        <tr><td>Date</td><td><input type="text" class="date" name="datedue"  value="'.$_POST['datedue'].'" alt="'. $_SESSION['DefaultDateFormat'].'" size="11" maxlength="10" required="required"  onchange="isDate(this, this.value, '."'" .$_SESSION['DefaultDateFormat']."'".')"/></td></tr>
        <tr><td><label>Time</label></td><td>
            <input type="time" name="time_from"  value="'.$_POST['time_from'].'"><span>TO</span> <input type="time" name="time_to"  value="'.$_POST['time_to'].'"></td></tr>
        <tr><td colspan="2">Details Of the Task<br>
            <textarea rows="4" cols="150" name="taskdetails" required="required"></textarea>
            <details><summary>HISTORY</summary><p>'.$_POST['taskdetails'].'</p></details>
        </tr>
        <tr><td colspan="2"><input type="submit" name="edittask" value="Update Task"  onclick="return confirm('. _('Do you want to Update this Task ?').');"></td></tr>
    </table></div>';
    
         
         
}else{
?>
<div style="width:60%;"><table class="table-bordered table-condensed">
    <tr><td>TASK Owner (who will perform this task)</td><td><select name="TaskOwner">
                <?php  foreach ($wwwusers as $Contacts){
                   $selection=(trim($Contacts['userid'])==trim($_SESSION['UserID']))?'selected="selected"':'';
                   echo sprintf('<option value="%s" %s>%s</option>',$Contacts['userid'],$selection,$Contacts['realname']);
                }  ?>
    </select></td></tr>
    <tr><td>TASK Name (What is the task called)</td><td><input type="text" name="Taskname" required="required" /></td></tr>
    <tr><td>Status</td><td><select name="Status">
                <option value="0">Not yet begun</option>
                <option value="1">In progress</option>
                <option value="2">Almost Done</option>
                <option value="3">Done</option>
            </select> </td></tr>
    <tr><td>Priority</td><td><select name="Priority">
                <option value="0">High</option>
                <option value="1">Moderate</option>
                <option value="2">low</option>
            </select> </td></tr>
    <tr><td>Due To Repeat ?</td>
        <td><select name="frequency">
                <option value="0">Once</option>
                <option value="1">Daily</option>
                <option value="7">Weekly</option>
                <option value="30">Monthly</option>
                <option value="365">Annualy</option>
            </select> 
        </td>
    </tr>
  
    <tr><td><label>Location</label></td><td>
            <input type="text" name="location"  value="">
     </td></tr>
        <tr><td>Date</td><td><input type="text" class="date" name="datedue"  value="" alt="<?php echo $_SESSION['DefaultDateFormat'];?>" size="11" maxlength="10" required="required"  onchange="isDate(this, this.value, '<?php echo $_SESSION['DefaultDateFormat'];?>')"/></td></tr>
      <tr><td><label>Time</label></td><td>
            <input type="time" name="time_from"   required="required" value="">
            <span>TO</span>
            <input type="time" name="time_to"  required="required" value="">
        </td></tr>
    
    <tr><td colspan="2">Details Of the Task<br>
            <textarea rows="4" cols="150" name="taskdetails" required="required"></textarea>
        </tr>
        <tr><td colspan="2"><input type="submit" name="savetask" value="Save Task"  onclick="return confirm('<?php echo _('Do you want to Save this Task ?');?>');"></td></tr>
    </table></div>

<?php
}
echo '</div></form>';

include('includes/footer.inc');
?>

