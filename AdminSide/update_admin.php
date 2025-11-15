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

$admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['Email']) ? trim($_POST['Email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$branch = isset($_POST['BranchName']) ? trim($_POST['BranchName']) : null;
$role = isset($_POST['role']) ? trim($_POST['role']) : null;

if ($admin_id <= 0 || $username === '' || $email === '') {
    header('Location: admin_acc_manage.php?error=db');
    exit;
}

try {
    // Get old admin data for comparison
    $stmt = $conn->prepare("SELECT username, Email, BranchName, role FROM admin WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc();
    $stmt->close();
    
    if (!$oldData) {
        throw new Exception('Admin account not found');
    }
    
    $oldUsername = $oldData['username'];
    
    // Check for duplicates (username or email) in other rows
    $chk = $conn->prepare("SELECT admin_id FROM admin WHERE (username = ? OR Email = ?) AND admin_id != ? LIMIT 1");
    $chk->bind_param("ssi", $username, $email, $admin_id);
    $chk->execute();
    $chk_res = $chk->get_result();
    if ($chk_res && $chk_res->num_rows > 0) {
        $chk->close();
        
        // Log duplicate attempt
        logAction($username_session, 'Error', "Failed to update admin '$oldUsername': Duplicate username or email");
        
        header('Location: admin_acc_manage.php?error=duplicate_edit');
        exit;
    }
    $chk->close();
    
    // Track what changed
    $changes = [];
    
    if ($oldData['username'] !== $username) {
        $changes[] = "username from '{$oldData['username']}' to '$username'";
    }
    if ($oldData['Email'] !== $email) {
        $changes[] = "email from '{$oldData['Email']}' to '$email'";
    }
    if (!empty($password)) {
        $changes[] = "password";
    }
    if ($oldData['BranchName'] !== $branch) {
        $changes[] = "branch from '{$oldData['BranchName']}' to '$branch'";
    }
    if ($oldData['role'] !== $role) {
        $changes[] = "role from '{$oldData['role']}' to '$role'";
    }
    
    // Update admin account
    $stmt = $conn->prepare("UPDATE admin SET username = ?, Email = ?, password = ?, BranchName = ?, role = ? WHERE admin_id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed');
    }
    
    $stmt->bind_param("sssssi", $username, $email, $password, $branch, $role, $admin_id);
    $ok = $stmt->execute();
    $stmt->close();
    
    if ($ok) {
        // 🧾 Log the audit trail
        if (!empty($changes)) {
            $changesList = implode(', ', $changes);
            logAction($username_session, 'Update Admin Account', "Updated admin account '$oldUsername' (ID: $admin_id): Changed $changesList");
        } else {
            logAction($username_session, 'Update Admin Account', "Attempted to update admin account '$oldUsername' (ID: $admin_id) but no changes detected");
        }
        
        header('Location: admin_acc_manage.php?success=1');
    } else {
        throw new Exception('Update failed');
    }
    
} catch (Exception $e) {
    // Log error
    logAction($username_session, 'Error', "Failed to update admin account (ID: $admin_id): " . $e->getMessage());
    
    header('Location: admin_acc_manage.php?error=db');
}

exit;
?>