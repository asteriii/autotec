<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php'; 

function alertAndRedirect($message, $url = 'profile.php') {
    echo "<script>alert(" . json_encode($message) . "); window.location.href='$url';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle profile update (Username, Email, Address)
    if (isset($_POST['update_profile'])) {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';

        $sql = "UPDATE users SET Username = ?, Email = ?, Address = ? WHERE UserID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            alertAndRedirect("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $address, $_SESSION['user_id']);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            alertAndRedirect("Profile updated successfully.");
        } else {
            mysqli_stmt_close($stmt);
            alertAndRedirect("Update failed: " . mysqli_error($conn));
        }
    }

    // Handle password update
    if (isset($_POST['change_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password === $confirm_password) {
            $sql = "UPDATE users SET password = ? WHERE UserID = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                alertAndRedirect("Prepare failed: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "si", $new_password, $_SESSION['user_id']);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                alertAndRedirect("Password changed successfully.");
            } else {
                mysqli_stmt_close($stmt);
                alertAndRedirect("Failed to change password: " . mysqli_error($conn));
            }
        } else {
            alertAndRedirect("Passwords do not match.");
        }
    }
}

// Fetch current user data
$sql = "SELECT * FROM users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Profile - AutoTEC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1e7dd 100%);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .page-title {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .page-title .highlight {
            color: #bd1e51;
        }

        .breadcrumb {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-card, .password-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover, .password-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .password-card h3 {
            color: #bd1e51;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }

        /* Form Styling */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        /* Password form specific styling - stacked vertically */
        .password-form .form-row {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #bd1e51;
            box-shadow: 0 0 0 3px rgba(189, 30, 81, 0.1);
        }

        .form-group input:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        /* Save Button */
        .save-btn {
            background: linear-gradient(135deg, #bd1e51, #d63969);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
            min-width: 180px;
        }

        .save-btn:hover {
            background: linear-gradient(135deg, #a01a45, #bd1e51);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(189, 30, 81, 0.3);
        }

        .save-btn:active {
            transform: translateY(0);
        }

        /* Profile Icon */
        .profile-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #bd1e51, #d63969);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            font-weight: 600;
        }

        /* Card Headers */
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            color: #bd1e51;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-header p {
            color: #666;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .hero {
                padding: 30px 20px;
            }

            .page-title {
                font-size: 2em;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .info-card, .password-card {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .save-btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 25px 15px;
            }

            .page-title {
                font-size: 1.8em;
            }

            .info-card, .password-card {
                padding: 20px;
            }

            .form-group input {
                padding: 10px 14px;
                font-size: 15px;
            }
        }

        /* Animation for form focus */
        @keyframes focusGlow {
            0% { box-shadow: 0 0 0 0 rgba(189, 30, 81, 0.3); }
            50% { box-shadow: 0 0 0 5px rgba(189, 30, 81, 0.1); }
            100% { box-shadow: 0 0 0 3px rgba(189, 30, 81, 0.1); }
        }

        .form-group input:focus {
            animation: focusGlow 0.3s ease-out;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1 class="page-title"><span class="highlight">MY</span> PROFILE</h1>
            <div class="breadcrumb">HOME &gt; PROFILE</div>
        </section>

        <div class="profile-grid">
            <div class="info-card">
                <div class="card-header">
                    <div class="profile-icon">
                        <?php echo strtoupper(substr($user['Fname'], 0, 1)); ?>
                    </div>
                    <h2>Personal Information</h2>
                    <p>Update your personal details and account information</p>
                </div>
                
                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['Fname']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['Address'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <button class="save-btn" type="submit" name="update_profile">Save Changes</button>
                </form>
            </div>

            <div class="password-card">
                <div class="card-header">
                    <h2>Change Password</h2>
                    <p>Keep your account secure with a strong password</p>
                </div>
                
                <form method="post" action="" class="password-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <button class="save-btn" type="submit" name="change_password">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
<?php include 'footer.php'; ?>
</html>