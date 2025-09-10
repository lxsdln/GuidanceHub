<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

function sendStatusEmail($email, $student_name, $appointment_date, $appointment_time, $status) {
    if (empty($email)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'example@gmail.com'; // your SMTP username
        $mail->Password   = 'secret'; // your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('example@gmail.com', 'Guidance Office');
        $mail->addAddress($email, $student_name);

        $mail->isHTML(true);
        $mail->Subject = 'Appointment Status Update';
        $mail->Body = "
            <p>Hi {$student_name},</p>
            <p>Your appointment on <strong>{$appointment_date} at {$appointment_time}-</strong> has been updated.</p>
            <p>Status: <strong>" . ucfirst($status) . "</strong></p>
            <p>Please check your account for more details.</p>
            <p>Regards,<br>Guidance Office</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}


?>

