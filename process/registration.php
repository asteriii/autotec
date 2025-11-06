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

    // Password validation with regex
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';
    
    if (!preg_match($passwordPattern, $password)) {
        echo "<script>alert('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#).'); window.location.href='../index.php';</script>";
        exit();
    }

    if (!$termsAccepted) {
        echo "<script>alert('You must accept the Terms and Conditions.'); window.location.href='../index.php';</script>";
        exit();
    }

    // Handle profile picture upload
    $profilePictureName = null;
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        // Use absolute path from document root
        $uploadDir = __DIR__ . '/../uploads/profile/';
        
        // Debug: Log the actual path being used
        error_log("Upload directory: " . $uploadDir);
        error_log("Upload directory exists: " . (file_exists($uploadDir) ? 'YES' : 'NO'));
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Failed to create directory: " . $uploadDir);
                echo "<script>alert('Failed to create upload directory. Please contact administrator.'); window.location.href='../index.php';</script>";
                exit();
            }
        }

        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            error_log("Directory not writable: " . $uploadDir);
            echo "<script>alert('Upload directory is not writable. Please contact administrator.'); window.location.href='../index.php';</script>";
            exit();
        }

        $fileTmpPath = $_FILES['profilePicture']['tmp_name'];
        $fileName = $_FILES['profilePicture']['name'];
        $fileSize = $_FILES['profilePicture']['size'];
        $fileType = $_FILES['profilePicture']['type'];
        
        // Additional validation: Check if file actually exists
        if (!file_exists($fileTmpPath)) {
            error_log("Temporary file not found: " . $fileTmpPath);
            echo "<script>alert('Upload failed: temporary file not found.'); window.location.href='../index.php';</script>";
            exit();
        }
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($fileExtension, $allowedExtensions)) {
            // Validate file size (5MB max)
            if ($fileSize <= 5 * 1024 * 1024) {
                // Validate actual image file (prevents fake extensions)
                $imageInfo = getimagesize($fileTmpPath);
                if ($imageInfo === false) {
                    echo "<script>alert('Invalid image file.'); window.location.href='../index.php';</script>";
                    exit();
                }
                
                // Generate unique filename
                $newFileName = uniqid('profile_', true) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                // Debug logging
                error_log("Attempting to move file from: " . $fileTmpPath);
                error_log("To: " . $destPath);

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $profilePictureName = $newFileName;
                    // Set proper permissions
                    chmod($destPath, 0644);
                    error_log("File uploaded successfully: " . $destPath);
                } else {
                    error_log("move_uploaded_file failed. Temp: " . $fileTmpPath . " Dest: " . $destPath);
                    error_log("Last error: " . error_get_last()['message']);
                    echo "<script>alert('Error uploading profile picture. Check folder permissions and PHP configuration.'); window.location.href='../index.php';</script>";
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
    } else if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Log upload error
        $uploadError = $_FILES['profilePicture']['error'];
        error_log("File upload error code: " . $uploadError);
        
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
        ];
        
        $errorMsg = $errorMessages[$uploadError] ?? 'Unknown upload error';
        echo "<script>alert('Upload error: " . $errorMsg . "'); window.location.href='../index.php';</script>";
        exit();
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