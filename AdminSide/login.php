<?php
session_start();

$servername = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'OUJHNoEzFNhsIgRFuduLzLFWunvvMrrP';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: adminDash.php');
    exit();
}

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            // Create database connection
            $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $db_username, $db_password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Prepare and execute query to find admin user
            $stmt = $pdo->prepare("SELECT admin_id, username, Email, password, BranchName FROM admin WHERE username = ? OR Email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            // Debug: Check if user exists (remove after testing)
            if (!$admin) {
                $error_message = "No user found with username/email: $username";
            } else {
                // Debug: Check password verification (remove after testing)
                if (!password_verify($password, $admin['password'])) {
                    // Check if password is stored as plain text (not recommended for production)
                    if ($password === $admin['password']) {
                        $error_message = "Password matches but stored as plain text. Please hash your passwords!";
                        // For now, allow login but recommend fixing this
                        $admin['password_verified'] = true;
                    } else {
                        $error_message = "Password verification failed. Check if passwords are properly hashed.";
                        // Debug info (remove after testing):
                        $error_message .= " Password length in DB: " . strlen($admin['password']);
                    }
                } else {
                    $admin['password_verified'] = true;
                }
                
                if (isset($admin['password_verified']) && $admin['password_verified']) {
                    // Login successful
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['Email'];
                    $_SESSION['admin_branch'] = $admin['BranchName'];
                    $_SESSION['login_time'] = time();
                    
                    // Set remember me cookie if checked (optional)
                    if ($remember) {
                        $cookie_token = bin2hex(random_bytes(32));
                        setcookie('admin_remember', $cookie_token, time() + (86400 * 30), '/', '', false, true); // 30 days - set secure to false for local testing
                    }
                    
                    // Update last login time (optional) - first check if column exists
                    try {
                        $update_stmt = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                        $update_stmt->execute([$admin['admin_id']]);
                    } catch (PDOException $e) {
                        // Column might not exist, ignore this error
                        error_log("Last login update failed: " . $e->getMessage());
                    }
                    
                    // Redirect to dashboard - Fixed redirect path
                    header('Location: adminDash.php'); // Changed from dashboard.php to match the redirect check at top
                    exit();
                }
            }
            
        } catch (PDOException $e) {
            // Log the error (don't show sensitive database errors to users)
            error_log("Database error: " . $e->getMessage());
            $error_message = 'Database connection failed: ' . $e->getMessage(); // Temporarily show error for debugging
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(164, 19, 60, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #a4133c;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #a4133c;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff4d6d;
            box-shadow: 0 0 0 3px rgba(255, 77, 109, 0.1);
            transform: translateY(-1px);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(164, 19, 60, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #ffe6e6;
            color: #d63384;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
            text-align: center;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            color: #666;
        }

        .remember-me input[type="checkbox"] {
            margin-right: 8px;
            accent-color: #ff4d6d;
        }

        .forgot-password {
            color: #ff4d6d;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .admin-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 0 10px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }

        /* Loading animation for form submission */
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff40;
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="admin-icon">
                👤
            </div>
            <h1>Admin Login</h1>
            <p>Please sign in to access the dashboard</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       placeholder="Enter your username or email"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Enter your password"
                       required>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    Remember me
                </label>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                Sign In
            </button>
        </form>
    </div>

    <script>
        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.innerHTML = '<span class="loading"></span>Signing In...';
            loginBtn.disabled = true;
        });

        // Focus on username field when page loads
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>