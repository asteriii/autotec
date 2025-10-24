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
    echo '<script>alert("Admin login successful, Welcome!")</script>';
    echo "<script>window.location = '../admin.php';</script>";
} elseif ($resultUser->num_rows > 0) {
    $user = $resultUser->fetch_assoc();
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['fname'] = $user['Fullname']; // adjust this to match actual column
    // $_SESSION['profile_image'] = $user['profile_image']; // optional
    $_SESSION['isAdmin'] = false;

    echo '<script>alert("User login successful, Welcome!")</script>';
    echo "<script>window.location = '../homepage.php';</script>";
} else {
    echo '<script>alert("Login failed. Please try again...")</script>';
    echo "<script>window.location = '../index.php';</script>";
}

$conn->close();
?>