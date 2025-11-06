<?php
session_start();
require 'db.php'; // Your database connection

$error = '';
$success = '';
$token = '';
$validToken = false;

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and check expiry
    $stmt = $conn->prepare("SELECT UserID, email, reset_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $expiry = strtotime($user['reset_expiry']);
        $now = time();
        
        if ($expiry > $now) {
            $validToken = true;
        } else {
            $error = 'This reset link has expired. Please request a new one.';
        }
    } else {
        $error = 'Invalid reset link.';
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Password validation regex
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match($passwordRegex, $newPassword)) {
        $error = 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.';
    } else {
        // Verify token again
        $stmt = $conn->prepare("SELECT id, email, reset_expiry FROM users WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $expiry = strtotime($user['reset_expiry']);
            
            if ($expiry > time()) {
                
                
                // Update password and clear reset token
                $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
                $stmt->bind_param("si", $newPassword, $user['id']);
                
                if ($stmt->execute()) {
                    $success = 'Password reset successfully! Redirecting to login...';
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 3000);
                    </script>";
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'This reset link has expired.';
            }
        } else {
            $error = 'Invalid reset link.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }

        .reset-header {
            background: linear-gradient(135deg, #b91c50 0%, #8b1538 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .reset-header .icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 35px;
        }

        .reset-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .reset-body {
            padding: 40px;
        }

        .note {
            background: #f8f9fa;
            border-left: 4px solid #b91c50;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }

        .note strong {
            color: #b91c50;
            display: block;
            margin-bottom: 5px;
        }

        .note p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 14px 45px 14px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #b91c50;
            box-shadow: 0 0 0 3px rgba(185, 28, 80, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 18px;
            user-select: none;
        }

        .toggle-password:hover {
            color: #b91c50;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            line-height: 1.6;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
        }

        .password-requirements li {
            padding-left: 20px;
            position: relative;
            margin-bottom: 3px;
        }

        .password-requirements li:before {
            content: "•";
            position: absolute;
            left: 5px;
            color: #b91c50;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #b91c50 0%, #8b1538 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(185, 28, 80, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        @media (max-width: 480px) {
            .reset-body {
                padding: 25px;
            }

            .btn-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="icon">🔒</div>
            <h2>Reset Password</h2>
        </div>

        <div class="reset-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($validToken && empty($success)): ?>
                <div class="note">
                    <strong>Note:</strong>
                    <p>Please enter your new password below. Make sure it's strong and secure.</p>
                </div>

                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                placeholder="Enter new password"
                                required
                            >
                            <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
                        </div>
                        <div class="password-requirements">
                            Password must contain:
                            <ul>
                                <li>At least 8 characters</li>
                                <li>One uppercase letter (A-Z)</li>
                                <li>One lowercase letter (a-z)</li>
                                <li>One number (0-9)</li>
                                <li>One special character (@$!%*?&#)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Confirm new password"
                                required
                            >
                            <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                        </div>
                    </div>

                    <div class="btn-container">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php elseif (!$validToken && empty($_GET['token'])): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span>No reset token provided. Please use the link from your email.</span>
                </div>
                <div class="btn-container">
                    <a href="index.php" class="btn btn-primary" style="flex: none; width: 100%;">Back to Home</a>
                </div>
            <?php elseif (!$validToken): ?>
                <div class="btn-container">
                    <a href="index.php" class="btn btn-primary" style="flex: none; width: 100%;">Back to Home</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.textContent = '🙈';
            } else {
                field.type = 'password';
                icon.textContent = '👁️';
            }
        }

        // Client-side password validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (!regex.test(password)) {
                e.preventDefault();
                alert('Password does not meet the requirements. Please check the password requirements listed below the input field.');
                return false;
            }
        });
    </script>
</body>
</html>