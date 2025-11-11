<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

require_once 'db.php';

try {
    $reservationId = $_POST['reservationId'] ?? '';
    $newDate = $_POST['newDate'] ?? '';
    $newTime = $_POST['newTime'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Validate inputs
    if (empty($reservationId) || empty($newDate) || empty($newTime)) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get current reservation details
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE ReservationID = ? AND UserID = ?");
    $stmt->bind_param("ii", $reservationId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception('Reservation not found');
    }

    // Check if reservation can be rescheduled (at least 24 hours before)
    $reservationDateTime = strtotime($reservation['Date'] . ' ' . $reservation['Time']);
    $now = time();
    
    if ($reservationDateTime - $now < 24 * 60 * 60) {
        throw new Exception('Reservations can only be rescheduled at least 24 hours in advance');
    }

    // Check if new time slot is available
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations 
                                 WHERE Date = ? AND Time = ? AND BranchName = ?");
    $checkStmt->bind_param("sss", $newDate, $newTime, $reservation['BranchName']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $slotCheck = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($slotCheck['count'] >= 3) {
        throw new Exception('Selected time slot is full. Please choose another time.');
    }

    // Insert into reschedule table (excluding RescheduleID and RescheduledAt which have defaults)
    $insertReschedule = $conn->prepare("INSERT INTO reschedule 
        (ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID, Fname, Lname, Mname, 
         PhoneNum, Email, Date, Time, NewDate, NewTime, Reason, Address, BranchName, 
         PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber, CreatedAt) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Fixed: Correct type string with 24 characters matching 24 placeholders
    $insertReschedule->bind_param(
        "iissiisssssssssssssssdss", // i=int, s=string, d=decimal
        $reservation['ReservationID'],
        $reservation['UserID'],
        $reservation['PlateNo'],
        $reservation['Brand'],
        $reservation['TypeID'],
        $reservation['CategoryID'],
        $reservation['Fname'],
        $reservation['Lname'],
        $reservation['Mname'],
        $reservation['PhoneNum'],
        $reservation['Email'],
        $reservation['Date'],
        $reservation['Time'],
        $newDate,
        $newTime,
        $reason,
        $reservation['Address'],
        $reservation['BranchName'],
        $reservation['PaymentMethod'],
        $reservation['PaymentStatus'],
        $reservation['PaymentReceipt'],
        $reservation['Price'],
        $reservation['ReferenceNumber'],
        $reservation['CreatedAt']
    );

    if (!$insertReschedule->execute()) {
        throw new Exception('Failed to create reschedule record: ' . $insertReschedule->error);
    }
    $insertReschedule->close();

    // DELETE from reservations table instead of UPDATE
    $deleteStmt = $conn->prepare("DELETE FROM reservations WHERE ReservationID = ? AND UserID = ?");
    $deleteStmt->bind_param("ii", $reservationId, $_SESSION['user_id']);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to remove original reservation: ' . $deleteStmt->error);
    }
    $deleteStmt->close();

    // Commit transaction
    $conn->commit();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment rescheduled successfully!'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>