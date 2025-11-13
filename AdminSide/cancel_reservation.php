<?php // needed for reservation.php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'] ?? null;

    if (!$reservation_id) {
        echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
        exit;
    }

    try {
        // Fetch the reservation record
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            echo json_encode(['success' => false, 'message' => 'Reservation not found']);
            exit;
        }

        // Insert into canceled table
        $insert = $pdo->prepare("
            INSERT INTO canceled (
                ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID,
                Fname, Lname, Mname, PhoneNum, Email,
                Date, Time, Address, BranchName, PaymentMethod,
                PaymentStatus, PaymentReceipt, Price, ReferenceNumber, CreatedAt
            ) VALUES (
                :ReservationID, :UserID, :PlateNo, :Brand, :TypeID, :CategoryID,
                :Fname, :Lname, :Mname, :PhoneNum, :Email,
                :Date, :Time, :Address, :BranchName, :PaymentMethod,
                :PaymentStatus, :PaymentReceipt, :Price, :ReferenceNumber, :CreatedAt
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
            ':CreatedAt' => $res['CreatedAt']
        ]);

        // Delete the reservation
        $delete = $pdo->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $delete->execute([$reservation_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
