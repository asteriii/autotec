<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 30); // Prevent infinite loading

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
        // IMPORTANT: Add timeouts to prevent hanging
        $mail->Timeout = 10; // Connection timeout
        $mail->SMTPDebug = 0; // Disable debug output
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'marlbenjaminbedana2@gmail.com';
        $mail->Password = 'kaqp jdom qtsq khsr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Add these to help with connection issues
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('marlbenjaminbedana2@gmail.com', 'AutoTec');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - AutoTec';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #a4133c;'>Password Reset Request</h2>
                    <p>You requested to reset your password. Click the button below to proceed:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' style='background-color: #a4133c; color: white; padding: 15px 30px; text-decoration: none; display: inline-block; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                    </div>
                    <p style='color: #666; font-size: 14px;'>Or copy this link:<br><a href='$resetLink'>$resetLink</a></p>
                    <p style='color: #999; font-size: 12px;'><strong>This link will expire in 10 minutes.</strong></p>
                    <p style='color: #999; font-size: 12px;'>If you didn't request this, please ignore this email.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Click this link to reset your password: $resetLink (Valid for 10 minutes)";

        // Try to send with error catching
        if ($mail->send()) {
            echo "<script>alert('Reset link sent successfully! Check your email.'); window.location.href = '../index.php';</script>";
        } else {
            throw new Exception("Email could not be sent.");
        }
        exit;

    } catch (Exception $e) {
        // Log the actual error
        error_log("Mailer Error: " . $mail->ErrorInfo);
        
        // Show user-friendly message but still save the token (they can try again)
        echo "<script>
            alert('There was an issue sending the email. Please check your email settings or try again later. Error: " . addslashes($mail->ErrorInfo) . "');
            window.location.href = '../index.php';
        </script>";
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>