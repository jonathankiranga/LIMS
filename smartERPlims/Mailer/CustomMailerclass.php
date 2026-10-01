<?php
require_once __DIR__ . '/PHPMailerAutoload.php';
$mail = new PHPMailer(true);
class phpmailerAppException extends phpmailerException {}

Class MyMailer {
    var $host='';
    var $port='';
    var $username='';
    var $password='';
    var $auth='';
    var $configError='';
     
    function __construct() {
        if (!defined('HOST')) {
            $configFile = __DIR__ . '/../chats/EmailConfig.php';
            if (!file_exists($configFile)) {
                $this->configError = 'Email configuration not found. Please configure SMTP settings.';
                return;
            }
            include $configFile;
        }
        $this->host     = HOST;
        $this->port     = PORT;
        $this->username = username;
        $this->password = trim(password);
        $this->auth     = defined('auth') ? auth : '';
    }
       
    
    function sendmail($USERNAME,$REALNAME,$BODY,$ATTACHMENTPATH='',$CCEMAIL='',$FROMNAME='',$REPLYTO=''){

        $results_messages = array();
        $mail = new PHPMailer(true);
        $mail->CharSet = 'utf-8';
        ini_set('default_charset', 'UTF-8');

        try {
        if($this->configError !== '') {
            throw new phpmailerAppException($this->configError);
        }
        if(!PHPMailer::validateAddress($USERNAME)) {
            throw new phpmailerAppException("Email address " . $USERNAME . " is invalid -- aborting!"); 
        }

        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true));
        $mail->isSMTP();
        $mail->SMTPDebug  = 0;
        $mail->Host       = $this->host;
        $mail->Port       = $this->port;
        $mail->SMTPSecure = (defined('SECURE') && SECURE !== '') ? SECURE : 'tls';
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->username;
        $mail->Password   = $this->password;
        // Explicit envelope + From: without these Gmail drops the message
        // after the relay accepts it (SPF alignment / missing sender).
        $mail->setFrom($this->username, trim((string)$FROMNAME) !== '' ? trim((string)$FROMNAME) : $this->username);
        $REPLYTO = trim((string)$REPLYTO);
        if ($REPLYTO !== '' && strcasecmp($REPLYTO, $USERNAME) !== 0 && PHPMailer::validateAddress($REPLYTO)) {
            $mail->addReplyTo($REPLYTO);
        }
        $mail->addAddress($USERNAME,$USERNAME);
        $CCEMAIL = trim((string)$CCEMAIL);
        if ($CCEMAIL !== '' && strcasecmp($CCEMAIL, $USERNAME) !== 0) {
            if(!PHPMailer::validateAddress($CCEMAIL)) {
                throw new phpmailerAppException("CC email address " . $CCEMAIL . " is invalid -- aborting!");
            }
            $mail->addCC($CCEMAIL);
        }
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
              throw new phpmailerAppException('Unable to send to: ' . $USERNAME. ': '.$e->getMessage());
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