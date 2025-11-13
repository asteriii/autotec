<?php
session_start();

require_once '../db.php';
require_once 'audit_trail.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'] ?? null;
    
    // Get username from session (matches login.php session variables)
    $username = $_SESSION['admin_username'] ?? 'Unknown Admin';
    $admin_branch = $_SESSION['branch_filter'] ?? null;

    if (!$reservation_id) {
        echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
        exit;
    }

    try {
        // Fetch reservation details
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE ReservationID = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Reservation not found']);
            exit;
        }

        $reservation = $result->fetch_assoc();
        $stmt->close();

        // Check if branch matches admin's branch (branch-specific access control)
        if ($admin_branch && $reservation['BranchName'] !== $admin_branch) {
            // Log unauthorized attempt
            logAction($username, 'Unauthorized Access', "Attempted to confirm reservation ID $reservation_id from {$reservation['BranchName']} but assigned to $admin_branch");
            
            echo json_encode([
                'success' => false, 
                'message' => 'You can only confirm reservations for your assigned branch (' . $admin_branch . ')'
            ]);
            exit;
        }

        // Prepare insert into completed table
        $sql_insert = "INSERT INTO completed (
            ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID, 
            Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName, 
            PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber, CreatedAt, CompletedAt
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param(
            "iissiiisssssssssssdss",
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
            $reservation['Address'],
            $reservation['BranchName'],
            $reservation['PaymentMethod'],
            $reservation['PaymentStatus'],
            $reservation['PaymentReceipt'],
            $reservation['Price'],
            $reservation['ReferenceNumber'],
            $reservation['CreatedAt']
        );

        if (!$stmt_insert->execute()) {
            throw new Exception("Insert to completed failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        // Delete from reservations table
        $stmt_delete = $conn->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $stmt_delete->bind_param("i", $reservation_id);
        
        if (!$stmt_delete->execute()) {
            throw new Exception("Delete from reservations failed: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // 🧾 Log the audit trail
        $customerName = trim($reservation['Fname'] . ' ' . $reservation['Lname']);
        logConfirmReservation($username, $reservation_id, $customerName);

        echo json_encode([
            'success' => true, 
            'message' => 'Reservation moved to completed successfully.',
            'reservation_id' => $reservation_id,
            'customer' => $customerName
        ]);
        
    } catch (Exception $e) {
        // Log error to audit trail
        logAction($username, 'Error', "Failed to confirm reservation ID $reservation_id: " . $e->getMessage());
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>