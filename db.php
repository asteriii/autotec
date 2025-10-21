<?php
// Production vs Local environment detection
$isProduction = (strpos($_SERVER['HTTP_HOST'], 'railway.app') !== false);

if ($isProduction) {
    // Production settings (Railway)
    $servername = getenv('MYSQLHOST');
    $username = getenv('MYSQLUSER');
    $password = getenv('MYSQLPASSWORD');
    $dbname = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT') ?: '3306';
} else {
    // Local XAMPP settings
    $servername = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'railway';
    $port = '3306';
}

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Unable to connect to database. Please try again later.");
}

$conn->set_charset("utf8mb4");
?>