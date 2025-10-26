<?php
// HI TESTING LANG BENJIE TO 2 testingss
$servername = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'OUJHNoEzFNhsIgRFuduLzLFWunvvMrrP';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$port = getenv('MYSQLPORT') ?: '3306';

// MySQLi connection (for check.php and other files using $conn)
$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// PDO connection 
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
    die("Connection failed: " . $e->getMessage());
}
?>