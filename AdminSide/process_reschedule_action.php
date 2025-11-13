<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rescheduleID = $_POST['rescheduleID'] ?? '';

    if (empty($action) || empty($rescheduleID)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    // Fetch the reschedule record
    $stmt = $pdo->prepare("SELECT * FROM reschedule WHERE RescheduleID = ?");
    $stmt->execute([$rescheduleID]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
        exit;
    }

    // Decide which date/time to use
    if ($action === 'confirm') {
        $finalDate = $res['NewDate'];
        $finalTime = $res['NewTime'];
    } else { // deny
        $finalDate = $res['Date'];
        $finalTime = $res['Time'];
    }

    try {
        $pdo->beginTransaction();

        // Move record to completed table with proper PaymentStatus
        // Use 'verified' instead of 'completed' since that's what the ENUM allows
        $insert = $pdo->prepare("
            INSERT INTO completed (
                ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID,
                Date, Time, PaymentMethod, PaymentReceipt, PaymentStatus,
                Fname, Mname, Lname, Email, PhoneNum, Address, ReferenceNumber, Price, BranchName
            ) VALUES (
                :ReservationID, :UserID, :PlateNo, :Brand, :TypeID, :CategoryID,
                :Date, :Time, :PaymentMethod, :PaymentReceipt, :PaymentStatus,
                :Fname, :Mname, :Lname, :Email, :PhoneNum, :Address, :ReferenceNumber, :Price, :BranchName
            )
        ");

        $insert->execute([
            ':ReservationID' => $res['ReservationID'],
            ':UserID' => $res['UserID'],
            ':PlateNo' => $res['PlateNo'],
            ':Brand' => $res['Brand'],
            ':TypeID' => $res['TypeID'],
            ':CategoryID' => $res['CategoryID'],
            ':Date' => $finalDate,
            ':Time' => $finalTime,
            ':PaymentMethod' => $res['PaymentMethod'],
            ':PaymentReceipt' => $res['PaymentReceipt'],
            ':PaymentStatus' => 'verified', // Changed from 'completed' to 'verified'
            ':Fname' => $res['Fname'],
            ':Mname' => $res['Mname'],
            ':Lname' => $res['Lname'],
            ':Email' => $res['Email'],
            ':PhoneNum' => $res['PhoneNum'],
            ':Address' => $res['Address'],
            ':ReferenceNumber' => $res['ReferenceNumber'],
            ':Price' => $res['Price'],
            ':BranchName' => $res['BranchName']
        ]);

        // Delete from reschedule table
        $delete = $pdo->prepare("DELETE FROM reschedule WHERE RescheduleID = ?");
        $delete->execute([$rescheduleID]);

        // Also delete from reservations table since it's now completed
        $deleteRes = $pdo->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $deleteRes->execute([$res['ReservationID']]);

        $pdo->commit();
        
        $message = $action === 'confirm' 
            ? 'Reschedule confirmed! Appointment moved to completed with new date/time.' 
            : 'Reschedule denied! Appointment moved to completed with original date/time.';
            
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>