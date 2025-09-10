<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';


function sendOTPEmail($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'exmple@gmail.com'; // your SMTP username
        $mail->Password   = 'secret'; // your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('example@gmail.com', 'Guidance Office');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code';
        $mail->Body = "<p>Your OTP code is: <b>$otp</b></p><p>This code expires in 5 minutes.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
