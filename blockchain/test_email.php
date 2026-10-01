<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->SMTPDebug  = 2;
    $mail->Host       = 'sh09.cloudpap.co.ke';
    $mail->Port       = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth   = true;
    $mail->Username   = 'recovery@erplabworks.co.ke';
    $mail->Password   = 'd0?f8m28N';
    $mail->SMTPOptions = ['ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true
    ]];

    $mail->setFrom('recovery@erplabworks.co.ke', 'ERP System');
    $mail->addAddress('jonathankiranga@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - SMTP Check';
    $mail->Body    = '<p>SMTP is working correctly from sh09.cloudpap.co.ke</p>';
    $mail->AltBody = 'SMTP is working correctly from sh09.cloudpap.co.ke';

    $mail->send();
    echo "\n\nSUCCESS: Email sent to jonathankiranga@gmail.com\n";
} catch (Exception $e) {
    echo "\n\nFAILED: " . $mail->ErrorInfo . "\n";
}
