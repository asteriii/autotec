<?php
require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = 'smtp.sendgrid.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'apikey';
    $mail->Password = getenv('SENDGRID_API_KEY');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 15;

    $mail->setFrom('estrellakyla0220@gmail.com', 'AutoTec Test');
    $mail->addAddress('estrellakyla0220@gmail.com');
    $mail->Subject = 'SendGrid Test';
    $mail->Body = 'This is a test email';

    if ($mail->send()) {
        echo "SUCCESS! Email sent.";
    } else {
        echo "FAILED: " . $mail->ErrorInfo;
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Mailer Error: " . $mail->ErrorInfo;
}
?>