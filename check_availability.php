<?php
// Set content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Database connection
require_once 'db.php';

// Get the date and branch from either GET or POST
$date = isset($_POST['date']) ? $_POST['date'] : (isset($_GET['date']) ? $_GET['date'] : '');
$branchName = isset($_POST['branch']) ? $_POST['branch'] : (isset($_GET['branchName']) ? $_GET['branchName'] : '');

if (empty($date)) {
    die(json_encode(['success' => false, 'error' => 'Date parameter is required']));
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    die(json_encode(['success' => false, 'error' => 'Invalid date format']));
}

try {
    // Count ALL bookings for the selected date
    // This counts all reservations regardless of payment status
    $query = "SELECT Time, COUNT(*) as booking_count 
              FROM reservations 
              WHERE Date = ?";

    // Add branch filter if provided
    $params = [$date];
    $types = "s";

    if (!empty($branchName)) {
        $query .= " AND BranchName = ?";
        $params[] = $branchName;
        $types .= "s";
    }

    $query .= " GROUP BY Time";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters dynamically
    if (!empty($branchName)) {
        $stmt->bind_param($types, $date, $branchName);
    } else {
        $stmt->bind_param($types, $date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Store count of bookings for each time slot
    $slot_counts = [];
    while ($row = $result->fetch_assoc()) {
        $slot_counts[$row['Time']] = intval($row['booking_count']);
    }

    // Define available time slots matching registration times
    // Morning slots: 9:00 AM - 11:40 AM (20-minute intervals)
    // Lunch break: 12:00 PM - 1:00 PM
    // Afternoon slots: 1:00 PM - 4:20 PM (20-minute intervals)
    $timeSlots = [
        // Morning
        '09:00:00', '09:20:00', '09:40:00',
        '10:00:00', '10:20:00', '10:40:00',
        '11:00:00', '11:20:00', '11:40:00',
        // Afternoon
        '13:00:00', '13:20:00', '13:40:00',
        '14:00:00', '14:20:00', '14:40:00',
        '15:00:00', '15:20:00', '15:40:00',
        '16:00:00', '16:20:00'
    ];

    $maxSlots = 3; // Maximum 3 slots per time
    $availableSlots = [];

    foreach ($timeSlots as $time) {
        // Get the actual booked count for this specific time slot
        $bookedCount = isset($slot_counts[$time]) ? $slot_counts[$time] : 0;
        $available = $maxSlots - $bookedCount;
        
        // Ensure available doesn't go negative
        if ($available < 0) {
            $available = 0;
        }
        
        // Format time for display
        $displayTime = date('g:i A', strtotime($time));
        
        $availableSlots[] = [
            'time' => $time,
            'display' => $displayTime,
            'available' => $available,
            'booked' => $bookedCount,
            'max' => $maxSlots
        ];
    }

    // Close connections
    $stmt->close();
    $conn->close();

    // Return the slots in the format expected by the frontend
    echo json_encode([
        'success' => true,
        'slots' => $availableSlots,
        'max_slots' => $maxSlots,
        'slot_counts' => $slot_counts,
        'debug' => [
            'date' => $date,
            'branch' => $branchName,
            'total_bookings' => array_sum($slot_counts)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>