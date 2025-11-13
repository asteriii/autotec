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
    $reason = $_POST['reason'] ?? ''; // ✅ capture reason
    
    // Get username and branch from session
    $username = $_SESSION['admin_username'] ?? 'Unknown Admin';
    $admin_branch = $_SESSION['branch_filter'] ?? null;

    if (!$reservation_id) {
        echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
        exit;
    }

    try {
        // Create PDO connection for this operation
        $servername = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
        $db_username = getenv('MYSQLUSER') ?: 'root';
        $db_password = getenv('MYSQLPASSWORD') ?: 'OUJHNoEzFNhsIgRFuduLzLFWunvvMrrP';
        $dbname = getenv('MYSQLDATABASE') ?: 'railway';
        $port = getenv('MYSQLPORT') ?: '3306';

        $pdo = new PDO(
            "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4",
            $db_username,
            $db_password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Fetch the reservation record
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            echo json_encode(['success' => false, 'message' => 'Reservation not found']);
            exit;
        }

        // Check if branch matches admin's branch (branch-specific access control)
        if ($admin_branch && $res['BranchName'] !== $admin_branch) {
            // Log unauthorized attempt
            logAction($username, 'Unauthorized Access', "Attempted to cancel reservation ID $reservation_id from {$res['BranchName']} but assigned to $admin_branch");
            
            echo json_encode([
                'success' => false, 
                'message' => 'You can only cancel reservations for your assigned branch (' . $admin_branch . ')'
            ]);
            exit;
        }

        // Insert into canceled table with Reason included
        $insert = $pdo->prepare("
            INSERT INTO canceled (
                ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID,
                Fname, Lname, Mname, PhoneNum, Email,
                Date, Time, Address, BranchName, PaymentMethod,
                PaymentStatus, PaymentReceipt, Price, ReferenceNumber, CreatedAt, Reason
            ) VALUES (
                :ReservationID, :UserID, :PlateNo, :Brand, :TypeID, :CategoryID,
                :Fname, :Lname, :Mname, :PhoneNum, :Email,
                :Date, :Time, :Address, :BranchName, :PaymentMethod,
                :PaymentStatus, :PaymentReceipt, :Price, :ReferenceNumber, :CreatedAt, :Reason
            )
        ");

        $insert->execute([
            ':ReservationID' => $res['ReservationID'],
            ':UserID' => $res['UserID'],
            ':PlateNo' => $res['PlateNo'],
            ':Brand' => $res['Brand'],
            ':TypeID' => $res['TypeID'],
            ':CategoryID' => $res['CategoryID'],
            ':Fname' => $res['Fname'],
            ':Lname' => $res['Lname'],
            ':Mname' => $res['Mname'],
            ':PhoneNum' => $res['PhoneNum'],
            ':Email' => $res['Email'],
            ':Date' => $res['Date'],
            ':Time' => $res['Time'],
            ':Address' => $res['Address'],
            ':BranchName' => $res['BranchName'],
            ':PaymentMethod' => $res['PaymentMethod'],
            ':PaymentStatus' => $res['PaymentStatus'],
            ':PaymentReceipt' => $res['PaymentReceipt'],
            ':Price' => $res['Price'],
            ':ReferenceNumber' => $res['ReferenceNumber'],
            ':CreatedAt' => $res['CreatedAt'],
            ':Reason' => $reason // ✅ renamed field
        ]);

        // Delete the reservation
        $delete = $pdo->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $delete->execute([$reservation_id]);

        // 🧾 Log the audit trail with reason included
        $customerName = trim($res['Fname'] . ' ' . $res['Lname']);
        logCancelReservation($username, $reservation_id, $customerName, $reason);
        

        echo json_encode([
            'success' => true,
            'message' => 'Reservation cancelled successfully.',
            'reservation_id' => $reservation_id,
            'customer' => $customerName
        ]);

    } catch (Exception $e) {
        // Log error to audit trail
        logAction($username, 'Error', "Failed to cancel reservation ID $reservation_id: " . $e->getMessage());
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>