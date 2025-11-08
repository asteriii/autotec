<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 30);

session_start();

require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send'])) {

    if (!isset($_POST['email']) || empty($_POST['email'])) {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .modal {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 440px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }
                .icon {
                    width: 64px;
                    height: 64px;
                    border: 4px solid #a4133c;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    color: #a4133c;
                    font-size: 36px;
                    font-weight: bold;
                }
                .title {
                    color: #a4133c;
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 16px;
                }
                .message {
                    color: #5f6368;
                    font-size: 16px;
                    line-height: 1.5;
                    margin-bottom: 32px;
                }
                .btn {
                    padding: 12px 32px;
                    border: none;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-primary {
                    background: #a4133c;
                    color: white;
                }
                .btn-primary:hover {
                    background: #8b0f31;
                }
            </style>
        </head>
        <body>
            <div class='modal'>
                <div class='icon'>!</div>
                <div class='title'>Required Field</div>
                <div class='message'>Email is required to reset your password.</div>
                <button class='btn btn-primary' onclick='history.back()'>Go Back</button>
            </div>
        </body>
        </html>";
        exit;
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .modal {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 440px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }
                .icon {
                    width: 64px;
                    height: 64px;
                    border: 4px solid #a4133c;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    color: #a4133c;
                    font-size: 36px;
                    font-weight: bold;
                }
                .title {
                    color: #a4133c;
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 16px;
                }
                .message {
                    color: #5f6368;
                    font-size: 16px;
                    line-height: 1.5;
                    margin-bottom: 32px;
                }
                .btn {
                    padding: 12px 32px;
                    border: none;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-primary {
                    background: #a4133c;
                    color: white;
                }
                .btn-primary:hover {
                    background: #8b0f31;
                }
            </style>
        </head>
        <body>
            <div class='modal'>
                <div class='icon'>!</div>
                <div class='title'>Invalid Email</div>
                <div class='message'>Please enter a valid email address.</div>
                <button class='btn btn-primary' onclick='history.back()'>Go Back</button>
            </div>
        </body>
        </html>";
        exit;
    }

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Security: Don't reveal if email exists or not
        echo "<!DOCTYPE html>
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .modal {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 440px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }
                .icon {
                    width: 64px;
                    height: 64px;
                    border: 4px solid #4caf50;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    color: #4caf50;
                    font-size: 36px;
                    font-weight: bold;
                }
                .icon::before {
                    content: '✓';
                }
                .title {
                    color: #333;
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 16px;
                }
                .message {
                    color: #5f6368;
                    font-size: 16px;
                    line-height: 1.5;
                    margin-bottom: 32px;
                }
                .btn {
                    padding: 12px 32px;
                    border: none;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-primary {
                    background: #a4133c;
                    color: white;
                }
                .btn-primary:hover {
                    background: #8b0f31;
                }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 3000);
            </script>
        </head>
        <body>
            <div class='modal'>
                <div class='icon'></div>
                <div class='title'>Email Sent</div>
                <div class='message'>If this email exists in our system, a reset link has been sent. Please check your inbox.</div>
                <button class='btn btn-primary' onclick='window.location.href=\"../index.php\"'>Return to Home</button>
            </div>
        </body>
        </html>";
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
    
    // Get credentials from environment
    $apiKey = getenv('SENDGRID_API_KEY');
    $fromEmail = getenv('SMTP_FROM_EMAIL');
    
    if (!$fromEmail) {
        $fromEmail = 'noreply@autotec.com';
    }

    // HTML Email content
    $htmlContent = "
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

    // Plain text version
    $textContent = "Password Reset Request\n\n"
                 . "Hello,\n\n"
                 . "We received a request to reset your password for your AutoTec account.\n\n"
                 . "Click this link to reset your password:\n$resetLink\n\n"
                 . "This link will expire in 10 minutes.\n\n"
                 . "If you didn't request this, please ignore this email.\n\n"
                 . "© 2025 AutoTec";

    // SendGrid API v3 payload
    $data = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email]
                ],
                'subject' => 'Password Reset Request - AutoTec'
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => 'AutoTec'
        ],
        'reply_to' => [
            'email' => $fromEmail,
            'name' => 'AutoTec Support'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $textContent
            ],
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];

    // Send via SendGrid Web API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Check response
    if ($httpCode === 202) {
        // Success - SendGrid accepted the email
        echo "<!DOCTYPE html>
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .modal {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 440px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }
                .icon {
                    width: 64px;
                    height: 64px;
                    border: 4px solid #4caf50;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    color: #4caf50;
                    font-size: 36px;
                    font-weight: bold;
                }
                .icon::before {
                    content: '✓';
                }
                .title {
                    color: #333;
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 16px;
                }
                .message {
                    color: #5f6368;
                    font-size: 16px;
                    line-height: 1.5;
                    margin-bottom: 32px;
                }
                .btn {
                    padding: 12px 32px;
                    border: none;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-primary {
                    background: #a4133c;
                    color: white;
                }
                .btn-primary:hover {
                    background: #8b0f31;
                }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 3000);
            </script>
        </head>
        <body>
            <div class='modal'>
                <div class='icon'></div>
                <div class='title'>Success!</div>
                <div class='message'>Password reset link sent successfully! Please check your email (including spam folder).</div>
                <button class='btn btn-primary' onclick='window.location.href=\"../index.php\"'>Return to Home</button>
            </div>
        </body>
        </html>";
    } else {
        // Error - log details
        error_log("SendGrid API Error - HTTP Code: $httpCode");
        error_log("Response: $response");
        error_log("Curl Error: $curlError");
        
        echo "<!DOCTYPE html>
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .modal {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 440px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }
                .icon {
                    width: 64px;
                    height: 64px;
                    border: 4px solid #f44336;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    color: #f44336;
                    font-size: 36px;
                    font-weight: bold;
                }
                .icon::before {
                    content: '✕';
                }
                .title {
                    color: #f44336;
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 16px;
                }
                .message {
                    color: #5f6368;
                    font-size: 16px;
                    line-height: 1.5;
                    margin-bottom: 32px;
                }
                .btn {
                    padding: 12px 32px;
                    border: none;
                    border-radius: 6px;
                    font-size: 15px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-primary {
                    background: #a4133c;
                    color: white;
                }
                .btn-primary:hover {
                    background: #8b0f31;
                }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 4000);
            </script>
        </head>
        <body>
            <div class='modal'>
                <div class='icon'></div>
                <div class='title'>Error</div>
                <div class='message'>Unable to send email at this time. Please try again later or contact support.</div>
                <button class='btn btn-primary' onclick='window.location.href=\"../index.php\"'>Return to Home</button>
            </div>
        </body>
        </html>";
    }
    exit;

} else {
    header('Location: ../index.php');
    exit;
}
?>