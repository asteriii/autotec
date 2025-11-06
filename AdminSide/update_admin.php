<?php
// update_admin.php needed sa admin_acc_manage
include 'db.php';

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

// check for duplicates (username or email) in other rows
$chk = $conn->prepare("SELECT admin_id FROM admin WHERE (username = ? OR Email = ?) AND admin_id != ? LIMIT 1");
$chk->bind_param("ssi", $username, $email, $admin_id);
$chk->execute();
$chk_res = $chk->get_result();
if ($chk_res && $chk_res->num_rows > 0) {
    $chk->close();
    header('Location: admin_acc_manage.php?error=duplicate_edit');
    exit;
}
$chk->close();

// Optional: hash password here if you decided to use hashed pw
$stmt = $conn->prepare("UPDATE admin SET username = ?, Email = ?, password = ?, BranchName = ?, role = ? WHERE admin_id = ?");
if (!$stmt) {
    header('Location: admin_acc_manage.php?error=db');
    exit;
}
$stmt->bind_param("sssssi", $username, $email, $password, $branch, $role, $admin_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    header('Location: admin_acc_manage.php?success=1');
} else {
    header('Location: admin_acc_manage.php?error=db');
}
exit;
