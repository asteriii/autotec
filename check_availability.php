<?php
// Set content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Database connection parameters
require_once 'db.php';

// Get the date and branch from the request
$date = isset($_GET['date']) ? $_GET['date'] : '';
$branchName = isset($_GET['branchName']) ? $_GET['branchName'] : '';

if (empty($date)) {
    die(json_encode(['error' => 'Date parameter is required']));
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    die(json_encode(['error' => 'Invalid date format']));
}

// Count bookings for each time slot on the given date and branch
// Maximum 3 slots per time (3 machines available)
$query = "SELECT Time, COUNT(*) as booking_count 
          FROM reservations 
          WHERE Date = ?";

// Add branch filter if provided
if (!empty($branchName)) {
    $query .= " AND BranchName = ?";
    $stmt = $conn->prepare($query . " GROUP BY Time");
    $stmt->bind_param("ss", $date, $branchName);
} else {
    $stmt = $conn->prepare($query . " GROUP BY Time");
    $stmt->bind_param("s", $date);
}

$stmt->execute();
$result = $stmt->get_result();

// Store count of bookings for each time slot
$slot_counts = [];
while ($row = $result->fetch_assoc()) {
    $slot_counts[$row['Time']] = intval($row['booking_count']);
}

// Close connections
$stmt->close();
$conn->close();

// Return the slot counts with max slots allowed (3 machines)
echo json_encode([
    'slot_counts' => $slot_counts,
    'max_slots' => 3
]);
?>