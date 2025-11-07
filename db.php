<?php
// Detect local dev (XAMPP)
$isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

if ($isLocal) {
    // Local settings
    $servername = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'autotec'; // your local database name
    $port = '3306';
} else {
    // Railway / production settings
    $servername = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
    $username = getenv('MYSQLUSER') ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: 'OUJHNoEzFNhsIgRFuduLzLFWunvvMrrP';
    $dbname = getenv('MYSQLDATABASE') ?: 'railway';
    $port = getenv('MYSQLPORT') ?: '3306';
}

// MySQLi connection (for check.php and others)
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("MySQLi Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// PDO connection (for admin dashboard, etc.)
try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>
