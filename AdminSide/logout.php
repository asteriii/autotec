<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Delete the remember me cookie if it exists
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/'); // Expire it
}

// Redirect to login page
header('Location: login.php');
exit();
?>
