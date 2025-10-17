<?php
$host = 'localhost';
$db = 'autodb';
$user = 'root';
$pass = ''; // adjust if you set a password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
