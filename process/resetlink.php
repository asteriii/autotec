<?php
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
        // For security, don't reveal if email exists or not
        echo "<script>alert('If this email exists, a reset link has been sent.'); window.location.href = 'https://autotec-production.up.railway.app/index.php';</script>";
        exit;
    }

    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32)); // 64-character token
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Save token to database
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $token, $expiry, $email);
    $stmt->execute();

    $resetLink = "https://autotec-production.up.railway.app/reset_password.php?token=" . $token;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'marlbenjaminbedana2@gmail.com';
        $mail->Password = 'kaqp jdom qtsq khsr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('marlbenjaminbedana2@gmail.com', 'AutoTec');
        $mail->addAddress($email);
        $mail->addReplyTo('marlbenjaminbedana2@gmail.com', 'AutoTec Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - AutoTec';
        $mail->Body    = "
            <h2>Password Reset Request</h2>
            <p>Click the link below to reset your password:</p>
            <p><a href='$resetLink' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;'>Reset Password</a></p>
            <p>Or copy this link: <br>$resetLink</p>
            <p><strong>This link will expire in 10 minutes.</strong></p>
            <p>If you didn't request this, please ignore this email.</p>
        ";
        $mail->AltBody = "Click this link to reset your password: $resetLink (Valid for 10 minutes)";

        $mail->send();
        echo "<script>alert('Reset link sent successfully! Check your email.'); window.location.href = 'https://autotec-production.up.railway.app/index.php';</script>";
        exit;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        echo "<script>alert('Failed to send email. Please try again later.'); window.history.back();</script>";
        exit;
    }
} else {
    header('Location: https://autotec-production.up.railway.app/index.php');
    exit;
}
?>