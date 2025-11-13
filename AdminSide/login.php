<?php
session_start();

require_once '../db.php';

// Include audit trail functions
require_once 'audit_trail.php';

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: adminDash.php');
    exit();
}

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    $remember = isset($_POST['remember']);
    $selected_role = isset($_POST['role']) ? $_POST['role'] : ''; // Get selected role from form
    
    // Basic validation
    if (empty($input_username) || empty($input_password)) {
        $error_message = 'Please enter both username and password.';
    } elseif (empty($selected_role)) {
        $error_message = 'Please select your role (Admin or Staff).';
    } else {
        try {
             $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
            
            // Prepare and execute query to find admin user with role filtering
            $stmt = $pdo->prepare("SELECT admin_id, username, Email, password, BranchName, role FROM admin WHERE (username = ? OR Email = ?) AND role = ?");
            $stmt->execute([$input_username, $input_username, $selected_role]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                $error_message = "Invalid credentials or you don't have access as " . ucfirst($selected_role) . ".";
                
                // Log failed login attempt
                logAction($input_username, 'Failed Login', "Failed login attempt for username/email: $input_username (Role: $selected_role)");
            } else {
                // Verify password
                $password_verified = false;
                
                if (password_verify($input_password, $admin['password'])) {
                    $password_verified = true;
                } elseif ($input_password === $admin['password']) {
                    // Plain text password (not recommended for production)
                    $password_verified = true;
                }
                
                if ($password_verified) {
                    // Normalize branch name to handle case variations
                    $branch_name = trim($admin['BranchName']);
                    $branch_normalized = strtolower(str_replace(' ', '', $branch_name));
                    
                    // Determine branch type
                    $branch_filter = null;
                    if (strpos($branch_normalized, 'shaw') !== false) {
                        $branch_filter = 'Autotec Shaw';
                    } elseif (strpos($branch_normalized, 'subic') !== false) {
                        $branch_filter = 'Autotec Subic';
                    }
                    
                    // Validate that admin has a valid branch
                    if (!$branch_filter) {
                        $error_message = "Your account is not assigned to a valid branch (Autotec Shaw or Autotec Subic).";
                        
                        // Log invalid branch attempt
                        logAction($admin['username'], 'Login Failed', "Login attempt with invalid branch assignment: $branch_name (Role: {$admin['role']})");
                    } else {
                        // Login successful
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['Email'];
                        $_SESSION['admin_branch'] = $branch_name;
                        $_SESSION['branch_filter'] = $branch_filter;
                        $_SESSION['role'] = $admin['role']; // Store the role (admin or staff)
                        $_SESSION['login_time'] = time();
                        
                        // Set remember me cookie if checked
                        if ($remember) {
                            $cookie_token = bin2hex(random_bytes(32));
                            setcookie('admin_remember', $cookie_token, time() + (86400 * 30), '/', '', false, true);
                        }
                        
                        // Log successful login
                        logLogin($admin['username']);
                        
                        // Redirect to dashboard
                        header('Location: adminDash.php');
                        exit();
                    }
                } else {
                    $error_message = "Invalid username/email or password.";
                    
                    // Log failed password attempt
                    logAction($admin['username'], 'Failed Login', "Failed login attempt - incorrect password for username: {$admin['username']} (Role: {$admin['role']})");
                }
            }
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = 'Database connection failed. Please try again later.';
            
            // Log database error
            logAction($input_username, 'System Error', "Database connection error during login attempt");
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

        .role-selection {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .role-option {
            flex: 1;
            position: relative;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .role-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .role-option input[type="radio"]:checked + .role-label {
            border-color: #ff4d6d;
            background: rgba(255, 77, 109, 0.05);
        }

        .role-label:hover {
            border-color: #ff4d6d;
            transform: translateY(-2px);
        }

        .role-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .role-text {
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }

        .role-option input[type="radio"]:checked + .role-label .role-text {
            color: #ff4d6d;
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

        .branch-info {
            background: #f0f9ff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #bae6fd;
        }

        .branch-info p {
            color: #0369a1;
            font-size: 12px;
            font-weight: 500;
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

        <div class="branch-info">
            <p>🏢 Branch-specific access • AutoTec Shaw & Subic</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <!-- Role Selection -->
            <div class="form-group">
                <label>Select Your Role</label>
                <div class="role-selection">
                    <div class="role-option">
                        <input type="radio" name="role" id="role_admin" value="admin" 
                               <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'checked' : ''; ?>>
                        <label for="role_admin" class="role-label">
                            <div class="role-icon">👔</div>
                            <div class="role-text">Admin</div>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" id="role_staff" value="staff"
                               <?php echo (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'checked' : ''; ?>>
                        <label for="role_staff" class="role-label">
                            <div class="role-icon">👥</div>
                            <div class="role-text">Staff</div>
                        </label>
                    </div>
                </div>
            </div>

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
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                Sign In
            </button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const roleSelected = document.querySelector('input[name="role"]:checked');
            if (!roleSelected) {
                e.preventDefault();
                alert('Please select your role (Admin or Staff)');
                return false;
            }
            
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.innerHTML = '<span class="loading"></span>Signing In...';
            loginBtn.disabled = true;
        });

        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>