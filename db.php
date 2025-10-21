<?php

$servername = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'OUJHNoEzFNhsIgRFuduLzLFWunvvMrrP';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>