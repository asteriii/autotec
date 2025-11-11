<?php //needed for reservations.php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'] ?? null;

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
        $stmt_delete->execute();
        $stmt_delete->close();

        echo json_encode(['success' => true, 'message' => 'Reservation moved to completed successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
