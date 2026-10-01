<html>
<head>
  <title>SMARTERNOW DATA VENTURE</title><meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="Jonathan Kirnaga">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="css/signin.css" type="text/css"/>
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css"/>
      <?php   
        if (!isset($RootPath)){
            $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
            if ($RootPath == '/' OR $RootPath == "\\") {
                $RootPath = '';
            }
        }
     echo '<script type="text/javascript" src="'.$RootPath.'/javascripts/jquery.min.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/jquery-1.12.0.min.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/jquery.tablesorter.pager.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/bootstrap.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/bootstrap.min.js"></script>';
        ?>
</head>
<body style="max-width:500px"><div class="w3-padding w3-center">
 	<form autocomplete="off" class="form-signin" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">
        <h3 class="form-signin-heading">Staff Email Configuration</h3>
        <div class="checkbox">
<?php
require_once 'Mailer/PHPMailerAutoload.php';

$_SESSION['MailserverSucess']=0;

echo '<p class="page_title_text">' . _('SMTP Server Settings') . '</p>';
// First check if there are smtp server data or not

if(isset($_POST['UserName'])){
$SaveClass = new Mailsavesettings();

$results_messages = array();
$mail = new PHPMailer(true);
class phpmailerAppException extends phpmailerException {}

$mail->CharSet = 'utf-8';
ini_set('default_charset','UTF-8');
  
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
$mail->SMTPSecure = $_POST['Secure'];
$mail->SMTPAuth   = true;
$mail->Username   = $_POST['UserName'];
$mail->Password   = $_POST['Password'];
//$mail->addReplyTo("NO-REPLY", "ERP");
$mail->setFrom($_POST['UserName'],'SmartERP');
$mail->addAddress($_POST['UserName'],'Smarterp');
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
      $SaveClass->Saveit();
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

if(file_exists('chats/EmailConfig.php')){
    include 'chats/EmailConfig.php';
        $MailServerSetting = 1;
        $myrow['host']=HOST;
        $myrow['port']=PORT;
        $myrow['heloaddress']=heloaddress;
        $myrow['username']=username;
        $myrow['auth']=auth;
        $myrow['timeout']=timeout;
        $myrow['secure']=defined('SECURE') ? SECURE : '';
}else{
       $MailServerSetting = 0;
        $myrow['host']='smtp.gmail.com';
        $myrow['port']='587';
        $myrow['heloaddress']='elo';
        $myrow['username']='';
        $myrow['password']='';
        $myrow['auth']=1;
        $myrow['timeout']=30;
        $myrow['secure']='';
}


echo '<form autocomplete="off"method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
	<div><input type="hidden" name="MailServerSetting" value="' . $MailServerSetting . '" />
	<table class="table table-bordered">
	<tr><td>' . _('Server Host Name') . '</td>
		<td><input type="text" name="Host" required="required" value="' . $myrow['host'] . '" /></td>
	</tr><tr><td>' . _('SMTP port') . '</td>
		<td><input type="text" name="Port" required="required"  class="number" value="' . $myrow['port'].'" /></td>
	</tr><tr>
     <td>' . _('Authorisation Required') . '</td><td><select name="Auth">';
        if ($myrow['auth']==1) {
                echo '<option selected="selected" value="1">' . _('True') . '</option>';
                echo '<option value="0">' . _('False') . '</option>';
        } else {
                echo '<option value="1">' . _('True') . '</option>';
                echo '<option selected="selected" value="0">' . _('False') . '</option>';
        }
    echo '</select></td></tr>
	<tr><td>' . _('Encryption') . '</td><td><select name="Secure">';
        $secureOpts = array(''=>'None','tls'=>'TLS','ssl'=>'SSL');
        foreach ($secureOpts as $val => $label) {
            $sel = ($myrow['secure']==$val) ? 'selected' : '';
            echo '<option value="'.$val.'" '.$sel.'>'.$label.'</option>';
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
 
class Mailsavesettings{

    function Saveit(){
        global $db;
  
        if(isset($_POST['submit']) ){
              
               // Save password as plaintext — no encryption
                    $msg = "<?php\n\n";
                    $msg .= "// Email User configurable variables\n";
                    $msg .= "//---------------------------------------------------\n";
                    $msg .= "define(\"HOST\",\"" .$_POST['Host']. "\");\n";
                    $msg .= "define(\"PORT\",\"" .$_POST['Port']. "\");\n";
                    $msg .= "define(\"heloaddress\",\"".$_POST['HeloAddress']."\");\n";
                    $msg .= "define(\"username\",\"".$_POST['UserName']."\");\n";
                    $msg .= "define(\"password\",\"".$_POST['Password']."\");\n";
                    $msg .= "define(\"auth\",\"".$_POST['Auth']."\");\n";
                    $msg .= "define(\"timeout\",\"30\");\n";
                    $msg .= "define(\"SECURE\",\"".$_POST['Secure']."\");\n";
                    $msg .= "?>";
                   //write the config.php file since we have test the writability of the root path and companies,
                    //there is little possibility that it will fail here. So just an warn if it is failed.
                    if(!$zp = fopen('chats/EmailConfig.php','w')){
                            prnMsg(_("Cannot open the configuration file"),'error');
                    } else {
                        if (!fwrite($zp, $msg)){
                            fclose($zp);
                            prnMsg(_("Cannot write to the configuration file"),'error');
                        }
                        //close file
                        fclose($zp);
                    }
			
}
      
        }
    }
    

    function encrypt($data){
     // Store cipher method
    $ciphering = "BF-CBC";

    $options = 0;
    // Use random_bytes() function which gives
    // randomly 16 digit values
    $encryption_iv = "12345678";
    // Alternatively, we can use any 16 digit
    // characters or numeric for iv
    $encryption_key = "12345678";
    // Encryption of string process starts
    $encryption = openssl_encrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);

    return $encryption;
    }


    function decrypt($data){
    // Store cipher method
    $ciphering = "BF-CBC";

    $options = 0;

    $decryption_iv ="12345678" ;
    // Store the decryption key
    $decryption_key = "12345678";
    // Descrypt the string
    $decryption = openssl_decrypt ($data, $ciphering,$decryption_key, $options,$decryption_iv);

    return $decryption;
    }





Class Encryptme{

      
function encrypt($data){
 // Store cipher method
$ciphering = "BF-CBC";
 
$options = 0;
// Use random_bytes() function which gives
// randomly 16 digit values
$encryption_iv = "12345678";
// Alternatively, we can use any 16 digit
// characters or numeric for iv
$encryption_key = "12345678";
// Encryption of string process starts
$encryption = openssl_encrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);

return $encryption;
}


function decrypt($data){
// Store cipher method
$ciphering = "BF-CBC";

$options = 0;

$decryption_iv ="12345678" ;
// Store the decryption key
$decryption_key = "12345678";
// Descrypt the string
$decryption = openssl_decrypt ($data, $ciphering,$decryption_key, $options,$decryption_iv);

return $decryption;
}


}


?>

</div></div></body>
</html>
