<?php
require __DIR__ . '/vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2;                                    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'skonnect.system@gmail.com';              
    $mail->Password   = 'dktl mpvg fwfu hqnt';                  
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('skonnect.system@gmail.com', 'SKonnect'); 
    $mail->addAddress('email@gmail.com', 'Emailer Name');         // Replace this with the email you want to send to

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SKonnect Test Email';
    $mail->Body    = 'This is a <strong>test email</strong> from SKonnect PHPMailer.';
    $mail->AltBody = 'This is a test email from SKonnect PHPMailer.';

    $mail->send();
    echo '<h2 style="color:green;">✅ Email sent successfully!</h2>';
} catch (Exception $e) {
    echo '<h2 style="color:red;">❌ Email could not be sent.</h2>';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
}
?>