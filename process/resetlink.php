<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 30);

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send'])) {

    if (!isset($_POST['email']) || empty($_POST['email'])) {
        echo "<script>alert('Email is required.'); window.history.back();</script>";
        exit;
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        echo "<script>alert('Invalid email format.'); window.history.back();</script>";
        exit;
    }

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Security: Don't reveal if email exists or not
        echo "<script>alert('If this email exists, a reset link has been sent.'); window.location.href = '../index.php';</script>";
        exit;
    }

    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Save token to database
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $token, $expiry, $email);
    $stmt->execute();

    $resetLink = "https://autotec-production.up.railway.app/reset_password.php?token=" . $token;

    $mail = new PHPMailer(true);

    try {
        // SendGrid SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey';
        $mail->Password = getenv('SENDGRID_API_KEY');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Timeouts
        $mail->Timeout = 15;
        $mail->SMTPDebug = 0;
        
        // Connection options
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );

        // Recipients
        $fromEmail = getenv('SMTP_FROM_EMAIL');
        $mail->setFrom($fromEmail, 'AutoTec');
        $mail->addAddress($email);
        $mail->addReplyTo($fromEmail, 'AutoTec Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - AutoTec';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #a4133c; margin: 0;'>AutoTec</h1>
                    </div>
                    <h2 style='color: #333; border-bottom: 2px solid #a4133c; padding-bottom: 10px;'>Password Reset Request</h2>
                    <p style='color: #555; font-size: 16px; line-height: 1.6;'>Hello,</p>
                    <p style='color: #555; font-size: 16px; line-height: 1.6;'>We received a request to reset your password for your AutoTec account. Click the button below to proceed:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' style='background-color: #a4133c; color: white; padding: 15px 40px; text-decoration: none; display: inline-block; border-radius: 5px; font-weight: bold; font-size: 16px;'>Reset Password</a>
                    </div>
                    <p style='color: #666; font-size: 14px; line-height: 1.6;'>Or copy and paste this link into your browser:</p>
                    <p style='background-color: #f8f8f8; padding: 10px; border-radius: 5px; word-break: break-all;'><a href='$resetLink' style='color: #a4133c;'>$resetLink</a></p>
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
                        <p style='color: #d32f2f; font-size: 14px; font-weight: bold; margin-bottom: 10px;'>⚠️ Important:</p>
                        <ul style='color: #666; font-size: 13px; line-height: 1.6;'>
                            <li>This link will expire in <strong>10 minutes</strong></li>
                            <li>If you didn't request this, please ignore this email</li>
                            <li>Your password will not change until you access the link and create a new one</li>
                        </ul>
                    </div>
                    <div style='margin-top: 30px; text-align: center; color: #999; font-size: 12px;'>
                        <p>© 2025 AutoTec. All rights reserved.</p>
                    </div>
                </div>
            </div>
        ";
        
        $mail->AltBody = "Password Reset Request\n\n"
                       . "Hello,\n\n"
                       . "We received a request to reset your password for your AutoTec account.\n\n"
                       . "Click this link to reset your password:\n$resetLink\n\n"
                       . "This link will expire in 10 minutes.\n\n"
                       . "If you didn't request this, please ignore this email.\n\n"
                       . "© 2025 AutoTec";

        if ($mail->send()) {
            echo "<script>
                alert('Password reset link sent successfully! Please check your email (including spam folder).');
                window.location.href = '../index.php';
            </script>";
        } else {
            throw new Exception("Email could not be sent.");
        }
        exit;

    } catch (Exception $e) {
        error_log("SendGrid Mailer Error: " . $mail->ErrorInfo);
        error_log("Exception Message: " . $e->getMessage());
        
        echo "<script>
            alert('There was an issue sending the email. Please try again later.');
            window.location.href = '../index.php';
        </script>";
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>