<?php
/* $Id: SMTPServer.php 4469 2011-01-15 02:28:37Z daintree $*/
include('includes/session.inc');
$Title = _('SMTP Server details');
include('includes/header.inc');
require_once 'Mailer/PHPMailerAutoload.php';
include('includes/encrypt.inc');
$_SESSION['MailserverSucess']=0;

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/email.gif" title="' . _('SMTP Server') . '" alt="" />' . ' ' . _('SMTP Server Settings') . '</p>';
// First check if there are smtp server data or not

class Mailsavesettings extends Encryptme{

    function __construct(){
        global $db;
     //   $Encrpt = New Encryptme();

        if(isset($_POST['submit']) and $_SESSION['MailserverSucess']==1){ 
            
              if($_POST['MailServerSetting']==1) {
        //If there are already data setup, Update the table
               $bas64=$this::encrypt($_POST['Password']);
                $sql="UPDATE emailsettings SET
                        host='".$_POST['Host']."',
                        port='".$_POST['Port']."',
                        heloaddress='".$_POST['HeloAddress']."',
                        username='".$_POST['UserName']."',
                        password='".$bas64."',
                        auth='".$_POST['Auth']."'";

                $ErrMsg = _('The email setting information failed to update');
                $DbgMsg = _('The SQL failed to update is ');
                $result1=DB_query($sql, $db, $ErrMsg, $DbgMsg);
                unset($_POST['MailServerSetting']);
                echo '<br />';

             } else {

               $bas64=$this::encrypt($_POST['Password']);
               $sql = "INSERT INTO emailsettings(
                                host,
                                port,
                                heloaddress,
                                username,
                                password,
                                auth)
                            VALUES ('".$_POST['Host']."',
                                    '".$_POST['Port']."',
                                    '".$_POST['HeloAddress']."',
                                    '".$_POST['UserName']."',
                                    '".$bas64."',
                                    '".$_POST['Auth']."')";
                $ErrMsg = _('The email settings failed to be inserted');
                $DbgMsg = _('The SQL failed to insert the email information is');
                $result2 = DB_query($sql,$db);
                prnMsg(_('The settings for the SMTP server have been sucessfully inserted'),'success');
                echo '<br/>';
        }
        
        }
    }
}


if(isset($_POST['UserName'])){
$results_messages = array();
$mail = new PHPMailer(true);
$mail->CharSet = 'utf-8';
ini_set('default_charset', 'UTF-8');
  
try {
    $to = $_POST['UserName'];
    if(!PHPMailer::validateAddress($to)) {
      throw new phpmailerAppException("Email address " . $to . " is invalid -- aborting!");
    }

$mail->SMTPOptions = array( 'ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true));
$mail->isSMTP();
$mail->SMTPDebug  = 0;
$mail->Host       = $_POST['Host'];
$mail->Port       = $_POST['Port'];
$mail->SMTPSecure = "tsl";
$mail->SMTPAuth   = true;
$mail->Username   = $_POST['UserName'];
$mail->Password   = $_POST['Password'];
//$mail->addReplyTo("NO-REPLY", "ERP");
$mail->setFrom($_POST['UserName'],$_SESSION['UsersRealName']);
$mail->addAddress($_POST['UserName'],$_SESSION['UsersRealName']);
$mail->Subject  = "Testing This mail";
$body = <<<'EOT'
Do not reply to this mail. Its computer generated
EOT;
$mail->WordWrap = 78;
$mail->msgHTML($body, dirname(__FILE__), true); //Create message bodies and embed images
$mail->addAttachment('Mailer/examples/images/phpmailer_mini.png','phpmailer_mini.png');  // optional name
$mail->addAttachment('Mailer/examples/images/phpmailer.png', 'phpmailer.png');  // optional name
 
    try {
      $mail->send();
      $results_messages[] = "Message has been sent using SMTP";
      $_SESSION['MailserverSucess']=1;
      (new Mailsavesettings());
    }catch (phpmailerException $e) {
      throw new phpmailerAppException('Unable to send to: ' . $to. ': '.$e->getMessage());
    }

}catch (phpmailerAppException $e) {
  $results_messages[] = $e->errorMessage();
}
 
if (count($results_messages) > 0) {
  echo "<h2>Run results</h2>\n";
  echo "<ul>\n";
foreach ($results_messages as $result) {
  echo "<li>$result</li>\n";
}
echo "</ul>\n";
}

}

  // Check the mail server setting status
$sql="SELECT  id,host,port,heloaddress,username,password,timeout,auth FROM emailsettings";
$ErrMsg = _('The email settings information cannot be retrieved');
$DbgMsg = _('The SQL that failed was');

$result=DB_query($sql, $db,$ErrMsg,$DbgMsg);
if(DB_num_rows($result)!=0){
        $MailServerSetting = 1;
        $myrow=DB_fetch_array($result); 
       
}else{
        DB_free_result($result);
        $MailServerSetting = 0;
        $myrow['host']='smtp.gmail.com';
        $myrow['port']='587';
        $myrow['heloaddress']='elo';
        $myrow['username']='';
        $myrow['password']='';
        $myrow['auth']=1;
        $myrow['timeout']=30;
}


echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
	<div>
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
	<input type="hidden" name="MailServerSetting" value="' . $MailServerSetting . '" />
	<table class="table-bordered">
	<tr>
		<td>' . _('Server Host Name') . '</td>
		<td><input type="text" name="Host" required="required" value="' . $myrow['host'] . '" /></td>
	</tr>
	<tr>
		<td>' . _('SMTP port') . '</td>
		<td><input type="text" name="Port" required="required" size="4" class="number" value="' . $myrow['port'].'" /></td>
	</tr>
	
	<tr>
     <td>' . _('Authorisation Required') . '</td><td><select name="Auth">';
        if ($myrow['auth']==1) {
                echo '<option selected="selected" value="1">' . _('True') . '</option>';
                echo '<option value="0">' . _('False') . '</option>';
        } else {
                echo '<option value="1">' . _('True') . '</option>';
                echo '<option selected="selected" value="0">' . _('False') . '</option>';
        }
    echo '</select></td></tr>
	<tr>
		<td>' . _('User Name') . '</td>
		<td><input type="text" required="required" name="UserName" size="50" maxlength="50" value="' . $myrow['username']  .'" /></td>
	</tr>
	<tr>
		<td>' . _('Password') . '</td>
		<td><input type="password" required="required" name="Password"/></td>
	</tr>
	
	<tr>
		<td colspan="2"><div class="centre"><input type="submit" name="submit" value="' . _('Update') . '" /></div></td>
	</tr>
	</table>
	</div>
	</form>';
    
    
    
    

include('includes/footer.inc');

?>
