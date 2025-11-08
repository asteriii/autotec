<?php
session_start();
require_once '../db.php';

// Get form values
$identifier = $_POST['email']; // This may be email OR username
$password = $_POST['password'];

// Admin login check (Email only)
$sqlAdmin = "SELECT * FROM admin WHERE Email = ? AND password = ?";
$stmtAdmin = $conn->prepare($sqlAdmin);
$stmtAdmin->bind_param("ss", $identifier, $password);
$stmtAdmin->execute();
$resultAdmin = $stmtAdmin->get_result();

// User login check (Email OR Username)
$sqlUser = "SELECT * FROM users WHERE (Email = ? OR Username = ?) AND password = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("sss", $identifier, $identifier, $password);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultAdmin->num_rows > 0) {
    $_SESSION['isAdmin'] = true;
    $welcomeName = "Admin";
    $redirectUrl = '../adminDash.php';
    $showModal = true;
} elseif ($resultUser->num_rows > 0) {
    $user = $resultUser->fetch_assoc();
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['username'] = $user['Username'];
    
    // Handle different possible column names for fullname
    if (isset($user['Fullname'])) {
        $_SESSION['fname'] = $user['Fullname'];
        $welcomeName = $user['Fullname'];
    } elseif (isset($user['FullName'])) {
        $_SESSION['fname'] = $user['FullName'];
        $welcomeName = $user['FullName'];
    } elseif (isset($user['full_name'])) {
        $_SESSION['fname'] = $user['full_name'];
        $welcomeName = $user['full_name'];
    } else {
        // Fallback to username if fullname column doesn't exist
        $_SESSION['fname'] = $user['Username'];
        $welcomeName = $user['Username'];
    }
    
    // $_SESSION['profile_image'] = $user['profile_image']; // optional
    $_SESSION['isAdmin'] = false;
    
    $redirectUrl = '../homepage.php';
    $showModal = true;
} else {
    echo '<script>alert("Login failed. Please try again...")</script>';
    echo "<script>window.location = '../index.php';</script>";
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Successful</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #a4133c 0%, #c9184a 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal {
            display: block;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 0 0 20px 0;
            border: none;
            border-radius: 18px;
            width: 92%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, -45%) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }

        /* Success Icon */
        .modal-content::before {
            content: "✓";
            display: block;
            width: 80px;
            height: 80px;
            margin: 40px auto 25px;
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            border-radius: 50%;
            color: white;
            font-size: 48px;
            line-height: 80px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(164, 19, 60, 0.3);
            animation: scaleIn 0.5s ease 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .modal-content h2 {
            color: #a4133c;
            margin: 0 0 15px 0;
            padding: 0 40px;
            font-size: 28px;
            font-weight: 700;
        }

        .modal-content p {
            color: #555;
            margin: 0 0 25px 0;
            padding: 0 40px;
            font-size: 16px;
            line-height: 1.7;
        }

        .welcome-name {
            color: #a4133c;
            font-weight: 700;
            font-size: 20px;
        }

        /* Loading animation */
        .loading-text {
            color: #888;
            font-size: 14px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e5e7eb;
            border-top: 2px solid #a4133c;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
            }

            .modal-content::before {
                width: 65px;
                height: 65px;
                font-size: 38px;
                line-height: 65px;
                margin: 32px auto 18px;
            }

            .modal-content h2 {
                font-size: 24px;
                padding: 0 28px;
            }

            .modal-content p {
                font-size: 15px;
                padding: 0 28px;
            }
        }
    </style>
</head>
<body>

<!-- Login Success Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <h2>Login Successful!</h2>
        <p>Welcome back, <span class="welcome-name"><?php echo htmlspecialchars($welcomeName); ?></span></p>
        <div class="loading-text">
            <div class="spinner"></div>
            <span>Redirecting to your dashboard...</span>
        </div>
    </div>
</div>

<script>
    // Auto redirect after 2 seconds
    setTimeout(function() {
        window.location.href = '<?php echo $redirectUrl; ?>';
    }, 2000);
</script>

</body>
</html>