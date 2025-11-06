<?php
// add_admin.php //needed sa admin_acc_manage
include 'db.php';

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
    header('Location: admin_acc_manage.php?error=db');
    exit;
}

// check duplicate username or email
$chk = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? OR Email = ? LIMIT 1");
$chk->bind_param("ss", $username, $email);
$chk->execute();
$chk_res = $chk->get_result();
if ($chk_res && $chk_res->num_rows > 0) {
    // duplicate
    $chk->close();
    header('Location: admin_acc_manage.php?error=duplicate_add');
    exit;
}
$chk->close();

// Optional: hash password
// $password_hashed = password_hash($password, PASSWORD_DEFAULT);
// currently insert raw password (to match existing system)
$stmt = $conn->prepare("INSERT INTO admin (username, Email, password, BranchName, role) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    header('Location: admin_acc_manage.php?error=db');
    exit;
}
$stmt->bind_param("sssss", $username, $email, $password, $branch, $role);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    header('Location: admin_acc_manage.php?added=1');
} else {
    header('Location: admin_acc_manage.php?error=db');
}
exit;
