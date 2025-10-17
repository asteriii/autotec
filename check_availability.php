<?php
// Set content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autotec";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the date from the request
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (empty($date)) {
    die(json_encode(['error' => 'Date parameter is required']));
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    die(json_encode(['error' => 'Invalid date format']));
}

// Get all booked time slots for the given date
$stmt = $conn->prepare("SELECT Time FROM reservations WHERE Date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_times = [];
while ($row = $result->fetch_assoc()) {
    $booked_times[] = $row['Time'];
}

// Close connections
$stmt->close();
$conn->close();

// Return the booked times as JSON
echo json_encode(['booked_times' => $booked_times]);
?>