<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

include 'db.php';

try {
    // Get form data
    $reservationId = $_POST['reservationId'] ?? null;
    $newDate = $_POST['newDate'] ?? null;
    $newTime = $_POST['newTime'] ?? null;
    $reason = $_POST['reason'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    if (!$reservationId || !$newDate || !$newTime) {
        throw new Exception('Missing required fields');
    }
    
    // Verify the reservation belongs to the logged-in user
    $verify_stmt = $conn->prepare("SELECT ReservationID, BranchName FROM reservations WHERE ReservationID = ? AND UserID = ?");
    $verify_stmt->bind_param("ii", $reservationId, $userId);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        throw new Exception('Reservation not found or you do not have permission to modify it');
    }
    
    $reservation = $verify_result->fetch_assoc();
    $branchName = $reservation['BranchName'];
    $verify_stmt->close();
    
    // Validate new date is in the future
    $newDateTime = strtotime($newDate . ' ' . $newTime);
    $currentTime = time();
    
    if ($newDateTime <= $currentTime) {
        throw new Exception('New appointment time must be in the future');
    }
    
    // Check if new time slot is available
    $check_stmt = $conn->prepare("SELECT ReservationID FROM reservations WHERE Date = ? AND Time = ? AND BranchName = ? AND ReservationID != ?");
    $check_stmt->bind_param("sssi", $newDate, $newTime, $branchName, $reservationId);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        throw new Exception('This time slot is already booked. Please choose a different time.');
    }
    $check_stmt->close();
    
    // Update the reservation
    $update_stmt = $conn->prepare("UPDATE reservations SET Date = ?, Time = ? WHERE ReservationID = ? AND UserID = ?");
    $update_stmt->bind_param("ssii", $newDate, $newTime, $reservationId, $userId);
    
    if ($update_stmt->execute()) {
        // Log the reschedule action (optional - you can create a separate table for this)
        // For now, we'll just return success
        
        $update_stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment rescheduled successfully',
            'newDate' => $newDate,
            'newTime' => $newTime
        ]);
    } else {
        throw new Exception('Failed to update reservation: ' . $update_stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>