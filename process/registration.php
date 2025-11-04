<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $username = trim($_POST['regUsername'] ?? '');
    $email = trim($_POST['regEmail'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['regPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $termsAccepted = isset($_POST['termsAccepted']);

    // Validation
    if (empty($fullName) || empty($username) || empty($email) || empty($phoneNumber) || empty($address) || empty($password)) {
        echo "<script>alert('All fields are required.'); window.location.href='../index.php';</script>";
        exit();
    }

    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.'); window.location.href='../index.php';</script>";
        exit();
    }

    if (!$termsAccepted) {
        echo "<script>alert('You must accept the Terms and Conditions.'); window.location.href='../index.php';</script>";
        exit();
    }

    // Handle profile picture upload
    $profilePictureName = null;
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        // FIXED: Correct path - go UP one level from /process, then into uploads/profile
        $uploadDir = '../uploads/profile/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo "<script>alert('Failed to create upload directory. Please contact administrator.'); window.location.href='../index.php';</script>";
                exit();
            }
        }

        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            echo "<script>alert('Upload directory is not writable. Please contact administrator.'); window.location.href='../index.php';</script>";
            exit();
        }

        $fileTmpPath = $_FILES['profilePicture']['tmp_name'];
        $fileName = $_FILES['profilePicture']['name'];
        $fileSize = $_FILES['profilePicture']['size'];
        $fileType = $_FILES['profilePicture']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($fileExtension, $allowedExtensions)) {
            // Validate file size (5MB max)
            if ($fileSize <= 5 * 1024 * 1024) {
                // Generate unique filename
                $newFileName = uniqid('profile_', true) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $profilePictureName = $newFileName;
                    // Set proper permissions
                    chmod($destPath, 0644);
                } else {
                    echo "<script>alert('Error uploading profile picture. Check folder permissions.'); window.location.href='../index.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Profile picture size must be less than 5MB.'); window.location.href='../index.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'); window.location.href='../index.php';</script>";
            exit();
        }
    }

    // Check if username or email already exists
    $checkSql = "SELECT * FROM users WHERE Username = ? OR Email = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "ss", $username, $email);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($checkResult) > 0) {
        mysqli_stmt_close($checkStmt);
        
        // Delete uploaded file if username/email already exists
        if ($profilePictureName && file_exists($uploadDir . $profilePictureName)) {
            @unlink($uploadDir . $profilePictureName);
        }
        
        echo "<script>alert('Username or Email already exists.'); window.location.href='../index.php';</script>";
        exit();
    }
    mysqli_stmt_close($checkStmt);

    // SECURITY WARNING: Password should be hashed!
    // $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user with profile picture
    $sql = "INSERT INTO users (Fname, Username, Email, PhoneNumber, Address, password, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        // Clean up uploaded file if database error
        if ($profilePictureName && file_exists($uploadDir . $profilePictureName)) {
            @unlink($uploadDir . $profilePictureName);
        }
        echo "<script>alert('Database error: " . mysqli_error($conn) . "'); window.location.href='../index.php';</script>";
        exit();
    }

    mysqli_stmt_bind_param($stmt, "sssssss", $fullName, $username, $email, $phoneNumber, $address, $password, $profilePictureName);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo "<script>alert('Registration successful! Please login.'); window.location.href='../index.php';</script>";
    } else {
        // Clean up uploaded file if insert fails
        if ($profilePictureName && file_exists($uploadDir . $profilePictureName)) {
            @unlink($uploadDir . $profilePictureName);
        }
        mysqli_stmt_close($stmt);
        echo "<script>alert('Registration failed: " . mysqli_error($conn) . "'); window.location.href='../index.php';</script>";
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>