<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...<br><br>";

$servername = getenv('MYSQLHOST') ?: 'localhost';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$port = getenv('MYSQLPORT') ?: '3306';

echo "Host: " . $servername . "<br>";
echo "User: " . $username . "<br>";
echo "Database: " . $dbname . "<br>";
echo "Port: " . $port . "<br><br>";

try {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Database connected successfully!<br><br>";
    
    // Test if the tables exist
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "✓ Tables found: " . $result->num_rows . "<br>";
        while($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>