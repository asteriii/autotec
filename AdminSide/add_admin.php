<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include 'db.php';
require_once 'audit_trail.php';

// Get session variables
$username_session = $_SESSION['admin_username'] ?? 'Unknown Admin';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_acc_manage.php');
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['Email']) ? trim($_POST['Email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$branch = isset($_POST['BranchName']) ? trim($_POST['BranchName']) : null;
$role = isset($_POST['role']) ? trim($_POST['role']) : null;

if ($username === '' || $email === '' || $password === '') {
    // Log validation error
    logAction($username_session, 'Error', "Failed to add admin: Missing required fields (username, email, or password)");
    
    header('Location: admin_acc_manage.php?error=db');
    exit;
}

try {
    // Check duplicate username or email
    $chk = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? OR Email = ? LIMIT 1");
    $chk->bind_param("ss", $username, $email);
    $chk->execute();
    $chk_res = $chk->get_result();
    
    if ($chk_res && $chk_res->num_rows > 0) {
        // duplicate
        $chk->close();
        
        // Log duplicate attempt
        logAction($username_session, 'Error', "Failed to add admin '$username': Duplicate username or email");
        
        header('Location: admin_acc_manage.php?error=duplicate_add');
        exit;
    }
    $chk->close();
    
    // Optional: hash password
    // $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    // currently insert raw password (to match existing system)
    
    $stmt = $conn->prepare("INSERT INTO admin (username, Email, password, BranchName, role) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Database prepare failed');
    }
    
    $stmt->bind_param("sssss", $username, $email, $password, $branch, $role);
    $ok = $stmt->execute();
    $new_admin_id = $conn->insert_id;
    $stmt->close();
    
    if ($ok) {
        // 🧾 Log the audit trail
        $branchText = $branch ? " for branch $branch" : " (no branch assigned)";
        $roleText = $role ? " with role '$role'" : "";
        
        logAction($username_session, 'Create Admin Account', "Created new admin account '$username' (ID: $new_admin_id)$branchText$roleText");
        
        header('Location: admin_acc_manage.php?added=1');
    } else {
        throw new Exception('Insert failed');
    }
    
} catch (Exception $e) {
    // Log error
    logAction($username_session, 'Error', "Failed to add admin account '$username': " . $e->getMessage());
    
    header('Location: admin_acc_manage.php?error=db');
}

exit;
?>