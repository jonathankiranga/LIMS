<?php
require_once 'PHPMailerAutoload.php';
$mail = new PHPMailer(true);
class phpmailerAppException extends phpmailerException {}

Class MyMailer {
    var $host='';
    var $port='';
    var $username='';
    var $password='';
    var $auth='';
     
    function __construct() {
           global $db;
            // Check the mail server setting status
            $sql="SELECT  id,host,port,heloaddress,username,password,timeout,auth FROM emailsettings";
            $ErrMsg = _('The email settings information cannot be retrieved');
            $DbgMsg = _('The SQL that failed was');
       
            $result=DB_query($sql, $db,$ErrMsg,$DbgMsg);
            if(DB_num_rows($result)==0){
                prnMsg('You have not setup the mail details','warn',$_SESSION['UsersRealName']);
            }else{
                   $myrow= DB_fetch_row($result);
                   $this->host = $myrow[1];
                   $this->port = $myrow[2];
                   $this->username = $myrow[4];
                   $this->password = trim($this::decrypt($myrow[5]));
                   $this->auth = $myrow[7];
            }
    }
       
    
    function sendmail($USERNAME,$REALNAME,$BODY,$ATTACHMENTPATH=''){

        $results_messages = array();
        $mail = new PHPMailer(true);
        $mail->CharSet = 'utf-8';
        ini_set('default_charset', 'UTF-8');

        try {
        if(!PHPMailer::validateAddress($USERNAME)) {
            throw new phpmailerAppException("Email address " . $USERNAME . " is invalid -- aborting!"); 
        }

        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true));
        $mail->isSMTP();
        $mail->SMTPDebug  = 0;
        $mail->Host       = $this->host;
        $mail->Port       = $this->port;
        $mail->SMTPSecure = "tsl";
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->username;
        $mail->Password   = $this->password;
        $mail->addAddress($USERNAME,$USERNAME);
        $mail->Subject  = $REALNAME;
        $mail->WordWrap = 78;
        $mail->msgHTML($BODY, dirname(__FILE__), true); //Create message bodies and embed images

        if(mb_strlen($ATTACHMENTPATH)){ 
             $mail->addAttachment($ATTACHMENTPATH,basename($ATTACHMENTPATH)); 
        }

         try {
              $mail->send();
              $results_messages[] = "Message has been sent using SMTP";
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