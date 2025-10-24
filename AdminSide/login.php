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
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    $remember = isset($_POST['remember']);
    
    // Basic validation
    if (empty($input_username) || empty($input_password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            // Create database connection
            $pdo = new PDO("mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4", $db_username, $db_password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Prepare and execute query to find admin user
            $stmt = $pdo->prepare("SELECT admin_id, username, Email, password, BranchName FROM admin WHERE username = ? OR Email = ?");
            $stmt->execute([$input_username, $input_username]);
            $admin = $stmt->fetch();
            
            // Check if user exists
            if (!$admin) {
                $error_message = "Invalid username/email or password.";
            } else {
                // Check password verification
                if (!password_verify($input_password, $admin['password'])) {
                    // Check if password is stored as plain text (not recommended for production)
                    if ($input_password === $admin['password']) {
                        // Plain text match - allow login but this is insecure
                        $admin['password_verified'] = true;
                    } else {
                        $error_message = "Invalid username/email or password.";
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
                        setcookie('admin_remember', $cookie_token, time() + (86400 * 30), '/', '', false, true);
                    }
                    
                    // Update last login time (optional)
                    try {
                        $update_stmt = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                        $update_stmt->execute([$admin['admin_id']]);
                    } catch (PDOException $e) {
                        // Column might not exist, ignore this error
                        error_log("Last login update failed: " . $e->getMessage());
                    }
                    
                    // Redirect to dashboard
                    header('Location: adminDash.php');
                    exit();
                }
            }
            
        } catch (PDOException $e) {
            // Log the error
            error_log("Database error: " . $e->getMessage());
            $error_message = 'Database connection failed. Please try again later.';
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
    <link rel="stylesheet" href="AdminSide/css/login.css">
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